<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 02/08/2016
 * Time: 23:33
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Payment\Model\Source\Cctype as PaymentCctype;

class Cctype extends PaymentCctype
{
    public function getAllowedTypes()
    {
        return ['VI', 'MC', 'AE', 'DI', 'OT'];
    }
}
