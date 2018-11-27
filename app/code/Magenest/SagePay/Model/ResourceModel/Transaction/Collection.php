<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 06/08/2016
 * Time: 14:47
 */

namespace Magenest\SagePay\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Magenest\SagePay\Model\Transaction', 'Magenest\SagePay\Model\ResourceModel\Transaction');
    }
}
