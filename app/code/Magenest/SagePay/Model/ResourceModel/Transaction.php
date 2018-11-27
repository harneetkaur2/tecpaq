<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 06/08/2016
 * Time: 14:46
 */

namespace Magenest\SagePay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Transaction extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('magenest_sagepay_transaction', 'id');
    }
}
