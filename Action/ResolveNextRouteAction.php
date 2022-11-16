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

use CoreShop\Bundle\PayumBundle\Request\ResolveNextRoute;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use Payum\Core\Action\ActionInterface;

final class ResolveNextRouteAction implements ActionInterface
{
    /**
     * @inheritdoc
     *
     * @param ResolveNextRoute $request
     */
    public function execute($request)
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $request->setRouteParameters([
            '_locale' => $order->getLocaleCode(),
        ]);

        if ($payment->getState() === PaymentInterface::STATE_COMPLETED ||
            $payment->getState() === PaymentInterface::STATE_AUTHORIZED ||
            $payment->getState() === PaymentInterface::STATE_PROCESSING
        ) {
            $request->setRouteName('coreshop_checkout_thank_you');
            $request->setRouteParameters([
                '_locale' => $order->getLocaleCode(),
                'token' => $order->getToken(),
                'paymentId' => $payment->getId()
            ]);

            return;
        }

        $request->setRouteName('coreshop_cart_summary');
        $request->setRouteParameters(array_merge($request->getRouteParameters(), ['token' => $order->getToken(), 'paymentId' => $payment->getId()]));
    }

    public function supports($request)
    {
        return
            $request instanceof ResolveNextRoute &&
            $request->getFirstModel() instanceof PaymentInterface;
    }
}
