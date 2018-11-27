<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 11/08/2016
 * Time: 16:07
 */

namespace Magenest\SagePay\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magenest\SagePay\Helper\Data;
use Magenest\SagePay\Model\TransactionFactory;
use Magenest\SagePay\Model\ProfileFactory;

class RedirectBack extends Action
{
    protected $_helper;

    protected $_transFactory;

    protected $_profileFactory;

    protected $sageHelper;

    protected $sageLogger;

    protected $checkoutSession;

    protected $orderSender;

    public function __construct(
        Context $context,
        Data $data,
        TransactionFactory $transactionFactory,
        ProfileFactory $profileFactory,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    ) {
        $this->_helper = $data;
        $this->_transFactory = $transactionFactory;
        $this->_profileFactory = $profileFactory;
        $this->sageHelper = $sageHelper;
        $this->sageLogger = $sageLogger;
        $this->checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $params = $this->getRequest()->getParams();
            $pares = $this->getRequest()->getParam('PaRes');
            $paMD = $this->getRequest()->getParam('MD');
            $order = $this->checkoutSession->getLastRealOrder();
            $payment = $order->getPayment();
            //handle for duplicate response
            $isSageResponse = $payment->getAdditionalInformation('is_sagepay_has_responsed');
            if ($isSageResponse == "true") {
                $this->_redirect('checkout/cart');
            }
            $this->_debug($params);
            $url = $this->_helper->getPiEndpointUrl();
            $resultSubmit3d = $this->_helper->submit3D($pares, $paMD);
            if (isset($resultSubmit3d->status)) {
                //3ds check ok
                $transUrl = $url . '/transactions/' . $paMD;
                $response = $this->_helper->sendRequest($transUrl, null);
                $this->_debug($response);
                $payment->setAdditionalInformation("sagepay_response", serialize($response));
                $payment->setAdditionalInformation("is_sagepay_has_responsed", "true");
                $payAction = $payment->getAdditionalInformation("sage_payment_action");
                $totalDue = $order->getTotalDue();
                $baseTotalDue = $order->getBaseTotalDue();
                if ($payAction == 'authorize') {
                    $payment->authorize(true, $baseTotalDue);
                    // base amount will be set inside
                    $payment->setAmountAuthorized($totalDue);
                }
                if ($payAction == 'authorize_capture') {
                    $payment->setAmountAuthorized($totalDue);
                    $payment->setBaseAmountAuthorized($baseTotalDue);
                    $payment->capture(null);
                }
                if ($order->getCanSendNewEmailFlag()) {
                    try {
                        $this->orderSender->send($order);
                    } catch (\Exception $e) {
                        $this->sageLogger->critical($e->getMessage());
                    }
                }
                //$order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->save();
                $this->_redirect('checkout/onepage/success');
            } else {
                //3ds fail
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('3d secure authenticate fail')
                );
            }
        } catch (\Exception $e) {
            $this->sageLogger->critical($e->getMessage());
            $this->cancelOrder();
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }

    private function cancelOrder()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order->canCancel()) {
            $order->cancel();
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->addStatusHistoryComment("Exception payment");
            $order->save();
            $payment = $order->getPayment();
            $payment->setStatus('Payment EXCEPTION');
            $payment
                ->setShouldCloseParentTransaction(1)
                ->setIsTransactionClosed(1);
        }
    }

    /**
     * @param array|string $debugData
     */
    private function _debug($debugData)
    {
        $this->sageLogger->debug(var_export($debugData, true));
    }
}
