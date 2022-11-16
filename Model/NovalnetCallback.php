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
use CoreShop\Component\Resource\Model\TimestampableTrait;

class NovalnetCallback implements NovalnetCallbackInterface
{
    use SetValuesTrait;
    use TimestampableTrait;

    /**
     * @param int
     */
    protected $id;

    /**
     * @param mixed
     */
    protected $orderId;

    /**
     * @param int
     */
    protected $callbackAmount;

    /**
     * @param mixed
     */
    protected $referenceTid;

    /**
     * @param string
     */
    protected $callbackDatetime;

    /**
     * @param mixed
     */
    protected $callbackTid;

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
     * @param int $callbackAmount
     */
    public function setCallbackAmount($callbackAmount)
    {
        $this->callbackAmount = $callbackAmount;
    }

    /**
     * @return int
     */
    public function getCallbackAmount()
    {
        return $this->callbackAmount;
    }

    /**
     * @param mixed $referenceTid
     */
    public function setReferenceTid($referenceTid)
    {
        $this->referenceTid = $referenceTid;
    }

    /**
     * @return mixed
     */
    public function getReferenceTid()
    {
        return $this->referenceTid;
    }

    /**
     * @param string $callbackDatetime
     */
    public function setCallbackDatetime($callbackDatetime)
    {
        $this->callbackDatetime = $callbackDatetime;
    }

    /**
     * @return string
     */
    public function getCallbackDatetime()
    {
        return $this->callbackDatetime;
    }

    /**
     * @param mixed $callbackTid
     */
    public function setCallbackTid($callbackTid)
    {
        $this->callbackTid = $callbackTid;
    }

    /**
     * @return mixed
     */
    public function getCallbackTid()
    {
        return $this->callbackTid;
    }
}
