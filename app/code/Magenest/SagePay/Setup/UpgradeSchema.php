<?php
/**
 * Created by Magenest.
 * Author: Pham Quang Hau
 * Date: 09/07/2016
 * Time: 01:47
 */

namespace Magenest\SagePay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table as Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('magenest_sagepay_subscription_profile'),
                'sequence_order_ids',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Sequential Order ID'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $table = $setup->getConnection()->newTable($setup->getTable('magenest_sagepay_saved_card'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Customer ID'
                )
                ->addColumn(
                    'customer_email',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Customer Email'
                )
                ->addColumn(
                    'card_id',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Card ID'
                )
                ->addColumn(
                    'last_4',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Last 4 number'
                )
                ->setComment('Customer card ID');
            $setup->getConnection()->createTable($table);
        }

        $setup->endSetup();
    }
}
