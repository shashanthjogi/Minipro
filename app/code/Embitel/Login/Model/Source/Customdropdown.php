<?php

namespace Embitel\Login\Model\Source;

class Customdropdown extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource 
{
 public function getAllOptions() 
 {
 if ($this->_options === null) {

 $this->_options = [

 ['value' => '', 'label' => __('Select An Option')],

 ['value' => '1', 'label' => __('Doctor')],

 ['value' => '2', 'label' => __('Engineer')],

 ['value' => '3', 'label' => __('Lawyer')],

 ['value' => '4', 'label' => __('Teacher')],

 ['value' => '5', 'label' => __('Musician')],

 ['value' => '6', 'label' => __('Artist')],

 ['value' => '7', 'label' => __('Police Officer')],

 ['value' => '8', 'label' => __('Pilot')],

 ['value' => '9', 'label' => __('Business Man')]

 ];

 }

 return $this->_options;

 }

 public function getOptionText($value) 
 {
 foreach ($this->getAllOptions() as $option)
 {
 if ($option['value'] == $value)
 {
 return $option['label'];
 }
 }
 return false;
 }
}
