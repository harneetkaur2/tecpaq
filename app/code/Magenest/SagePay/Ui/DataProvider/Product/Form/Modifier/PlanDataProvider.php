<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 09/08/2016
 * Time: 00:33
 */

namespace Magenest\SagePay\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magenest\SagePay\Model\PlanFactory;

class PlanDataProvider extends AbstractModifier
{
    protected $_locator;

    protected $_request;

    protected $_logger;

    protected $_planFactory;

    public function __construct(
        RequestInterface $request,
        LocatorInterface $locator,
        \Psr\Log\LoggerInterface $loggerInterface,
        PlanFactory $planFactory
    ) {
        $this->_planFactory = $planFactory;
        $this->_logger = $loggerInterface;
        $this->_request = $request;
        $this->_locator = $locator;
    }

    public function modifyData(array $data)
    {
        $product = $this->_locator->getProduct();
        $productId = $product->getId();

        $planModel = $this->_planFactory->create();
        $plan = $planModel->getCollection()->addFieldToFilter('product_id', $productId)->getFirstItem();
        if (!!$plan->getData()) {
            $isEnabled = $plan->getEnabled();
            $options = unserialize($plan->getSubscriptionValue());

            $data[strval($productId)]['event']['magenest_sagepay_enabled']['enable'] = $isEnabled;

            $data[strval($productId)]['event']['magenest_sagepay']['subscription_options']['subscription_options'] = $options;
        }

        return $data;
    }

    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
