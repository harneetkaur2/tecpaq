<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 12/08/2016
 * Time: 15:31
 */

namespace Magenest\SagePay\Controller\Adminhtml\Transaction;

use Magento\Backend\App\Action;
use Magenest\SagePay\Controller\Adminhtml\Transaction;

class Index extends Transaction
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Transactions'));

        return $resultPage;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_SagePay::transaction');
    }
}
