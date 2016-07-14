<?php

/**
 * Class DigitalPianism_Abandonedcarts_Block_Adminhtml_Logs
 */
class DigitalPianism_Abandonedcarts_Block_Adminhtml_Logs extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_logs';
        $this->_blockGroup = 'abandonedcarts';
        $this->_headerText = Mage::helper('abandonedcarts')->__('Abandoned Carts Logs');
        parent::__construct();
        $this->_removeButton('add');
    }

}