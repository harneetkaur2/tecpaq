<?php
namespace Magenest\SagePay\Controller\Checkout\RedirectBack;

/**
 * Interceptor class for @see \Magenest\SagePay\Controller\Checkout\RedirectBack
 */
class Interceptor extends \Magenest\SagePay\Controller\Checkout\RedirectBack implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magenest\SagePay\Helper\Data $data, \Magenest\SagePay\Model\TransactionFactory $transactionFactory, \Magenest\SagePay\Model\ProfileFactory $profileFactory, \Magenest\SagePay\Helper\SageHelper $sageHelper, \Magenest\SagePay\Helper\Logger $sageLogger, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender)
    {
        $this->___init();
        parent::__construct($context, $data, $transactionFactory, $profileFactory, $sageHelper, $sageLogger, $checkoutSession, $orderSender);
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
