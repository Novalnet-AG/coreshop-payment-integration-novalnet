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
namespace NovalnetBundle\Helper;

use NovalnetBundle\NovalnetBundle;
use NovalnetBundle\Model\Constants;
use Payum\Core\Model\GatewayConfigInterface;
use Doctrine\Persistence\ObjectManager;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use NovalnetBundle\Model\NovalnetTransactionInterface;
use NovalnetBundle\Model\NovalnetCallbackInterface;
use Symfony\Component\Intl\Currencies;

final class NovalnetHelper
{
    /**
     * Novalnet Payment types
     *
     * @var array|ArrayObject
     */
    protected $paymentTypes = [
        Constants::NOVALNET_SEPA => 'DIRECT_DEBIT_SEPA',
        Constants::NOVALNET_CC => 'CREDITCARD',
        Constants::NOVALNET_INVOICE => 'INVOICE',
        Constants::NOVALNET_PREPAYMENT => 'PREPAYMENT',
        Constants::NOVALNET_INVOICE_GUARANTEE => 'GUARANTEED_INVOICE',
        Constants::NOVALNET_SEPA_GUARANTEE => 'GUARANTEED_DIRECT_DEBIT_SEPA',
        Constants::NOVALNET_IDEAL => 'IDEAL',
        Constants::NOVALNET_BANKTRANSFER => 'ONLINE_TRANSFER',
        Constants::NOVALNET_ONLINEBANKTRANSFER => 'ONLINE_BANK_TRANSFER',
        Constants::NOVALNET_GIROPAY => 'GIROPAY',
        Constants::NOVALNET_CASHPAYMENT => 'CASHPAYMENT',
        Constants::NOVALNET_PRZELEWY => 'PRZELEWY24',
        Constants::NOVALNET_EPS => 'EPS',
        Constants::NOVALNET_PAYPAL => 'PAYPAL',
        Constants::NOVALNET_POSTFINANCE_CARD => 'POSTFINANCE_CARD',
        Constants::NOVALNET_POSTFINANCE => 'POSTFINANCE',
        Constants::NOVALNET_BANCONTACT => 'BANCONTACT',
        Constants::NOVALNET_MULTIBANCO => 'MULTIBANCO',
        Constants::NOVALNET_ALIPAY => 'ALIPAY',
        Constants::NOVALNET_WECHATPAY => 'WECHATPAY',
        Constants::NOVALNET_TRUSTLY => 'TRUSTLY'
    ];

    /**
     * Form type payments
     *
     * @var array|ArrayObject
     */
    protected $formPayments = [
        Constants::NOVALNET_CC,
        Constants::NOVALNET_SEPA,
        Constants::NOVALNET_INVOICE_GUARANTEE,
        Constants::NOVALNET_SEPA_GUARANTEE
    ];

    /**
     * Redirect payment methods
     *
     * @var array|ArrayObject
     */
     protected $redirectPayments = [
        Constants::NOVALNET_PAYPAL,
        Constants::NOVALNET_BANKTRANSFER,
        Constants::NOVALNET_ONLINEBANKTRANSFER,
        Constants::NOVALNET_IDEAL,
        Constants::NOVALNET_BANCONTACT,
        Constants::NOVALNET_EPS,
        Constants::NOVALNET_GIROPAY,
        Constants::NOVALNET_PRZELEWY,
        Constants::NOVALNET_POSTFINANCE_CARD,
        Constants::NOVALNET_POSTFINANCE,
        Constants::NOVALNET_ALIPAY,
        Constants::NOVALNET_WECHATPAY,
        Constants::NOVALNET_TRUSTLY
     ];

    /**
     * One click supported payment methods
     *
     * @var array|ArrayObject
     */
    protected $oneClickPayments = [
        Constants::NOVALNET_CC,
        Constants::NOVALNET_SEPA,
        Constants::NOVALNET_PAYPAL
    ];

    /**
     * Get the formated amount in cents/euro
     *
     * @param mixed $amount
     * @param string $type
     * @return string|float|int|null
     */
    public function getFormattedAmount($amount, $type = 'CENT')
    {
        if (!empty($amount)) {
            return ($type == 'RAW') ? number_format($amount / 100, 2, '.', '') : round($amount, 2) * 100;
        }

        return null;
    }

    /**
     * To get client IP address
     *
     * @return string
     */
    public function getRequestIp()
    {
        $pimcoreTool = new \Pimcore\Tool();
        return $pimcoreTool->getClientIp();
    }

