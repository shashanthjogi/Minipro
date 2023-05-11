<?php
namespace Embitel\Login\Setup;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface 
{
 private $eavSetupFactory;

 public function __construct(
 EavSetupFactory $eavSetupFactory,
 Config $eavConfig
 ) 
 {
 $this->eavSetupFactory = $eavSetupFactory;
 $this->eavConfig = $eavConfig;
 }

 public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) 
 {
 $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

 
//creating a custom text field programmatically


 $eavSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'number', [
 'label' => 'Number',
 'system' => 0,
 'position' => 700,
 'sort_order' => 700,
 'visible' => true,
 'note' => '',
 'type' => 'int',
 'input' => 'text',
 ]
 );

 $this->getEavConfig()
 ->getAttribute('customer', 'number')
 ->setData('is_user_defined', 1)
 ->setData('is_required', 0)
 ->setData('default_value', '')
 ->setData('used_in_forms', 
 ['adminhtml_customer', 'checkout_register', 'customer_account_create', 'customer_account_edit', 'adminhtml_checkout'])
 ->save();

 
//magento 2 create customer dropdown attribute programmatically


 $eavSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'custom_dropdown', [
 'label' => 'Occupation',
 'system' => 0,
 'position' => 700,
 'sort_order' => 700,
 'visible' => true,
 'note' => '',
 'type' => 'int',
 'input' => 'select',
 'source' => 'Embitel\Login\Model\Source\Customdropdown',
 ]
 );


//  $custCodEnable=$this->eavConfig->getAttribute(Customer::ENTITY, 'cust_cod_enable'); 
//  $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY); 
//  $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY); 
//  $custCodEnable->setData('attribute_set_id', $attributeSetId); 
//  $custCodEnable->setData('attribute_group_id', $attributeGroupId); 
//  $custCodEnable->setData( 'used_in_forms', ['adminhtml_customer'] ); 
//  $custCodEnable->save();

 $this->getEavConfig()->getAttribute('customer', 'custom_dropdown')
 ->setData('is_user_defined', 1)
 ->setData('is_required', 0)
 ->setData('default_value', '')
 ->setData('used_in_forms', 
 ['adminhtml_customer', 'checkout_register', 'customer_account_create', 'customer_account_edit', 'adminhtml_checkout'])
 ->save();
}
 
public function getEavConfig() {
return $this->eavConfig;
}
}