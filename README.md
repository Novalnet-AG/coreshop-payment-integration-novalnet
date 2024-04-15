# NOVALNET PAYMENT BUNDLE FOR CORESHOP
Novalnet payment bundle for CoreShop simplifies your daily work by automating the entire payment process, from checkout till collection. This addon is designed to help you increase your sales by offering various payment methods on a one-page checkout. The payment bundle is perfectly adjusted to the CoreShop shop with top-quality range of services of the payment provider.

## CoreShop Bundle Integration Requirements
<a href="https://www.novalnet.de/">Novalnet</a> merchant account is required for processing all international and local payments through this CoreShop payment bundle. The bunlde is available for Pimcore versions: 10.15.10, CoreShop versions: 3.0.0 in the following languages: EN & DE

## Key Features

* Easy configuration of all international & local payment methods
* One PCI DSS certified payment platform for all payment services from checkout to collection
* Complete automation of all payment processes
* 60+ risk & payment fraud detection modules to prevent defaults in real time
* Effortless configuration of risk management with fraud prevention
* Affiliate solution supporting frictionless onboarding, split payments & currency conversion
* Dynamic subscription and member management for recurring payments
* No PCI DSS certification required when using our payment module
* Clear overview of payment status from checkout to receivables
* Multilevel claims management with integrated handover to collection and various export functions for the accounting
* Automated e-mail notifications for staying up to date on the payment status
* Clear real-time overview and monitoring of payment status
* Automated bookkeeping report in XML, SOAP, CSV, MT940
* Simple seamless integration of the payment bundle
* Secure SSL- encoded gateways
* On-hold transaction configuration in the shop admin panel
* Easy way of confirmation and cancellation of on-hold transactions for Direct Debit SEPA, Direct Debit SEPA with payment guarantee, Credit/Debit Cards, Invoice, Invoice with payment guarantee & PayPal
* Refund option for Credit/Debit Cards, Direct Debit SEPA, Direct Debit SEPA with payment guarantee, Invoice, Invoice with payment guarantee, Prepayment, Barzahlen/viacash, Sofort, iDEAL, eps, giropay, PayPal, Przelewy24, PostFinance Card, PostFinance E-Finance, Bancontact, Online bank transfer, Trustly, Alipay & WeChat Pay.
* Responsive templates

## Integrated Payment Methods

- Direct Debit SEPA
- Credit/Debit Cards
- Invoice
- Prepayment
- Invoice with payment guarantee
- Direct Debit SEPA with payment guarantee
- iDEAL
- Sofort
- giropay
- Barzahlen/viacash
- Przelewy24
- eps
- PayPal
- PostFinance Card
- PostFinance E-Finance
- Bancontact
- Multibanco
- Online bank transfer
- Alipay
- WeChat Pay
- Trustly

## Installation via Composer

#### Follow the below steps and run each command from the shop root directory
 ##### 1. Run the below command to start with upgrading composer to the latest version
 ```
 composer self-update
 ```
 ##### 2. Run the below command to install the composer package using the Composer require command:
 ```
 composer require novalnet/pimcore-coreshop
 ```
 ##### 3. Run the below command to enable Novalnet bundle
 ```
 bin/console pimcore:bundle:enable NovalnetBundle
 ```
 ##### 4. Run the below command to create the Novalnet database tables.
 ```
 bin/console coreshop:resources:create-tables novalnet -f
 ```
 ##### 5. Run the below command to create Novalnet payment types and to set default payment configurations.
 ```
 bin/console coreshop:install:fixtures
 ```
 ##### 6. Run the below command clear the cache
 ```
 bin/console pimcore:cache:clear
 ```
## License
See our License Agreement at: https://www.novalnet.com/payment-plugins-free-license/

## Support 
For more information about the CoreShop payment bundle by Novalnet, please get in touch with us: <a href="mailto:sales@novalnet.de"> sales@novalnet.de </a> or +49 89 9230683-20<br>

Novalnet AG<br>
Zahlungsinstitut (ZAG)<br>
Gutenbergstraße 7<br>
D-85748 Garching<br>
Deutschland<br>
E-mail: sales@novalnet.de<br>
Tel: +49 89 9230683-20<br>
Web: www.novalnet.de

## About Novalnet AG
Novalnet AG is a leading financial services institution offering online gateways for processing of online payments. Operating in the market as a full payment service provider, Novalnet AG was founded in Ismaning near Munich, and provides online merchants user-friendly payment modules for all major shop systems as well as for self-programmed websites. The product and service portfolio is very comprehensive and includes all commonly used payment methods of online payment. These include a variety of intelligent fraud prevention modules, free technical support, an automated accounts receivable management system, a comprehensive subscription and membership management, as well as a very useful affiliate program. The experienced and international team of specialists at Novalnet is committed to support online merchants with in-depth knowledge and to work together with them hand in hand to increase their revenue and the quality of their online payments.
