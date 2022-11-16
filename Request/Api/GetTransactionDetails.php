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

namespace NovalnetBundle\Request\Api;

use Payum\Core\Request\Generic;
use CoreShop\Component\Core\Model\OrderInterface;
use Payum\Core\Model\PaymentInterface;

class GetTransactionDetails extends Generic
{
    /**
     * @var mixed
     */
    protected $order;

    /**
     * @var mixed
     */
    protected $payment;

    /**
     * @param mixed $model
     * @param OrderInterface $order
     * @param mixed $payment
     */
    public function __construct($model, $order, $payment)
    {
        parent::__construct($model);
        $this->order = $order;
        $this->payment = $payment;
    }

    /**
     * To get order object
     *
     * @return mixed
     */
    public function getOrderObject()
    {
        return $this->order;
    }

    /**
     * To get payment object
     *
     * @return mixed
     */
    public function getPaymentObject()
    {
        return $this->payment;
    }
}
