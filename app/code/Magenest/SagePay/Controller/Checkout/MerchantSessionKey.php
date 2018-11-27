<?php
/**
 * Created by PhpStorm.
 * User: joel
 * Date: 01/03/2017
 * Time: 17:02
 */

namespace Magenest\SagePay\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class MerchantSessionKey extends Action
{
    protected $_helper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    public function __construct(
        Context $context,
        \Magenest\SagePay\Helper\Data $helperData,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {
        $this->_helper = $helperData;
        $this->_formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $result->setData([
                'error' => true,
                'error_msg' => "Invalid Form Key"
            ]);
        }
        if ($this->getRequest()->isAjax()) {
            $payload = '{ "vendorName": "' . $this->_helper->getVendorName() . '" }';
            $url = $this->_helper->getPiEndpointUrl() . '/merchant-session-keys';
            $response = $this->_helper->sendCurlRequest($url, $payload);
            if ($response['status'] == 201) {
                return $result->setData($response['data']);
            } else {
                return $result->setData([
                    'error' => true,
                    'success' => false
                ]);
            }
        }
    }
}
