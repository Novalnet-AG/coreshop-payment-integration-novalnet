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

namespace NovalnetBundle\Model;

use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\HttpClientInterface;
use Payum\Core\Exception\Http\HttpException;
use NovalnetBundle\Model\Constants;
use League\Uri\Http as HttpUri;
use League\Uri\UriModifier;
use NovalnetBundle\Helper\NovalnetHelper;

class Api
{
    /**
     * @var mixed
     */
    protected $api;

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array|ArrayObject
     */
    protected $options = [];

    /**
     * @var NovalnetHelper
     */
    protected $novalnetHelper;

    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->options = ArrayObject::ensureArrayObject($options);
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->novalnetHelper = \Pimcore::getContainer()->get(NovalnetHelper::class);
    }

    /**
     * Authorize Payment action
     *
     * @param aray $data
     * @param mixed $order
     * @return array
     */
    public function doAuthorize(array $data, $order)
    {
        $requestParams = $this->buildRequestData($data, $order);
        return $this->doRequest($requestParams, Constants::NOVALNET_AUTHORIZE_URL);
    }

    /**
     * Purchase action
     *
     * @param aray $data
     * @param mixed $order
     * @return array
     */
    public function doPurchase(array $data, $order)
    {
        $requestParams = $this->buildRequestData($data, $order);
        return $this->doRequest($requestParams, Constants::NOVALNET_PAYMENT_URL);
    }

    /**
     * Do capture action
     *
     * @param aray $data
     * @return array
     */
    public function doCapture(array $data)
    {
        $requestParams = [
            'transaction' => [
                'tid' => $data['NnTransactionTid'],
            ],
            'custom' => [
                'lang' => 'EN',
            ]
        ];

        return $this->doRequest($requestParams, Constants::NOVALNET_CAPTURE_URL);
    }

    /**
     * Do void action
     *
     * @param aray $data
     * @return array
     */
    public function doVoid(array $data)
    {
        $requestParams = [
            'transaction' => [
                'tid' => $data['NnTransactionTid'],
            ],
            'custom' => [
                'lang' => 'EN',
            ]
        ];

        return $this->doRequest($requestParams, Constants::NOVALNET_CANCEL_URL);
    }

    /**
     * Do Refund
     *
     * @param aray $data
     * @return array
     */
    public function doRefund(array $data)
    {
        $requestParams = [
            'transaction' => [
                'tid' => $data['NnTransactionTid'],
                'amount' => (int) $data['NnTransactionAmount']
            ],
            'custom' => [
                'lang' => 'EN',
            ]
        ];

        return $this->doRequest($requestParams, Constants::NOVALNET_REFUND_URL);
    }

    /**
     * Build Request Data
     *
     * @param aray $response
     * @param mixed $order
     * @return array
     */
    public function getTransactionDetails($response, $order)
    {
        $requestParams = [
            'transaction' => ['tid'  => $response['tid']],
            'custom' => ['lang' => $order->getLocaleCode()]
        ];

        return $this->doRequest($requestParams, Constants::NOVALNET_TRANSACTION_DETAIL_URL);
    }

    /**
     * Do API request to the server
     *
     * @param aray $requestParams
     * @param aray $endPointURL
     * @return array
     */
    protected function doRequest(array $requestParams, $endPointURL)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Charset' => 'utf-8',
            'Accept' => 'application/json',
            'X-NN-Access-Key' => base64_encode($this->getOption('payment_access_key'))
        ];

        $request = $this->messageFactory->createRequest('POST', $endPointURL, $headers, json_encode($requestParams));
        $response = $this->client->send($request);
        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $response = $response->getBody()->getContents();
        return ($this->novalnetHelper->isJson($response)) ? json_decode($response, true) : $response;
    }

    /**
     * Build Request Data
     *
     * @param aray $data
     * @param mixed $order
     * @return array
     */
    protected function buildRequestData($data, $order)
    {
        $merchantParams = $this->buildMerchantParams();
        $customerParams = $this->buildCustomerParams($data, $order);
        $transactionParams = $this->buildTransactionParams($data, $order);
        $customParams = $this->buildCustomParams($order);
        $data = array_merge($merchantParams, $customerParams, $transactionParams, $customParams);
        $data = $this->novalnetHelper->filterStandardParameter($data);

        if ($this->getOption('factory_name') == Constants::NOVALNET_PAYPAL) {
            $data = array_merge($data, $this->builtCartInfoParams($order));
        }

        return $data;
    }

    /**
     * Build merchant parameters
     *
     * @return array
     */
    protected function buildMerchantParams()
    {
        $data = [];
        $data['merchant'] = [
            'signature' => $this->getOption('signature'),
            'tariff'    => $this->getOption('tariff')
        ];

        return $data;
    }

    /**
     * Build customer parameters
     *
     * @param array $data
     * @param mixed $order
     * @return array
     */
    protected function buildCustomerParams(array $data, $order)
    {
        $paymentMethod = $this->getOption('factory_name');
        $customer = $order->getCustomer();
        $paymentData = !empty($data['payment_data']) ? $data['payment_data'] : [];
        $invoiceAddress = $order->getInvoiceAddress();
        $shippingAddress = $order->getShippingAddress();
        $data = [];

        $data['customer'] = [
            'gender' => ($invoiceAddress->getSalutation() == 'mr') ? 'm' : 'f',
            'first_name' => $invoiceAddress->getFirstname(),
            'last_name' => $invoiceAddress->getLastname(),
            'email' => $customer->getEmail(),
            'customer_ip' => $this->novalnetHelper->getRequestIp()
        ];

        $data['customer']['billing'] = [
            'street' => $invoiceAddress->getStreet(),
            'city' => $invoiceAddress->getCity(),
            'zip' => $invoiceAddress->getPostcode(),
            'country_code' => $invoiceAddress->getCountry()->getIsoCode(),
            'house_no' => $invoiceAddress->getNumber(),
            'state' => $invoiceAddress->getState()
        ];

        if (!empty($paymentData[$paymentMethod . '_dob'])) {
            $data['customer']['birth_date'] = date('Y-m-d', strtotime(str_replace('.', '/', $paymentData[$paymentMethod . '_dob'])));
        }

        if (empty($data['customer']['birth_date']) && !empty($invoiceAddress->getCompany())) {
            $data['customer']['billing']['company'] = $invoiceAddress->getCompany();
        }

        if (!empty($shippingAddress)) {
            if ($this->novalnetHelper->isShippingSameAsBilling($invoiceAddress, $shippingAddress)) {
                $data['customer']['shipping']['same_as_billing'] = 1;
            } else {
                $data['customer']['shipping'] = [
                    'first_name' => $shippingAddress->getFirstname(),
                    'last_name' => $shippingAddress->getLastname(),
                    'email' => $customer->getEmail(),
                    'tel' => $shippingAddress->getPhoneNumber(),
                    'street' => $shippingAddress->getStreet(),
                    'city' => $shippingAddress->getCity(),
                    'zip' => $shippingAddress->getPostcode(),
                    'country_code' => $shippingAddress->getCountry()->getIsoCode(),
                    'state' => $shippingAddress->getState(),
                    'company' => $shippingAddress->getCompany()
                ];
            }
        }

        return $data;
    }

    /**
     * Build transaction parameters
     *
     * @param array $data
     * @param mixed $order
     * @return array
     */
    protected function buildTransactionParams(array $data, $order)
    {
        $paymentMethod = $this->getOption('factory_name');
        $paymentData = !empty($data['payment_data']) ? $data['payment_data'] : [];
        $targetUrl = !empty($data['target_url']) ? $data['target_url'] : '';
        $data = [];

        $data['transaction'] = [
            'payment_type' => $this->novalnetHelper->getPaymentTypeByCode($this->getOption('factory_name')),
            'amount' => $order->getTotal(true),
            'currency' => $this->novalnetHelper->getShopperCurrency(),
            'test_mode' => ($this->getOption('test_mode') && $this->getOption('test_mode') == 1) ? 1 : 0,
            'order_no' => $order->getOrderNumber(),
            'system_ip' => $_SERVER['SERVER_ADDR'],
            'system_name' => $this->novalnetHelper->getSystemName(),
            'system_version' => $this->novalnetHelper->getSystemVersion(),
            'system_url' => $this->novalnetHelper->getSystemUrl(),
            'hook_url' => $this->novalnetHelper->getGlobalConfigurations('webhook_url')
        ];

        $paymentDataKeys = ['token', 'pan_hash', 'unique_id', 'iban', 'bic'];
        foreach ($paymentDataKeys as $paymentDataKey) {
            if (!empty($paymentData[$paymentMethod . '_' . $paymentDataKey])) {
                $data['transaction']['payment_data'][$paymentDataKey] = preg_replace('/\s+/', '', (string) $paymentData[$paymentMethod . '_' . $paymentDataKey]);
            }
        }

        $paymentDueDate = $this->getOption('due_days');
        $paymentDueDate = (!empty($paymentDueDate)) ? ltrim($paymentDueDate, '0') : '';
        if ($paymentDueDate) {
            $data['transaction']['due_date'] = date('Y-m-d', strtotime('+' . $paymentDueDate . ' days'));
        }

        if ($this->novalnetHelper->isRedirectPayment($this->getOption('factory_name')) || (!empty($paymentData[$paymentMethod . '_' . 'do_redirect']) && $paymentData[$paymentMethod . '_' . 'do_redirect'] == '1')) {
            $targetUrl = HttpUri::createFromString($targetUrl);
            $targetUrl = UriModifier::mergeQuery($targetUrl, 'order_no=' . $order->getOrderNumber());
            $data['transaction']['return_url'] = (string) $targetUrl;
            $data['transaction']['error_return_url'] = (string) $targetUrl;
        }

        if ($this->getOption('enforce_3d') && $this->getOption('enforce_3d') == 1) {
            $data['transaction']['enforce_3d'] = 1;
        }

        return $data;
    }

    /**
     * Build custom parameters
     *
     * @param mixed $order
     * @return array
     */
    protected function buildCustomParams($order)
    {
        $data = [];
        $data['custom'] = [
            'lang' => $order->getLocaleCode()
        ];

        return $data;
    }

    /**
     * Build cart info params for paypal payment
     *
     * @param mixed $order
     * @return array
     */
    protected function builtCartInfoParams($order)
    {
        $data['cart_info'] = [];
        $data['cart_info'] = [
            'items_shipping_price' => $order->getShipping(),
            'items_tax_price' => $order->getTotalTax()
        ];

        $lineItems = [];
        foreach ($order->getItems() as $item) {
            array_push($lineItems, [
                'category' => ($item->getDigitalProduct()) ? 'virtual' : 'physical',
                'description' => '',
                'name' => $item->getName(),
                'price' => $item->getTotalGross(),
                'quantity' => $item->getQuantity()
            ]);
        }

        if ($order->getDiscount()) {
            array_push($lineItems, [
                'category' => '',
                'description' => '',
                'name' => 'Discount',
                'price' => '-' . $order->getDiscount(),
                'quantity' => 1
            ]);
        }

        $data['cart_info']['line_items'] = $lineItems;
        return $data;
    }

    /**
     * To get configuration values
     *
     * @param string $key
     * @return string|null
     */
    public function getOption($key)
    {
        return (!empty($this->options[$key])) ? $this->options[$key] : null;
    }
}
