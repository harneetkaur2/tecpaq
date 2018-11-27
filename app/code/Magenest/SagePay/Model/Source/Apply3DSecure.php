<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 05/08/2016
 * Time: 22:45
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Apply3DSecure implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'UseMSPSetting',
                'label' => __('Default: Use default MySagePay settings'),
            ],
            [
                'value' => 'Force',
                'label' => __('Force: Apply authentication even if turned off')
            ],
            [
                'value' => 'Disable',
                'label' => __('Disable: Disable authentication and rules')
            ],
            [
                'value' => 'ForceIgnoringRules',
                'label' => __('Force & Ignore: Apply authentication but ignore rules')
            ],
        ];
    }
}
