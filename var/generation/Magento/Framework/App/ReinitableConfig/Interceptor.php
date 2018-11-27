<?php
namespace Magento\Framework\App\ReinitableConfig;

/**
 * Interceptor class for @see \Magento\Framework\App\ReinitableConfig
 */
class Interceptor extends \Magento\Framework\App\ReinitableConfig implements \Magento\Framework\Interception\InterceptorInterface
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
    public function reinit()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'reinit');
        if (!$pluginInfo) {
            return parent::reinit();
        } else {
            return $this->___callPlugins('reinit', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($path, $value, $scope = 'default', $scopeCode = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setValue');
        if (!$pluginInfo) {
            return parent::setValue($path, $value, $scope, $scopeCode);
        } else {
            return $this->___callPlugins('setValue', func_get_args(), $pluginInfo);
        }
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
