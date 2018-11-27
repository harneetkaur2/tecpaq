<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 07/08/2016
 * Time: 14:19
 */

namespace Magenest\SagePay\Block\Adminhtml\Order\View\Info;

use Magento\Backend\Block\Template\Context;
use Magenest\SagePay\Model\TransactionFactory;
use Magento\Framework\Registry;

class Transaction extends \Magento\Backend\Block\Template
{
    protected $_transFactory;

    protected $_registry;

    public function __construct(
        Context $context,
        TransactionFactory $transactionFactory,
        Registry $registry,
        array $data = []
    ) {
        $this->_transFactory = $transactionFactory;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    public function getTransaction()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_registry->registry('current_order');
        $orderId = $order->getIncrementId();

        $transactionModel = $this->_transFactory->create();
        $transaction = $transactionModel
            ->getCollection()
            ->addFieldToFilter('order_id', $orderId)
//            ->addFieldToFilter('transaction_type', 'Payment')
            ->getFirstItem();

        if ($transaction) {
            return $transaction;
        }

        return false;
    }
}
