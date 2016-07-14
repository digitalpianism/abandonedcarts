<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Resource_Log
 */
class DigitalPianism_Abandonedcarts_Model_Resource_Log extends Mage_Core_Model_Mysql4_Abstract
{

    protected function _construct()
    {
        $this->_init('abandonedcarts/log', 'log_id');
    }

}