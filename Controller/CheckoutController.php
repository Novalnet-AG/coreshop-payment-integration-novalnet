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

namespace NovalnetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Tracking\Tracker\TrackerInterface;

class CheckoutController extends \CoreShop\Bundle\FrontendBundle\Controller\CheckoutController
{
    /**
     * {@inheritdoc}
     */
    public function thankYouAction(Request $request): Response
    {
        $token = $this->getParameterFromRequest($request, 'token');
        $order = $this->getOrderRepository()->findByToken($token);
        $payment = null;

        if ($order) {
            if ($request->query->has('paymentId')) {
                $paymentObject = $this->getPaymentRepository()->find($request->query->get('paymentId'));
                if ($paymentObject instanceof PaymentInterface) {
                    $payment = $paymentObject;
                }
            }
        }

        if ($payment) {
            $gatewayFactoryName = $payment->getPaymentProvider()->getGatewayConfig()->getFactoryName();
            if (preg_match('/novalnet/i', (string) $gatewayFactoryName)) {
                $novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
                $paymentDetails = $payment->getDetails();
                $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
                $templateData = [
                    'order' => $order,
                    'novalnet_comments' => nl2br($novalnetHelper->getOrderComments($additionalData))
                ];

                if ($additionalData['NnTransactionPaymentType'] == 'CASHPAYMENT' && !empty($additionalData['NnTransactionCheckoutJs']) && !empty($additionalData['NnTransactionCheckoutToken'])) {
                    $templateData['checkout_js'] = $additionalData['NnTransactionCheckoutJs'];
                    $templateData['checkout_token'] = $additionalData['NnTransactionCheckoutToken'];
                }

                return $this->render('@Novalnet/Checkout/thank-you.html.twig', $templateData);
            }
        }

        return parent::thankYouAction($request);
    }

    /**
     * To get order repository
     *
     * @return OrderRepositoryInterface
     */
    protected function getOrderRepository(): OrderRepositoryInterface
    {
        return $this->get('coreshop.repository.order');
    }

    /**
     * To get payment repository
     *
     * @return PaymentRepositoryInterface
     */
    private function getPaymentRepository(): PaymentRepositoryInterface
    {
        return $this->get('coreshop.repository.payment');
    }
}
