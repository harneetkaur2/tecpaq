<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 02/08/2016
 * Time: 22:40
 */

namespace Magenest\SagePay\Model;

use Magento\Payment\Model\CcGenericConfigProvider;

class ConfigProvider extends CcGenericConfigProvider
{
    protected $methodCodes = [
        SagePay::CODE,
    ];
}
