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

namespace NovalnetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use Psr\Container\ContainerInterface;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Payum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use NovalnetBundle\Model\NovalnetTransactionInterface;
use NovalnetBundle\Model\NovalnetCallbackInterface;
use NovalnetBundle\Model\NovalnetTransaction;
use NovalnetBundle\Model\NovalnetCallback;
use CoreShop\Component\Payment\Model\PaymentInterface as CorePaymentInterface;
use Symfony\Component\Intl\Currencies;
use NovalnetBundle\Model\Constants;

class NovalnetCallbackController extends AbstractController
{
    /**
     * @var mixed $response
     */
    protected $response = null;

    /**
     * @var mixed $eventData
     */
    protected $eventData = [];

    /**
     * @var mixed $callbackTestMode
     */
    protected $callbackTestMode = null;

    /**
     * @var mixed $currentTime
     */
    protected $currentTime = null;

    /**
     * @var mixed $paymentAccessKey
     */
    protected $paymentAccessKey = null;

    /**
     * @var mixed $eventType
     */
    protected $eventType = null;

    /**
     * @var mixed $eventTid
     */
    protected $eventTid = null;

    /**
     * @var mixed $parentTid
     */
    protected $parentTid = null;

    /**
     * @var mixed $orderNo
     */
    protected $orderNo = null;

    /**
     * @var mixed $order
     */
    protected $order = null;

    /**
     * @var mixed $payment
     */
    protected $payment = null;

    /**
     * @var mixed $paymentMethodCode
     */
    protected $paymentMethodCode = null;

    /**
     * @var mixed $paymentTxnId
     */
    protected $paymentTxnId = null;

    /**
     * @var mixed $currency
     */
    protected $currency = null;

    /**
     * @var mixed $orderEntityId
     */
    protected $orderEntityId = null;

    /**
     * @var mixed $additionalMessage
     */
    protected $additionalMessage = null;

    /**
     * @var mixed $callbackMessage
     */
    protected $callbackMessage = null;

    /**
     * @var mixed $novalnetHelper
     */
    protected $novalnetHelper = null;

    /**
     * @var array
     */
    private $mandatoryParams = [
        'event' => [
            'type',
            'checksum',
            'tid'
        ],
        'merchant' => [
            'vendor',
            'project'
        ],
        'transaction' => [
            'tid',
            'payment_type',
            'status',
        ],
        'result' => [
            'status'
        ],
    ];

    /**
     * Callback handler
     *
     * @param Request $request
     * @return Response
     */
    public function callbackAction(Request $request): Response
    {
        if ($this->assignGlobalParams($request)) {
            if ($this->eventType == 'PAYMENT') {
                if (empty($this->paymentTxnId)) {
                    $this->handleCommunicationFailure();
                } else {
                    $this->displayMessage('Novalnet Callback executed. The Transaction ID already existed');
                }
            } elseif ($this->eventType == 'TRANSACTION_CAPTURE') {
                $this->transactionCapture();
            } elseif ($this->eventType == 'TRANSACTION_CANCEL') {
                $this->transactionCancellation();
            } elseif ($this->eventType == 'TRANSACTION_REFUND') {
                $this->transactionRefund();
            } elseif ($this->eventType == 'TRANSACTION_UPDATE') {
                $this->transactionUpdate();
            } elseif ($this->eventType == 'CREDIT') {
                $this->creditProcess();
            } elseif (in_array($this->eventType, ['CHARGEBACK', 'RETURN_DEBIT', 'REVERSAL'])) {
                $this->chargebackProcess();
            } elseif (in_array($this->eventType, ['PAYMENT_REMINDER_1', 'PAYMENT_REMINDER_2'])) {
                $this->paymentReminderProcess();
            } elseif ($this->eventType == 'SUBMISSION_TO_COLLECTION_AGENCY') {
                $this->collectionProcess();
            } else {
                $this->displayMessage("The webhook notification has been received for the unhandled EVENT type($this->eventType)");
            }
        }

        $message = $this->additionalMessage . $this->callbackMessage;
        return Response::create((string) $message);
    }

    /**
     * Handle communication failure
     *
     * @return void
     */
    private function handleCommunicationFailure()
    {
        $additionalData = $this->novalnetHelper->buildPaymentAdditionalData($this->eventData);
        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);

        $this->payment->setDetails($paymentDetails);
        $this->payment->setNumber($additionalData['NnTransactionTid']);

