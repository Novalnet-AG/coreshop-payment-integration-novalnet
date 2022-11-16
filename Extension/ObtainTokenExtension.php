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

namespace NovalnetBundle\Extension;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Request\RenderTemplate;

final class ObtainTokenExtension implements ExtensionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }


    /**
     * @param Context $context
     */
    public function onPostExecute(Context $context): void
    {

    }

    /**
     * {@inheritdoc}
     */
    public function onPreExecute(Context $context): void
    {
        $request = $context->getRequest();

        if (!$request instanceof RenderTemplate) {
            return;
        }

        $request->addParameter('layout', '@CoreShopFrontend/layout.html.twig');

        if (count($context->getPrevious()) === 0) {
            return;
        }

        $previous = $context->getPrevious();
        $previous = reset($previous);

        if (!$previous->getRequest() instanceof Capture) {
            return;
        }

        /** @var PaymentInterface $payment */
        $payment = $previous->getRequest()->getFirstModel();


        if (false === $payment instanceof PaymentInterface) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onExecute(Context $context): void
    {

    }
}
