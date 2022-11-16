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

namespace NovalnetBundle\Event\Payment;

use CoreShop\Bundle\PayumBundle\Model\GatewayConfig;
use CoreShop\Component\Payment\Model\PaymentInterface;
use Payum\Core\Reply\ReplyInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Payum;
use CoreShop\Component\Order\OrderTransitions;
use CoreShop\Component\Order\Model\OrderInterface;

class CompleteBefore
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
     * Payment complete before
     *
     * @param PaymentInterface $payment
     * @param OrderInterface $order
     * @throws ReplyInterface
     */
    public function completeBefore(PaymentInterface $payment, OrderInterface $order)
    {
        $gatewayFactoryName = $payment->getPaymentProvider()->getGatewayConfig()->getFactoryName();
        if (preg_match('/novalnet/i', (string) $gatewayFactoryName)) {
            /** @var GatewayConfig $gatewayConfig */
            $gatewayConfig = $payment->getPaymentProvider()->getGatewayConfig();
            $novalnetGateway = $this->payum->getGateway($gatewayConfig->getGatewayName());
            $novalnetGateway->execute(new Capture($payment));
        }
    }
}
