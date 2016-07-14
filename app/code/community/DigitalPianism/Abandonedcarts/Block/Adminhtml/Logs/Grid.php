<?php

/**
 * Class DigitalPianism_Abandonedcarts_Block_Adminhtml_Logs_Grid
 */
class DigitalPianism_Abandonedcarts_Block_Adminhtml_Logs_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('abandonedcartLogsGrid');
        $this->setDefaultSort('log_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('abandonedcarts/log_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('customer_email', array(
            'header' => Mage::helper('abandonedcarts')->__('Customer Email'),
            'index' => 'customer_email',
        ));

        $this->addColumn('type', array(
            'header' => Mage::helper('abandonedcarts')->__('Type'),
            'index' => 'type',
            'type' => 'options',
            'options' => Mage::getModel('abandonedcarts/log')->toOptionArray()
        ));

        $this->addColumn('comment', array(
            'header' => Mage::helper('abandonedcarts')->__('Comment'),
            'index' => 'comment',
        ));

        $this->addColumn('store', array(
            'header' => Mage::helper('abandonedcarts')->__('Store #'),
            'index' => 'store',
        ));

        $this->addColumn('dryrun', array(
            'header' => Mage::helper('abandonedcarts')->__('Dry Run'),
            'type' => 'options',
            'index' => 'dryrun',
            'options' => array(
                0   =>  Mage::helper('abandonedcarts')->__("No"),
                1   =>  Mage::helper('abandonedcarts')->__("Yes")
            )
        ));

        // Output format for the start and end dates
        $outputFormat = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);

        $this->addColumn('added', array(
            'header' => Mage::helper('abandonedcarts')->__('Date'),
            'index' => 'added',
            'type' => 'datetime',
            'format' => $outputFormat,
            'default' => ' -- '
        ));

        return parent::_prepareColumns();
    }

}
