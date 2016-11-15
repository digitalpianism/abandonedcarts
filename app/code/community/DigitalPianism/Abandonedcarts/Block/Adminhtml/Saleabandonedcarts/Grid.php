<?php

/**
 * Class DigitalPianism_Abandonedcarts_Block_Adminhtml_Saleabandonedcarts_Grid
 */
class DigitalPianism_Abandonedcarts_Block_Adminhtml_Saleabandonedcarts_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('saleabandonedcartsGrid');
        $this->setDefaultSort('cart_updated_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * @return mixed
     */
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        // Default store and website
        $defaults = $this->_getDefaultStoreAndWebsite();

        // Store and website from the multistore switcher
        $store = $this->_getStore();
        if ($storeId = $store->getId())
        {
            $defaults = array(
                $storeId,
                Mage::getModel('core/store')->load($storeId)->getWebsiteId()
            );
        }

        $collection = Mage::getModel('abandonedcarts/collection')->getSalesCollection($defaults[0], $defaults[1]);

        // Group by to have a nice grid
        $collection->getSelect()->group('customer_email');

        if (Mage::helper('catalog/product_flat')->isEnabled()) {
            $collection->getSelect()->columns(
                array(
                    'product_ids'   =>  'GROUP_CONCAT(e.entity_id)',
                    'product_names'   =>  'GROUP_CONCAT(catalog_flat.name)',
                    'product_prices'   =>  'SUM(catalog_flat.price * quote_items.qty)',
                    'product_special_prices'   =>  'SUM(IFNULL(catalog_flat.special_price,quote_items.price) * quote_items.qty)',
                )
            );

            $collection->getSelect()->having("SUM(quote_items.price) < SUM(IFNULL(catalog_flat.special_price,quote_items.price))");
        } else {
            $collection->getSelect()->columns(
                array(
                    'product_ids'   =>  'GROUP_CONCAT(e.entity_id)',
                    'product_names'   =>  'GROUP_CONCAT(catalog_name.value)',
                    'product_prices'   =>  'SUM(catalog_price.value * quote_items.qty)',
                    'product_special_prices'   =>  'SUM(IFNULL(catalog_sprice.value,quote_items.price) * quote_items.qty)',
                )
            );

            $collection->getSelect()->having("SUM(quote_items.price) > SUM(IFNULL(catalog_sprice.value,quote_items.price))");
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $currencyCode = $this->_getStore()->getCurrentCurrencyCode();
        
        $this->addColumn('customer_email', array(
            'header' => Mage::helper('abandonedcarts')->__('Customer Email'),
            'index' => 'customer_email',
            'filter_condition_callback'  =>  array($this, 'filterCallback')
        ));

        $this->addColumn('customer_firstname', array(
            'header' => Mage::helper('abandonedcarts')->__('Customer Firstname'),
            'index' => 'customer_firstname',
            'filter_condition_callback'  =>  array($this, 'filterCallback')
        ));

        $this->addColumn('customer_lastname', array(
            'header' => Mage::helper('abandonedcarts')->__('Customer Lastname'),
            'index' => 'customer_lastname',
            'filter_condition_callback'  =>  array($this, 'filterCallback')
        ));

        $this->addColumn('product_ids', array(
            'header' => Mage::helper('abandonedcarts')->__('Product Ids'),
            'index' => 'product_ids',
            'filter_index'  =>  "e.entity_id",
            'filter_condition_callback'  =>  array($this, 'filterEqualCallback')
        ));

        $this->addColumn('product_names', array(
            'header' => Mage::helper('abandonedcarts')->__('Product Names'),
            'index' => 'product_names',
            'filter_index'  =>  (Mage::helper('catalog/product_flat')->isEnabled() ? "catalog_flat.name" : "catalog_name.value"),
            'filter_condition_callback'  =>  array($this, 'filterEqualCallback')
        ));

        $this->addColumn('product_prices', array(
            'header' => Mage::helper('abandonedcarts')->__('Cart Regular Total'),
            'index' => 'product_prices',
            'type'      => 'price',
            'currency_code'  => $currencyCode,
            'filter'    => false
        ));

        $this->addColumn('product_special_prices', array(
            'header' => Mage::helper('abandonedcarts')->__('Cart Sale Total'),
            'index' => 'product_special_prices',
            'type'      => 'price',
            'currency_code'  => $currencyCode,
            'filter'    =>  false
        ));

        // Output format for the start and end dates
        $outputFormat = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);

        $this->addColumn('cart_updated_at', array(
            'header' => Mage::helper('abandonedcarts')->__('Cart Updated At'),
            'index' => 'cart_updated_at',
            'type'  => 'datetime',
            'format' => $outputFormat,
            'default' => ' -- ',
            'filter_index'  =>  'quote_table.updated_at',
            'filter_condition_callback' =>  array($this, 'filterDateCallback')
        ));

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('customer_email');
        $this->getMassactionBlock()->setFormFieldName('abandonedcarts');

        $this->getMassactionBlock()->addItem('notifySale', array(
            'label' => Mage::helper('abandonedcarts')->__('Send notification'),
            'url' => $this->getUrl('*/*/notifySale', array('store'  =>  $this->getRequest()->getParam('store', 0)))
        ));

        return $this;
    }

    /**
     * @return array
     */
    protected function _getDefaultStoreAndWebsite()
    {
        foreach (Mage::app()->getWebsites() as $website) {
            // Get the website id
            $websiteId = $website->getWebsiteId();
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {

                    // Get the store id
                    $storeId = $store->getStoreId();
                    break 3;
                }
            }
        }
        return array($storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/salegrid', array('current' => true, 'store'  =>  $this->getRequest()->getParam('store', 0)));
    }

    /**
     * @param $collection
     * @param $column
     */
    public function filterCallback($collection, $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $value = $column->getFilter()->getValue();
        $collection->getSelect()->where("$field like ?", '%' . $value . '%');
    }

    /**
     * @param $collection
     * @param $column
     */
    public function filterEqualCallback($collection, $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $value = $column->getFilter()->getValue();
        $collection->getSelect()->where("$field = ?", $value);
    }

    /**
     * @param $collection
     * @param $column
     */
    public function filterDateCallback($collection, $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $value = $column->getFilter()->getValue();

        $where = false;

        if (is_array($value) && array_key_exists('from', $value)) {
            $where = sprintf("%s > '%s'", $field, $value['from']->toString('Y-MM-dd HH:mm:ss'));
            if (array_key_exists('to', $value)) {
                $where .= sprintf(" AND %s < '%s'", $field, $value['to']->toString('Y-MM-dd HH:mm:ss'));
            }
        } elseif (is_array($value) && array_key_exists('to', $value)) {
            $where = sprintf("%s < '%s'", $field, $value['to']->toString('Y-MM-dd HH:mm:ss'));
        }

        if ($where) {
            $collection->getSelect()->where($where);
        }
    }
}
