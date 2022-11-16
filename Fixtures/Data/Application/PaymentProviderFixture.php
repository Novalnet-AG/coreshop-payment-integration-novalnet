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

namespace NovalnetBundle\Fixtures\Data\Application;

use CoreShop\Bundle\FixtureBundle\Fixture\VersionedFixtureInterface;
use CoreShop\Component\Core\Model\PaymentProviderInterface;
use CoreShop\Component\PayumPayment\Model\GatewayConfig;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Provider\Lorem;
use Pimcore\Tool;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pimcore\Model\Staticroute;
use NovalnetBundle\Model\Constants;

class PaymentProviderFixture extends AbstractFixture implements ContainerAwareInterface, VersionedFixtureInterface
{
    private ?ContainerInterface $container;

    /**
     * To get version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return '2.0';
    }

    /**
     * To set container
     *
     * @param ContainerInterface|null $container
     * @return void
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * Load
     *
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $this->installNovalnetStaticRoutes();
        $this->autoCreateNovalnetPayments($manager);
    }

    /**
     * Create novalnet static routes
     *
     * @return Staticroute
     */
    private function installNovalnetStaticRoutes(): Staticroute
    {
        $staticRoutes = [
            'coreshop_novalnet_callback' => [
                'pattern' => '/(\w+)\/novalnet\/callback$/',
                'reverse' => '/%_shop/novalnet/callback/',
                'controller' => 'NovalnetBundle\Controller\NovalnetCallbackController:callbackAction',
                'variables' => '_locale',
                'priority' => 1
            ],
            'coreshop_novalnet_globalconfig' => [
                'pattern' => '/(\w+)\/novalnet\/globalconfig$/',
                'reverse' => '/%_shop/novalnet/globalconfig/',
                'controller' => 'NovalnetBundle\Controller\NovalnetGlobalConfigController:processAction',
                'variables' => '_locale',
                'priority' => 1
            ]
        ];

        foreach ($staticRoutes as $routeName => $data) {
            $route = Staticroute::getByName($routeName);
            if (!$route) {
                $route = new Staticroute();
                $route->setId($routeName);
                $route->setName($routeName);
                $route->setPattern($data['pattern']);
                $route->setReverse($data['reverse']);
                $route->setController($data['controller']);
                $route->setVariables($data['variables']);
                $route->setPriority($data['priority']);
                $route->save();
            }
        }

        return $route;
    }

    /**
     * Create novalnet payments in the admin (CLI- coreshop:install:fixtures)
     *
     * @param ObjectManager $manager
     * @return void
     */
    private function autoCreateNovalnetPayments(ObjectManager $manager)
    {
        foreach ($this->getNovalnetPaymentProvidersData() as $providerName => $data) {
            if (!count($this->container->get('coreshop.repository.payment_provider')->findByIdentifier($providerName))) {

                $defaultStore = $this->container->get('coreshop.repository.store')->findStandard();

                /**
                 * @var PaymentProviderInterface $providerModel
                 */
                $providerModel = $this->container->get('coreshop.factory.payment_provider')->createNew();
                $gatewayConfig = new GatewayConfig();
                $gatewayConfig->setFactoryName($providerName);
                $gatewayConfig->setGatewayName($providerName);
                $gatewayConfig->setConfig($data['config']);

                $providerModel->setIdentifier($providerName);
                $providerModel->setActive(false);
                $providerModel->setPosition($data['position']);
                $providerModel->addStore($defaultStore);
                $providerModel->setGatewayConfig($gatewayConfig);
                $manager->persist($providerModel);
                $manager->flush();

                $this->setPaymentTranslations($providerName, $data);

                $this->setReference('payment_provider', $providerModel);
            }
        }
    }

