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

class SageHelper extends AbstractHelper
{

    const SECURE_STATUS_APPLY = "Authenticated";
    const SECURE_STATUS_NOT_APPLY = "NotAuthenticated";
    const SECURE_STATUS_NOT_ENROLL = "CardNotEnrolled";
    const SECURE_STATUS_ISSUER_NOT_ENROLL = "IssuerNotEnrolled";

    const SAGE_TRANSACTION_ID = "transaction_id";

    const SAGE_PAY_TYPE_AUTHORIZE = "Deferred";
    const SAGE_PAY_TYPE_CAPTURE = "Payment";
    const SAGE_PAY_TYPE_REPEAT = "Repeat";
    const SAGE_PAY_TYPE_REFUND = "Refund";

    const SAGE_PAY_TYPE_INSTRUCTION_VOID = "void";
    const SAGE_PAY_TYPE_INSTRUCTION_ABORT = "abort";
    const SAGE_PAY_TYPE_INSTRUCTION_RELEASE = "release";

    protected $orderFactory;
    protected $_encryptor;
    protected $_curlFactory;
    protected $storeManager;

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Context $context
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->_encryptor = $encryptor;
        $this->_curlFactory = $curlFactory;
        $this->storeManager = $storeManager;
    }

    public function getPiEndpointUrl()
    {
        if ($this->getIsSandbox()) {
            return 'https://pi-test.sagepay.com/api/v1';
        } else {
            return 'https://pi-live.sagepay.com/api/v1';
        }
    }

    public function getIsSandbox()
    {
        return $this->getConfigValue('test');
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

    public function getCanSave()
    {
        return $this->getConfigValue('can_save_card');
    }

    public function debug($debugData)
    {
        $this->_logger->debug(var_export($debugData, true));
    }

    public function isDebugMode()
    {
        return $this->getConfigValue('debug');
    }

    public function buildInstructionUrl($transId)
    {
        return $this->getPiEndpointUrl() . '/transactions/' . $transId . "/instructions";
    }

    public function getEndpointUrl()
    {
        if ($this->getIsSandbox()) {
            return 'https://test.sagepay.com/api/v1';
        } else {
            return 'https://live.sagepay.com/api/v1';
        }
    }

    public function isGiftAid()
    {
        return $this->getConfigValue('gift_aid');
    }

    public function getInstructions()
    {
        return preg_replace('/\s+|\n+|\r/', ' ', $this->getConfigValue('instructions'));
    }

    public function get3DStatusAllow()
    {
        $data = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/additional_config/apply_3d_allow',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$data) {
            $data = "Authenticated,NotChecked,NotAuthenticated,Error,CardNotEnrolled,IssuerNotEnrolled,MalformedOrInvalid,AttemptOnly,Incomplete";
        }
        return $data;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $transId
     * @param $amount
     * @return string
     */
    public function buildInstructionQuery($order, $instructionType, $amount = 0)
    {
        $currencyCode = $order->getBaseCurrencyCode();
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($amount * $multiply);
        $query = '{' .
            '"instructionType": "' . $instructionType . '"';

        if ($instructionType == self::SAGE_PAY_TYPE_INSTRUCTION_RELEASE) {
            $query .= ',"amount": ' . $amount;
        }
        $query .= '}';

        return $query;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $sessionKey
     * @param $cardId
     * @param $type
     * @param null|bool $save
     * @param null|bool $reusable
     * @return string
     */
    public function buildPaymentQuery($order, $sessionKey, $cardId, $type, $save = null, $reusable = null)
    {
        $ignoreAddressCheck = $this->getConfigValue('ignore_address_check');
        $testMode = $this->getIsSandbox();
        if ($ignoreAddressCheck && $testMode) {
            $address1 = "88";
            $address2 = "88";
            $postCode = "412";
        } else {
            $address1 = $order->getBillingAddress()->getStreetLine(1);
            $address2 = $order->getBillingAddress()->getStreetLine(2);
            $postCode = $order->getBillingAddress()->getPostcode();
        }
        $save = ($save == true) ? 'true' : 'false';
        $reusable = ($reusable == true) ? 'true' : 'false';
        $amount = $order->getBaseGrandTotal();
        $currencyCode = $order->getBaseCurrencyCode();
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $giftAid = ($payment->getAdditionalInformation('gift_aid')) ? "true" : "false";
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($amount * $multiply);
        $payload = '{' .
            '"transactionType": "' . $type . '",' .
            '"paymentMethod": {' .
            '    "card": {';
        if (1) {
            $payload .=
                '        "merchantSessionKey": "' . $sessionKey . '",';
        }
        $payload .=
            '        "cardIdentifier": "' . $cardId . '",' .
            '        "save": "' . $save . '",' .
            '        "reusable": "' . $reusable . '"' .
            '    }' .
            '},' .
            '"vendorTxCode": "' . $this->generateVendorTxCode($order->getIncrementId()) . '",' .
            '"amount": ' . $amount . ',' .
            '"currency": "' . $currencyCode . '",' .
            '"description": "' . $this->getPaymentDescription($order) . '",' .
            '"apply3DSecure": "' . $this->getIsApply3DSecure() . '",' .
            '"applyAvsCvcCheck": "' . $this->getIsApplyCvcCheck() . '",' .
            '"customerFirstName": "' . $order->getBillingAddress()->getFirstname() . '",' .
            '"customerLastName": "' . $order->getBillingAddress()->getLastname() . '",' .
            '"customerEmail": "' . $order->getBillingAddress()->getEmail() . '",' .
            '"customerPhone": "' . $order->getBillingAddress()->getTelephone() . '",';
        $payload .=
            '"billingAddress": {' .
            '    "address1": "' . $address1 . '",' .
            '    "address2": "' . $address2 . '",';
        $payload .= '    "postalCode": "' . $postCode . '",';
        if ($order->getBillingAddress()->getCountryId() == 'US') {
            $payload .= '    "state": "' . $order->getBillingAddress()->getRegionCode() . '",';
        } else {
        }

        $payload .= '    "city": "' . $order->getBillingAddress()->getCity() . '",' .
            '    "country": "' . $order->getBillingAddress()->getCountryId() . '"';
        $payload .= '},';
        $shipping = $order->getShippingAddress();
        if (!!$shipping) {
            $payload .=
                '"shippingDetails": {' .
                '    "recipientFirstName": "' . $shipping->getFirstname() . '",' .
                '    "recipientLastName": "' . $shipping->getLastname() . '",' .
                '    "shippingAddress1": "' . $shipping->getStreetLine(1) . '",' .
                '    "shippingAddress2": "' . $shipping->getStreetLine(2) . '",';
            $payload .= '    "shippingPostalCode": "' . $shipping->getPostcode() . '",';
            if ($shipping->getCountryId() == 'US') {
                $payload .= '    "shippingState": "' . $shipping->getRegionCode() . '",';
            } else {
            }

            $payload .= '    "shippingCity": "' . $shipping->getCity() . '",' .
                '    "shippingCountry": "' . $shipping->getCountryId() . '"';
            $payload .= '},';
        }
        $payload .= '"giftAid": "' . $giftAid . '",';
        $payload .= '"entryMethod": "Ecommerce",';
        $payload .= '"referrerId": "1BC70868-12A8-1383-A2FB-D7A0205DE97B"';
        $payload .= '}';

        return $payload;
    }

    public function getVendorCode()
    {
        return $this->getConfigValue('vendor_code');
    }

    public function getIsApply3DSecure($disable3DSecure = false)
    {
        $config3Dsecure = $this->scopeConfig->getValue('payment/magenest_sagepay/apply_3d_secure');
        if ($disable3DSecure) {
            return "Disable";
        } else {
            return $config3Dsecure;
        }
    }

    public function getIsApplyCvcCheck()
    {
        return $this->scopeConfig->getValue('payment/magenest_sagepay/apply_cvc_check');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $transId
     * @param $amount
     * @return string
     */
    public function buildRefundQuery($order, $transId, $amount)
    {
        $currencyCode = $order->getBaseCurrencyCode();
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($amount * $multiply);
        $payload = '{' .
            '"transactionType": "Refund",' .
            '"referenceTransactionId": "' . $transId . '",' .
            '"amount": ' . $amount . ',' .
            '"vendorTxCode": "' . $this->generateVendorTxCode($order->getIncrementId()) . '",' .
            '"description": "' . $this->getRefundDescription($order) . '"';
        $payload .= '}';

        return $payload;
    }

    /**
     * @param $refTransId
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function buildRepeatQuery($refTransId, $order)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $giftAid = ($payment->getAdditionalInformation('gift_aid')) ? "true" : "false";
        $amount = $order->getBaseGrandTotal();
        $currencyCode = $order->getBaseCurrencyCode();
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($amount * $multiply);
        $payload = '{' .
            '"transactionType": "Repeat",' .
            '"referenceTransactionId":"' . $refTransId . '",' .
            '"vendorTxCode": "' . $this->generateVendorTxCode($order->getIncrementId()) . '",' .
            '"amount": ' . $amount . ',' .
            '"currency": "' . $currencyCode . '",' .
            '"description": "' . $this->getRepeatDescription($order) . '",';
        $shipping = $order->getShippingAddress();
        if (!!$shipping) {
            $payload .=
                '"shippingDetails": {' .
                '    "recipientFirstName": "' . $shipping->getFirstname() . '",' .
                '    "recipientLastName": "' . $shipping->getLastname() . '",' .
                '    "shippingAddress1": "' . $shipping->getStreetLine(1) . '",' .
                '    "shippingAddress2": "' . $shipping->getStreetLine(2) . '",';
            $payload .= '    "shippingPostalCode": "' . $shipping->getPostcode() . '",';
            if ($shipping->getCountryId() == 'US') {
                $payload .= '    "shippingState": "' . $shipping->getRegionCode() . '",';
            } else {
            }
            $payload .= '    "shippingCity": "' . $shipping->getCity() . '",' .
                '    "shippingCountry": "' . $shipping->getCountryId() . '"';
            $payload .= '},';
        }
        $payload .= '"giftAid": "' . $giftAid . '",';
        $payload .= '"referrerId": "1BC70868-12A8-1383-A2FB-D7A0205DE97B"';
        $payload .= '}';

        return $payload;
    }

    public function buildLinkSecureCodeQuery($secureCode)
    {
        $payload = '{' .
            '"securityCode": "' . $secureCode . '"' .
            '}';

        return $payload;
    }

    public function sendRequest($url, $payload)
    {
        $integrationKey = $this->getConfigValue('integration_key', true);
        $integrationPass = $this->getConfigValue('integration_password', true);
        $http = $this->_curlFactory->create();
        $encoded_credential = base64_encode($integrationKey . ':' . $integrationPass);
        $headers = [
            "Authorization: Basic " . $encoded_credential,
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ];

        $method = \Zend_Http_Client::POST;

        if (!$payload) {
            $method = \Zend_Http_Client::GET;
        }
        $http->write(
            $method,
            $url,
            '1.1',
            $headers,
            $payload
        );
        $response = $http->read();

        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);

        $response = (array)json_decode($response);

        return $response;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function getPaymentDescription($order, $isMoto = false)
    {
        $storeName = $this->storeManager->getStore()->getName();
        if (!$isMoto) {
            return "Order " . $order->getIncrementId() . " at " . $storeName;
        } else {
            return "MOTO transaction. Order " . $order->getIncrementId() . " at " . $storeName;
        }
    }

    public function getRefundDescription($order)
    {
        $storeName = $this->storeManager->getStore()->getName();

        return "Refund Order " . $order->getIncrementId() . " at " . $storeName;
    }

    public function getRepeatDescription($order)
    {
        $storeName = $this->storeManager->getStore()->getName();

        return "Recurring Order " . $order->getIncrementId() . " at " . $storeName;
    }

    public function isZeroDecimal($currency)
    {
        return in_array(strtolower($currency), [
            'bif',
            'djf',
            'jpy',
            'krw',
            'pyg',
            'vnd',
            'xaf',
            'xpf',
            'clp',
            'gnf',
            'kmf',
            'mga',
            'rwf',
            'vuv',
            'xof'
        ]);
    }

    public function generateVendorTxCode($order_id = "")
    {
        $vendorCode = $this->getVendorCode();

        return substr($order_id . "-" . date("Ymd") . "-" .time()."-".$vendorCode, 0, 40);
    }

    public function activeMoto()
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay/active_moto'
        );
    }

    public function buildMotoPaymentQuery($order, $sessionKey, $cardId, $type, $save = null, $reusable = null)
    {
        $ignoreAddressCheck = $this->getConfigValue('ignore_address_check');
        $testMode = $this->getIsSandbox();
        if ($ignoreAddressCheck && $testMode) {
            $address1 = "88";
            $address2 = "88";
            $postCode = "412";
        } else {
            $address1 = $order->getBillingAddress()->getStreetLine(1);
            $address2 = $order->getBillingAddress()->getStreetLine(2);
            $postCode = $order->getBillingAddress()->getPostcode();
        }
        $save = ($save == true) ? 'true' : 'false';
        $reusable = ($reusable == true) ? 'true' : 'false';
        $amount = $order->getBaseGrandTotal();
        $currencyCode = $order->getBaseCurrencyCode();
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $giftAid = ($payment->getAdditionalInformation('gift_aid')) ? "true" : "false";
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($amount * $multiply);
        $payload = '{' .
            '"transactionType": "' . $type . '",' .
            '"paymentMethod": {' .
            '    "card": {';
        if (1) {
            $payload .=
                '        "merchantSessionKey": "' . $sessionKey . '",';
        }
        $payload .=
            '        "cardIdentifier": "' . $cardId . '"' .
            '    }' .
            '},' .
            '"vendorTxCode": "' . $this->generateVendorTxCode($order->getIncrementId()) . '",' .
            '"amount": ' . $amount . ',' .
            '"currency": "' . $currencyCode . '",' .
            '"description": "' . $this->getPaymentDescription($order, true) . '",' .
            '"apply3DSecure": "' . $this->getIsApply3DSecure(true) . '",' .
            '"applyAvsCvcCheck": "' . $this->getIsApplyCvcCheck() . '",' .
            '"customerFirstName": "' . $order->getBillingAddress()->getFirstname() . '",' .
            '"customerLastName": "' . $order->getBillingAddress()->getLastname() . '",' .
            '"customerEmail": "' . $order->getBillingAddress()->getEmail() . '",' .
            '"customerPhone": "' . $order->getBillingAddress()->getTelephone() . '",';
        $payload .=
            '"billingAddress": {' .
            '    "address1": "' . $address1 . '",' .
            '    "address2": "' . $address2 . '",';
        $payload .= '    "postalCode": "' . $postCode . '",';
        if ($order->getBillingAddress()->getCountryId() == 'US') {
            $payload .= '    "state": "' . $order->getBillingAddress()->getRegionCode() . '",';
        } else {
        }

        $payload .= '    "city": "' . $order->getBillingAddress()->getCity() . '",' .
            '    "country": "' . $order->getBillingAddress()->getCountryId() . '"';
        $payload .= '},';
        $shipping = $order->getShippingAddress();
        if (!!$shipping) {
            $payload .=
                '"shippingDetails": {' .
                '    "recipientFirstName": "' . $shipping->getFirstname() . '",' .
                '    "recipientLastName": "' . $shipping->getLastname() . '",' .
                '    "shippingAddress1": "' . $shipping->getStreetLine(1) . '",' .
                '    "shippingAddress2": "' . $shipping->getStreetLine(2) . '",';
            $payload .= '    "shippingPostalCode": "' . $shipping->getPostcode() . '",';
            if ($shipping->getCountryId() == 'US') {
                $payload .= '    "shippingState": "' . $shipping->getRegionCode() . '",';
            } else {
            }

            $payload .= '    "shippingCity": "' . $shipping->getCity() . '",' .
                '    "shippingCountry": "' . $shipping->getCountryId() . '"';
            $payload .= '},';
        }
        $payload .= '"entryMethod": "TelephoneOrder",';
        $payload .= '"referrerId": "1BC70868-12A8-1383-A2FB-D7A0205DE97B"';
        $payload .= '}';

        return $payload;
    }
}
