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
namespace NovalnetBundle\Action\Api;

use ArrayAccess;
use NovalnetBundle\Request\Api\ObtainToken;
use NovalnetBundle\Model\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;
use Payum\Core\Model\PaymentInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use NovalnetBundle\Helper\NovalnetHelper;
use NovalnetBundle\Model\Constants;

class ObtainTokenAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @deprecated BC will be removed in 2.x. Use $this->api
     *
     * @var Keys
     */
    protected $keys;

    /**
     * @param string $templateName
     */
    public function __construct($templateName)
    {
        $this->templateName = $templateName;
        $this->apiClass = Api::class;
        $this->novalnetHelper = \Pimcore::getContainer()->get(NovalnetHelper::class);
    }

    public function execute($request)
    {
        /** @var ObtainToken $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $order = $request->getOrderObject();
        $payment = $request->getPaymentObject();
        $errorMessage = $request->getErrorMessage();
        $this->gateway->execute(new GetHttpRequest());
        $paymentMethod = $this->api->getOption('factory_name');
        $allowB2b = (bool) $this->api->getOption('allow_b2b');

        $templateData = [
            'payment_form_data' => json_encode((array) $this->getPaymentFormData($order)),
            'actionUrl' => $request->getToken() ? $request->getToken()->getTargetUrl() : null,
            'error' => false,
            'errorMessage' => null,
            'logos' => $this->getPaymentLogo($paymentMethod),
            'cancel_url' => $this->getCancelUrl($order, $payment)
        ];

        $templateData = $this->addLanguageParams($templateData, $order->getLocaleCode());

        if ($paymentMethod == Constants::NOVALNET_INVOICE_GUARANTEE ||
            $paymentMethod == Constants::NOVALNET_SEPA_GUARANTEE
        ) {
            $templateData['canShowDob'] = (bool) $this->novalnetHelper->canShowDob($order->getInvoiceAddress()->getCompany(), $allowB2b);
        }

        if ($errorMessage) {
            $templateData['error'] = true;
            $templateData['errorMessage'] = $errorMessage;
        }

        $renderTemplate = new RenderTemplate($this->templateName, $templateData);
        $this->gateway->execute($renderTemplate);

        $paramsToUnset = ['payment_data', 'target_url', 'payment_details'];
        foreach ($paramsToUnset as $param) {
            unset($model[$param]);
        }

        throw new HttpResponse($renderTemplate->getResult());
    }

    /**
     * To get cart page URL
     *
     * @param mixed $order
     * @param mixed $payment
     * @return void
     */
    public function getCancelUrl($order, $payment)
    {
        $parameters = [
            'token' => $order->getToken(),
            'paymentId' => $payment->getId(),
            'nnErrMsg' => $this->novalnetHelper->translate('novalnet.customer.cancel.text', $order->getLocaleCode())
        ];

        return $this->novalnetHelper->getSystemUrl() . \Pimcore::getContainer()->get('router')->generate('coreshop_cart_summary', $parameters);
    }

    /**
     * Add template language params
     *
     * @param array $data
     * @param string $locale
     * @return array
     */
    private function addLanguageParams($data, $locale)
    {
        $data['novalnet_cc_payment_title'] = $this->novalnetHelper->translate('novalnet.cc.payment.title', $locale);
        $data['novalnet_cc_payment_instructions'] = $this->novalnetHelper->translate('novalnet.cc.payment.instructions', $locale);
        $data['invoice_guarantee_payment_title'] = $this->novalnetHelper->translate('novalnet.invoice.guarantee.payment.title', $locale);
        $data['guarantee_dob_label'] = $this->novalnetHelper->translate('novalnet.guarantee.dob.label', $locale);
        $data['dob_placeholder'] = $this->novalnetHelper->translate('novalnet.dob.placeholder', $locale);
        $data['invoice_guarantee_payment_instructions'] = $this->novalnetHelper->translate('novalnet.invoice.guarantee.payment.instructions', $locale);
        $data['sepa_payment_title'] = $this->novalnetHelper->translate('novalnet.sepa.payment.title', $locale);
        $data['iban_label'] = $this->novalnetHelper->translate('novalnet.iban.label', $locale);
        $data['bic_label'] = $this->novalnetHelper->translate('novalnet.bic.label', $locale);
        $data['sepa_instructions'] = $this->novalnetHelper->translate('novalnet.sepa.instructions', $locale);
        $data['sepa_mandate_toggle'] = $this->novalnetHelper->translate('novalnet.sepa.mandate.toggle', $locale);
        $data['sepa_mandate_authorise'] = $this->novalnetHelper->translate('novalnet.sepa.mandate.authorise', $locale);
        $data['sepa_mandate_identifier'] = $this->novalnetHelper->translate('novalnet.sepa.mandate.identifier', $locale);
        $data['sepa_mandate_note'] = $this->novalnetHelper->translate('novalnet.sepa.mandate.note', $locale);
        $data['sepa_guarantee_payment_title'] = $this->novalnetHelper->translate('novalnet.sepa.guarantee.payment.title', $locale);
        $data['novalnet_cancel_payment_alert'] = $this->novalnetHelper->translate('novalnet.cancel.payment.alert', $locale);
        $data['novalnet_submit_btn_label'] = $this->novalnetHelper->translate('novalnet.submit.btn.label', $locale);
        $data['novalnet_cancel_btn_label'] = $this->novalnetHelper->translate('novalnet.cancel.btn.label', $locale);

        return $data;
    }

    /**
     * To get payment form data
     *
     * @param mixed $order
     * @return array
     */
    private function getPaymentLogo($paymemtMethodCode)
    {
        $logos = [];
        $domain = $this->novalnetHelper->getSystemUrl();

        if ($paymemtMethodCode == Constants::NOVALNET_CC) {
            $logotypes = $this->api->getOption('logo_types');
            $logotypes = !empty($logotypes) ? explode(',', $logotypes) : [];
            foreach ($logotypes as $type) {
                $imageUrl = $domain . '/bundles/novalnet/static/images/' . $type . '.png';
                array_push($logos, [
                    'src' => $imageUrl,
                    'title' => $type,
                    'alt' => $type
                ]);
            }

            return $logos;
        }

        $imageUrl = $domain . '/bundles/novalnet/static/images/' . $paymemtMethodCode . '.png';

        array_push($logos, [
            'src' => $imageUrl,
            'title' => $paymemtMethodCode,
            'alt' => $paymemtMethodCode
        ]);

        return $logos;
    }

    /**
     * To get payment form data
     *
     * @param mixed $order
     * @return array
     */
    protected function getPaymentFormData($order)
    {
        $paymentMethod = $this->api->getOption('factory_name');
        if ($paymentMethod == Constants::NOVALNET_CC) {
            return $this->buildCreditCardIframeData($order);
        }

        return [];
    }

    /**
     * To build creditcard iframe data
     *
     * @param mixed $order
     * @return string
     */
    protected function buildCreditCardIframeData($order)
    {
        $invoiceAddress = $order->getInvoiceAddress();
        $shippingAddress = $order->getShippingAddress();
        $customer = $order->getCustomer();
        $locale = $order->getLocaleCode();

        $data = [];
        $data = [
            'clientKey' => $this->api->getOption('client_key'),
            'inline' => $this->novalnetHelper->getPaymentConfig(Constants::NOVALNET_CC, 'inline_form'),
            'text' => [
                'lang' => $locale,
                'error' => $this->novalnetHelper->translate('novalnet.cc.iframe.error', $locale),
                'card_holder' => [
                    'label' => $this->novalnetHelper->translate('novalnet.cc.iframe.card-holder.label', $locale),
                    'place_holder' => $this->novalnetHelper->translate('novalnet.cc.iframe.card-holder.placeholder', $locale),
                    'error' => $this->novalnetHelper->translate('novalnet.cc.iframe.validation.error', $locale)
                ],
                'card_number' => [
                    'label' => $this->novalnetHelper->translate('novalnet.cc.iframe.card-number.label', $locale),
                    'place_holder' => $this->novalnetHelper->translate('novalnet.cc.iframe.card-number.placeholder', $locale),
                    'error' => $this->novalnetHelper->translate('novalnet.cc.iframe.validation.error', $locale)
                ],
                'expiry_date' => [
                    'label' => $this->novalnetHelper->translate('novalnet.cc.iframe.expiry-date.label', $locale),
                    'error' => $this->novalnetHelper->translate('novalnet.cc.iframe.validation.error', $locale)
                ],
                'cvc' => [
                    'label' => $this->novalnetHelper->translate('novalnet.cc.iframe.cvc.label', $locale),
                    'place_holder' => $this->novalnetHelper->translate('novalnet.cc.iframe.cvc.placeholder', $locale),
                    'error' => $this->novalnetHelper->translate('novalnet.cc.iframe.validation.error', $locale)
                ]
            ],
            'style' => [
                'container' => $this->novalnetHelper->getPaymentConfig(Constants::NOVALNET_CC, 'form_css'),
                'input' => $this->novalnetHelper->getPaymentConfig(Constants::NOVALNET_CC, 'form_input_style'),
                'label' => $this->novalnetHelper->getPaymentConfig(Constants::NOVALNET_CC, 'form_label_style')
            ],
            'transaction' => [
                'amount' => $order->getTotal(true),
                'currency' => $order->getCurrency()->getIsoCode(),
                'enforce_3d' => $this->api->getOption('enforce_3d'),
                'test_mode' => $this->api->getOption('test_mode') ? 1 : 0
            ],
            'custom' => [
                'lang' => $order->getLocaleCode()
            ],
            'customer' => [
                'first_name' => $invoiceAddress->getFirstname(),
                'last_name' => $invoiceAddress->getLastname(),
                'email' => $customer->getEmail(),
                'billing' => [
                    'street' => $invoiceAddress->getStreet(),
                    'city' => $invoiceAddress->getCity(),
                    'zip' => $invoiceAddress->getPostcode(),
                    'country_code' => $invoiceAddress->getCountry()->getIsoCode()
                ]
            ]
        ];

        if (!empty($shippingAddress)) {
            if ($this->novalnetHelper->isShippingSameAsBilling($invoiceAddress, $shippingAddress)) {
                $data['customer']['shipping']['same_as_billing'] = 1;
            } else {
                $data['customer']['shipping'] = [
                    'first_name' => $shippingAddress->getFirstname(),
                    'last_name' => $shippingAddress->getLastname(),
                    'email' => $customer->getEmail(),
                    'street' => $shippingAddress->getStreet(),
                    'city' => $shippingAddress->getCity(),
                    'zip' => $shippingAddress->getPostcode(),
                    'country_code' => $shippingAddress->getCountry()->getIsoCode()
                ];
            }
        }

        return $data;
    }

    public function supports($request)
    {
        return ($request instanceof ObtainToken && $request->getModel() instanceof ArrayAccess);
    }
}