    /**
     * To set payment provider translation texts
     *
     * @param string $providerName
     * @param array $data
     * @return void
     */
    private function setPaymentTranslations($providerName, $data)
    {
        $provider = $this->container->get('coreshop.repository.payment_provider')->findByIdentifier($providerName);
        $translatableId = null;
        if (!empty($translatableId = $provider[0]->getId())) {
            foreach (Tool::getValidLanguages() as $locale) {
                if (!in_array($locale, ['en', 'de'])) {
                    continue;
                }
                $title = !empty($data['title'][$locale]) ? $data['title'][$locale] : '';
                $description = !empty($data['description'][$locale]) ? $data['description'][$locale] : '';
                $instruction = !empty($data['instruction'][$locale]) ? $data['instruction'][$locale] : '';

                $entityManager = \Pimcore::getContainer()->get('doctrine')->getManager();
                $sql = "INSERT INTO `coreshop_payment_provider_translation` (translatable_id, title, description, instructions, locale) VALUES ('$translatableId', '$title', '$description', '$instruction', '$locale')";
                $query = $entityManager->getConnection()->prepare($sql);
                $query->execute();
            }
        }
    }

    /**
     * Novalnet payments default configuration values
     *
     * @return array
     */
    private function getNovalnetPaymentProvidersData()
    {
        return [
            Constants::NOVALNET_GLOBAL => [
                'position' => 1,
                'title' => [
                    'en' => "Novalnet Global Configuration",
                    'de' => "Novalnet Haupteinstellungen"
                ],
                'description' => [
                    'en' => '',
                    'de' => ''
                ],
                'instruction' => [
                    'de' => '',
                    'en' => ''
                ],
                'config' => [
                    'webhook_testmode' => '0',
                    'onhold_status' => 'authorized',
                    'restore_cart' => '1'
                ]
            ],
            Constants::NOVALNET_SEPA => [
                'position' => 2,
                'title' => [
                    'en' => "Direct Debit SEPA",
                    'de' => "SEPA-Lastschrift"
                ],
                'description' => [
                    'en' => "Europe-wide Direct Debit system that allows you to collect Euro currencies from buyers in the 34 SEPA countries and associated regions",
                    'de' => "Europaweites SEPA Lastschriftverfahren, welches Ihnen ermöglicht, EUR-Währungen von Käufern innerhalb der 34 SEPA Ländern und assoziierte Regionen einzuziehen"
                ],
                'instruction' => [
                    'de' => "Der Betrag wird durch Novalnet von Ihrem Konto abgebucht",
                    'en' => "The amount will be debited from your account by Novalnet"
                ],
                'config' => [
                    'test_mode' => '1',
                    'use_authorize' => false,
                    'payment_action' => '0',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_CC => [
                'position' => 3,
                'title' => [
                    'en' => "Credit / Debit Cards",
                    'de' => "Kredit- / Debitkarte"
                ],
                'description' => [
                    'en' => "Funds are withdrawn from the buyer\'s account using credit/debit card details",
                    'de' => "Der Betrag wird von dem Konto des Käufers bei Nutzung einer Kredit-/Bankkarte eingezogen"
                ],
                'instruction' => [
                    'de' => "Ihre Karte wird nach Bestellabschluss sofort belastet",
                    'en' => "Your credit/debit card will be charged immediately after the order is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'enforce_3d' => '1',
                    'use_authorize' => false,
                    'payment_action' => '0',
                    'completed_status' => 'captured',
                    'inline_form' => '1',
                    'form_label_style' => 'font-family: Raleway,Helvetica Neue,Verdana,Arial,sans-serif;font-size: 13px;font-weight: 600;color: #636363;line-height: 1.5;',
                    'form_input_style' => 'color: #636363;font-family: Helvetica Neue,Verdana,Arial,sans-serif;font-size: 14px;',
                    'form_css' => '',
                    'logo_types' => 'novalnetvisa,novalnetmastercard,novalnetamex,novalnetmaestro,novalnetcartasi,novalnetunionpay,novalnetdiscover,novalnetdiners,novalnetjcb,novalnetcartebleue'
                ]
            ],
            Constants::NOVALNET_INVOICE => [
                'position' => 4,
                'title' => [
                    'en' => "Invoice",
                    'de' => "Kauf auf Rechnung"
                ],
                'description' => [
                    'en' => "A payable credit note with the order details",
                    'de' => "Der Kunde erhält die Ware vor der Bezahlung und erhält mit der Lieferung die Rechnung mit den Bestelldetails sowie einem festgelegtem Zahlungsziel"
                ],
                'instruction' => [
                    'de' => "Sie erhalten eine E-Mail mit den Bankdaten von Novalnet, um die Zahlung abzuschließen",
                    'en' => "You will receive an e-mail with the Novalnet account details to complete the payment"
                ],
                'config' => [
                    'test_mode' => '1',
                    'use_authorize' => false,
                    'payment_action' => '0',
                    'completed_status' => 'pending',
                    'webhook_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_PREPAYMENT => [
                'position' => 5,
                'title' => [
                    'en' => "Prepayment",
                    'de' => "Vorkasse"
                ],
                'description' => [
                    'en' => "Payment is debited after order confirmation and, the goods are then delivered",
                    'de' => "Der Betrag wird nach der Bestellbestätigung eingezogen, erst nach erfolgreicher Abbuchung wird die Ware an den Kunden verschickt"
                ],
                'instruction' => [
                    'de' => "Sie erhalten eine E-Mail mit den Bankdaten von Novalnet, um die Zahlung abzuschließen",
                    'en' => "You will receive an e-mail with the Novalnet account details to complete the payment"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'pending',
                    'webhook_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_INVOICE_GUARANTEE => [
                'position' => 6,
                'title' => [
                    'en' => "Invoice with payment guarantee",
                    'de' => "Rechnung mit Zahlungsgarantie"
                ],
                'description' => [
                    'en' => "Guaranteed payment made to you either from the buyer or from payment guarantee for the purchase made through invoice",
                    'de' => "Garantierte Zahlung an Sie aufgrund der Zahlungsgarantie bei einem Kauf auf Rechnung"
                ],
                'instruction' => [
                    'de' => "Sie erhalten eine E-Mail mit den Bankdaten von Novalnet, um die Zahlung abzuschließen",
                    'en' => "You will receive an e-mail with the Novalnet account details to complete the payment"
                ],
                'config' => [
                    'test_mode' => '1',
                    'use_authorize' => false,
                    'payment_action' => '0',
                    'completed_status' => 'captured',
                    'allow_b2b' => '1',
                    'min_order_amount' => '9.99'
                ]
            ],
            Constants::NOVALNET_SEPA_GUARANTEE => [
                'position' => 7,
                'title' => [
                    'en' => "Direct Debit SEPA with payment guarantee",
                    'de' => "SEPA-Lastschrift mit Zahlungsgarantie"
                ],
                'description' => [
                    'en' => "Guaranteed payment made to you either from the buyer or from payment guarantee for the purchase made through SEPA",
                    'de' => "Garantierte Zahlung an Sie aufgrund der Zahlungsgarantie bei einer SEPA Lastschrift"
                ],
                'instruction' => [
                    'de' => "Der Betrag wird durch Novalnet von Ihrem Konto abgebucht",
                    'en' => "The amount will be debited from your account by Novalnet"
                ],
                'config' => [
                    'test_mode' => '1',
                    'use_authorize' => false,
                    'payment_action' => '0',
                    'completed_status' => 'captured',
                    'allow_b2b' => '1',
                    'min_order_amount' => '9.99'
                ]
            ],
            Constants::NOVALNET_IDEAL => [
                'position' => 8,
                'title' => [
                    'en' => "iDEAL",
                    'de' => "iDEAL"
                ],
                'description' => [
                    'en' => "Dutch payment method that allow your buyers to make instant payments online through his own bank",
                    'de' => "Niederländische Zahlungsmethode, mit der Ihre Käufer sofortige Online-Zahlungen über ihre eigene Bank vornehmen können"
                ],
                'instruction' => [
                    'de' => "Sie werden zu iDEAL weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist.",
                    'en' => "You will be redirected to iDEAL. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_BANKTRANSFER => [
                'position' => 9,
                'title' => [
                    'en' => "Sofort",
                    'de' => "Sofortüberweisung"
                ],
                'description' => [
                    'en' => "Pan European payment method allows buyers to pay through their own internet banking system",
                    'de' => "Paneuropäische Zahlungsmethode, mit der Ihre Käufer Online-Zahlungen über ihr eigenes Online Banking System vornehmen können"
                ],
                'instruction' => [
                    'de' => "Sie werden zu Sofortüberweisung weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to Sofort. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_GIROPAY => [
                'position' => 10,
                'title' => [
                    'en' => "giropay",
                    'de' => "giropay"
                ],
                'description' => [
                    'en' => "German based online payment method where funds are instantly transferred from buyers account to your account",
                    'de' => "Deutsche Online-Zahlungsmethode, bei der Gelder sofort vom Konto des Käufers auf Ihr Konto überwiesen werden"
                ],
                'instruction' => [
                    'de' => "Sie werden zu giropay weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to giropay. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_CASHPAYMENT => [
                'position' => 11,
                'title' => [
                    'en' => "Barzahlen/viacash",
                    'de' => "Barzahlen/viacash"
                ],
                'description' => [
                    'en' => "Transaction is completed through cash payments using cash slips in countries like Germany and Austria",
                    'de' => "Die Transaktion wird in Ländern wie Deutschland und Österreich durch Barzahlungen mit Kassenzetteln abgeschlossen"
                ],
                'instruction' => [
                    'de' => "Nach erfolgreichem Bestellabschluss erhalten Sie einen Zahlschein bzw. eine SMS. Damit können Sie Ihre Online-Bestellung bei einem unserer Partner im Einzelhandel (z.B. Drogerie, Supermarkt etc.) bezahlen",
                    'en' => "On successful checkout, you will receive a payment slip/SMS to pay your online purchase at one of our retail partners (e.g. supermarket)"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'pending',
                    'webhook_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_PRZELEWY => [
                'position' => 12,
                'title' => [
                    'en' => "Przelewy24",
                    'de' => "Przelewy24"
                ],
                'description' => [
                    'en' => "Poland based payment method which allows buyers pay using bank transfers or any other methods",
                    'de' => "Polnische Zahlungsmethode, die dem Käufer es ermöglicht durch Banküberweisungen oder andere Methoden zu bezahlen"
                ],
                'instruction' => [
                    'de' => "Sie werden zu Przelewy24 weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to Przelewy24. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_EPS => [
                'position' => 13,
                'title' => [
                    'en' => "eps",
                    'de' => "eps"
                ],
                'description' => [
                    'en' => "Austria based online banking method that allows your buyers to pay using any form of electronic payments",
                    'de' => "Österreichische Zahlungsmethode, die es dem Käufer ermöglicht Zahlungen über ihr eigenes Online Banking System vornehmen zu können"
                ],
                'instruction' => [
                    'de' => "Sie werden zu eps weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to eps. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_PAYPAL => [
                'position' => 14,
                'title' => [
                    'en' => "PayPal",
                    'de' => "PayPal"
                ],
                'description' => [
                    'en' => "Electronic wallet that alows buyers to pay using any payment modes they have added to their PayPal account",
                    'de' => "Cyberwallet System, die es Ihren Käufern ermöglicht, mit allen Zahlungsmethoden zu bezahlen, die sie ihrem PayPal-Konto hinzugefügt haben"
                ],
                'instruction' => [
                    'de' => "Sie werden zu PayPal weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to PayPal. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'use_authorize' => false,
                    'payment_action' => '0',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_POSTFINANCE => [
                'position' => 15,
                'title' => [
                    'en' => "PostFinance E-Finance",
                    'de' => "PostFinance E-Finance"
                ],
                'description' => [
                    'en' => "Swiss based online account system where buyers are redirected to login and pay using their PostFinance Card",
                    'de' => "Online-Zahlungsmethode in der Schweiz, bei dem Käufer sich in ihr PostFinance E-Finance Konto einloggen müssen und über die PostFinance Card bezahlen"
                ],
                'instruction' => [
                    'de' => "Sie werden zu PostFinance weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to PostFinance. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_POSTFINANCE_CARD => [
                'position' => 16,
                'title' => [
                    'en' => "PostFinance Card",
                    'de' => "PostFinance Card"
                ],
                'description' => [
                    'en' => "Swiss based online card payment method which allows buyers to pay using PostFinance Card",
                    'de' => "Online-Zahlungsmethode in der Schweiz, die es Käufern ermöglicht mit der PostFinance Card zu bezahlen"
                ],
                'instruction' => [
                    'de' => "Sie werden zu PostFinance weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to PostFinance. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_BANCONTACT => [
                'position' => 17,
                'title' => [
                    'en' => "Bancontact",
                    'de' => "Bancontact"
                ],
                'description' => [
                    'en' => "Belgium based online payment method where buyers are redirected to Bancontact site/app for payment authorization",
                    'de' => "Belgische Online-Zahlungsmethode, bei der die Käufer auf die Bancontact Seite weitergeleitet werden um die Zahlungsgenehmigung zu beantragen"
                ],
                'instruction' => [
                    'de' => "Sie werden zu Bancontact weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to Bancontact. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_MULTIBANCO => [
                'position' => 18,
                'title' => [
                    'en' => "Multibanco",
                    'de' => "Multibanco"
                ],
                'description' => [
                    'en' => "Voucher based payment method where buyer pays in ATM or at retail outlets using a reference ID",
                    'de' => "Belegbasierte Zahlungsmethode, bei der der Käufer am Geldautomaten oder in Einzelhandelsgeschäften unter Verwendung einer Referenz-ID bezahlt"
                ],
                'instruction' => [
                    'de' => "Nach erfolgreichem Bestellabschluss erhalten Sie eine Zahlungsreferenz. Damit können Sie entweder an einem Multibanco-Geldautomaten oder im Onlinebanking bezahlen.",
                    'en' => "On successful checkout, you will receive a payment reference. Using this payment reference, you can either pay in the Multibanco ATM or through your online bank account"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'pending',
                    'webhook_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_ONLINEBANKTRANSFER => [
                'position' => 19,
                'title' => [
                    'en' => "Online bank transfer",
                    'de' => "Onlineüberweisung"
                ],
                'description' => [
                    'en' => "Pan European payment method allows buyers to pay through their own internet banking system",
                    'de' => "Paneuropäische Zahlungsmethode, mit der Ihre Käufer Online-Zahlungen über ihr eigenes Online Banking System vornehmen können"
                ],
                'instruction' => [
                    'de' => "Sie werden auf die Banking-Seite weitergeleitet. Bitte schließen oder aktualisieren Sie den Browser nicht, bis die Zahlung abgeschlossen ist",
                    'en' => "You will be redirected to banking page. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_ALIPAY => [
                'position' => 20,
                'title' => [
                    'en' => "Alipay",
                    'de' => "Alipay"
                ],
                'description' => [
                    'en' => "Dutch payment method that allow your buyers to make instant payments online through his own bank",
                    'de' => "Niederländische Zahlungsmethode, mit der Ihre Käufer sofortige Online-Zahlungen über ihre eigene Bank vornehmen können"
                ],
                'instruction' => [
                    'de' => "Sie werden zu Alipay weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to Alipay. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_WECHATPAY => [
                'position' => 21,
                'title' => [
                    'en' => "WeChat Pay",
                    'de' => "WeChat Pay"
                ],
                'description' => [
                    'en' => "Dutch payment method that allow your buyers to make instant payments online through his own bank",
                    'de' => "Niederländische Zahlungsmethode, mit der Ihre Käufer sofortige Online-Zahlungen über ihre eigene Bank vornehmen können"
                ],
                'instruction' => [
                    'de' => "Sie werden zu WeChat Pay weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to WeChat Pay. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ],
            Constants::NOVALNET_TRUSTLY => [
                'position' => 22,
                'title' => [
                    'en' => "Trustly",
                    'de' => "Trustly"
                ],
                'description' => [
                    'en' => "Dutch payment method that allow your buyers to make instant payments online through his own bank",
                    'de' => "Niederländische Zahlungsmethode, mit der Ihre Käufer sofortige Online-Zahlungen über ihre eigene Bank vornehmen können"
                ],
                'instruction' => [
                    'de' => "Sie werden zu Trustly weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist",
                    'en' => "You will be redirected to Trustly. Please don\'t close or refresh the browser until the payment is completed"
                ],
                'config' => [
                    'test_mode' => '1',
                    'completed_status' => 'captured'
                ]
            ]
        ];
    }
}
