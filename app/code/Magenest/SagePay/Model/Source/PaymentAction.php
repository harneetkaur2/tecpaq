<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 02/08/2016
 * Time: 22:52
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'authorize_capture',
                'label' => __('Authorize and Capture (Payment)')
            ],
            [
                'value' => 'authorize',
                'label' => __('Authorize Only (Deferred)'),
            ],
        ];
    }
}
