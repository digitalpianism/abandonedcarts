<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Link_Cleaner
 */
class DigitalPianism_Abandonedcarts_Model_Link_Cleaner {

    /**
     *
     */
    public function cleanExpiredLinks()
    {
        $now = new Zend_Date(Mage::getModel('core/date')->timestamp());

        // Get the collection of links expired
        $collection = Mage::getResourceModel('abandonedcarts/link_collection')
            ->addFieldToSelect('link_id')
            ->addFieldToFilter('expiration_date', array(
                    'lteq'    =>    $now->toString('YYYY-MM-dd HH:mm:ss')
                )
            );

        if (!$collection->getSize())
            return;

        // Delete the expired links
        foreach ($collection as $expiredLink) {
            $expiredLink->delete();
        }
    }
}