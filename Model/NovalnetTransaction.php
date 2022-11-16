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

use CoreShop\Component\Resource\Model\SetValuesTrait;

class NovalnetTransaction implements NovalnetTransactionInterface
{
    use SetValuesTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $orderId;

    /**
     * @var mixed
     */
    protected $tid;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var mixed
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var mixed
     */
    protected $tokenInfo;

    /**
     * @param int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param mixed $tid
     */
    public function setTid($tid)
    {
        $this->tid = $tid;
    }

    /**
     * @return mixed
     */
    public function getTid()
    {
        return $this->tid;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param string $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $tokenInfo
     */
    public function setTokenInfo($tokenInfo)
    {
        $this->tokenInfo = $tokenInfo;
    }

    /**
     * @return string
     */
    public function getTokenInfo()
    {
        return $this->tokenInfo;
    }
}
