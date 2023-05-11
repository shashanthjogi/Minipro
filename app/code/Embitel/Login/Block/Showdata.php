<?php

namespace Embitel\Login\Block;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use Embitel\Login\Model\ResourceModel\Employee\CollectionFactory;

class Showdata extends Template
{

    public $collection;

    public function __construct(Context $context, CollectionFactory $collectionFactory, array $data = [])
    {
        $this->collection = $collectionFactory;
        parent::__construct($context, $data);
    }

    public function getCollection()
    {
        return $this->collection->create();
    }

}
?>