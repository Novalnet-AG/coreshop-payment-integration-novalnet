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

interface NovalnetTransactionInterface extends ResourceInterface
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
     * @param mixed $tid
     */
    public function setTid($tid);

    /**
     * @return mixed
     */
    public function getTid();

    /**
     * @param string $status
     */
    public function setStatus($status);

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @param mixed $customerId
     */
    public function setCustomerId($customerId);

    /**
     * @return mixed
     */
    public function getCustomerId();

    /**
     * @param string $paymentMethod
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @param string $token
     */
    public function setToken($token);

    /**
     * @return string
     */
    public function getToken();

    /**
     * @param string $tokenInfo
     */
    public function setTokenInfo($tokenInfo);

    /**
     * @return string
     */
    public function getTokenInfo();
}
