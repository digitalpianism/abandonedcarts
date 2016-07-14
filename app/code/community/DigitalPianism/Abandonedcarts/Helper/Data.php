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
		return Mage::getStoreConfigFlag('abandonedcartsconfig/options/enable');
	}

	/**
	 * @return mixed
	 */
	public function isSaleEnabled()
	{
		return Mage::getStoreConfigFlag('abandonedcartsconfig/options/enable_sale');
	}

	/**
	 * @return mixed
	 */
	public function getDryRun()
	{
		return Mage::getStoreConfigFlag('abandonedcartsconfig/test/dryrun');
	}

	/**
	 * @return mixed
	 */
	public function getTestEmail()
	{
		return Mage::getStoreConfig('abandonedcartsconfig/test/testemail');
	}

	/**
	 * @return mixed
	 */
	public function getCustomerGroupsLimitation()
	{
		return explode(',',Mage::getStoreConfig('abandonedcartsconfig/options/customer_groups'));
	}

	/**
	 * @return bool
	 */
	public function isCampaignEnabled()
	{
		return Mage::getStoreConfigFlag('abandonedcartsconfig/campaign/enable');
	}

	/**
	 * @return mixed
	 */
	public function getCampaignName()
	{
		return Mage::getStoreConfig('abandonedcartsconfig/campaign/name');
	}

	/**
	 * @return bool
	 */
	public function isAutologin()
	{
		return Mage::getStoreConfigFlag('abandonedcartsconfig/email/autologin');
	}

	/**
	 * @return bool
	 */
	public function isLogEnabled()
	{
		return Mage::getStoreConfigFlag('abandonedcartsconfig/test/log');
	}

}