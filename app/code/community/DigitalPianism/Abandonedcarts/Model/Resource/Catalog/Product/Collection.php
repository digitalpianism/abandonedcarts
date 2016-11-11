<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Resource_Catalog_Product_Collection
 */
class DigitalPianism_Abandonedcarts_Model_Resource_Catalog_Product_Collection
    extends Mage_Catalog_Model_Resource_Product_Collection {

    /**
     * @param null $select
     * @param bool $resetLeftJoins
     * @return Varien_Db_Select
     * @throws Zend_Db_Select_Exception
     */
    protected function _getSelectCountSql($select = null, $resetLeftJoins = true)
    {
        $this->_renderFilters();
        $countSelect = (is_null($select)) ?
            $this->_getClearSelect() :
            $this->_buildClearSelect($select);

        // Fix the count when group by
        if(count($this->getSelect()->getPart(Zend_Db_Select::GROUP)) > 0) {
            $countSelect->reset(Zend_Db_Select::GROUP);
            $countSelect->distinct(true);
            $group = $this->getSelect()->getPart(Zend_Db_Select::GROUP);
            $countSelect->columns("COUNT(DISTINCT ".implode(", ", $group).")");
        } else {
            $countSelect->columns('COUNT(*)');
        }

        if ($resetLeftJoins) {
            $countSelect->resetJoinLeft();
        }
        return $countSelect;
    }
}