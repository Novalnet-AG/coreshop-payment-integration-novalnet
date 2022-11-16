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
namespace NovalnetBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use CoreShop\Bundle\ResourceBundle\AbstractResourceBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use CoreShop\Bundle\ResourceBundle\CoreShopResourceBundle;

class NovalnetBundle extends AbstractResourceBundle
{
    use PackageVersionTrait;

    public const NOVALNET_BUNDLE_VERSION = '1.0.0';

    /**
     * @inheritDoc
     */
    protected function getComposerPackageName(): string
    {
        return 'novalnet/pimcore-coreshop';
    }

    /**
     * @inheritDoc
     */
    public function getSupportedDrivers(): array
    {
        return [
            CoreShopResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getModelNamespace(): ?string
    {
        return 'NovalnetBundle\Model';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return self::NOVALNET_BUNDLE_VERSION;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Novalnet Payment bundle for coreshop';
    }
}
