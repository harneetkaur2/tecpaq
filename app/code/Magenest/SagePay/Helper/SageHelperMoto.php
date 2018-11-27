<?php
/**
 * Created by PhpStorm.
 * User: magenest
 * Date: 06/04/2017
 * Time: 19:25
 */

namespace Magenest\SagePay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class SageHelperMoto extends AbstractHelper
{
    private $_curlFactory;
    private $_encryptor;

    public function __construct(
        Context $context,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->_curlFactory = $curlFactory;
        $this->_encryptor = $encryptor;
    }

    public function getVendorName()
    {
        return $this->getConfigValue('vendor_name');
    }

    public function getPiEndpointUrl()
    {
        if ($this->getConfigValue('test')) {
            return 'https://pi-test.sagepay.com/api/v1';
        } else {
            return 'https://pi-live.sagepay.com/api/v1';
        }
    }

    public function getIntegrationKey()
    {
        return $this->getConfigValue('integration_key', true);
    }

    public function getIntegrationPassword()
    {
        return $this->getConfigValue('integration_password', true);
    }

    public function getConfigValue($value, $encrypted = false)
    {
        $configValue = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/' . $value,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($encrypted) {
            return $this->_encryptor->decrypt($configValue);
        } else {
            return $configValue;
        }
    }

    public function generateMerchantKey()
    {
        $jsonBody = json_encode(["vendorName" => $this->getVendorName()]);
        $url = $this->getPiEndpointUrl() . '/merchant-session-keys';
        $result = $this->executeRequest($url, $jsonBody);
        if ($result['status'] == 201) {
            return $result["data"]->merchantSessionKey;
        } else {
            return false;
        }
    }

    public function getCardIdentifier($merchantKey, $cardName, $cardNum, $expDate, $ccv)
    {
        $url = $this->getPiEndpointUrl() . '/card-identifiers';
        $jsonBody = json_encode([
            "cardDetails" => [
                "cardholderName" => $cardName,
                "cardNumber" => $cardNum,
                "expiryDate" => $expDate,
                "securityCode" => $ccv
            ]
        ]);
        $result = $this->sendRequest($url, $jsonBody, $merchantKey);

        if ($result['status'] == 201) {
            return $result["data"];
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('get card identify error')
            );
        }
    }

    public function executeRequest($url, $body)
    {

        $curl = $this->_curlFactory->create();

        $curl->setConfig(
            [
                'timeout' => 20,
                'verifypeer' => false,
                'verifyhost' => 2,
                'userpwd' => $this->getIntegrationKey() . ":" . $this->getIntegrationPassword()
            ]
        );

        $curl->write(
            \Zend_Http_Client::POST,
            $url,
            '1.0',
            ['Content-type: application/json'],
            $body
        );
        $data = $curl->read();

        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        $data = preg_split('/^\r?$/m', $data, 2);
        $data = json_decode(trim($data[1]));

        $response = [
            "status" => $response_status,
            "data" => $data
        ];

        return $response;
    }

    private function sendRequest($url, $cardJson, $merchantKey)
    {
        $http = $this->_curlFactory->create();
        $http->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2
            ]
        );
        $headers = [
            "Authorization: Bearer " . $merchantKey,
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ];

        $http->write(
            \Zend_Http_Client::POST,
            $url,
            '1.0',
            $headers,
            $cardJson
        );
        $rawResponse = $http->read();
        $response_status = $http->getInfo(CURLINFO_HTTP_CODE);
        $http->close();

        $data = preg_split('/^\r?$/m', $rawResponse, 2);
        $data = json_decode(trim($data[1]));

        $response = [
            "status" => $response_status,
            "data" => $data
        ];

        return $response;
    }
}