    /**
     * To get system version
     *
     * @return string
     */
    public function getSystemVersion()
    {
        $coreshopApplication = new \CoreShop\Bundle\CoreBundle\Application\Version();
        return $coreshopApplication->getVersion() . '-NN' . NovalnetBundle::NOVALNET_BUNDLE_VERSION;
    }

    /**how to get factory class doctrine
     * To get system name
     *
     * @return string
     */
    public function getSystemName()
    {
        $coreShopBundle = new \CoreShop\Bundle\CoreBundle\CoreShopCoreBundle();
        return $coreShopBundle->getNiceName();
    }

    /**
     * To get system URL
     *
     * @return string
     */
    public function getSystemUrl()
    {
        $pimcoreTool = new \Pimcore\Tool();
        return $pimcoreTool->getHostUrl();
    }

    /**
     * To get language
     *
     * @return string
     */
    public function getLanguageCode()
    {
        $translator = \Pimcore::getContainer()->get('translator');
        return strtoupper($translator->getLocale());
    }

    /**
     * To build payment additional data from response
     *
     * @param array $response
     * @param string|null $keyName
     * @return array
     */
    public function buildPaymentAdditionalData($response, $keyName = null)
    {
        $data = [
            'NnTransactionTid' => !empty($response['transaction']['tid']) ? $response['transaction']['tid'] : '',
            'NnTransactionCurrency' => !empty($response['transaction']['currency']) ? $response['transaction']['currency'] : '',
            'NnTransactionAmount' => !empty($response['transaction']['amount']) ? (int) $response['transaction']['amount'] : 0,
            'NnTransactionPaymentType' => !empty($response['transaction']['payment_type']) ? $response['transaction']['payment_type'] : '',
            'NnTransactionStatus' => !empty($response['transaction']['status']) ? $response['transaction']['status'] : '',
            'NnTransactionTestMode' => !empty($response['transaction']['test_mode']) ? $response['transaction']['test_mode'] : '',
            'NnTransactionOrderNo' => !empty($response['transaction']['order_no']) ? $response['transaction']['order_no'] : '',
            'NnTransactionDueDate' => !empty($response['transaction']['due_date']) ? $response['transaction']['due_date'] : '',
            'NnTransactionPartnerPaymentReference' => !empty($response['transaction']['partner_payment_reference']) ? $response['transaction']['partner_payment_reference'] : '',
            'NnTransactionCheckoutJs' => !empty($response['transaction']['checkout_js']) ? $response['transaction']['checkout_js'] : '',
            'NnTransactionCheckoutToken' => !empty($response['transaction']['checkout_token']) ? $response['transaction']['checkout_token'] : '',
            'NnTransactionInvoiceRef' => !empty($response['transaction']['invoice_ref']) ? $response['transaction']['invoice_ref'] : '',
            'NnTransactionTxnSecret' => !empty($response['transaction']['txn_secret']) ? $response['transaction']['txn_secret'] : '',
            'NnResultStatus' => !empty($response['result']['status']) ? $response['result']['status'] : '',
            'NnResultRedirectUrl' => !empty($response['result']['redirect_url']) ? $response['result']['redirect_url'] : '',
            'NnResultStatusText' => !empty($response['result']['status_text']) ? $response['result']['status_text'] : '',
        ];

        if (!empty($response['transaction']['nearest_stores'])) {
            $data['NnTransactionNearestStores'] = $response['transaction']['nearest_stores'];
        }

        if (!empty($response['transaction']['bank_details'])) {
            $bankDetails = $response['transaction']['bank_details'];
            $data['NnBankDetailsAccountHolder'] = !empty($bankDetails['account_holder']) ? $bankDetails['account_holder'] : '';
            $data['NnBankDetailsIban'] = !empty($bankDetails['iban']) ? $bankDetails['iban'] : '';
            $data['NnBankDetailsBic'] = !empty($bankDetails['bic']) ? $bankDetails['bic'] : '';
            $data['NnBankDetailsBankName'] = !empty($bankDetails['bank_name']) ? $bankDetails['bank_name'] : '';
        }

        return $data;
    }