        $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
        $novalnetGateway->execute(new GetHumanStatus($this->payment));

        $message = $this->novalnetHelper->getOrderComments($additionalData, $this->order->getLocaleCode());
        $comment = !empty($this->order->getComment()) ? $this->order->getComment() . '<br>' . $message : $message;
        $this->order->setComment($comment);
        $this->order->save();
        $this->savePaymentResponse($additionalData);
        $this->displayMessage('Novalnet Callback Script executed successfully on ' . $this->currentTime);
        $this->sendEmail('Novalnet Callback Script executed successfully on ' . $this->currentTime);
    }

    /**
     * Transaction capture process
     *
     * @return void
     */
    private function transactionCapture()
    {
        $transactionStatusModel = $this->getTransactionModel();
        $paymentState = $this->payment->getState();
        if ($paymentState == CorePaymentInterface::STATE_AUTHORIZED) {
            $paymentDetails = $this->payment->getDetails();
            $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];

            $additionalData['NnTransactionStatus'] = !empty($this->response->transaction->status) ? $this->response->transaction->status : $additionalData['NnTransactionStatus'];
            $additionalData['NnWebhookCapture'] = 1;
            $additionalData['ApiProcess'] = 'capture';
            $additionalData['CaptureApiProcessedAt'] = $this->currentTime;

            $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
            $this->payment->setDetails($paymentDetails);

            $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
            $novalnetGateway->execute(new GetHumanStatus($this->payment));

            $paymentDetails = $this->payment->getDetails();
            $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
            unset($additionalData['NnWebhookCapture']);
            $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
            $this->payment->setDetails($paymentDetails);

            if ($transactionStatusModel) {
                $transactionStatusModel->setStatus($additionalData['NnTransactionStatus']);
                $this->persistModel($transactionStatusModel);
            }

            $message = sprintf($this->novalnetHelper->translate('novalnet.transaction.capture.label', $this->order->getLocaleCode()), $this->currentTime);
            $comment = $this->order->getComment() . '<br><br>' . $message;
            $this->order->setComment($comment);
            $this->order->save();
            $this->displayMessage($message);
            $this->sendEmail($message);
        } else {
            $this->displayMessage('Order already captured.');
        }
    }

    /**
     * Transaction cancel process
     *
     * @return void
     */
    private function transactionCancellation()
    {
        $transactionStatusModel = $this->getTransactionModel();
        $paymentState = $this->payment->getState();
        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];

        $additionalData['NnTransactionStatus'] = !empty($this->response->transaction->status) ? $this->response->transaction->status : $additionalData['NnTransactionStatus'];
        $additionalData['NnWebhookVoid'] = 1;
        $additionalData['ApiProcess'] = 'void';
        $additionalData['VoidApiProcessedAt'] = $this->currentTime;

        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);

        $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
        $novalnetGateway->execute(new GetHumanStatus($this->payment));

        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
        unset($additionalData['NnWebhookVoid']);
        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);

        if ($transactionStatusModel) {
            $transactionStatusModel->setStatus($additionalData['NnTransactionStatus']);
            $this->persistModel($transactionStatusModel);
        }

        $message = sprintf($this->novalnetHelper->translate('novalnet.transaction.void.label', $this->order->getLocaleCode()), $this->currentTime);
        $comment = $this->order->getComment() . '<br><br>' . $message;
        $this->order->setComment($comment);
        $this->order->save();
        $this->displayMessage($message);
        $this->sendEmail($message);
    }

    /**
     * Transaction refund process
     *
     * @return void
     */
    private function transactionRefund()
    {
        $transactionStatusModel = $this->getTransactionModel();
        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
        $locale = $this->order->getLocaleCode();

        $additionalData['NnTransactionStatus'] = !empty($this->response->transaction->status) ? $this->response->transaction->status : $additionalData['NnTransactionStatus'];
        $noOfRefunds = empty($additionalData['NnRefundsOccured']) ? 1 : $additionalData['NnRefundsOccured'] + 1;
        $additionalData['NnRefundsOccured'] = $noOfRefunds;
        $additionalData['NnWebhookRefund'] = 1;
        $refundAmount = $this->novalnetHelper->getFormattedAmount($this->response->transaction->refund->amount, 'RAW');
        $additionalData['NnRefundedAmount'] = !empty($additionalData['NnRefundedAmount']) ? $additionalData['NnRefundedAmount'] + $this->response->transaction->refund->amount : $this->response->transaction->refund->amount;
        $refundTid = !empty($this->response->transaction->refund->tid) ? $this->response->transaction->refund->tid : $additionalData['NnTransactionTid'] . '-refund' . $noOfRefunds;

        $additionalData['NnRefunded'][$refundTid] = [
            'refundTid' => $refundTid,
            'refundAmount' => $refundAmount,
            'parentTid' => $additionalData['NnTransactionTid']
        ];

        $message = sprintf($this->novalnetHelper->translate('novalnet.transaction.refund.label', $locale), $additionalData['NnTransactionTid'], $refundAmount, $this->currency);

        if (!empty($this->response->transaction->refund->tid)) {
            $message = sprintf($this->novalnetHelper->translate('novalnet.transaction.refund.newtid.label', $locale), $additionalData['NnTransactionTid'], $refundAmount, $this->currency, $this->response->transaction->refund->tid);
        }

        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);
        $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
        $novalnetGateway->execute(new GetHumanStatus($this->payment));

        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
        unset($additionalData['NnWebhookRefund']);
        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);

        if ($transactionStatusModel) {
            $transactionStatusModel->setStatus($additionalData['NnTransactionStatus']);
            $this->persistModel($transactionStatusModel);
        }

        $comment = $this->order->getComment() . '<br><br>' . $message;
        $this->order->setComment($comment);
        $this->order->save();
        $this->displayMessage($message);
        $this->sendEmail($message);
    }

    /**
     * Transaction update process
     *
     * @return void
     */
    private function transactionUpdate()
    {
        $transactionStatusModel = $this->getTransactionModel();
        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
        $paymentState = $this->payment->getState();
        $transactionStatus = !empty($this->response->transaction->status) ? $this->response->transaction->status : '';
        $invoiceDuedate = !empty($this->response->transaction->due_date) ? $this->response->transaction->due_date : '';
        $updateType = $this->response->transaction->update_type;
        $transactionAmount = isset($this->response->instalment->cycle_amount) ? $this->response->instalment->cycle_amount : $this->response->transaction->amount;
        $updatedAmount = $this->novalnetHelper->getFormattedAmount($transactionAmount, 'RAW');
        $message = sprintf('Novalnet callback received for the unhandled transaction type(%s) for %s EVENT', $this->response->transaction->payment_type, $this->eventType);

        if ($paymentState == CorePaymentInterface::STATE_PROCESSING && $updateType == 'STATUS') {
            if ($transactionStatus == 'ON_HOLD') {
                $message = sprintf(
                    $this->novalnetHelper->translate('novalnet.pending.onhold.update.label', $this->order->getLocaleCode()),
                    $this->parentTid,
                    $this->currentTime
                );

            } elseif ($transactionStatus == 'CONFIRMED') {
                $message = sprintf(
                    $this->novalnetHelper->translate('novalnet.transaction.update.label', $this->order->getLocaleCode()),
                    $this->eventTid,
                    $updatedAmount .' ' . $this->currency,
                    $this->currentTime
                );

            }

            $additionalData['NnTransactionStatus'] = $transactionStatus;
            $additionalData['NnTransactionAmount'] = (int) $transactionAmount;
        } elseif (($invoiceDuedate && $this->response->result->status == 'SUCCESS')) {

            $message = sprintf(
                $this->novalnetHelper->translate('novalnet.update.with.duedate', $this->order->getLocaleCode()),
                $updatedAmount .' ' . $this->currency,
                $invoiceDuedate
            );

            if ($this->paymentMethodCode == Constants::NOVALNET_CASHPAYMENT) {
                $additionalData['NnTransactionDueDate'] = $formatDate;
                $message = sprintf(
                    $this->novalnetHelper->translate('novalnet.update.with.slipexpiry', $this->order->getLocaleCode()),
                    $updatedAmount .' ' . $this->currency,
                    $invoiceDuedate
                );
            }

            $additionalData['NndueDateUpdateAt'] = $this->currentTime;
            $additionalData['NnTransactionDueDate'] = $invoiceDuedate;
            $additionalData['NnTransactionStatus'] = $transactionStatus;
            $additionalData['NnTransactionAmount'] = (int) $transactionAmount;
        } elseif (
            $paymentState == CorePaymentInterface::STATE_AUTHORIZED &&
            $transactionStatus == 'ON_HOLD' &&
            in_array($this->paymentMethodCode, [Constants::NOVALNET_SEPA, Constants::NOVALNET_PREPAYMENT, Constants::NOVALNET_SEPA_GUARANTEE, Constants::NOVALNET_INVOICE_GUARANTEE])
        ) {
            $message = sprintf(
                $this->novalnetHelper->translate('novalnet.transaction.update.label', $this->order->getLocaleCode()),
                $this->eventTid,
                $updatedAmount .' ' . $this->currency,
                $this->currentTime
            );

            $additionalData['NnTransactionStatus'] = $transactionStatus;
            $additionalData['NnTransactionAmount'] = (int) $transactionAmount;
        }

        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
                $additionalData['NnComments'] . '<br><br>' . $message;
        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);

        $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
        $novalnetGateway->execute(new GetHumanStatus($this->payment));

        if ($transactionStatusModel) {
            $transactionStatusModel->setStatus($transactionStatus);
            $this->persistModel($transactionStatusModel);
        }

        $comment = $this->order->getComment() . '<br><br>' . $message;
        $this->order->setComment($comment);
        $this->order->save();
        $this->displayMessage($message);
        $this->sendEmail($message);
    }

    /**
     * Handle credit process
     *
     * @return void
     */
    private function creditProcess()
    {
        $transactionStatusModel = $this->getTransactionModel();
        $transactionPaymentType = $this->response->transaction->payment_type;
        $message = "Novalnet callback received for the unhandled transaction type($transactionPaymentType) for $this->eventType EVENT";
        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
        $amount = $this->novalnetHelper->getFormattedAmount(
            $this->response->transaction->amount,
            'RAW'
        );

        if (in_array(
            $transactionPaymentType,
            ['INVOICE_CREDIT', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT', 'MULTIBANCO_CREDIT']
        )) {
            $updatedAmountByCallback = (int) $additionalData['NnTransactionAmount'];
            $refundedAmount = !empty($additionalData['NnRefundedAmount']) ? (int) $additionalData['NnRefundedAmount'] : 0;
            $updatedAmountToPaid = (int) $updatedAmountByCallback - (int) $refundedAmount;

            $callbackInfo = $this->getCallbackInfo();
            $previousPaidAmount = !empty($callbackInfo && $callbackInfo->getCallbackAmount()) ? (int) $callbackInfo->getCallbackAmount() : 0;
            $currentTotalPaid = (int) $this->response->transaction->amount + (int) $previousPaidAmount;
            $orderGrandTotal = (int) $this->order->getTotal(true);

            if ($callbackInfo == null) {
                $this->logCallbackInfo($currentTotalPaid);
            } else {
                $callbackInfo->setCallbackAmount($currentTotalPaid);
                $this->persistModel($callbackInfo);
            }

            $message = sprintf(
                $this->novalnetHelper->translate('novalnet.creditprocess.label', $this->order->getLocaleCode()),
                $this->parentTid,
                $amount . ' ' . $this->currency,
                $this->currentTime,
                $this->eventTid
            );

            $additionalData['NnTransactionStatus'] = $this->response->transaction->status;

            if ($transactionStatusModel) {
                $transactionStatusModel->setStatus($this->response->transaction->status);
                $this->persistModel($callbackInfo);
            }

            if ($currentTotalPaid >= $updatedAmountToPaid) {
                $additionalData['NnPaid'] = 1;
                $additionalData['NnTransactionStatus'] = $this->response->transaction->status;
            } elseif (($currentTotalPaid < $orderGrandTotal) ||
                $transactionPaymentType == 'ONLINE_TRANSFER_CREDIT'
            ) {
                if ($transactionPaymentType == 'ONLINE_TRANSFER_CREDIT' && ($currentTotalPaid > $orderGrandTotal)) {
                    $message = $message  . '<br>' . __(
                        $this->novalnetHelper->translate('novalnet.creditprocess.label', $this->order->getLocaleCode()),
                        $this->parentTid,
                        $amount . ' ' . $this->currency,
                        $this->currentTime,
                        $this->eventTid
                    );
                }
            }
        }  else {
            $message = sprintf(
                $this->novalnetHelper->translate('novalnet.creditprocess.label', $this->order->getLocaleCode()),
                $this->parentTid,
                $amount . ' ' . $this->currency,
                $this->currentTime,
                $this->eventTid
            );
        }

        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
                $additionalData['NnComments'] . '<br><br>' . $message;
        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);

        $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
        $novalnetGateway->execute(new GetHumanStatus($this->payment));

        $comment = $this->order->getComment() . '<br><br>' . $message;
        $this->order->setComment($comment);
        $this->order->save();
        $this->displayMessage($message);
        $this->sendEmail($message);
    }

    /**
     * Handle chargeback process
     *
     * @return void
     */
    private function chargebackProcess()
    {
        $message = sprintf(
            $this->novalnetHelper->translate('novalnet.chargeback.label', $this->order->getLocaleCode()),
            $this->parentTid,
            $this->novalnetHelper->getFormattedAmount($this->response->transaction->amount, 'RAW') . ' ' . $this->currency,
            $this->currentTime,
            $this->response->transaction->tid
        );

        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message : $additionalData['NnComments'] . '<br><br>' . $message;
        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);

        $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
        $novalnetGateway->execute(new GetHumanStatus($this->payment));

        $comment = $this->order->getComment() . '<br><br>' . $message;
        $this->order->setComment($comment);
        $this->order->save();
        $this->displayMessage($message);
        $this->sendEmail($message);
    }

    /**
     * Handle payment reminder process
     *
     * @return void
     */
    private function paymentReminderProcess()
    {
        $reminderCount = explode('_', $this->eventType);
        $reminderCount = end($reminderCount);
        $message = sprintf(
                $this->novalnetHelper->translate('novalnet.paymentreminder.label', $this->order->getLocaleCode()),
                $reminderCount
            );

        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
                $additionalData['NnComments'] . '<br><br>' . $message;
        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);

        $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
        $novalnetGateway->execute(new GetHumanStatus($this->payment));

        $comment = $this->order->getComment() . '<br><br>' . $message;
        $this->order->setComment($comment);
        $this->order->save();
        $this->displayMessage($message);
        $this->sendEmail($message);
    }

    /**
     * Handle collection process
     *
     * @return void
     */
    private function collectionProcess()
    {
        $message = sprintf(
                $this->novalnetHelper->translate('novalnet.collection.label', $this->order->getLocaleCode()),
                $this->response->collection->reference
            );

        $paymentDetails = $this->payment->getDetails();
        $additionalData = !empty($paymentDetails['NovalnetAdditionalData']) ? json_decode($paymentDetails['NovalnetAdditionalData'], true) : [];
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
                $additionalData['NnComments'] . '<br><br>' . $message;
        $paymentDetails['NovalnetAdditionalData'] = json_encode($additionalData);
        $this->payment->setDetails($paymentDetails);

        $novalnetGateway = $this->getPayum()->getGateway($this->paymentMethodCode);
        $novalnetGateway->execute(new GetHumanStatus($this->payment));

        $comment = $this->order->getComment() . '<br><br>' . $message;
        $this->order->setComment($comment);
        $this->order->save();
        $this->displayMessage($message);
        $this->sendEmail($message);
    }

    /**
     * Assign global params for callback process
     *
     * @param Request $request
     * @return bool
     */
    private function assignGlobalParams(Request $request)
    {
        $this->novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
        if ($jsonContent = $request->getContent()) {
            $this->eventData = json_decode($jsonContent, true);
        }

        $this->response = $this->convertArrayToObject($this->eventData);
        $this->callbackTestMode = $this->novalnetHelper->getGlobalConfigurations('webhook_testmode');
        $this->currentTime = date('d-m-Y H:i:s');
        $this->paymentAccessKey = $this->novalnetHelper->getGlobalConfigurations('payment_access_key');

        if (!$this->checkIP()) {
            return false;
        }

        if (empty((array) $this->response)) {
            $this->displayMessage('No params passed over!');
            return false;
        }

        if (!$this->validateEventData()) {
            return false;
        }

        $this->eventType = $this->response->event->type;
        $this->eventTid  = $this->response->event->tid;
        $this->parentTid = !empty($this->response->event->parent_tid) ? $this->response->event->parent_tid : $this->eventTid;
        $this->orderNo = $this->response->transaction->order_no;
        $this->order = $this->getOrder($this->orderNo);
        if ($this->order == false || $this->order == null) {
            $this->displayMessage('Required (Transaction ID) not Found!');
            return false;
        }

        $this->payment = $this->getPaymentForOrder();
        if ($this->payment == false || $this->payment == null) {
            $this->displayMessage('Required (Transaction ID) not Found!');
            return false;
        }
        $this->paymentMethodCode = $this->payment->getPaymentProvider()->getGatewayConfig()->getGatewayName();
        $this->paymentTxnId = $this->payment->getNumber();
        $this->currency = Currencies::getSymbol($this->order->getCurrency()->getIsoCode());

        if (!$this->validateTrasactionIds()) {
            return false;
        }

        return true;
    }

    /**
     * Loads order object
     *
     * @return mixed
     */
    private function getOrder($orderNo)
    {
        $entityManager = \Pimcore::getContainer()->get('doctrine')->getManager();
        $sql = "SELECT * FROM `object_cs_order` WHERE orderNumber='" . $this->orderNo . "'";
        $query = $entityManager->getConnection()->prepare($sql);
        $query->execute();
        $result = $query->fetchAll();
        $order = null;
        if (is_array($result) && count($result) && !empty($result[0]['oo_id'])) {
            $orderEntityId = $result[0]['oo_id'];
            $this->orderEntityId = $orderEntityId;
            $order = $this->getOrderRepository()->find($orderEntityId);
        }

        if (empty($order)) {
            $result = $this->getPaymentRepository()->findByNumber($this->parentTid);
            if (is_array($result) && count($result) && !empty($result[0]->getOrder())) {
                $order = $result[0]->getOrder();
                $this->orderEntityId = $order->getId();
            }
        }

        return $order;
    }

    /**
     * Loads payment object
     *
     * @return mixed
     */
    private function getPaymentForOrder()
    {
        $payment = null;
        $paymentData = $this->getPaymentRepository()->findByOrderId($this->orderEntityId);
        if (is_array($paymentData) && count($paymentData) && !empty($paymentData[0])) {
            $payment = $paymentData[0];
        }

        if (empty($payment)) {
            $result = $this->getPaymentRepository()->findByNumber($this->parentTid);
            if (is_array($result) && count($result) && !empty($result[0])) {
                $payment = $result[0];
            }
        }

        return $payment;
    }

    /**
     * Authorise the request IP address
     *
     * @return bool
     */
    private function checkIP()
    {
        $requestReceivedIp = $this->novalnetHelper->getRequestIp();
        $novalnetHostIp = gethostbyname('pay-nn.de');

        if (!empty($novalnetHostIp) && !empty($requestReceivedIp)) {
            if ($novalnetHostIp !== $requestReceivedIp && !$this->callbackTestMode) {
                $this->displayMessage(
                    sprintf('Unauthorised access from the IP [ %s ]', $requestReceivedIp)
                );

                return false;
            }
        } else {
            $this->displayMessage('Unauthorised access from the IP');
            return false;
        }

        return true;
    }

    /**
     * validate mandatory parameters
     *
     * @return bool
     */
    private function validateEventData()
    {
        foreach ($this->mandatoryParams as $category => $parameters) {
            if (empty($this->response->{$category})) {
                $this->displayMessage('Required parameter category(' . $category . ') not received');
                return false;
            } else {
                foreach ($parameters as $parameter) {
                    if (empty($this->response->{$category}->{$parameter})) {
                        $this->displayMessage(
                            'Required parameter(' . $parameter . ') in the category(' . $category . ') not received'
                        );

                        return false;
                    }
                }
            }
        }

        if (!$this->validateChecksum()) {
            return false;
        }

        return true;
    }

    /**
     * validate transaction ids
     *
     * @return bool
     */
    private function validateTrasactionIds()
    {
        if (!empty($this->parentTid) && !preg_match('/^\d{17}$/', (string) $this->parentTid)
        ) {
            $this->displayMessage(
                sprintf('Invalid TID[%s] for Order :%s', $this->parentTid, $this->response->transaction->order_no)
            );

            return false;
        } elseif (!empty($this->eventTid) && !preg_match('/^\d{17}$/', (string) $this->eventTid)) {
            $this->displayMessage(
                sprintf('Invalid TID[%s] for Order :%s', $this->eventTid, $this->response->transaction->order_no)
            );

            return false;
        }

        return true;
    }

    /**
     * Validate checksum in response
     *
     * @return bool
     */
    private function validateChecksum()
    {
        $checksumString  = $this->response->event->tid . $this->response->event->type . $this->response->result->status;

        if (isset($this->response->transaction->amount)) {
            $checksumString .= $this->response->transaction->amount;
        }

        if (!empty($this->response->transaction->currency)) {
            $checksumString .= $this->response->transaction->currency;
        }

        $accessKey = trim($this->paymentAccessKey);
        if (!empty($accessKey)) {
            $checksumString .= strrev($accessKey);
        }

        $generatedChecksum = hash('sha256', $checksumString);
        if ($generatedChecksum !== $this->response->event->checksum) {
            $this->displayMessage('While notifying some data has been changed. The hash check failed');

            return false;
        }

        return true;
    }

    /**
     * Save Novalnet transaction
     *
     * @return void
     */
    private function savePaymentResponse($additionalData)
    {
        $container = \Pimcore::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $novalnetTransaction = $container->get(NovalnetTransaction::class);
        $novalnetTransaction->setOrderId($this->order->getOrderNumber());
        $novalnetTransaction->setTid($additionalData['NnTransactionTid']);
        $novalnetTransaction->setStatus($additionalData['NnTransactionStatus']);
        $novalnetTransaction->setCustomerId($this->order->getCustomer()->getId());
        $novalnetTransaction->setPaymentMethod($additionalData['NnTransactionPaymentType']);
        $novalnetTransaction->setToken(null);
        $novalnetTransaction->setTokenInfo(null);

        $this->persistModel($novalnetTransaction);
    }

    /**
     * Log callback info in table
     *
     * @return void
     */
    private function logCallbackInfo($callbackAmount = null)
    {
        $container = \Pimcore::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $novalnetCallback = $container->get(NovalnetCallback::class);
        $novalnetCallback->setOrderId($this->orderNo);
        $novalnetCallback->setCallbackAmount($callbackAmount);
        $novalnetCallback->setReferenceTid($this->eventTid);
        $novalnetCallback->setCallbackTid($this->parentTid);
        $this->persistModel($novalnetCallback);
    }

    /**
     * To get transaction model
     *
     * @return object|null
     */
    private function getTransactionModel()
    {
        $container = \Pimcore::getContainer();
        $transactionModel = $container->get('doctrine')->getRepository(NovalnetTransactionInterface::class)->findByOrderId($this->orderNo);

        if (!empty($transactionModel[0])) {
            return $transactionModel[0];
        }

        return null;
    }

    /**
     * To get callback model
     *
     * @return void
     */
    private function getCallbackInfo()
    {
        $container = \Pimcore::getContainer();
        $callbackModel = $container->get('doctrine')->getRepository(NovalnetCallbackInterface::class)->findByOrderId($this->orderNo);

        if (!empty($callbackModel[0])) {
            return $callbackModel[0];
        }

        return null;
    }

    /**
     * Save model and flush database
     *
     * @param mixed $model
     * @return void
     */
    private function persistModel($model)
    {
        if ($model) {
            $container = \Pimcore::getContainer();
            $entityManager = $container->get('doctrine')->getManager();
            $entityManager->persist($model);
            $entityManager->flush();
        }
    }

    /**
     * callback process transaction comments
     *
     * @param string $text
     * @param bool $exit
     * @return void
     */
    private function displayMessage($text, $exit = true)
    {
        if ($exit === false) {
            $this->additionalMessage = $text;
        } else {
            $this->callbackMessage = $text;
        }
    }

    /**
     * convert array to object
     *
     * @param array $data
     * @return mixed
     */
    private function convertArrayToObject($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = (object) $this->convertArrayToObject($value);
            }
        }

        return (object) $data;
    }

    /**
     * To get order repository
     *
     * @return OrderRepositoryInterface
     */
    private function getOrderRepository(): OrderRepositoryInterface
    {
        return $this->get('coreshop.repository.order');
    }

    /**
     * To get order repository
     *
     * @return PaymentRepositoryInterface
     */
    private function getPaymentRepository(): PaymentRepositoryInterface
    {
        return $this->get('coreshop.repository.payment');
    }

    /**
     * To get payum
     *
     * @return Payum
     */
    private function getPayum(): Payum
    {
        return $this->get('payum');
    }

    /**
     * To sent callback merchant mail
     *
     * @param string $message
     * @return void
     */
    private function sendEmail($message)
    {
        $merchatMailTo = $this->novalnetHelper->getGlobalConfigurations('merchant_mailto');
        if (!empty($merchatMailTo)) {
            $mail = new \Pimcore\Mail();
            $mail->to($merchatMailTo);
            $mail->setIgnoreDebugMode(true);
            $mail->setSubject('Novalnet notification / webhook Access Report - order no: ' . $this->order->getOrderNumber());
            $mail->text($message);
            $mail->send();
        }
    }
}
