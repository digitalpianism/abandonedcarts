<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Log
 */
class DigitalPianism_Abandonedcarts_Model_Log extends Mage_Core_Model_Abstract
{

    const TYPE_NORMAL = 0;
    const TYPE_SALES = 1;

    protected function _construct()
    {
        $this->_init('abandonedcarts/log', 'log_id');
    }

    public function toOptionArray()
    {
        return array(
            self::TYPE_NORMAL   =>  Mage::helper('abandonedcarts')->__('Abandoned cart email'),
            self::TYPE_SALES   =>  Mage::helper('abandonedcarts')->__('Sale abandoned cart email')
        );
    }

}