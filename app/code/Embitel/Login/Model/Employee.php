<?php
namespace Embitel\Login\Model;
use Magento\Framework\Model\AbstractModel;

class Employee extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Embitel\Login\Model\ResourceModel\Employee');
    }
}
?>