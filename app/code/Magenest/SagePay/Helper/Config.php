<?php
/**
 * Created by PhpStorm.
 * User: joel
 * Date: 23/11/2016
 * Time: 16:02
 */

namespace Magenest\SagePay\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function getIsApply3DSecure()
    {
        return $this->scopeConfig->getValue('payment/magenest_sagepay/apply_3d_secure');
    }

    public function getIsApplyCvcCheck()
    {
        return $this->scopeConfig->getValue('payment/magenest_sagepay/apply_cvc_check');
    }

    public function getIsSandbox()
    {
        return $this->scopeConfig->getValue('payment/magenest_sagepay/test');
    }

    public function getDropInActive()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay_dropin/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isDebugMode()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay/debug',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
