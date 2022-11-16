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
use CoreShop\Component\Core\Model\CustomerInterface;

class CustomerController extends \CoreShop\Bundle\FrontendBundle\Controller\CustomerController
{
    /**
     * {@inheritdoc}
     */
    public function orderDetailAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CORESHOP_CUSTOMER_PROFILE_ORDER_DETAIL');

        $orderId = $this->getParameterFromRequest($request, 'order');
        $customer = $this->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return $this->redirectToRoute('coreshop_index');
        }

        $order = $this->get('coreshop.repository.order')->find($orderId);

        if (!$order instanceof OrderInterface) {
            return $this->redirectToRoute('coreshop_customer_orders');
        }

        if (!$order->getCustomer() instanceof CustomerInterface || $order->getCustomer()->getId() !== $customer->getId()) {
            return $this->redirectToRoute('coreshop_customer_orders');
        }

        $payment = null;
        foreach ($this->get('coreshop.repository.payment')->findForPayable($order) as $paymentObject) {
            $payment = $paymentObject;
        }

        if ($order && $payment) {
            $gatewayFactoryName = $payment->getPaymentProvider()->getGatewayConfig()->getFactoryName();
            if (preg_match('/novalnet/i', (string) $gatewayFactoryName)) {
                $novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
                $paymentDetails = $payment->getDetails();
                $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];

                return $this->render('@Novalnet/Customer/order_detail.html.twig', [
                    'customer' => $customer,
                    'order' => $order,
                    'novalnet_comments' => nl2br($novalnetHelper->getOrderComments($additionalData))
                ]);
            }
        }

        return parent::orderDetailAction($request);
    }
}
