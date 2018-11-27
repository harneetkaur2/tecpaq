<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 09/08/2016
 * Time: 09:20
 */

namespace Magenest\SagePay\Block\Catalog\Product;

use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magenest\SagePay\Model\PlanFactory;

class View extends \Magento\Catalog\Block\Product\AbstractProduct
{
    protected $_date;

    protected $_planFactory;

    public function __construct(
        Context $context,
        DateTime $dateTime,
        PlanFactory $planFactory,
        array $data = []
    ) {
        $this->_date = $dateTime;
        $this->_planFactory = $planFactory;
        parent::__construct($context, $data);
    }

    public function getIsSubscriptionProduct()
    {
        $product = $this->_coreRegistry->registry('current_product');
        $productId = $product->getId();

        $planModel = $this->_planFactory->create();
        $plan = $planModel->getCollection()->addFieldToFilter('product_id', $productId)->getFirstItem();

        if ($plan) {
            $value = $plan->getEnabled();

            return $value;
        }

        return false;
    }

    public function getSubscriptionOptions()
    {
        $product = $this->_coreRegistry->registry('current_product');
        $productId = $product->getId();

        $planModel = $this->_planFactory->create();
        $plan = $planModel->getCollection()->addFieldToFilter('product_id', $productId)->getFirstItem();

        if ($plan) {
            $options = $plan->getSubscriptionValue();
            $options = unserialize($options);

            return $options;
        }

        return '';
    }
}
