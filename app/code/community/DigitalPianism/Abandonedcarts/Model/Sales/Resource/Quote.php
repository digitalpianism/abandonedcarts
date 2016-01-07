<?php

class DigitalPianism_Abandonedcarts_Model_Sales_Resource_Quote extends Mage_Sales_Model_Resource_Quote
{
    /**
     * Save attribute to model
     *
     * @param $model - the model
     * @param $attributes - array of attributes to get and save from model
     * @return $this
     * @throws Exception
     */
    public function saveAttribute($model, $attributes)
    {
        try {
            $adapter = $this->_getWriteAdapter();
            $adapter->beginTransaction();
            $condition = $this->_getWriteAdapter()->quoteInto($this->getIdFieldName() . '=?', $model->getId());
            $data      = array();
            foreach ($attributes as $attribute) {
                $value = $model->getData($attribute);
                if (isset($value)) {
                    $data[$attribute] = $value;
                }
            }
            if (!empty($data)) {
                $this->_getWriteAdapter()->update($this->getMainTable(), $data, $condition);
            }
            $adapter->commit();
        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }
        return $this;
    }
}