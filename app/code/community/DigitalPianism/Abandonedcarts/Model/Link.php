<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Link
 */
class DigitalPianism_Abandonedcarts_Model_Link extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('abandonedcarts/link', 'link_id');
    }

}