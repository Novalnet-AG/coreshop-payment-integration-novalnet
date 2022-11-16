<?php
/**
 * Novalnet payment bundle
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment bundle for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

declare(strict_types=1);

namespace NovalnetBundle\Action;

use NovalnetBundle\Model\Api;
use NovalnetBundle\Model\Constants;
use NovalnetBundle\Request\Api\ObtainToken;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Authorize;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Request\Sync;
use Payum\Core\Request\GetHttpRequest;
use NovalnetBundle\Request\Api\GetTransactionDetails;
use Payum\Core\Model\PaymentInterface;
use CoreShop\Component\Core\Model\OrderInterface;

abstract class PurchaseAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * @param $request
     *
     * @throws \Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $this->gateway->execute($httpRequest = new GetHttpRequest());
        $requestParams = array_merge($httpRequest->request, $httpRequest->query);
        $paymentMethod = $this->api->getOption('factory_name');

        if (empty($model['payment_details']['NnTransactionTxnSecret'])) {

            if ($this->novalnetHelper->isFormPayment($paymentMethod) && $this->doesPaymentneedForm($paymentMethod, $order)) {
                if (empty($requestParams['nn_token_done'])) {
                    $obtainToken = new ObtainToken($request->getToken(), $order, $payment);
                    $obtainToken->setModel($model);
                    $this->gateway->execute($obtainToken);
                } elseif (!empty($requestParams['nn_token_done'])) {
                    if (!empty($requestParams[$paymentMethod . '_dob']) && !$this->novalnetHelper->validateBirthDate($requestParams[$paymentMethod . '_dob'])) {
                        $obtainToken = new ObtainToken($request->getToken(), $order, $payment, $this->novalnetHelper->translate('novalnet.guarantee.dob.error', $order->getLocaleCode()));
                        $obtainToken->setModel($model);
                        $this->gateway->execute($obtainToken);
                    }

                    $model['payment_data'] = $requestParams;
                }
            }

            $model['target_url'] = $request->getToken()->getTargetUrl();
            $response = [];
            $minAuthorizeAmount = $this->api->getOption('min_authorize_amount');
            $minAuthorizeAmount = ($minAuthorizeAmount) ? $minAuthorizeAmount : 0;
            if ($request instanceof Authorize && $order->getTotal(true) >= $minAuthorizeAmount) {
                $response = $this->api->doAuthorize((array) $model, $order);
            } else {
                $response = $this->api->doPurchase((array) $model, $order);
            }

            $paramsToUnset = ['payment_data', 'target_url', 'payment_details'];
            foreach ($paramsToUnset as $param) {
                unset($model[$param]);
            }

            $payment->setNumber(null);
            $response = $this->novalnetHelper->buildPaymentAdditionalData($response);

            $message = $this->novalnetHelper->getOrderComments($response, $order->getLocaleCode());
            $comment = !empty($order->getComment()) ? $order->getComment() . '<br><br>' . $message : $message;
            $order->setComment($comment);
            $order->save();

            $paymentDetails['NovalnetAdditionalData'] = json_encode($response);
            $model->replace(
                new ArrayObject($paymentDetails)
            );

            if (!empty($response['NnResultRedirectUrl'])) {
                throw new HttpRedirect($response['NnResultRedirectUrl']);
            }

            $payment->setNumber(
                !empty($response['NnTransactionTid']) ? $response['NnTransactionTid'] : null
            );

            $this->savePaymentResponse($order, $response);
        } elseif (!empty($model['payment_details']['NnTransactionTxnSecret'])) {
            if (empty($payment->getNumber())) {
                $model['redirect_response'] = $requestParams;
                $this->gateway->execute(new GetTransactionDetails($model, $order, $payment));
            }
        }
    }

    /**
     * Check whether the payment need form page
     *
     * @param string $paymentMethod
     * @param mixed $order
     * @return bool
     */
    private function doesPaymentneedForm($paymentMethod, $order)
    {
        $allowB2b = (bool) $this->api->getOption('allow_b2b');

        if ($paymentMethod == Constants::NOVALNET_INVOICE_GUARANTEE &&
            !$this->novalnetHelper->canShowDob($order->getInvoiceAddress()->getCompany(), $allowB2b)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Save novalnet response
     *
     * @param mixed $order
     * @param array $response
     * @return void
     */
    private function savePaymentResponse($order, $response)
    {
        $container = \Pimcore::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $novalnetTransaction = $container->get(\NovalnetBundle\Model\NovalnetTransaction::class);
        $novalnetTransaction->setOrderId($order->getOrderNumber());
        $novalnetTransaction->setTid($response['NnTransactionTid']);
        $novalnetTransaction->setStatus($response['NnTransactionStatus']);
        $novalnetTransaction->setCustomerId($order->getCustomer()->getID());
        $novalnetTransaction->setPaymentMethod($response['NnTransactionPaymentType']);
        $novalnetTransaction->setToken(null);
        $novalnetTransaction->setTokenInfo(null);

        $entityManager->persist($novalnetTransaction);
        $entityManager->flush();
    }
}
