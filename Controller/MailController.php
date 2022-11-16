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
use Pimcore\Http\Request\Resolver\EditmodeResolver;

class MailController extends \CoreShop\Bundle\FrontendBundle\Controller\MailController
{
    /**
     * {@inheritdoc}
     */
    public function orderConfirmationAction(Request $request): Response
    {
        $order = $request->attributes->get('object');
        $viewParameters = [];

        if (!$this->get(EditmodeResolver::class)->isEditmode($request) && $order instanceof OrderInterface) {
            $viewParameters['order'] = $order;
        }

        if ($order) {
            $payment = null;
            foreach ($this->get('coreshop.repository.payment')->findForPayable($order) as $paymentObject) {
                $payment = $paymentObject;
            }

            if ($payment) {
                $gatewayFactoryName = $payment->getPaymentProvider()->getGatewayConfig()->getFactoryName();
                if (preg_match('/novalnet/i', (string) $gatewayFactoryName)) {
                    $novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
                    $paymentDetails = $payment->getDetails();
                    $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];

                    $viewParameters['novalnet_comments'] = nl2br($novalnetHelper->getOrderComments($additionalData));
                    return $this->render('@Novalnet/Mail/order-confirmation.html.twig', $viewParameters);
                }
            }
        }

        return parent::orderConfirmationAction($request);
    }
}
