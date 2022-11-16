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
namespace NovalnetBundle\Gateway;

use NovalnetBundle\Action\Api\ObtainTokenAction;
use NovalnetBundle\Action\Api\GetTransactionDetailsAction;
use NovalnetBundle\Action\CaptureAction;
use NovalnetBundle\Action\AuthorizeAction;
use NovalnetBundle\Action\CancelAction;
use NovalnetBundle\Action\RefundAction;
use NovalnetBundle\Action\StatusAction;
use NovalnetBundle\Model\Api;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Payum\Core\Model\GatewayConfigInterface;
use NovalnetBundle\Model\Constants;
use NovalnetBundle\Helper\NovalnetHelper;

class NovalnetGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => Constants::NOVALNET_GLOBAL,
            'payum.factory_title' => 'Novalnet',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.api.get_transaction_details' => new GetTransactionDetailsAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'payment_access_key' => '',
                'signature' => '',
                'tariff' => '',
                'use_authorize' => '',
                'test_mode' => true
            ];

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [
                'payment_access_key',
                'signature',
                'tariff'
            ];

            $novalnetHelper = \Pimcore::getContainer()->get(NovalnetHelper::class);
            $globalConfig = $novalnetHelper->getGlobalConfigurations();
            $globalParams = ['signature', 'payment_access_key', 'tariff', 'restore_cart', 'client_key', 'onhold_status', 'webhook_url', 'webhook_testmode', 'merchant_mailto'];

            foreach ($globalParams as $param) {
                $config[$param] = !empty($globalConfig[$param]) ? $globalConfig[$param] : null;
            }

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    [
                        'factory_name' => $config['payum.factory_name'],
                        'signature' => $config['signature'],
                        'payment_access_key' => $config['payment_access_key'],
                        'tariff' => $config['tariff'],
                        'restore_cart' => $config['restore_cart'],
                        'client_key' => $config['client_key'],
                        'onhold_status' => $config['onhold_status'],
                        'webhook_url' => $config['webhook_url'],
                        'webhook_testmode' => $config['webhook_testmode'],
                        'merchant_mailto' => $config['merchant_mailto'],
                        'use_authorize' => $config['use_authorize'],
                        'min_authorize_amount' => $config['min_authorize_amount'],
                        'test_mode' => $config['test_mode'],
                        'enforce_3d' => $config['enforce_3d'],
                        'completed_status' => $config['completed_status'],
                        'webhook_status' => $config['webhook_status'],
                        'inline_form' => $config['inline_form'],
                        'form_label_style' => $config['form_label_style'],
                        'form_input_style' => $config['form_input_style'],
                        'form_css' => $config['form_css'],
                        'logo_types' => $config['logo_types'],
                        'due_days' => $config['due_days'],
                        'allow_b2b' => $config['allow_b2b'],
                        'min_order_amount' => $config['min_order_amount'],
                    ],
                    $config['payum.http_client'],
                    $config['httplug.message_factory']
                );
            };

            $config['payum.paths'] = array_replace([
            'PayumNovalnet' => __DIR__.'/../Resources/views',
        ], $config['payum.paths'] ?: []);
        }
    }
}
