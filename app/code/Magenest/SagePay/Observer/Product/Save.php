<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 08/08/2016
 * Time: 16:18
 */

namespace Magenest\SagePay\Observer\Product;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magenest\SagePay\Model\PlanFactory;
use Psr\Log\LoggerInterface;

class Save implements ObserverInterface
{
    protected $_logger;

    protected $_request;

    protected $_planFactory;

    public function __construct(
        LoggerInterface $loggerInterface,
        RequestInterface $requestInterface,
        PlanFactory $planFactory
    ) {
        $this->_logger = $loggerInterface;
        $this->_request = $requestInterface;
        $this->_planFactory = $planFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $planModel = $this->_planFactory->create();
        $data = $this->_request->getParams();

        $product = $observer->getProduct();
        $productId = $product->getId();

        $plan = $planModel->getCollection()->addFieldToFilter('product_id', $productId)->getFirstItem();
//        if ($plan) {
//            $planModel->load($plan->getId());
//        }

        $data = $data['event'];
        //$planData = [];
        $result = [];

        if (array_key_exists('magenest_sagepay', $data)) {
            $newData = $data['magenest_sagepay']['subscription_options']['subscription_options'];

            if ($newData != 'false') {
                foreach ($newData as $item) {
                    if (array_key_exists('is_delete', $item)) {
                        if ($item['is_delete']) {
                            continue;
                        }
                    }

                    array_push($result, $item);
                }

//                $planData = [
//                    'product_id' => $productId,
//                    'subscription_value' => serialize($result)
//                ];
            }
        }

        //$planData['enabled'] = $data['magenest_sagepay_enabled']['enable'];

        //$plan->addData($planData)->save();
        $plan->setData("enabled", $data['magenest_sagepay_enabled']['enable']);
        $plan->setData("subscription_value", serialize($result));
        $plan->setData("product_id", $productId);
        $plan->save();
    }
}
