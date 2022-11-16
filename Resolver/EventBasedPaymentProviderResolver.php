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

namespace NovalnetBundle\Resolver;

use CoreShop\Bundle\PayumPaymentBundle\Event\PaymentProviderSupportsEvent;
use CoreShop\Bundle\PayumPaymentBundle\Events;
use CoreShop\Component\Payment\Resolver\PaymentProviderResolverInterface;
use CoreShop\Component\Resource\Model\ResourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use NovalnetBundle\Model\Constants;

class EventBasedPaymentProviderResolver implements PaymentProviderResolverInterface
{
    /**
     * Construct
     *
     * @param PaymentProviderResolverInterface $inner
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private PaymentProviderResolverInterface $inner,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Validate supported payment methods
     *
     * @return array
     */
    public function resolvePaymentProviders(ResourceInterface $subject = null): array
    {
        $allowedPaymentProviders = [];

        foreach ($this->inner->resolvePaymentProviders($subject) as $paymentProvider) {

            $event = new PaymentProviderSupportsEvent($paymentProvider, $subject);
            $factoryName = $paymentProvider->getGatewayConfig()->getFactoryName();
            if (preg_match('/novalnet/i', (string) $factoryName)) {
                if (!$this->validateBasicParams()) {
                    $event->setSupported(false);
                } else {
                    if ($factoryName == Constants::NOVALNET_GLOBAL || $paymentProvider->getGatewayConfig()->getGatewayName() == Constants::NOVALNET_GLOBAL) {
                        $event->setSupported(false);
                    }

                    if (in_array($factoryName, [Constants::NOVALNET_INVOICE_GUARANTEE, Constants::NOVALNET_SEPA_GUARANTEE]) && !$this->canShowGuaranteePayments($factoryName)) {
                        $event->setSupported(false);
                    }
                }
            }

            $this->eventDispatcher->dispatch($event, Events::SUPPORTS_PAYMENT_PROVIDER);

            if ($event->isSupported()) {
                array_push($allowedPaymentProviders, $paymentProvider);
            }
        }

        return $allowedPaymentProviders;
    }

    /**
     * Can show guarantee payment methods
     *
     * @param string $factoryName
     * @return bool
     */
    private function canShowGuaranteePayments($factoryName)
    {
        $container = \Pimcore::getContainer();
        $cart = $container->get(\CoreShop\Component\Order\Context\CartContextInterface::class)->getCart();
        $novalnetHelper = $container->get(\NovalnetBundle\Helper\NovalnetHelper::class);

        $minOrderAmount = $novalnetHelper->getPaymentConfig($factoryName, 'min_order_amount');
        $minOrderAmount = !empty($minOrderAmount) ? $minOrderAmount : 9.99;

        $invoiceAddress = $cart->getInvoiceAddress();
        $shippingAddress = $cart->getShippingAddress();
        $customer = $cart->getCustomer();

        return ($this->isAddressSame($invoiceAddress, $shippingAddress) &&
            $novalnetHelper->getShopperCurrency() == 'EUR' &&
            $novalnetHelper->getFormattedAmount($cart->getTotal(true), 'RAW') >= $minOrderAmount &&
            in_array($invoiceAddress->getCountry()->getIsoCode(), ['DE', 'AT', 'CH'])
        );
    }

    /**
     * To check the billing and shipping address are same
     *
     * @param mixed $invoiceAddress
     * @param mixed $shippingAddress
     * @return bool
     */
    private function isAddressSame($invoiceAddress, $shippingAddress)
    {
        return (
                $invoiceAddress->getStreet() == $shippingAddress->getStreet() &&
                $invoiceAddress->getCity() == $shippingAddress->getCity() &&
                $invoiceAddress->getCountry()->getIsoCode() ==$shippingAddress->getCountry()->getIsoCode() &&
                $invoiceAddress->getPostcode() == $shippingAddress->getPostcode()
            );
    }

    /**
     * Validate Novalnet basic params
     *
     * @return bool
     */
    private function validateBasicParams()
    {
        $novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
        $tariff = $novalnetHelper->getGlobalConfigurations('tariff');

        return (!empty($novalnetHelper->getGlobalConfigurations('signature')) &&
            !empty($novalnetHelper->getGlobalConfigurations('payment_access_key')) &&
            !empty($tariff) &&
            $novalnetHelper->checkIsNumeric($tariff)
        );
    }
}
