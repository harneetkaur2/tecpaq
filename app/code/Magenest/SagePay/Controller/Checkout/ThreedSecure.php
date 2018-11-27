<?php
/**
 * Created by PhpStorm.
 * User: magenest
 * Date: 14/06/2017
 * Time: 15:36
 */

namespace Magenest\SagePay\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;

class ThreedSecure extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_subscriptionFactory;
    protected $_chargeFactory;
    protected $invoiceSender;
    protected $transactionFactory;
    protected $jsonFactory;
    protected $stripeConfig;
    protected $storeManagerInterface;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    public function __construct(
        Context $context,
        CheckoutSession $session,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $session;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->jsonFactory = $resultJsonFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->_formKeyValidator = $formKeyValidator;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $result->setData([
                'error' => true,
                'error_msg' => "Invalid Form Key"
            ]);
        }
        if ($this->getRequest()->isAjax()) {
            try {
                $order = $this->_checkoutSession->getLastRealOrder();
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $order->getPayment();
                $threeDAction = $payment->getAdditionalInformation("sage_3ds_active");
                if ($threeDAction == 'false') {
                    return $result->setData([
                        'success' => true,
                        'threeDSercueActive' => false,
                        'defaultPay' => true
                    ]);
                } else {
                    if ($threeDAction == 'true') {
                        $threeDSecureUrl = $payment->getAdditionalInformation("sage_3ds_url");
                        $transId = $payment->getAdditionalInformation("sage_trans_id_secure");
                        $paReq = $payment->getAdditionalInformation("sage_3ds_pareq");
                        $formInfo = [
                            'PaReq' => $paReq,
                            'TermUrl' => $this->_url->getUrl('sagepay/checkout/redirectBack'),
                            'MD' => $transId
                        ];

                        return $result->setData([
                            'success' => true,
                            'threeDSercueActive' => true,
                            'threeDSercueUrl' => $threeDSecureUrl,
                            'formData' => $formInfo,
                            'defaultPay' => false
                        ]);
                    } else {
                        return $result->setData([
                            'error' => true,
                            'message' => "Payment exception"
                        ]);
                    }
                }
            } catch (\Exception $e) {
                return $result->setData([
                    'error' => true,
                    'message' => "Payment exception"
                ]);
            }
        }

        return false;
    }
}
