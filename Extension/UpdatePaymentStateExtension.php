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

namespace NovalnetBundle\Extension;

use CoreShop\Bundle\WorkflowBundle\Manager\StateMachineManager;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Payment\PaymentTransitions;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Request\Notify;
use Payum\Core\Request\GetHumanStatus;


final class UpdatePaymentStateExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(private StateMachineManager $stateMachineManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPreExecute(Context $context): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onExecute(Context $context): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostExecute(Context $context): void
    {
        if ($context->getException()) {
            return;
        }

        $previousStack = $context->getPrevious();
        /**
         * @var int
         *
         * @psalm-type int
         */
        $previousStackSize = count($previousStack);

        if ($previousStackSize > 1) {
            return;
        }

        /** @var Generic|bool $request */
        $request = $context->getRequest();
        if (false === $request instanceof Generic) {
            return;
        }

        /** @var PaymentInterface|bool $payment */
        $payment = $request->getFirstModel();
        if (false === $payment instanceof PaymentInterface) {
            return;
        }

        $context->getGateway()->execute($status = new GetHumanStatus($payment));
        $value = $status->getValue();
        $value = ($value == GetHumanStatus::STATUS_CANCELED) ? PaymentInterface::STATE_CANCELLED : $value;
        $value = ($value == GetHumanStatus::STATUS_PENDING) ? PaymentInterface::STATE_PROCESSING : $value;

        if ($payment->getState() !== $value && PaymentInterface::STATE_UNKNOWN !== $value) {
            $this->updatePaymentState($payment, $value);
        }
    }

    /**
     * Update payment state
     *
     * @param OrderInterface $order
     * @return void
     */
    private function updatePaymentState(PaymentInterface $payment, string $nextState): void
    {
        $workflow = $this->stateMachineManager->get($payment, PaymentTransitions::IDENTIFIER);
        $transition = $this->stateMachineManager->getTransitionToState($workflow, $payment, $nextState);
        if ($transition && $workflow->can($payment, $transition)) {
            $workflow->apply($payment, $transition);
        }
    }
}
