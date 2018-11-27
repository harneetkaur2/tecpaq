<?php
/**
 * Created by PhpStorm.
 * User: joel
 * Date: 23/12/2016
 * Time: 13:54
 */

namespace Magenest\SagePay\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magenest\SagePay\Helper\Subscription;

class Test extends \Magento\Framework\App\Action\Action
{
    protected $quoteFactory;
    protected $_profileFactory;

    public function __construct(
        Context $context,
        \Magenest\SagePay\Model\ProfileFactory $profileFactory,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magenest\SagePay\Cron\Daily $cron
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->sageLogger = $sageLogger;
        $this->_profileFactory = $profileFactory;
        $this->cart = $cart;
        $this->cron = $cron;
        parent::__construct($context);
    }

    public function execute()
    {
        //$this->cart->truncate()->save();
        //$this->cron->execute();
    }
}
