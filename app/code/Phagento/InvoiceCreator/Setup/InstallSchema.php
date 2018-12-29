<?php
/**
 * @category    Phagento
 * @package  InvoiceCreator
 * @copyright   Copyright (c) 2018
 * @author  Joenas Ejes
 */

namespace Phagento\InvoiceCreator\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface {
    /**
     * Function install
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
    }
}
