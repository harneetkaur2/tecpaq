<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 12/08/2016
 * Time: 16:49
 */

namespace Magenest\SagePay\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action;
use Magenest\SagePay\Controller\Adminhtml\Profile;

class Index extends Profile
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Profiles'));

        return $resultPage;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_SagePay::profile');
    }
}
