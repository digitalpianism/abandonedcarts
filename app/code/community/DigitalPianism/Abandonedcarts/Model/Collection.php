<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Collection
 */
class DigitalPianism_Abandonedcarts_Model_Collection {

    /**
     * @param $delay
     * @param $storeId
     * @param $websiteId
     * @param $emails
     * @return mixed
     */
    public function getCollection($delay, $storeId, $websiteId, $emails = array())
    {
        // Get the product collection
        $collection = Mage::getResourceModel('catalog/product_collection')->setStore($storeId);

        // Get the attribute id for the status attribute
        $eavAttribute = Mage::getModel('eav/entity_attribute');
        $statusId = $eavAttribute->getIdByCode('catalog_product', 'status');
        $nameId = $eavAttribute->getIdByCode('catalog_product', 'name');
        $priceId = $eavAttribute->getIdByCode('catalog_product', 'price');

        // Normal join condition
        $emailJoin = sprintf('quote_items.quote_id = quote_table.entity_id AND quote_table.items_count > 0 AND quote_table.is_active = 1 AND quote_table.customer_email IS NOT NULL AND quote_table.abandoned_notified = 0 AND quote_table.updated_at < "%s" AND quote_table.store_id = %s', $delay, $storeId);

        // In case an array of emails has been specified
        if (!empty($emails)) {
            $emailJoin = sprintf('%s AND quote_table.customer_email IN (%s)', $emailJoin, '"' . implode('", "', $emails) . '"');
        }

        // If flat catalog is enabled
        if (Mage::helper('catalog/product_flat')->isEnabled())
        {
            // First collection: carts with products that became on sale
            // Join the collection with the required tables
            $collection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('e.entity_id AS product_id',
                        'e.sku',
                        'catalog_flat.name as product_name',
                        'catalog_flat.price as product_price',
                        'quote_table.entity_id as cart_id',
                        'quote_table.updated_at as cart_updated_at',
                        'quote_table.abandoned_notified as has_been_notified',
                        'quote_table.customer_email as customer_email',
                        'quote_table.customer_firstname as customer_firstname',
                        'quote_table.customer_lastname as customer_lastname',
                        'customer.group_id as customer_group'
                    )
                )
                ->joinInner(
                    array('quote_items' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote_item')),
                    'quote_items.product_id = e.entity_id AND quote_items.price > 0.00',
                    null)
                ->joinInner(
                    array('quote_table' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote')),
                    $emailJoin,
                    null)
                ->joinInner(
                    array('catalog_flat' => Mage::getSingleton("core/resource")->getTableName('catalog_product_flat_'.$storeId)),
                    'catalog_flat.entity_id = e.entity_id',
                    null)
                ->joinInner(
                    array('catalog_enabled'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_int')),
                    'catalog_enabled.entity_id = e.entity_id AND catalog_enabled.attribute_id = '.$statusId.' AND catalog_enabled.value = 1',
                    null)
                ->joinInner(
                    array('inventory' => Mage::getSingleton("core/resource")->getTableName('cataloginventory_stock_status')),
                    'inventory.product_id = e.entity_id AND inventory.stock_status = 1 AND website_id = '.$websiteId,
                    null)
                ->joinInner(
                    array('customer' => Mage::getSingleton("core/resource")->getTableName('customer_entity')),
                    'quote_table.customer_email = customer.email',
                    null)
                ->order('quote_table.updated_at DESC');
        }
        else
        {
            // First collection: carts with products that became on sale
            // Join the collection with the required tables
            $collection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('e.entity_id AS product_id',
                        'e.sku',
                        'catalog_name.value as product_name',
                        'catalog_price.value as product_price',
                        'quote_table.entity_id as cart_id',
                        'quote_table.updated_at as cart_updated_at',
                        'quote_table.abandoned_notified as has_been_notified',
                        'quote_table.customer_email as customer_email',
                        'quote_table.customer_firstname as customer_firstname',
                        'quote_table.customer_lastname as customer_lastname',
                        'customer.group_id as customer_group'
                    )
                )
                // Name
                ->joinInner(
                    array('catalog_name'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_varchar')),
                    "catalog_name.entity_id = e.entity_id AND catalog_name.attribute_id = $nameId",
                    null)
                // Price
                ->joinInner(
                    array('catalog_price'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_decimal')),
                    "catalog_price.entity_id = e.entity_id AND catalog_price.attribute_id = $priceId",
                    null)
                ->joinInner(
                    array('quote_items' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote_item')),
                    'quote_items.product_id = e.entity_id AND quote_items.price > 0.00',
                    null)
                ->joinInner(
                    array('quote_table' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote')),
                    $emailJoin,
                    null)
                ->joinInner(
                    array('catalog_enabled'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_int')),
                    'catalog_enabled.entity_id = e.entity_id AND catalog_enabled.attribute_id = '.$statusId.' AND catalog_enabled.value = 1',
                    null)
                ->joinInner(
                    array('inventory' => Mage::getSingleton("core/resource")->getTableName('cataloginventory_stock_status')),
                    'inventory.product_id = e.entity_id AND inventory.stock_status = 1 AND website_id = '.$websiteId,
                    null)
                ->joinInner(
                    array('customer' => Mage::getSingleton("core/resource")->getTableName('customer_entity')),
                    'quote_table.customer_email = customer.email',
                    null)
                ->order('quote_table.updated_at DESC');
        }

        return $collection;
    }

    public function getSalesCollection($storeId, $websiteId, $emails = array())
    {
        // Get the product collection
        $collection = Mage::getResourceModel('catalog/product_collection')->setStore($storeId);

        // Get the attribute id for the status attribute
        $eavAttribute = Mage::getModel('eav/entity_attribute');
        $statusId = $eavAttribute->getIdByCode('catalog_product', 'status');
        $nameId = $eavAttribute->getIdByCode('catalog_product', 'name');
        $priceId = $eavAttribute->getIdByCode('catalog_product', 'price');
        $spriceId = $eavAttribute->getIdByCode('catalog_product', 'special_price');
        $spfromId = $eavAttribute->getIdByCode('catalog_product', 'special_from_date');
        $sptoId = $eavAttribute->getIdByCode('catalog_product', 'special_to_date');

        // Normal join condition
        $emailJoin = sprintf('quote_items.quote_id = quote_table.entity_id AND quote_table.items_count > 0 AND quote_table.is_active = 1 AND quote_table.customer_email IS NOT NULL AND quote_table.abandoned_sale_notified = 0 AND quote_table.store_id = %s', $storeId);

        // In case an array of emails has been specified
        if (!empty($emails)) {
            $emailJoin = sprintf('%s AND quote_table.customer_email IN (%s)', $emailJoin, '"' . implode('", "', $emails) . '"');
        }

        // If flat catalog is enabled
        if (Mage::helper('catalog/product_flat')->isEnabled())
        {
            // First collection: carts with products that became on sale
            // Join the collection with the required tables
            $collection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('e.entity_id AS product_id',
                        'e.sku',
                        'catalog_flat.name as product_name',
                        'catalog_flat.price as product_price',
                        'catalog_flat.special_price as product_special_price',
                        'catalog_flat.special_from_date as product_special_from_date',
                        'catalog_flat.special_to_date as product_special_to_date',
                        'quote_table.entity_id as cart_id',
                        'quote_table.updated_at as cart_updated_at',
                        'quote_table.abandoned_sale_notified as has_been_notified',
                        'quote_items.price as product_price_in_cart',
                        'quote_table.customer_email as customer_email',
                        'quote_table.customer_firstname as customer_firstname',
                        'quote_table.customer_lastname as customer_lastname',
                        'customer.group_id as customer_group'
                    )
                )
                ->joinInner(
                    array('quote_items' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote_item')),
                    'quote_items.product_id = e.entity_id AND quote_items.price > 0.00',
                    null)
                ->joinInner(
                    array('quote_table' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote')),
                    $emailJoin,
                    null)
                ->joinInner(
                    array('catalog_flat' => Mage::getSingleton("core/resource")->getTableName('catalog_product_flat_'.$storeId)),
                    'catalog_flat.entity_id = e.entity_id',
                    null)
                ->joinInner(
                    array('catalog_enabled'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_int')),
                    'catalog_enabled.entity_id = e.entity_id AND catalog_enabled.attribute_id = '.$statusId.' AND catalog_enabled.value = 1',
                    null)
                ->joinInner(
                    array('inventory' => Mage::getSingleton("core/resource")->getTableName('cataloginventory_stock_status')),
                    'inventory.product_id = e.entity_id AND inventory.stock_status = 1 AND inventory.website_id = '.$websiteId,
                    null)
                ->joinInner(
                    array('customer' => Mage::getSingleton("core/resource")->getTableName('customer_entity')),
                    'quote_table.customer_email = customer.email',
                    null)
                ->order('quote_table.updated_at DESC');
        }
        else
        {
            // First collection: carts with products that became on sale
            // Join the collection with the required tables
            $collection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('e.entity_id AS product_id',
                        'e.sku',
                        'catalog_name.value as product_name',
                        'catalog_price.value as product_price',
                        'catalog_sprice.value as product_special_price',
                        'catalog_spfrom.value as product_special_from_date',
                        'catalog_spto.value as product_special_to_date',
                        'quote_table.entity_id as cart_id',
                        'quote_table.updated_at as cart_updated_at',
                        'quote_table.abandoned_sale_notified as has_been_notified',
                        'quote_items.price as product_price_in_cart',
                        'quote_table.customer_email as customer_email',
                        'quote_table.customer_firstname as customer_firstname',
                        'quote_table.customer_lastname as customer_lastname',
                        'customer.group_id as customer_group'
                    )
                )
                // Name
                ->joinInner(
                    array('catalog_name'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_varchar')),
                    "catalog_name.entity_id = e.entity_id AND catalog_name.attribute_id = $nameId",
                    null)
                // Price
                ->joinInner(
                    array('catalog_price'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_decimal')),
                    "catalog_price.entity_id = e.entity_id AND catalog_price.attribute_id = $priceId",
                    null)
                // Special Price
                ->joinInner(
                    array('catalog_sprice'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_decimal')),
                    "catalog_sprice.entity_id = e.entity_id AND catalog_sprice.attribute_id = $spriceId",
                    null)
                // Special From Date
                ->joinInner(
                    array('catalog_spfrom'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_datetime')),
                    "catalog_spfrom.entity_id = e.entity_id AND catalog_spfrom.attribute_id = $spfromId",
                    null)
                // Special To Date
                ->joinInner(
                    array('catalog_spto'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_datetime')),
                    "catalog_spto.entity_id = e.entity_id AND catalog_spto.attribute_id = $sptoId",
                    null)
                ->joinInner(
                    array('quote_items' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote_item')),
                    'quote_items.product_id = e.entity_id AND quote_items.price > 0.00',
                    null)
                ->joinInner(
                    array('quote_table' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote')),
                    $emailJoin,
                    null)
                ->joinInner(
                    array('catalog_enabled'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_int')),
                    'catalog_enabled.entity_id = e.entity_id AND catalog_enabled.attribute_id = '.$statusId.' AND catalog_enabled.value = 1',
                    null)
                ->joinInner(
                    array('inventory' => Mage::getSingleton("core/resource")->getTableName('cataloginventory_stock_status')),
                    'inventory.product_id = e.entity_id AND inventory.stock_status = 1 AND inventory.website_id = '.$websiteId,
                    null)
                ->joinInner(
                    array('customer' => Mage::getSingleton("core/resource")->getTableName('customer_entity')),
                    'quote_table.customer_email = customer.email',
                    null)
                ->order('quote_table.updated_at DESC');
        }

        return $collection;
    }
}