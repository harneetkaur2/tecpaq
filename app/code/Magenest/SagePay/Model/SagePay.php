<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 02/08/2016
 * Time: 22:41
 */

namespace Magenest\SagePay\Model;

use Magenest\SagePay\Helper\SageHelper;
use Magenest\SagePay\Helper\Logger;
use Magenest\SagePay\Helper\Subscription as SubsHelper;

class SagePay extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'magenest_sagepay';

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canCaptureOnce = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canOrder = false;
    protected $_isInitializeNeeded = true;

    protected $sageHelper;
    protected $subsHelper;
    protected $_transFactory;
    protected $_profileFactory;
    protected $_cardFactory;
    protected $_curlFactory;
    protected $_customerSession;
    protected $sageLogger;
    protected $sageHelperMoto;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        TransactionFactory $transactionFactory,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magenest\SagePay\Helper\Subscription $subscriptionHelper,
        \Magenest\SagePay\Model\ProfileFactory $profileFactory,
        \Magenest\SagePay\Model\CardFactory $cardFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Customer\Model\Session $session,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magenest\SagePay\Helper\SageHelperMoto $sageHelperMoto,
        array $data = []
    ) {
        $this->_customerSession = $session;
        $this->_curlFactory = $curlFactory;
        $this->_cardFactory = $cardFactory;
        $this->_transFactory = $transactionFactory;
        $this->_profileFactory = $profileFactory;
        $this->subsHelper = $subscriptionHelper;
        $this->sageHelper = $sageHelper;
        $this->sageHelperMoto = $sageHelperMoto;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
        $this->sageLogger = $sageLogger;
    }

    protected $isSave = false;
    protected $reusable = false;
    protected $cardId;

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $this->sageLogger->debug("-----------------begin payment----------------");
        $infoInstance = $this->getInfoInstance();

        $additionalData = $data->getData('additional_data');
        if (array_key_exists('is_sage_subscription_payment', $additionalData)
            && $additionalData['is_sage_subscription_payment'] === true
        ) {
            $infoInstance->setAdditionalInformation('is_sage_subscription_payment', true);

            return $this;
        } else {
            $infoInstance->setAdditionalInformation('is_sage_subscription_payment', false);
        }
        //if internal order
        if ($this->_appState->getAreaCode() == 'adminhtml') {
            $infoInstance->setAdditionalInformation('saved_id', "0");
            $infoInstance->setAdditionalInformation('is_save', "0");
            $infoInstance->setAdditionalInformation('gift_aid', "0");
            $merchantSessionKey = $this->sageHelperMoto->generateMerchantKey();
            $cardNum = $additionalData['cc_number'];
            $cardCcv = $additionalData['cc_cid'];
            $cardExpMonth = $additionalData['cc_exp_month'];
            $cardExpYear = $additionalData['cc_exp_year'];
            $dateTime = \DateTime::createFromFormat('m Y', $cardExpMonth." ".$cardExpYear);
            $cardExp = $dateTime->format('my');
            $cardName = $additionalData['cc_name'];
            $cardIdentifierData = $this->sageHelperMoto->getCardIdentifier(
                $merchantSessionKey,
                $cardName,
                $cardNum,
                $cardExp,
                $cardCcv
            );
            $cardIdentifier = $cardIdentifierData->cardIdentifier;
            $cardType = $cardIdentifierData->cardType;
            $infoInstance->setAdditionalInformation('card_identifier', $cardIdentifier);
            $infoInstance->setAdditionalInformation('merchant_sessionKey', $merchantSessionKey);
            return $this;
        } else {
            $_tmpData = $data->_data;
            $_serializedAdditionalData = serialize($_tmpData['additional_data']);
            $additionalDataRef = $_serializedAdditionalData;
            $additionalDataRef = unserialize($additionalDataRef);
            $saveId = isset($additionalDataRef['selected_card'])?$additionalDataRef['selected_card']:"0";
            $cardId = isset($additionalDataRef['card_identifier'])?$additionalDataRef['card_identifier']:"0";
            $merchantSessionKey = isset($additionalDataRef['merchant_sessionKey'])?$additionalDataRef['merchant_sessionKey']:"0";
            $isSave = isset($additionalDataRef['save'])?$additionalDataRef['save']:"0";
            $giftAid = isset($additionalDataRef['gift_aid'])?$additionalDataRef['gift_aid']:"0";
            $infoInstance->setAdditionalInformation('saved_id', $saveId);
            $infoInstance->setAdditionalInformation('card_identifier', $cardId);
            $infoInstance->setAdditionalInformation('merchant_sessionKey', $merchantSessionKey);
            $infoInstance->setAdditionalInformation('is_save', $isSave);
            $infoInstance->setAdditionalInformation('gift_aid', $giftAid);
        }
        return $this;
    }

    public function initialize($paymentAction, $stateObject)
    {
        try {
            /**
             * @var \Magento\Sales\Model\Order $order
             * @var \Magento\Sales\Model\Order\Payment $payment
             */
            $this->_debug("initialize");
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $this->_debug("orderId: " . $order->getIncrementId());

            $isSubscriptionPayment = $this->getInfoInstance()->getAdditionalInformation('is_sage_subscription_payment');
            if ($isSubscriptionPayment === true) {
                $shippingAddress = $order->getShippingAddress();
                if ($shippingAddress) {
                    $stateObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $stateObject->setStatus('processing');
                    $stateObject->setIsNotified(false);
                } else {
                    $stateObject->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                    $stateObject->setStatus('complete');
                    $stateObject->setIsNotified(false);
                }
                $amount = $order->getBaseGrandTotal();
                $this->capture($payment, $amount);

                return true;
            }

            $items = $order->getAllItems();
            $isSubscription = $this->subsHelper->isSubscriptionItems($items);
            if (!$this->_customerSession->isLoggedIn() && $isSubscription) {
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug("customer not logged in");
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Customer is not logged in")
                );
            }

            $cardId = $payment->getAdditionalInformation('saved_id');
            if ($cardId !== '0' && $cardId !== null) {
                $card = $this->_cardFactory->create()
                    ->getCollection()
                    ->addFieldToFilter("card_id", $cardId)
                    ->getFirstItem()
                    ->getData();
                if (count($card) > 0) {
                    if ($card['customer_id'] != $order->getCustomerId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __("Card ID exception")
                        );
                    }
                }

                $this->isSave = false;
                $this->reusable = true;
                $payment->setAdditionalInformation('card_identifier', $cardId);
                $this->cardId = $cardId;
            } else {
                $this->isSave = ($payment->getAdditionalInformation('is_save') && $this->sageHelper->getCanSave());
                $this->reusable = false;
                $this->cardId = $payment->getAdditionalInformation('card_identifier');
            }
            $this->_debug($this->cardId);
            $response = [];
            $payment->setAdditionalInformation("sage_payment_action", $paymentAction);
            if ($paymentAction == 'authorize') {
                if ($isSubscription) {
                    $response = $this->_capture($payment);
                } else {
                    $response = $this->_authorize($payment);
                }
            }

            if ($paymentAction == 'authorize_capture') {
                $response = $this->_capture($payment);
            }

            //$this->_debug($response);
            if (isset($response['statusCode'])) {
                //normal pay
                if ($response['statusCode'] == 0000) {
                    $ccLast4 = $response['paymentMethod']->card->lastFourDigits;
                    $expiryDate = $response['paymentMethod']->card->expiryDate;
                    $expM = substr($expiryDate, 0, 2);
                    $expY = substr($expiryDate, -2);
                    $ccType = $response['paymentMethod']->card->cardType;
                    $payment->setCcLast4($ccLast4);
                    $payment->setCcType($ccType);
                    $payment->setCcExpMonth($expM);
                    $payment->setCcExpYear($expY);
                    $payment->setAdditionalInformation("sagepay_response", serialize($response));
                    $payment->setAdditionalInformation("sage_3ds_active", "false");
                    $stateObject->setData('state', \Magento\Sales\Model\Order::STATE_PROCESSING);

                    $totalDue = $order->getTotalDue();
                    $baseTotalDue = $order->getBaseTotalDue();

                    switch ($paymentAction) {
                        case \Magento\Payment\Model\Method\AbstractMethod::ACTION_ORDER:
                            $payment->_order($baseTotalDue);
                            break;
                        case \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE:
                            if ($isSubscription) {
                                $payment->setAmountAuthorized($totalDue);
                                $payment->setBaseAmountAuthorized($baseTotalDue);
                                $payment->capture(null);
                            } else {
                                $payment->authorize(true, $baseTotalDue);
                                // base amount w 2w ill be set inside
                                $payment->setAmountAuthorized($totalDue);
                            }
                            break;
                        case \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE:
                            $payment->setAmountAuthorized($totalDue);
                            $payment->setBaseAmountAuthorized($baseTotalDue);
                            $payment->capture(null);
                            break;
                        default:
                            break;
                    }
                } //3d secure
                else {
                    if ($response['statusCode'] == 2007) {
                        $order->setCanSendNewEmailFlag(false);
                        $order->addStatusHistoryComment($response['statusDetail']);
                        $order->addStatusHistoryComment("Payment status: " . $response['status']);
                        $payment->setAdditionalInformation("sage_trans_id_secure", $response['transactionId']);
                        $payment->setAdditionalInformation("sage_3ds_url", $response['acsUrl']);
                        $payment->setAdditionalInformation("sage_3ds_pareq", $response['paReq']);
                        $payment->setAdditionalInformation("sage_3ds_active", "true");
                    } else {
                        $errorMsg = isset($response['statusDetail']) ? $response['statusDetail'] : "Payment exception";
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __($errorMsg)
                        );
                    }
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Payment exception")
                );
            }
        } catch (\Exception $e) {
            $this->_debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return parent::initialize($paymentAction, $stateObject);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _authorize($payment)
    {
        try {
            $order = $payment->getOrder();
            $sessionKey = $payment->getAdditionalInformation("merchant_sessionKey");
            $url = $this->sageHelper->getPiEndpointUrl() . '/transactions';
            //if internal order
            if ($this->_appState->getAreaCode() == 'adminhtml') {
                $payload = $this->sageHelper->buildMotoPaymentQuery(
                    $order,
                    $sessionKey,
                    $this->cardId,
                    SageHelper::SAGE_PAY_TYPE_AUTHORIZE,
                    $this->isSave,
                    $this->reusable
                );
            } else {
                $payload = $this->sageHelper->buildPaymentQuery(
                    $order,
                    $sessionKey,
                    $this->cardId,
                    SageHelper::SAGE_PAY_TYPE_AUTHORIZE,
                    $this->isSave,
                    $this->reusable
                );
            }
            $this->_debug(json_decode($payload, true));
            $response = $this->sageHelper->sendRequest($url, $payload);
            $this->_debug($response);

            return $response;
        } catch (\Exception $e) {
            $this->_debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $isSubscriptionPayment = $this->getInfoInstance()->getAdditionalInformation('is_sage_subscription_payment');
            if ($isSubscriptionPayment === true) {
                $this->getInfoInstance()->unsAdditionalInformation();

                return parent::authorize($payment, $amount);
            }

            $transModel = $this->_transFactory->create();
            $order = $payment->getOrder();

            $currencyCode = $order->getBaseCurrencyCode();
            $items = $order->getAllItems();
            $isSubscription = $this->subsHelper->isSubscriptionItems($items);

            //payment successful
            $response = unserialize($payment->getAdditionalInformation('sagepay_response'));
            if (isset($response['statusCode']) && ($response['statusCode'] == 0000)) {
                $order->setCanSendNewEmailFlag(true);
                $payment->setTransactionId($response['transactionId']);
                $payment->setParentTransactionId($response['transactionId']);
                $payment->setAdditionalInformation("sage_trans_id", $response['transactionId']);
                $payment->setIsTransactionClosed(0);
                $order->addStatusHistoryComment("Payment status detail: " . $response['statusDetail']);
                $order->addStatusHistoryComment("Payment status: " . $response['status']);
                $cardSecure = (array)$response['3DSecure'];
                $data = [
                    'transaction_id' => $response['transactionId'],
                    'transaction_type' => $response['transactionType'],
                    'transaction_status' => $response['status'],
                    'card_secure' => $cardSecure['status'],
                    'status_detail' => $response['statusDetail'],
                    'order_id' => $order->getRealOrderId(),
                    'customer_id' => $order->getCustomerId(),
                    'is_subscription' => $isSubscription ? "1" : "0"
                ];
                $transModel->setData($data)->save();
                $isSave = $payment->getAdditionalInformation('is_save');
                $cardId = $payment->getAdditionalInformation('card_identifier');
                if ($isSave && $this->_customerSession->isLoggedIn()) {
                    $card = $this->_cardFactory->create();
                    $ccLast4 = $response['paymentMethod']->card->lastFourDigits;
                    $card->addCard($order->getCustomerId(), $cardId, $ccLast4);
                }
                if ($isSubscription) {
                    $subsPlandata = $this->subsHelper->getSubscriptionData($items[0]);
                    /** @var \Magenest\SagePay\Model\Profile $profileModel */
                    $profileModel = $this->_profileFactory->create();
                    $subsData = [
                        'transaction_id' => $response['transactionId'],
                        'order_id' => $order->getIncrementId(),
                        'customer_id' => $order->getCustomerId(),
                        'status' => SubsHelper::SUBS_STAT_ACTIVE_CODE,
                        'amount' => $amount,
                        'total_cycles' => $subsPlandata['total_cycles'],
                        'currency' => $currencyCode,
                        'frequency' => $subsPlandata['frequency'],
                        'remaining_cycles' => --$subsPlandata['total_cycles'],
                        'start_date' => date('Y-m-d'),
                        'last_billed' => date('Y-m-d'),
                        'next_billing' => date('Y-m-d', strtotime("+ " . $subsPlandata['frequency'])),
                        'sequence_order_ids' => ""
                    ];
                    $profileModel->setData($subsData)->save();
                }
            } else {
                $errorMsg = isset($response['statusDetail']) ? $response['statusDetail'] : "Payment exception";
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($errorMsg)
                );
            }
        } catch (\Exception $e) {
            $this->_debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return parent::authorize($payment, $amount);
    }

    /**
     * @param array|string $debugData
     */
    protected function _debug($debugData)
    {
        $this->sageLogger->debug(var_export($debugData, true));
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _capture($payment)
    {
        try {
            $order = $payment->getOrder();
            $sessionKey = $payment->getAdditionalInformation("merchant_sessionKey");
            $url = $this->sageHelper->getPiEndpointUrl() . '/transactions';
            if ($this->_appState->getAreaCode() == 'adminhtml') {
                $payload = $this->sageHelper->buildMotoPaymentQuery(
                    $order,
                    $sessionKey,
                    $this->cardId,
                    SageHelper::SAGE_PAY_TYPE_CAPTURE,
                    $this->isSave,
                    $this->reusable
                );
            } else {
                $payload = $this->sageHelper->buildPaymentQuery(
                    $order,
                    $sessionKey,
                    $this->cardId,
                    SageHelper::SAGE_PAY_TYPE_CAPTURE,
                    $this->isSave,
                    $this->reusable
                );
            }
            $this->_debug(json_decode($payload, true));
            $response = $this->sageHelper->sendRequest($url, $payload);
            $this->_debug($response);

            return $response;
        } catch (\Exception $e) {
            $this->_debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Payment exception")
            );
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $isSubscriptionPayment = $this->getInfoInstance()->getAdditionalInformation('is_sage_subscription_payment');
            if ($isSubscriptionPayment === true) {
                $this->getInfoInstance()->unsAdditionalInformation();

                return parent::capture($payment, $amount);
            }
            //have transaction id, capture with trans id
            $transactionId = $payment->getAdditionalInformation("sage_trans_id");
            if (!!$transactionId) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $messageManager = $objectManager->create("\\Magento\\Framework\\Message\\ManagerInterface");
                $order = $payment->getOrder();
                $url = $this->sageHelper->buildInstructionUrl($transactionId);
                $query = $this->sageHelper->buildInstructionQuery(
                    $order,
                    SageHelper::SAGE_PAY_TYPE_INSTRUCTION_RELEASE,
                    $amount
                );
                $response = $this->sageHelper->sendRequest($url, $query);
                $this->_debug($response);
                //capture done
                if ((isset($response['instructionType'])) && ($response['instructionType'] == 'release')) {
                    $payment->setLastTransId($transactionId);
                    $payment->setIsTransactionClosed(0);
                    $messageManager->addNoticeMessage("You can capture only once time");
                } else {
                    //capture false
                    $messageManager->addNoticeMessage("You can capture only once time");
                    if (isset($response['description'])) {
                        $messageManager->addErrorMessage($response['description']);
                    }
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __("Capture exception")
                    );
                }
            } else {
                //capture normal
                try {
                    $transModel = $this->_transFactory->create();
                    $order = $payment->getOrder();

                    $currencyCode = $order->getBaseCurrencyCode();
                    $items = $order->getAllItems();
                    $isSubscription = $this->subsHelper->isSubscriptionItems($items);

                    $response = unserialize($payment->getAdditionalInformation('sagepay_response'));
                    //payment successful
                    if (isset($response['statusCode']) && ($response['statusCode'] == 0000)) {
                        $order->setCanSendNewEmailFlag(true);
                        $ccLast4 = $response['paymentMethod']->card->lastFourDigits;
                        $payment->setTransactionId($response['transactionId']);
                        $payment->setParentTransactionId($response['transactionId']);
                        $payment->setAdditionalInformation("sage_trans_id", $response['transactionId']);
                        $payment->setIsTransactionClosed(0);
                        $order->addStatusHistoryComment("Payment status detail: " . $response['statusDetail']);
                        $order->addStatusHistoryComment("Payment status: " . $response['status']);
                        $cardSecure = (array)$response['3DSecure'];
                        $data = [
                            'transaction_id' => $response['transactionId'],
                            'transaction_type' => $response['transactionType'],
                            'transaction_status' => $response['status'],
                            'card_secure' => $cardSecure['status'],
                            'status_detail' => $response['statusDetail'],
                            'order_id' => $order->getIncrementId(),
                            'customer_id' => $order->getCustomerId(),
                            'is_subscription' => $isSubscription ? "1" : "0"
                        ];
                        $transModel->setData($data)->save();
                        $isSave = $payment->getAdditionalInformation('is_save');
                        $cardId = $payment->getAdditionalInformation('card_identifier');
                        if ($isSave && $this->_customerSession->isLoggedIn()) {
                            $card = $this->_cardFactory->create();
                            $card->addCard($order->getCustomerId(), $cardId, $ccLast4);
                        }
                        if ($isSubscription) {
                            $subsPlandata = $this->subsHelper->getSubscriptionData($items[0]);
                            /** @var \Magenest\SagePay\Model\Profile $profileModel */
                            $profileModel = $this->_profileFactory->create();
                            $subsData = [
                                'transaction_id' => $response['transactionId'],
                                'order_id' => $order->getIncrementId(),
                                'customer_id' => $order->getCustomerId(),
                                'status' => SubsHelper::SUBS_STAT_ACTIVE_CODE,
                                'amount' => $amount,
                                'total_cycles' => $subsPlandata['total_cycles'],
                                'currency' => $currencyCode,
                                'frequency' => $subsPlandata['frequency'],
                                'remaining_cycles' => --$subsPlandata['total_cycles'],
                                'start_date' => date('Y-m-d'),
                                'last_billed' => date('Y-m-d'),
                                'next_billing' => date('Y-m-d', strtotime("+ " . $subsPlandata['frequency'])),
                                'sequence_order_ids' => ""
                            ];
                            $profileModel->setData($subsData)->save();
                        }
                    } else {
                        $errorMsg = isset($response['statusDetail']) ? $response['statusDetail'] : "Payment exception";
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __($errorMsg)
                        );
                    }
                } catch (\Exception $e) {
                    $this->_logger->debug($e->getMessage());
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return parent::capture($payment, $amount);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $transactionId = $payment->getAdditionalInformation("sage_trans_id");
            $order = $payment->getOrder();
            $url = $this->sageHelper->getPiEndpointUrl();
            $url .= '/transactions';
            $payload = $this->sageHelper->buildRefundQuery($order, $transactionId, $amount);
            $this->_debug(json_decode($payload, true));
            $response = $this->sageHelper->sendRequest($url, $payload);
            $this->_debug($response);
            //refund success
            if (isset($response['statusCode']) && ($response['statusCode'] == 0000)) {
                $payment->setIsTransactionClosed(0);
                $payment->setTransactionId($response['transactionId']);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Refund exception")
                );
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return parent::refund($payment, $amount);
    }

    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->void($payment);

        return parent::cancel($payment); // TODO: Change the autogenerated stub
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface|\Magento\Sales\Model\Order\Payment $payment
     * @param float $amount
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $order = $payment->getOrder();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $messageManager = $objectManager->create("\\Magento\\Framework\\Message\\ManagerInterface");
            $transactionId = $payment->getAdditionalInformation("sage_trans_id");
            $url = $this->sageHelper->buildInstructionUrl($transactionId);
            $query = $this->sageHelper->buildInstructionQuery(
                $order,
                SageHelper::SAGE_PAY_TYPE_INSTRUCTION_ABORT
            );
            $response = $this->sageHelper->sendRequest($url, $query);
            $this->_debug($response);
            //cancel done
            if ((isset($response['instructionType'])) && ($response['instructionType'] == 'abort')) {
                $payment->setIsTransactionClosed(1);
                $payment->setShouldCloseParentTransaction(1);
            } else {
                $messageManager->addErrorMessage($response['description']);
                if (isset($response['description'])) {
                    $messageManager->addErrorMessage($response['description']);
                }
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Exception")
                );
            }
        } catch (\Exception $e) {
            $this->_debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return parent::void($payment); // TODO: Change the autogenerated stub
    }

    public function validate()
    {
        return true;
    }

    public function canUseInternal()
    {
        return $this->sageHelper->activeMoto();
    }

    public function hasVerification()
    {
        return true;
    }
}
