<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 06/08/2016
 * Time: 14:44
 */

namespace Magenest\SagePay\Model;

use Magenest\SagePay\Model\ResourceModel\Transaction as Resource;
use Magenest\SagePay\Model\ResourceModel\Transaction\Collection as Collection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class Transaction extends AbstractModel
{
    protected $_eventPrefix = 'transaction_';

    public function __construct(
        Context $context,
        Registry $registry,
        Resource $resource,
        Collection $resourceCollection,
        $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
}
