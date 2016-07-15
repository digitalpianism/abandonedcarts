<?php

/**
 * Class DigitalPianism_Abandonedcarts_Block_Adminhtml_Saleabandonedcarts
 */
class DigitalPianism_Abandonedcarts_Block_Adminhtml_Saleabandonedcarts extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_saleabandonedcarts';
        $this->_blockGroup = 'abandonedcarts';
        $this->_headerText = Mage::helper('abandonedcarts')->__('Sale Abandoned Carts');
        parent::__construct();
        $this->_removeButton('add');
        $this->_addButton('notify', array(
            'label'     => Mage::helper('abandonedcarts')->__('Send notifications'),
            'onclick'   => "setLocation('".$this->getUrl('*/*/notifySaleAll', array('store'  =>  $this->getRequest()->getParam('store', 0)))."')",
        ));
        $this->setTemplate('digitalpianism/abandonedcarts/list.phtml');
    }

    /**
     * Prepare the layout
     */
    protected function _prepareLayout()
    {
        // Display store switcher if system has more one store
        if (!Mage::app()->isSingleStoreMode())
        {
            $this->setChild('store_switcher', $this->getLayout()->createBlock('adminhtml/store_switcher')
                ->setUseConfirm(false)
                ->setSwitchUrl($this->getUrl('*/*/*', array('store' => null)))
            );
        }
        return parent::_prepareLayout();
    }

    /**
     * Getter for the store switcher HTML
     */
    public function getStoreSwitcherHtml()
    {
        return $this->getChildHtml('store_switcher');
    }

}