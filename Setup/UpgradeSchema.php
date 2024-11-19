<?php

namespace Railsformers\EcarImport\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this->createEcarImportTable($installer);
        $installer->endSetup();
    }

    private function createEcarImportTable($installer)
    {
        $tableName = $installer->getTable('ecar_import');

        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'czesci_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false],
                    'Czesci ID'
                )
                ->addColumn(
                    'ecar_code',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Ecar Code'
                )
                ->addColumn(
                    'b_update',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false],
                    'Update'
                )
                ->addColumn(
                    'price_pl',
                    Table::TYPE_FLOAT,
                    null,
                    ['nullable' => true],
                    'Price PL'
                )
                ->addColumn(
                    'stock',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => true],
                    'Stock'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                );
            $installer->getConnection()->createTable($table);
        }
    }
}
