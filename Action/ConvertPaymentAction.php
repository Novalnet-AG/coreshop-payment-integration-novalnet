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
use Payum\Core\Exception\RequestNotSupportedException;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use Payum\Core\Request\Convert;

/**
 * This class is needed, because Payum sets offline payments always to paid.
 */
final class ConvertPaymentAction implements ActionInterface
{
    /**
     * @inheritdoc
     *
     * @param Convert $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();
        $paymentDetails = $payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];

        $request->setResult([
            'payment_details' => $additionalData
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        $a = ($request instanceof Convert &&
        $request->getSource() instanceof PaymentInterface &&
        $request->getTo() === 'array');

        return $a;
    }
}
