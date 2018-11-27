<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 08/08/2016
 * Time: 23:22
 */

namespace Magenest\SagePay\Model\ResourceModel\Card;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Magenest\SagePay\Model\Card', 'Magenest\SagePay\Model\ResourceModel\Card');
    }
}