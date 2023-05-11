<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Embitel\Login\Model\Data;

use \Magento\Framework\Api\AttributeValueFactory;

/**
 * Customer data model
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Customer extends \Magento\Customer\Model\Data\Customer 
  { 

    const NUMBER = 'number';
    const OCCUPATION = 'occupation';
    /**
     * Get mobile number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->_get(self::NUMBER);
    }
    
    /**
     * Get occupation
     *
     * @return string
     */
    public function getCustomdropdown()
    {
        return $this->_get(self::OCCUPATION);
    }
    
    

    /**
     * Set mobile number
     *
     * @param string $number
     * @return $this
     */
    public function setNumber($number)
    {
        return $this->setData(self::NUMBER, $number);
    }

    /**
     * Set occupation
     *
     * @param string $occupation
     * @return $this
     */
    public function setCustomdropdown($occupation)
    {
        return $this->setData(self::OCCUPATION, $occupation);
    }

   
}