    /**
     * Build order comments
     *
     * @param array $data
     * @param string|null $locale
     * @return string
     */
    public function getOrderComments(array $data, $locale = null)
    {
        $notes = '';
        if (!empty($data['NnTransactionTid'])) {
            $currency = $data['NnTransactionCurrency'];
            $currency = Currencies::getSymbol($currency);
            $amount = $this->getFormattedAmount($data['NnTransactionAmount'], 'RAW');
            $paymentType = !empty($data['NnTransactionPaymentType']) ? $data['NnTransactionPaymentType'] : '';
            $transactionStatus = !empty($data['NnTransactionStatus']) ? $data['NnTransactionStatus'] : '';

            $notes = $this->translate('novalnet.' . $paymentType, $locale) . PHP_EOL;
            $notes .= $this->translate('novalnet.transactionid.label', $locale) . ' ' . $data['NnTransactionTid'] . PHP_EOL;
            $notes .= !empty($data['NnTransactionTestMode']) ? $this->translate('novalnet.testmode.label', $locale) : '';

            if ($paymentType == $this->paymentTypes[Constants::NOVALNET_INVOICE_GUARANTEE] && $transactionStatus == 'PENDING') {
                $notes .= '<br><br>' . $this->translate('novalnet.invoice.guarantee.verification', $locale)  . PHP_EOL;
            }

            if ($paymentType == $this->paymentTypes[Constants::NOVALNET_SEPA_GUARANTEE] && $transactionStatus == 'PENDING') {
                $notes .= '<br><br>' . $this->translate('novalnet.sepa.guarantee.verification', $locale)  . PHP_EOL;
            }

            if (!empty($data['ApiProcess']) && $data['ApiProcess'] == 'capture') {
                $notes .= '<br><br>';
                $notes .= sprintf($this->translate('novalnet.transaction.capture.label', $locale), $data['CaptureApiProcessedAt'])  . PHP_EOL;
            }

            if (!empty($data['ApiProcess']) && $data['ApiProcess'] == 'void') {
                $notes .= '<br><br>';
                $notes .= sprintf($this->translate('novalnet.transaction.void.label', $locale), $data['VoidApiProcessedAt'])  . PHP_EOL;
            }

            if (!empty($data['NnRefunded'])) {
                foreach ($data['NnRefunded'] as $key => $value) {
                    $notes .= '<br><br>';
                    if (!empty($value['refundTid']) && preg_match("/-refund/i", (string) $value['refundTid'])) {
                        $notes .= sprintf($this->translate('novalnet.transaction.refund.label', $locale),
                                    $this->makeValidNumber($value['refundTid']),
                                    $value['refundAmount'],
                                    $currency
                                )  . PHP_EOL;
                    } else {
                        $notes .= sprintf($this->translate('novalnet.transaction.refund.newtid.label', $locale),
                                    $this->makeValidNumber($value['parentTid']),
                                    $value['refundAmount'],
                                    $currency,
                                    $this->makeValidNumber($value['refundTid'])
                                )  . PHP_EOL;
                    }
                }
            }

            if (in_array($paymentType, ['INVOICE', 'PREPAYMENT', 'GUARANTEED_INVOICE'])) {
                $notes .= $this->getBankComments($paymentType, $transactionStatus, $data, $locale);
            }

            if ($paymentType == 'CASHPAYMENT') {
                $cpDueDate = !empty($data['NnTransactionDueDate']) ? $data['NnTransactionDueDate'] : '';
                $notes .= sprintf($this->translate('novalnet.cashpayment.slipexpire.label', $locale), $cpDueDate) . PHP_EOL;
                if (!empty($data['NnTransactionNearestStores'])) {
                    foreach ($data['NnTransactionNearestStores'] as $key => $value) {
                        $notes .= '<br><br>';
                        $notes .= $value['city'] . PHP_EOL;
                        $notes .= $value['country_code'] . PHP_EOL;
                        $notes .= $value['store_name'] . PHP_EOL;
                        $notes .= $value['street'] . PHP_EOL;
                        $notes .= $value['zip'] . PHP_EOL;

                    }
                }
            }

            if ($paymentType == 'MULTIBANCO' && !empty($data['NnTransactionPartnerPaymentReference'])) {
                $notes .= '<br><br>';
                $notes .= sprintf($this->translate('novalnet.multibanco.referencetitle.label', $locale), $amount, $currency) . PHP_EOL;
                $notes .= sprintf($this->translate('novalnet.multibanco.reference.label', $locale), $data['NnTransactionPartnerPaymentReference'])  . PHP_EOL;
            }

            if (!empty($data['NnComments'])) {
                $notes .= $data['NnComments'];
            }
        }

        return $notes;
    }

