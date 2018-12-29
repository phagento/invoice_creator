<?php
/**
 * @category    Phagento
 * @package  InvoiceCreator
 * @copyright   Copyright (c) 2018
 * @author  Joenas Ejes
 */

namespace Phagento\InvoiceCreator\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface {
    /**
     * Function install
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //install data here
    }
}