<?php

namespace Magenest\SagePay\Block\Checkout;

use Magento\Catalog\Block\Product\Context;

class SageCheckout extends \Magento\Framework\View\Element\Template
{
    protected $_config;
    protected $_cardFactory;
    protected $_customerSession;
    protected $_storeResolver;

    public function __construct(
        Context $context,
        \Magenest\SagePay\Helper\SageHelper $config,
        \Magenest\SagePay\Model\CardFactory $cardFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreResolver $storeResolver,
        array $data
    ) {
        $this->_config = $config;
        $this->_customerSession = $customerSession;
        $this->_cardFactory = $cardFactory;
        $this->_storeResolver = $storeResolver;
        parent::__construct($context, $data);
    }

    public function getIsTest()
    {
        return $this->_config->getIsSandbox();
    }

    public function getIsDebugMode()
    {
        return $this->_config->isDebugMode();
    }

    public function getMerchantSessionKeyUrl()
    {
        return $this->getUrl('sagepay/checkout/merchantSessionKey');
    }

    public function getConfig()
    {
        return $this->_config;
    }

    public function canSaveCard()
    {
        return $this->_scopeConfig->getValue(
            'payment/magenest_sagepay/can_save_card',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function hasCard()
    {
        if (!$this->_customerSession->isLoggedIn()) {
            return false;
        }

        return $this->_cardFactory->create()->hasCard($this->_customerSession->getCustomerId());
    }

    public function loadCards()
    {
        return $this->_cardFactory->create()->loadCards($this->_customerSession->getCustomerId());
    }

    public function getCanShowLogo()
    {
        return $this->_scopeConfig->getValue(
            'payment/magenest_sagepay/show_logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeResolver->getCurrentStoreId()
        );
    }

    public function getCustomerSession()
    {
        return $this->_customerSession;
    }
}