    /**
     * To build bank comments
     *
     * @param string $paymentType
     * @param string $transactionStatus
     * @param array $data
     * @param string|null $locale
     * @return string
     */
    public function getBankComments($paymentType, $transactionStatus, $data, $locale = null)
    {
        $notes = '<br><br>';
        $currency = $data['NnTransactionCurrency'];
        $currency = Currencies::getSymbol($currency);

        if (in_array($paymentType, ['INVOICE', 'PREPAYMENT']) && !empty($data['NnPaid']) && $data['NnPaid'] == '1') {
            return $notes;
        }

        if (in_array($paymentType, ['INVOICE', 'PREPAYMENT']) && !in_array($transactionStatus, ['PENDING', 'ON_HOLD', 'CONFIRMED'])) {
            return $notes;
        }

        if ($paymentType == $this->paymentTypes[Constants::NOVALNET_INVOICE_GUARANTEE] && !in_array($transactionStatus, ['ON_HOLD', 'CONFIRMED'])) {
            return $notes;
        }

        $amount = $this->getFormattedAmount($data['NnTransactionAmount'], 'RAW');
        $dueDate = !empty($data['NnTransactionDueDate']) ? $data['NnTransactionDueDate'] : '';

        if ($transactionStatus != 'ON_HOLD') {
            $notes .= sprintf($this->translate('novalnet.invoice.transfer.duedate.label', $locale), $amount, $currency, $dueDate) . PHP_EOL;
        } else {
            $notes .= sprintf($this->translate('novalnet.invoice.transfer.label', $locale), $amount, $currency) . PHP_EOL;
        }
        $notes .= $this->translate('novalnet.invoice.holder.label', $locale) . $data['NnBankDetailsAccountHolder'] . PHP_EOL;
        $notes .= 'IBAN: ' . $data['NnBankDetailsIban'] . PHP_EOL;
        $notes .= 'BIC: ' . $data['NnBankDetailsBic'] . PHP_EOL;
        $notes .= 'Bank: ' . $data['NnBankDetailsBankName'] . PHP_EOL;
        $notes .= $this->translate('novalnet.invoice.neccessary.label', $locale) . PHP_EOL;
        $notes .= $this->translate('novalnet.invoice.reference.label', $locale) . PHP_EOL;
        $notes .= $this->translate('novalnet.payment.reference.1', $locale) . $data['NnTransactionTid'] . PHP_EOL;
        if (!empty($data['NnTransactionInvoiceRef'])) {
            $notes .= $this->translate('novalnet.payment.reference.2', $locale) . $data['NnTransactionInvoiceRef'] . PHP_EOL;
        }

        return $notes;
    }

    /**
     * To translate the string
     *
     * @param string $key
     * @return string
     */
    public function translate(string $key, $locale = null)
    {
        $translator = \Pimcore::getContainer()->get('translator');
        $locale = !empty($locale) ? $locale : $translator->getLocale();
        return $translator->trans($key, [], null, $locale);
    }

