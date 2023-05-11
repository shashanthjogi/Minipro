<?php

namespace Embitel\Login\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use \PandaGroup\MyAdminController\Model\BlogFactory as BlogFactory;

class UpgradeData implements UpgradeDataInterface
  {
    protected $_blogFactory;

    public function __construct(BlogFactory $blogFactory)
  {
    $this->_blogFactory = $blogFactory;
  }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
  {
    if (version_compare($context->getVersion(), '1.1.2', '<')) {
    $data = [
    'title'        => "Test title",
    'content'      => "Test content",
    'status'       =>  1,
    'slug'         => "test slug",
    'tags'         => 'tag',
    ];
    $newBlogPost = $this->_blogFactory->create();
    $newBlogPost->addData($data)->save();
  }
  }
  }