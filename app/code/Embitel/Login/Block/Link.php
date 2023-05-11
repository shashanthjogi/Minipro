<?php
namespace Embitel\Login\Block;

use \Magento\Framework\View\Element\Template;

class Link extends Template
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param array $data
    */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ){
        parent::__construct($context, $data);
     }
         
}
?>
