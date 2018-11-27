<?php
namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon\Index;

/**
 * Interceptor class for @see \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon\Index
 */
class Interceptor extends \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory, \Magento\Framework\Code\NameBuilder $nameBuilder, \Ess\M2ePro\Controller\Adminhtml\Context $context)
    {
        $this->___init();
        parent::__construct($amazonFactory, $nameBuilder, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        if (!$pluginInfo) {
            return parent::execute();
        } else {
            return $this->___callPlugins('execute', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isCompleted()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isCompleted');
        if (!$pluginInfo) {
            return parent::isCompleted();
        } else {
            return $this->___callPlugins('isCompleted', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isSkipped()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'isSkipped');
        if (!$pluginInfo) {
            return parent::isSkipped();
        } else {
            return $this->___callPlugins('isSkipped', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setStepAction()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setStepAction');
        if (!$pluginInfo) {
            return parent::setStepAction();
        } else {
            return $this->___callPlugins('setStepAction', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setStatusAction()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setStatusAction');
        if (!$pluginInfo) {
            return parent::setStatusAction();
        } else {
            return $this->___callPlugins('setStatusAction', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageManager()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getMessageManager');
        if (!$pluginInfo) {
            return parent::getMessageManager();
        } else {
            return $this->___callPlugins('getMessageManager', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        if (!$pluginInfo) {
            return parent::dispatch($request);
        } else {
            return $this->___callPlugins('dispatch', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function _processUrlKeys()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, '_processUrlKeys');
        if (!$pluginInfo) {
            return parent::_processUrlKeys();
        } else {
            return $this->___callPlugins('_processUrlKeys', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($route = '', $params = array())
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getUrl');
        if (!$pluginInfo) {
            return parent::getUrl($route, $params);
        } else {
            return $this->___callPlugins('getUrl', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getActionFlag()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getActionFlag');
        if (!$pluginInfo) {
            return parent::getActionFlag();
        } else {
            return $this->___callPlugins('getActionFlag', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getRequest');
        if (!$pluginInfo) {
            return parent::getRequest();
        } else {
            return $this->___callPlugins('getRequest', func_get_args(), $pluginInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getResponse');
        if (!$pluginInfo) {
            return parent::getResponse();
        } else {
            return $this->___callPlugins('getResponse', func_get_args(), $pluginInfo);
        }
    }
}
