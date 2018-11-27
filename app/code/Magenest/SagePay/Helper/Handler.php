<?php

namespace Magenest\SagePay\Helper;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    protected $fileName = '/var/log/sagepay/debug.log';
    protected $loggerType = \Monolog\Logger::DEBUG;
}
