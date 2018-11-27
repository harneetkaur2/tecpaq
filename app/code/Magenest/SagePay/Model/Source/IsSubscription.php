<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 12/08/2016
 * Time: 15:54
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class IsSubscription extends AbstractSource
{
    public static function getOptionArray()
    {
        return [
            1 => __('Yes'),
            0 => __('No')
        ];
    }

    public function getAllOptions()
    {
        $result = [];
        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }

    public function getOptionGrid($optionId)
    {
        $options = self::getOptionArray();
        if ($optionId == 1) {
            $html = '<span class="grid-severity-notice"><span>' . $options[$optionId] . '</span>' . '</span>';
        } else {
            $html = '<span class="grid-severity-critical"><span>' . $options[$optionId] . '</span></span>';
        }

        return $html;
    }
}
