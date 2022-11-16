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
use Payum\Core\Request\Cancel;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Model\PaymentInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use NovalnetBundle\Helper\NovalnetHelper;
use CoreShop\Component\Payment\Model\PaymentInterface as CorePaymentInterface;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use NovalnetBundle\Model\NovalnetTransactionInterface;

/**
 * @property Api $api
 */
class CancelAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
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

        if (!empty($requestParams['transition']) && $payment->getState() !== CorePaymentInterface::STATE_AUTHORIZED) {
            throw new ValidationException(
                sprintf("Unable to Cancel the Payment from the current state '%s'", $payment->getState())
            );
        }

        if (!empty($additionalData['NnTransactionTid']) && !empty($additionalData['NnTransactionStatus']) && $additionalData['NnTransactionStatus'] == 'ON_HOLD' && empty($additionalData['NnWebhookVoid'])) {
            $response = $this->api->doVoid((array) $additionalData);
            if (!empty($response['result']['status']) && $response['result']['status'] == 'SUCCESS') {
                $additionalData['NnTransactionStatus'] = $response['transaction']['status'];
                $additionalData['ApiProcess'] = 'void';
                $additionalData['VoidApiProcessedAt'] = date('Y-m-d H:i:s');

                $message = sprintf($this->novalnetHelper->translate('novalnet.transaction.void.label', $order->getLocaleCode()), $additionalData['VoidApiProcessedAt']);
                $comment = !empty($order->getComment()) ? $order->getComment() . '<br><br>' . $message : $message;
                $order->setComment($comment);
                $order->save();

                $model->replace(
                    new ArrayObject(['NovalnetAdditionalData' => json_encode($additionalData)])
                );

                $this->updateNovalnetTransactionModel($order, $additionalData);
            } else {
                $errorMsg = !empty($response['result']['status_text']) ? $response['result']['status_text'] : 'something went wrong. Please try again..';
                throw new ValidationException($errorMsg);
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
            $request instanceof Cancel &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
