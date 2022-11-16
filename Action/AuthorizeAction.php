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

namespace NovalnetBundle\Action;

use NovalnetBundle\Model\Api;
use NovalnetBundle\Helper\NovalnetHelper;
use NovalnetBundle\Request\Api\ObtainToken;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Authorize;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Request\Sync;
use Payum\Core\Request\GetHttpRequest;
use NovalnetBundle\Request\Api\GetTransactionDetails;
use Payum\Core\Model\PaymentInterface;
use CoreShop\Component\Core\Model\OrderInterface;

class AuthorizeAction extends PurchaseAction
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * AuthorizeAction constructor.
     */
    public function __construct()
    {
        $this->apiClass = Api::class;
        $this->novalnetHelper = \Pimcore::getContainer()->get(NovalnetHelper::class);
    }
    /**
     * @param Authorize $request
     *
     * @throws \Exception
     */
    public function execute($request)
    {
        parent::execute($request);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Authorize &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
