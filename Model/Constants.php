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

final class Constants
{
    public const NOVALNET_GLOBAL = 'novalnet_global_config';

    // Payment methods
    public const NOVALNET_CC = 'novalnet_cc';
    public const NOVALNET_SEPA = 'novalnet_sepa';
    public const NOVALNET_INVOICE = 'novalnet_invoice';
    public const NOVALNET_PREPAYMENT = 'novalnet_prepayment';
    public const NOVALNET_INVOICE_GUARANTEE = 'novalnet_invoice_guarantee';
    public const NOVALNET_SEPA_GUARANTEE = 'novalnet_sepa_guarantee';
    public const NOVALNET_CASHPAYMENT = 'novalnet_cashpayment';
    public const NOVALNET_PAYPAL = 'novalnet_paypal';
    public const NOVALNET_BANKTRANSFER = 'novalnet_banktransfer';
    public const NOVALNET_ONLINEBANKTRANSFER = 'novalnet_online_banktransfer';
    public const NOVALNET_IDEAL = 'novalnet_ideal';
    public const NOVALNET_EPS = 'novalnet_eps';
    public const NOVALNET_GIROPAY = 'novalnet_giropay';
    public const NOVALNET_PRZELEWY = 'novalnet_przelewy';
    public const NOVALNET_POSTFINANCE = 'novalnet_post_finance';
    public const NOVALNET_POSTFINANCE_CARD = 'novalnet_post_finance_card';
    public const NOVALNET_MULTIBANCO = 'novalnet_multibanco';
    public const NOVALNET_BANCONTACT = 'novalnet_bancontact';
    public const NOVALNET_ALIPAY = 'novalnet_alipay';
    public const NOVALNET_TRUSTLY = 'novalnet_trustly';
    public const NOVALNET_WECHATPAY = 'novalnet_wechatpay';

    // API end points
    public const NOVALNET_PAYMENT_URL = 'https://payport.novalnet.de/v2/payment';
    public const NOVALNET_AUTHORIZE_URL = 'https://payport.novalnet.de/v2/authorize';
    public const NOVALNET_CAPTURE_URL = 'https://payport.novalnet.de/v2/transaction/capture';
    public const NOVALNET_REFUND_URL = 'https://payport.novalnet.de/v2/transaction/refund';
    public const NOVALNET_CANCEL_URL = 'https://payport.novalnet.de/v2/transaction/cancel';
    public const NOVALNET_MERCHANT_DETAIL_URL = 'https://payport.novalnet.de/v2/merchant/details';
    public const NOVALNET_TRANSACTION_DETAIL_URL = 'https://payport.novalnet.de/v2/transaction/details';
    public const NOVALNET_WEBHOOK_CONFIG_URL = 'https://payport.novalnet.de/v2/webhook/configure';

    final private function __construct()
    {
    }
}
