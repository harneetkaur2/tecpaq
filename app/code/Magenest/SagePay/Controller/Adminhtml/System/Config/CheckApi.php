<?php

namespace Magenest\SagePay\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class CheckApi extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    protected $sageHelper;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        \Magenest\SagePay\Helper\SageHelperMoto $sageHelperMoto
    ) {
        parent::__construct($context);
        $this->sageHelper = $sageHelperMoto;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            /** @var \Magento\Framework\Controller\Result\Json $result */
            $check = $this->callApi();
            if ($check['status'] == 201) {
                return $result->setData([
                    'error' => false,
                    'success' => true,
                    'content' => json_encode($check)
                ]);
            } else {
                return $result->setData([
                    'error' => true,
                    'success' => false,
                    'content' => json_encode($check)
                ]);
            }
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'error_msg' => $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function callApi()
    {
        $jsonBody = json_encode(["vendorName" => $this->sageHelper->getVendorName()]);
        $url = $this->sageHelper->getPiEndpointUrl() . '/merchant-session-keys';
        return $this->sageHelper->executeRequest($url, $jsonBody);
    }
}
