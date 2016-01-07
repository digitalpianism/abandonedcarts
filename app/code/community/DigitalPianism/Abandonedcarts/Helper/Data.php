<?php

/**
 * Class DigitalPianism_Abandonedcarts_Helper_Data
 */
class DigitalPianism_Abandonedcarts_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $logFileName = 'digitalpianism_abandonedcarts.log';
	
	/**
	 * Log data
	 * @param string|object|array data to log
	 */
	public function log($data) 
	{
		Mage::log($data, null, $this->logFileName);
	}

    /**
     * @return mixed
     */
    public function isEnabled()
	{
		return Mage::getStoreConfig('abandonedcartsconfig/options/enable');
	}

    /**
     * @return mixed
     */
    public function isSaleEnabled()
	{
		return Mage::getStoreConfig('abandonedcartsconfig/options/enable_sale');
	}

    /**
     * @return mixed
     */
    public function getDryRun()
	{
		return Mage::getStoreConfig('abandonedcartsconfig/options/dryrun');
	}

    /**
     * @return mixed
     */
    public function getTestEmail()
	{
		return Mage::getStoreConfig('abandonedcartsconfig/options/testemail');
	}

    /**
     * @return mixed
     */
    public function getCustomerGroupsLimitation()
	{
		return explode(',',Mage::getStoreConfig('abandonedcartsconfig/options/customer_groups'));
	}

}