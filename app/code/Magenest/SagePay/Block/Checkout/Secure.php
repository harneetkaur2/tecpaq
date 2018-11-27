<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 06/08/2016
 * Time: 10:58
 */

namespace Magenest\SagePay\Block\Checkout;

use Magento\Catalog\Block\Product\Context;

class Secure extends \Magento\Framework\View\Element\Template
{
    protected $_registry;

    public function __construct(
        Context $context,
        array $data
    ) {
        $this->_registry = $context->getRegistry();
        parent::__construct($context, $data);
    }

    public function getResponseData()
    {
        $data = $this->_registry->registry('secure_response');

//        $this->_logger->addDebug(print_r($data, true));

        return $data;
    }
}