    /**
     * To check the data is valid JSON
     *
     * @param mixed $data
     * @return bool
     */
    public function isJson($data)
    {
        try {
            json_decode($data, true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get filter standard param
     *
     * @param array $requestData
     *
     * @return array
     */
    public function filterStandardParameter($requestData)
    {
        $excludedParams = ['test_mode', 'enforce_3d', 'amount', 'storeId'];

        foreach ($requestData as $key => $value) {
            if (is_array($value)) {
                $requestData[$key] = $this->filterStandardParameter($requestData[$key]);
            }

            if (!in_array($key, $excludedParams) && empty($requestData[$key])) {
                unset($requestData[$key]);
            }
        }

        return $requestData;
    }

    /**
     * Get filter standard param
     *
     * @param mixed $invoiceAddress
     * @param mixed $shippingAddress
     * @return bool
     */
    public function isShippingSameAsBilling($invoiceAddress, $shippingAddress)
    {
        return ($invoiceAddress->getFirstname() == $shippingAddress->getFirstname() &&
                $invoiceAddress->getLastname() == $shippingAddress->getLastname() &&
                $invoiceAddress->getStreet() == $shippingAddress->getStreet() &&
                $invoiceAddress->getCity() == $shippingAddress->getCity() &&
                $invoiceAddress->getCountry()->getIsoCode() ==$shippingAddress->getCountry()->getIsoCode() &&
                $invoiceAddress->getPostcode() == $shippingAddress->getPostcode()
            );
    }

    /**
     * Get Payment method codes
     *
     * @return array
     */
     public function getPaymentMethodCodes()
     {
         return array_keys($this->paymentTypes);
     }

    /**
     * Get Payment method code by Type
     *
     * @param string $paymentType
     * @return string
     */
     public function getPaymentCodeByType($paymentType)
     {
         return array_search($paymentType, $this->paymentTypes);
     }

    /**
     * Get Payment Type
     *
     * @param string $code
     * @return string
     */
     public function getPaymentTypeByCode($code)
     {
         return $this->paymentTypes[$code];
     }

    /**
     * Check is redirect Payment method
     *
     * @param string $code
     * @return bool
     */
     public function isRedirectPayment($code)
     {
         return (bool) (in_array($code, $this->redirectPayments));
     }

    /**
     * Check is one click supported
     *
     * @param string $code
     * @return bool
     */
     public function isOneClickPayment($code)
     {
         return (bool) (in_array($code, $this->oneClickPayments));
     }

    /**
     * Check is form payment method
     *
     * @param string $code
     * @return bool
     */
     public function isFormPayment($code)
     {
         return (bool) (in_array($code, $this->formPayments));
     }

    /**
     * To get global configurations
     *
     * @param string|null $key
     * @return array|null
     */
     public function getGlobalConfigurations($key = null)
     {
        $globalConfig = null;
        $container = \Pimcore::getContainer();
        $gatewayConfig = $container->get('doctrine')
            ->getRepository(GatewayConfigInterface::class)
            ->findByFactoryName(Constants::NOVALNET_GLOBAL);

        if (is_array($gatewayConfig) && count($gatewayConfig) && !empty(end($gatewayConfig))) {
            $globalConfig = end($gatewayConfig)->getConfig();
        }

        if (!empty($key)) {
            return (isset($globalConfig[$key])) ? $globalConfig[$key] : null;
        }

        return $globalConfig;
     }

    /**
     * To get payment configurations
     *
     * @param string|null $key
     * @return array|null
     */
     public function getPaymentConfig($paymentCode, $key = null)
     {
        $paymentConfig = null;
        $container = \Pimcore::getContainer();
        $gatewayConfig = $container->get('doctrine')
            ->getRepository(GatewayConfigInterface::class)
            ->findByFactoryName($paymentCode);

        if (is_array($gatewayConfig) && count($gatewayConfig) && !empty(end($gatewayConfig))) {
            $paymentConfig = end($gatewayConfig)->getConfig();
        }

        if (!empty($key)) {
            return (isset($paymentConfig[$key])) ? $paymentConfig[$key] : null;
        }

        return $paymentConfig;
     }

    /**
     * Validate Customer Company param
     *
     * @param string $company
     * @param bool $allowB2b
     * @return bool
     */
    public function canShowDob($company, $allowB2b)
    {
        if (!empty($company) && $allowB2b) {
            if (preg_match('/^(?:\d+|(?:)\.?|[^a-zA-Z0-9]+|[a-zA-Z]{1})$|^(herr|frau|jahr|mr|miss|mrs|others|andere|anrede|salutation|null|none|keine|company|firma|no|na|n\/a|test|private|privat)$/i', (string) $company)) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Check customer DOB is valid
     *
     * @param string $birthDate
     * @return bool
     */
    public function validateBirthDate($birthDate)
    {
        if (empty($birthDate)) {
            return true;
        }
        $age = strtotime('+18 years', strtotime($birthDate));
        return (time() < $age) ? false : true;
    }

    /**
     * Replace strings from the tid passed
     *
     * @param mixed $tid
     * @return string|null
     */
    public function makeValidNumber($tid)
    {
        return preg_replace('/[^0-9]+/', '', (string) $tid);
    }

    /**
     * Check the value is numeric
     *
     * @param  mixed $value
     * @return bool
     */
    public function checkIsNumeric(string $value)
    {
        if (!empty($value)) {
            return (bool) preg_match('/^\d+$/', $value);
        }

        return false;
    }

    /**
     * Check shoppers currency code
     *
     * @return bool
     */
    public function getShopperCurrency()
    {
        $shopperContext = \Pimcore::getContainer()->get(\CoreShop\Component\Core\Context\ShopperContextInterface::class);
        return $shopperContext->getCurrency()->getIsoCode();
    }
}
