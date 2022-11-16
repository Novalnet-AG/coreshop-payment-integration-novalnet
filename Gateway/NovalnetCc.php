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
use Payum\Core\Bridge\Spl\ArrayObject;
use NovalnetBundle\Model\Constants;

class NovalnetCc extends NovalnetGatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults(
            [
                'payum.factory_name' => Constants::NOVALNET_CC,
                'payum.factory_title' => Constants::NOVALNET_CC,
                'payum.template.obtain_token' => '@PayumNovalnet/Action/novalnet_cc.html.twig',
                'payum.action.api.obtain_token' => function (ArrayObject $config) {
                    return new ObtainTokenAction($config['payum.template.obtain_token']);
                }
            ]
        );

        parent::populateConfig($config);
    }
}
