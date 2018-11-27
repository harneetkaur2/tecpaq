<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 09/08/2016
 * Time: 09:17
 */

namespace Magenest\SagePay\Observer\Layout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class Add implements ObserverInterface
{
    protected $_logger;

    public function __construct(
        LoggerInterface $loggerInterface
    ) {
        $this->_logger = $loggerInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        if ($product->getTypeId() != 'grouped') {
            $product->setHasOptions(true);
        }
    }
}
