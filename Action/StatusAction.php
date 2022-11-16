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

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use NovalnetBundle\Model\Constants;
use NovalnetBundle\Helper\NovalnetHelper;

class StatusAction implements ActionInterface
{

    public function __construct()
    {
        $this->novalnetHelper = \Pimcore::getContainer()->get(NovalnetHelper::class);
    }

    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = new ArrayObject($request->getModel());

        $additionalData = !empty($model['NovalnetAdditionalData']) ? json_decode($model['NovalnetAdditionalData'], true) : [];

        if (empty($additionalData['NnTransactionTid'])) {
            $request->markNew();
            return;
        }

        if (!empty($additionalData['NnWebhookRefund']) || !empty($additionalData['NnShopInvokedRefund'])) {
            $request->markRefunded();
            return;
        }

        if (isset($additionalData['NnResultStatus']) && $additionalData['NnResultStatus'] == 'SUCCESS') {
            $transactionStatus = isset($additionalData['NnTransactionStatus']) ? $additionalData['NnTransactionStatus'] : '';
            $paymentMethod = isset($additionalData['NnTransactionPaymentType']) ? $additionalData['NnTransactionPaymentType'] : '';

            if ($transactionStatus == 'ON_HOLD') {
                $request->markAuthorized();
                $globalOnHoldStatus = $this->novalnetHelper->getGlobalConfigurations('onhold_status');
                if ($globalOnHoldStatus) {
                    $this->setOrderStatus($request, $globalOnHoldStatus);
                }
            } elseif ($transactionStatus == 'PENDING') {
                $request->markPending();
            } elseif ($transactionStatus == 'CONFIRMED') {
                $request->markCaptured();
                $methodCode = $this->novalnetHelper->getPaymentCodeByType($additionalData['NnTransactionPaymentType']);

                $completionStatus = $this->novalnetHelper->getPaymentConfig($methodCode, 'completed_status');

                if (in_array($paymentMethod, ['INVOICE', 'PREPAYMENT', 'CASHPAYMENT', 'MULTIBANCO']) && !empty($additionalData['NnPaid']) && $additionalData['NnPaid'] == 1) {
                    $completionStatus = $this->novalnetHelper->getPaymentConfig($methodCode, 'webhook_status');
                }

                if ($completionStatus) {
                    $this->setOrderStatus($request, $completionStatus);
                }

            } elseif (in_array($transactionStatus, ['CANCELLED', 'DEACTIVATED', 'FAILURE'])) {
                $request->markCanceled();
            }

            return;
        } else {
            $request->markFailed();
            return;
        }

        $request->markUnknown();
        return;
    }

    /**
     * To set order status
     *
     * @param $request
     */
    private function setOrderStatus($request, $status)
    {
        if ($status == 'authorized') {
            $request->markAuthorized();
        } elseif ($status == 'captured') {
            $request->markCaptured();
        } elseif ($status == 'payedout') {
            $request->markPayedout();
        } elseif ($status == 'refunded') {
            $request->markRefunded();
        } elseif ($status == 'unknown') {
            $request->markUnknown();
        } elseif ($status == 'failed') {
            $request->markFailed();
        } elseif ($status == 'suspended') {
            $request->markSuspended();
        } elseif ($status == 'expired') {
            $request->markExpired();
        } elseif ($status == 'pending') {
            $request->markPending();
        } elseif ($status == 'canceled') {
            $request->markCanceled();
        } elseif ($status == 'new') {
            $request->markNew();
        }

        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
