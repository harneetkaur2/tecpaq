<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 10/08/2016
 * Time: 10:06
 */

namespace Magenest\SagePay\Model\ResourceModel\Profile;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Magenest\SagePay\Model\Profile', 'Magenest\SagePay\Model\ResourceModel\Profile');
    }
}
