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

use CoreShop\Component\Resource\Model\ResourceInterface;
use CoreShop\Component\Resource\Model\TimestampableInterface;

interface NovalnetCallbackInterface extends ResourceInterface, TimestampableInterface
{
    /**
     * @param int $id
     */
    public function getId();

    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId);

    /**
     * @return mixed
     */
    public function getOrderId();

    /**
     * @param int $callbackAmount
     */
    public function setCallbackAmount($callbackAmount);

    /**
     * @return int
     */
    public function getCallbackAmount();

    /**
     * @param mixed $referenceTid
     */
    public function setReferenceTid($referenceTid);

    /**
     * @return mixed
     */
    public function getReferenceTid();

    /**
     * @param string $callbackDatetime
     */
    public function setCallbackDatetime($callbackDatetime);

    /**
     * @return string
     */
    public function getCallbackDatetime();

    /**
     * @param mixed $callbackTid
     */
    public function setCallbackTid($callbackTid);

    /**
     * @return mixed
     */
    public function getCallbackTid();
}
