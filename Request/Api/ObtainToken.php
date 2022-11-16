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

class ObtainToken extends Generic
{
    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var mixed
     */
    protected $payment;

    /**
     * @var string|null
     */
    protected $errorMessage;

    /**
     * @param mixed $token
     * @param OrderInterface $order
     * @param mixed $payment
     * @param string|null $errorMessage
     */
    public function __construct($token, $order, $payment, $errorMessage = null)
    {
        parent::__construct($token);
        $this->order = $order;
        $this->payment = $payment;
        $this->errorMessage = $errorMessage;
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

    /**
     * To get error message
     *
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
