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
use CoreShop\Bundle\OrderBundle\DTO\AddToCartInterface;
use CoreShop\Component\Tracking\Tracker\TrackerInterface;
use CoreShop\Component\Order\Model\PurchasableInterface;
use CoreShop\Bundle\OrderBundle\Form\Type\AddToCartType;

class CartController extends \CoreShop\Bundle\FrontendBundle\Controller\CartController
{
    /**
     * {@inheritdoc}
     */
    public function summaryAction(Request $request): Response
    {
        $token = $this->getParameterFromRequest($request, 'token');
        $payment = null;

        /** @var OrderInterface $order */
        $order = $this->getOrderRepository()->findOneBy(['token' => $token]);

        if ($order) {
            $novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
            $restoreCart = $novalnetHelper->getGlobalConfigurations('restore_cart');

            if ($request->query->has('nnErrMsg')) {
                $this->addFlash('error', $request->query->get('nnErrMsg'));
                // restore cart items
                if (!empty($restoreCart) && $restoreCart == '1') {
                    $this->restoreCart($request, $order);
                }
            }

            if ($request->query->has('paymentId')) {
                $paymentObject = $this->getPaymentRepository()->find($request->query->get('paymentId'));
                if ($paymentObject instanceof PaymentInterface) {
                    $payment = $paymentObject;
                }
            }

            foreach ($this->getPaymentRepository()->findForPayable($order) as $payment) {
                if ($payment->getState() === PaymentInterface::STATE_COMPLETED) {
                    $this->addFlash('error', $this->get('translator')->trans('coreshop.ui.error.order_already_paid'));

                    return $this->redirectToRoute('coreshop_index');
                }
            }

            if ($payment && $payment->getDetails()) {
                $gatewayFactoryName = $payment->getPaymentProvider()->getGatewayConfig()->getFactoryName();
                if (preg_match('/novalnet/i', (string) $gatewayFactoryName)) {

                    // Add error message for cancelled payments
                    if ($payment->getState() === PaymentInterface::STATE_FAILED || $payment->getState() === PaymentInterface::STATE_CANCELLED) {
                        $paymentDetails = $payment->getDetails();
                        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
                        $errMessage = !empty($additionalData['NnResultStatusText']) ? $additionalData['NnResultStatusText'] : 'Transaction Failed';
                        $this->addFlash('error', $errMessage);
                    }

                    // restore cart items
                    if (!empty($restoreCart) && $restoreCart == '1') {
                        $this->restoreCart($request, $order);
                    }
                }
            }
        }

        return parent::summaryAction($request);
    }

    /**
     * To restore cart on payment failed
     *
     * @param Request $request
     * @param mixed $order
     * @return void
     */
    private function restoreCart(Request $request, $order): void
    {
        if ($order->getItems()) {
            foreach ($order->getItems() as $item) {
                if (!empty($item->__getRawRelationData()[0]['dest_id'])) {
                    $productId = $item->__getRawRelationData()[0]['dest_id'];
                    $product = $this->get('coreshop.repository.stack.purchasable')->find($productId);
                    if ($product instanceof PurchasableInterface) {
                        $cartItem = $this->get('coreshop.factory.order_item')->createWithPurchasable($product);
                        $this->getQuantityModifer()->modify($cartItem, $item->getQuantity());
                        $addToCart = $this->createAddToCart($this->getCart(), $cartItem);

                        $form = $this->get('form.factory')->createNamed('coreshop-' . $product->getId(), AddToCartType::class, $addToCart);
                        $form->handleRequest($request);
                        $addToCart = $form->getData();

                        $this->getCartModifier()->addToList($addToCart->getCart(), $addToCart->getCartItem());
                        $this->getCartManager()->persistCart($this->getCart());
                        $this->get(TrackerInterface::class)->trackCartAdd(
                            $addToCart->getCart(),
                            $addToCart->getCartItem()->getProduct(),
                            $addToCart->getCartItem()->getQuantity(),
                        );
                    }
                }
            }
        }
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
