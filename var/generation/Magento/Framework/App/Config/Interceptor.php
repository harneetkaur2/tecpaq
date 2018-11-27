<?php
namespace Magento\Framework\App\Config;

/**
 * Interceptor class for @see \Magento\Framework\App\Config
 */
class Interceptor extends \Magento\Framework\App\Config implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Config\ScopePool $scopePool)
    {
        $this->___init();
        parent::__construct($scopePool);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($path = null, $scope = 'default', $scopeCode = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getValue');
        if (!$pluginInfo) {
            return parent::getValue($path, $scope, $scopeCode);
        } else {
            return $this->___callPlugins('getValue', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isSetFlag($path, $scope = 'default', $scopeCode = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isSetFlag');
        if (!$pluginInfo) {
            return parent::isSetFlag($path, $scope, $scopeCode);
        } else {
            return $this->___callPlugins('isSetFlag', func_get_args(), $pluginInfo);
        }
    }
}
