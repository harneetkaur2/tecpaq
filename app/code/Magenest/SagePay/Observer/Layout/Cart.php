<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 10/08/2016
 * Time: 08:22
 */

namespace Magenest\SagePay\Observer\Layout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class Cart implements ObserverInterface
{
    protected $_logger;
    protected $_cart;
    protected $_helper;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magenest\SagePay\Helper\Subscription $helper,
        LoggerInterface $loggerInterface
    ) {
        $this->_cart = $cart;
        $this->_helper = $helper;
        $this->_logger = $loggerInterface;
    }

    public function execute(Observer $observer)
    {
        $item = $observer->getEvent()->getQuoteItem();
        $buyInfo = $item->getBuyRequest();

        $addedItems = $this->_cart->getQuote()->getAllItems();
        $flag = $this->_helper->isSubscriptionItems($addedItems);
        if ($flag && (count($addedItems) > 1)) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Item with subscription option can be purchased standalone only"));
        }

        if ($options = $buyInfo->getAdditionalOptions()) {
            $additionalOptions = [];
            foreach ($options as $key => $value) {
                if ($value) {
                    $additionalOptions[] = array(
                        'label' => $key,
                        'value' => $value
                    );
                }
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $version = $productMetadata->getVersion();
            if (version_compare($version, "2.2.0") < 0) {
                $item->addOption(array(
                    'code' => 'additional_options',
                    'value' => serialize($additionalOptions)
                ));
            } else {
                $item->addOption(array(
                    'code' => 'additional_options',
                    'value' => json_encode($additionalOptions)
                ));
            }
        }
    }
}
