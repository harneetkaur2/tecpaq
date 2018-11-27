<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 08/08/2016
 * Time: 23:21
 */

namespace Magenest\SagePay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Card extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('magenest_sagepay_saved_card', 'id');
    }
}
