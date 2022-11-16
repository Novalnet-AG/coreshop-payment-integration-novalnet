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
namespace NovalnetBundle\Action\Api;

use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use NovalnetBundle\Model\Api;
use NovalnetBundle\Helper\NovalnetHelper;
use NovalnetBundle\Request\Api\GetTransactionDetails;
use Payum\Core\Model\PaymentInterface;
use CoreShop\Component\Core\Model\OrderInterface;

class GetTransactionDetailsAction implements ActionInterface, ApiAwareInterface
{
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
        $this->novalnetHelper = \Pimcore::getContainer()->get(NovalnetHelper::class);
    }

    public function execute($request)
    {
        /** @var GetTransactionDetails $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $order = $request->getOrderObject();
        $payment = $request->getPaymentObject();

        $redirectResponse = $model['redirect_response'];
        if (!empty($model['payment_details']['NnTransactionTxnSecret'])) {
            if (!$this->checkPaymentHash($redirectResponse, $model['payment_details']['NnTransactionTxnSecret'])) {

            }

            $response = $this->api->getTransactionDetails($redirectResponse, $order);

            $paramsToUnset = ['payment_data', 'target_url', 'payment_details', 'redirect_response'];
            foreach ($paramsToUnset as $param) {
                unset($model[$param]);
            }

            $payment->setNumber(null);

            $response = $this->novalnetHelper->buildPaymentAdditionalData($response);

            $payment->setNumber(
                !empty($response['NnTransactionTid']) ? $response['NnTransactionTid'] : null
            );

            $message = $this->novalnetHelper->getOrderComments($response, $order->getLocaleCode());
            $comment = !empty($order->getComment()) ? $order->getComment() . '<br><br>' . $message : $message;
            $order->setComment($comment);
            $order->save();

            $paymentDetails['NovalnetAdditionalData'] = json_encode($response);
            $model->replace(
                new ArrayObject($paymentDetails)
            );

            $this->savePaymentResponse($order, $response);
        }
    }

    /**
     * Check payment hash for redirect payments
     *
     * @param array $response
     * @param string $txnSecret
     * @return bool
     */
    protected function checkPaymentHash($response, $txnSecret)
    {
        $paymentAccessKey = !empty($this->api->getOption('payment_access_key')) ? trim($this->api->getOption('payment_access_key')) : '';
        $generatedChecksum = $response['tid'] . $txnSecret . $response['status'] . strrev($paymentAccessKey);
        $generatedChecksum = (!empty($generatedChecksum)) ? hash('sha256', $generatedChecksum) : '';

        if ($generatedChecksum !== $response['checksum']) {
            return false;
        }

        return true;
    }

    /**
     * Check action supports
     *
     * @param mixed $request
     * @return bool
     */
    public function supports($request)
    {
        return $request instanceof GetTransactionDetails &&
            $request->getModel() instanceof ArrayAccess
        ;
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
