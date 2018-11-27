<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 10/08/2016
 * Time: 10:05
 */

namespace Magenest\SagePay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Profile extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('magenest_sagepay_subscription_profile', 'id');
    }
}
