<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Adminhtml_Observer
 */
class DigitalPianism_Abandonedcarts_Model_Adminhtml_Observer {

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function registerController(Varien_Event_Observer $observer)
    {
        $action = $observer->getControllerAction()->getFullActionName();

        switch ($action)
        {
            case 'adminhtml_report_shopcart_abandoned':
            case 'adminhtml_report_shopcart_exportAbandonedCsv':
            case 'adminhtml_report_shopcart_exportAbandonedExcel':
                Mage::register('abandonedcart_report', true);
                break;
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addExtraColumnsToGrid(Varien_Event_Observer $observer)
    {
        // Get the block
        $block = $observer->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Report_Shopcart_Abandoned_Grid) {
            $block->addColumnAfter(
                'abandoned_notified',
                array(
                    'header'    =>  Mage::helper('abandonedcarts')->__('Abandoned cart email sent'),
                    'index'     =>  'abandoned_notified',
                    'type'      =>  'options',
                    'options'   =>  array(
                        0   =>  Mage::helper('abandonedcarts')->__('No'),
                        1   =>  Mage::helper('abandonedcarts')->__('Yes')
                    )
                ),
                'remote_ip'
            );

            $block->addColumnAfter(
                'abandoned_sale_notified',
                array(
                    'header'    =>  Mage::helper('abandonedcarts')->__('Abandoned cart sale email sent'),
                    'index'     =>  'abandoned_sale_notified',
                    'type'      =>  'options',
                    'options'   =>  array(
                        0   =>  Mage::helper('abandonedcarts')->__('No'),
                        1   =>  Mage::helper('abandonedcarts')->__('Yes')
                    )
                ),
                'abandoned_notified'
            );
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addExtraColumnsToCollection(Varien_Event_Observer $observer)
    {
        // Get the collection
        $collection = $observer->getCollection();
        
        if ($collection instanceof Mage_Reports_Model_Resource_Quote_Collection
            && Mage::registry('abandonedcart_report')) {
            // Add the extra fields
            // Using columns() instead of addFieldToSelect seems to fix the ambiguous column error
            $collection->getSelect()->columns(
                array(
                    'abandoned_notified'    =>  'main_table.abandoned_notified',
                    'abandoned_sale_notified'    =>  'main_table.abandoned_sale_notified'
                )
            );
        } 
    }
}