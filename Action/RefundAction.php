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
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Refund;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Model\PaymentInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use NovalnetBundle\Helper\NovalnetHelper;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use NovalnetBundle\Model\NovalnetTransactionInterface;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\Intl\Currencies;

/**
 * @property Api $api
 */
class RefundAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * CaptureAction constructor.
     */
    public function __construct()
    {
        $this->apiClass = Api::class;
        $this->novalnetHelper = \Pimcore::getContainer()->get(NovalnetHelper::class);
    }

    /**
     * @param Capture $request
     *
     * @throws \Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $additionalData = !empty($model['NovalnetAdditionalData']) ? json_decode($model['NovalnetAdditionalData'], true) : [];

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $this->gateway->execute($httpRequest = new GetHttpRequest());
        $requestParams = array_merge($httpRequest->request, $httpRequest->query);

        if (!empty($additionalData['NnTransactionTid']) && empty($additionalData['NnWebhookRefund']) && empty($additionalData['NnShopInvokedRefund'])) {
            $response = $this->api->doRefund((array) $additionalData);

            if (!empty($response['result']['status']) && $response['result']['status'] == 'SUCCESS') {
                $currency = $additionalData['NnTransactionCurrency'];
                $currency = Currencies::getSymbol($currency);
                $additionalData['NnTransactionStatus'] = $response['transaction']['status'];
                $noOfRefunds = empty($additionalData['NnRefundsOccured']) ? 1 : $additionalData['NnRefundsOccured'] + 1;
                $additionalData['NnRefundsOccured'] = $noOfRefunds;
                $additionalData['NnShopInvokedRefund'] = 1;
                $refundAmount = $this->novalnetHelper->getFormattedAmount($response['transaction']['refund']['amount'], 'RAW');
                $refundTid = !empty($response['transaction']['refund']['tid']) ? $response['transaction']['refund']['tid'] : $additionalData['NnTransactionTid'] . '-refund' . $noOfRefunds;

                $additionalData['NnRefunded'][$refundTid] = [
                    'refundTid' => $refundTid,
                    'refundAmount' => $refundAmount,
                    'parentTid' => $additionalData['NnTransactionTid']
                ];

                $message = sprintf($this->novalnetHelper->translate('novalnet.transaction.refund.label', $order->getLocaleCode()), $additionalData['NnTransactionTid'], $refundAmount, $currency);

                if (!empty($response['transaction']['refund']['tid'])) {
                    $message = sprintf($this->novalnetHelper->translate('novalnet.transaction.refund.newtid.label', $order->getLocaleCode()), $additionalData['NnTransactionTid'], $refundAmount, $currency, $response['transaction']['refund']['tid']);
                }

                $comment = !empty($order->getComment()) ? $order->getComment() . '<br><br>' . $message : $message;
                $order->setComment($comment);
                $order->save();
                $model->replace(
                    new ArrayObject(['NovalnetAdditionalData' => json_encode($additionalData)])
                );
                $this->updateNovalnetTransactionModel($order, $additionalData);
            } else {
                $errMsg = !empty($response['result']['status_text']) ? $response['result']['status_text'] : 'something went wrong. Please try again..';
                throw new ValidationException($errMsg);
            }
        }
    }

    /**
     * To update novalnet transaction model
     *
     * @param mixed $order
     * @param array $additionalData
     * @return void
     */
    private function updateNovalnetTransactionModel($order, $additionalData)
    {
        $container = \Pimcore::getContainer();
        $transactionModel = $container->get('doctrine')->getRepository(NovalnetTransactionInterface::class)->findByOrderId($order->getOrderNumber());
        $entityManager = $container->get('doctrine')->getManager();

        if (!empty($transactionModel[0])) {
            $model = $transactionModel[0];
            $model->setStatus($additionalData['NnTransactionStatus']);
            $entityManager->persist($model);
            $entityManager->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Refund &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
