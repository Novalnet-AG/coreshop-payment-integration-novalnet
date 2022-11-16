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

namespace NovalnetBundle\Event\Order;

use CoreShop\Bundle\PayumBundle\Model\GatewayConfig;
use CoreShop\Component\Order\Model\OrderInterface;
use Payum\Core\Reply\ReplyInterface;
use Payum\Core\Request\Cancel;
use Payum\Core\Payum;
use CoreShop\Component\Order\OrderTransitions;
use CoreShop\Component\Payment\Model\PaymentInterface;
use Symfony\Component\Workflow\Event\Event;
use CoreShop\Component\Order\OrderPaymentStates;
use Pimcore\Model\Element\ValidationException;

class CancelBefore
{
    /**
     * @var Payum
     */
    protected $payum;

    /**
     * @param Payum $payum
     */
    public function __construct(Payum $payum)
    {
        $this->payum = $payum;
    }

    /**
     * Order cancel before
     *
     * @param OrderInterface $order
     * @param Event $event
     * @throws ReplyInterface
     */
    public function cancelBefore(OrderInterface $order, Event $event)
    {
        $gatewayFactoryName = $order->getPaymentProvider()->getGatewayConfig()->getFactoryName();
        if (preg_match('/novalnet/i', (string) $gatewayFactoryName)) {
            $paymentState = $order->getPaymentState();

            if ($paymentState !== PaymentInterface::STATE_AUTHORIZED && $paymentState !== OrderPaymentStates::STATE_AWAITING_PAYMENT) {
                throw new ValidationException("Unable to Cancel the Order from the current payment state '$paymentState'");
            }
        }
    }
}
