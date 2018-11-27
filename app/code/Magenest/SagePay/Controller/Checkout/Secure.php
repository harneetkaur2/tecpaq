<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 06/08/2016
 * Time: 08:52
 */

namespace Magenest\SagePay\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;

class Secure extends Action
{
    protected $_logger;

    protected $resultPageFactory;

    protected $coreRegistry;

    public function __construct(
        Context $context,
        LoggerInterface $loggerInterface,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->_logger = $loggerInterface;
        $this->resultPageFactory = $pageFactory;
        $this->coreRegistry = $registry;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $this->coreRegistry->register('secure_response', $params);

        $result = $this->resultPageFactory->create();
        $result->getConfig()->getTitle()->prepend(__('Redirecting to 3-D Secure Authentication Page'));

        return $result;
    }
}
