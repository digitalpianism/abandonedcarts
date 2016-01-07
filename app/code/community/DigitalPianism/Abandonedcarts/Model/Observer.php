<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Observer
 */
class DigitalPianism_Abandonedcarts_Model_Observer extends Mage_Core_Model_Abstract
{

	protected $_recipients = array();
	protected $_saleRecipients = array();
	protected $_today = "";
	protected $_customerGroups = "";

	protected function _setToday()
	{
		// Date handling	
		$store = Mage_Core_Model_App::ADMIN_STORE_ID;
		$timezone = Mage::app()->getStore($store)->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
		date_default_timezone_set($timezone);

		// Current date
		$currentdate = date("Ymd");

		$day = (int)substr($currentdate,-2);
		$month = (int)substr($currentdate,4,2);
		$year = (int)substr($currentdate,0,4);

		$date = array('year' => $year,'month' => $month,'day' => $day,'hour' => 23,'minute' => 59,'second' => 59);

		$today = new Zend_Date($date);
		$today->setTimeZone("UTC");

		date_default_timezone_set($timezone);

		$this->_today = $today->toString("Y-MM-dd HH:mm:ss");
	}

    /**
     * @return string
     */
    protected function _getToday()
	{
		return $this->_today;
	}

    /**
     * @return array
     */
    protected function _getRecipients()
	{
		return $this->_recipients;
	}

    /**
     * @return array
     */
    protected function _getSaleRecipients()
	{
		return $this->_saleRecipients;
	}

    /**
     * @param $args
     */
    public function generateRecipients($args)
	{
		// Customer group check
		if (array_key_exists('customer_group',$args['row']) && !in_array($args['row']['customer_group'],$this->_customerGroups))
		{
			return;
		}

		// Test if the customer is already in the array
		if (!array_key_exists($args['row']['customer_email'], $this->_recipients))
		{
			// Create an array of variables to assign to template
			$emailTemplateVariables = array();

			// Array that contains the data which will be used inside the template
			$emailTemplateVariables['fullname'] = $args['row']['customer_firstname'].' '.$args['row']['customer_lastname'];
			$emailTemplateVariables['firstname'] = $args['row']['customer_firstname'];
			$emailTemplateVariables['productname'] = $args['row']['product_name'];

			// Assign the values to the array of recipients
			$this->_recipients[$args['row']['customer_email']]['cartId'] = $args['row']['cart_id'];

			// Get product image via collection
			$_productCollection = Mage::getResourceModel('catalog/product_collection');
			// Add attributes to the collection
			$_productCollection->addAttributeToFilter('entity_id',array('eq' => $args['row']['product_id']));
			// Add image to the collection
			$_productCollection->joinAttribute('image', 'catalog_product/image', 'entity_id', null, 'left');
			// Limit the collection to get the specific product
			$_productCollection->setPageSize(1);

			// Add product image
			$emailTemplateVariables['productimage'] = (string)Mage::helper('catalog/image')->init($_productCollection->getFirstItem(), 'image');

			$emailTemplateVariables['extraproductcount'] = 0;
		}
		else
		{
			// We create some extra variables if there is several products in the cart
			$emailTemplateVariables = $this->_recipients[$args['row']['customer_email']]['emailTemplateVariables'];
			// We increase the product count
			$emailTemplateVariables['extraproductcount'] += 1;
		}
		// Assign the array of template variables
		$this->_recipients[$args['row']['customer_email']]['emailTemplateVariables'] = $emailTemplateVariables;
	}

    /**
     * @param $args
     */
    public function generateSaleRecipients($args)
	{
		// Customer group check
		if (array_key_exists('customer_group',$args['row']) && !in_array($args['row']['customer_group'],$this->_customerGroups))
		{
			return;
		}

		// Double check if the special from date is set
		if (!array_key_exists('product_special_from_date',$args['row']) || !$args['row']['product_special_from_date'])
		{
			// If not we use today for the comparison
			$fromDate = $this->_getToday();
		}
		else $fromDate = $args['row']['product_special_from_date'];

		// Do the same for the special to date
		if (!array_key_exists('product_special_to_date',$args['row']) || !$args['row']['product_special_to_date'])
		{
			$toDate = $this->_getToday();
		}
		else $toDate = $args['row']['product_special_to_date'];

		// We need to ensure that the price in cart is higher than the new special price
		// As well as the date comparison in case the sale is over or hasn't started
		if ($args['row']['product_price_in_cart'] > 0.00
			&& $args['row']['product_special_price'] > 0.00
			&& ($args['row']['product_price_in_cart'] > $args['row']['product_special_price'])
			&& ($fromDate <= $this->_getToday())
			&& ($toDate >= $this->_getToday()))
		{

			// Test if the customer is already in the array
			if (!array_key_exists($args['row']['customer_email'], $this->_saleRecipients))
			{
				// Create an array of variables to assign to template
				$emailTemplateVariables = array();

				// Array that contains the data which will be used inside the template
				$emailTemplateVariables['fullname'] = $args['row']['customer_firstname'].' '.$args['row']['customer_lastname'];
				$emailTemplateVariables['firstname'] = $args['row']['customer_firstname'];
				$emailTemplateVariables['productname'] = $args['row']['product_name'];
				$emailTemplateVariables['cartprice'] = number_format($args['row']['product_price_in_cart'],2);
				$emailTemplateVariables['specialprice'] = number_format($args['row']['product_special_price'],2);

				// Assign the values to the array of recipients
				$this->_saleRecipients[$args['row']['customer_email']]['cartId'] = $args['row']['cart_id'];

				// Get product image via collection
				$_productCollection = Mage::getResourceModel('catalog/product_collection');
				// Add attributes to the collection
				$_productCollection->addAttributeToFilter('entity_id',array('eq' => $args['row']['product_id']));
				// Add image to the collection
				$_productCollection->joinAttribute('image', 'catalog_product/image', 'entity_id', null, 'left');
				// Limit the collection to get the specific product
				$_productCollection->setPageSize(1);

				// Add product image
				$emailTemplateVariables['productimage'] = (string)Mage::helper('catalog/image')->init($_productCollection->getFirstItem(), 'image');
			}
			else
			{
				// We create some extra variables if there is several products in the cart
				$emailTemplateVariables = $this->_saleRecipients[$args['row']['customer_email']]['emailTemplateVariables'];
				// Discount amount
				// If one product before
				if (!array_key_exists('discount',$emailTemplateVariables))
				{
					$emailTemplateVariables['discount'] = $emailTemplateVariables['cartprice'] - $emailTemplateVariables['specialprice'];
				}
				// We add the discount on the second product
				$moreDiscount = number_format($args['row']['product_price_in_cart'],2) - number_format($args['row']['product_special_price'],2);
				$emailTemplateVariables['discount'] += $moreDiscount;
				// We increase the product count
				if (!array_key_exists('extraproductcount',$emailTemplateVariables))
				{
					$emailTemplateVariables['extraproductcount'] = 0;
				}
				$emailTemplateVariables['extraproductcount'] += 1;
			}

			// Add currency codes to prices
			$emailTemplateVariables['cartprice'] = Mage::helper('core')->currency($emailTemplateVariables['cartprice'], true, false);
			$emailTemplateVariables['specialprice'] = Mage::helper('core')->currency($emailTemplateVariables['specialprice'], true, false);
			if (array_key_exists('discount',$emailTemplateVariables))
			{
				$emailTemplateVariables['discount'] = Mage::helper('core')->currency($emailTemplateVariables['discount'], true, false);
			}

			// Assign the array of template variables
			$this->_saleRecipients[$args['row']['customer_email']]['emailTemplateVariables'] = $emailTemplateVariables;
		}
	}

    /**
     * @param $dryrun
     * @param $testemail
     */
    protected function _sendSaleEmails($dryrun,$testemail)
	{
		try
		{
			// Get the transactional email template
			$templateId = Mage::getStoreConfig('abandonedcartsconfig/options/email_template_sale');
			// Get the sender
			$sender = array();
			$sender['email'] = Mage::getStoreConfig('abandonedcartsconfig/options/email');
			$sender['name'] = Mage::getStoreConfig('abandonedcartsconfig/options/name');

			// Send the emails via a loop
			foreach ($this->_getSaleRecipients() as $email => $recipient)
			{
				// Don't send the email if dryrun is set
				if ($dryrun)
				{
					// Log data when dried run
					Mage::helper('abandonedcarts')->log(__METHOD__);
					Mage::helper('abandonedcarts')->log($recipient['emailTemplateVariables']);
					// If the test email is set and found
					if (isset($testemail) && $email == $testemail)
					{
						Mage::helper('abandonedcarts')->log(__METHOD__ . "sendAbandonedCartsSaleEmail test: " . $email);
						// Send the test email
						Mage::getModel('core/email_template')
								->sendTransactional(
										$templateId,
										$sender,
										$email,
										$recipient['emailTemplateVariables']['fullname'] ,
										$recipient['emailTemplateVariables'],
										null);
					}
				}
				else
				{
					Mage::helper('abandonedcarts')->log(__METHOD__ . "sendAbandonedCartsSaleEmail: " . $email);

					// Send the email
					Mage::getModel('core/email_template')
							->sendTransactional(
									$templateId,
									$sender,
									$email,
									$recipient['emailTemplateVariables']['fullname'] ,
									$recipient['emailTemplateVariables'],
									null);
				}

				// Load the quote
				$quote = Mage::getModel('sales/quote')->load($recipient['cartId']);

				// We change the notification attribute
				$quote->setAbandonedSaleNotified(1);

				// Save only if dryrun is false or if the test email is set and found
				if (!$dryrun || (isset($testemail) && $email == $testemail))
				{
                    $quote->getResource()->saveAttribute($quote,array('abandoned_sale_notified'));
				}
			}
		}
		catch (Exception $e)
		{
			Mage::helper('abandonedcarts')->log(__METHOD__ . " " . $e->getMessage());
		}
	}

    /**
     * @param $dryrun
     * @param $testemail
     */
    protected function _sendEmails($dryrun,$testemail)
	{
		try
		{
			// Get the transactional email template
			$templateId = Mage::getStoreConfig('abandonedcartsconfig/options/email_template');
			// Get the sender
			$sender = array();
			$sender['email'] = Mage::getStoreConfig('abandonedcartsconfig/options/email');
			$sender['name'] = Mage::getStoreConfig('abandonedcartsconfig/options/name');

			// Send the emails via a loop
			foreach ($this->_getRecipients() as $email => $recipient)
			{
				// Don't send the email if dryrun is set
				if ($dryrun)
				{
					// Log data when dried run
					Mage::helper('abandonedcarts')->log(__METHOD__);
					Mage::helper('abandonedcarts')->log($recipient['emailTemplateVariables']);
					// If the test email is set and found
					if (isset($testemail) && $email == $testemail)
					{
						Mage::helper('abandonedcarts')->log(__METHOD__ . "sendAbandonedCartsEmail test: " . $email);
						// Send the test email
						Mage::getModel('core/email_template')
								->sendTransactional(
										$templateId,
										$sender,
										$email,
										$recipient['emailTemplateVariables']['fullname'] ,
										$recipient['emailTemplateVariables'],
										null);
					}
				}
				else
				{
					Mage::helper('abandonedcarts')->log(__METHOD__ . "sendAbandonedCartsEmail: " . $email);

					// Send the email
					Mage::getModel('core/email_template')
							->sendTransactional(
									$templateId,
									$sender,
									$email,
									$recipient['emailTemplateVariables']['fullname'] ,
									$recipient['emailTemplateVariables'],
									null);
				}

				// Load the quote
				$quote = Mage::getModel('sales/quote')->load($recipient['cartId']);

				// We change the notification attribute
				$quote->setAbandonedNotified(1);

				// Save only if dryrun is false or if the test email is set and found
				if (!$dryrun || (isset($testemail) && $email == $testemail))
				{
                    $quote->getResource()->saveAttribute($quote,array('abandoned_notified'));
				}
			}
		}
		catch (Exception $e)
		{
			Mage::helper('abandonedcarts')->log(__METHOD__ . " " . $e->getMessage());
		}
	}

	/**
	 * Send notification email to customer with abandoned cart containing sale products
	 * @param boolean $dryrun if dryrun is set to true, it won't send emails and won't alter quotes
	 * @param string $testemail email to test
	 */
	public function sendAbandonedCartsSaleEmail($dryrun = false, $testemail = null)
	{
		try
		{
			if (Mage::helper('abandonedcarts')->getDryRun()) $dryrun = true;
			if (Mage::helper('abandonedcarts')->getTestEmail()) $testemail = Mage::helper('abandonedcarts')->getTestEmail();
			// Set customer groups
			$this->_customerGroups = $this->_customerGroups ? $this->_customerGroups : Mage::helper('abandonedcarts')->getCustomerGroupsLimitation();

			if (Mage::helper('abandonedcarts')->isSaleEnabled())
			{
				$this->_setToday();

				// Get the attribute id for the status attribute
				$eavAttribute = Mage::getModel('eav/entity_attribute');
				$statusId = $eavAttribute->getIdByCode('catalog_product', 'status');
				$nameId = $eavAttribute->getIdByCode('catalog_product', 'name');
				$priceId = $eavAttribute->getIdByCode('catalog_product', 'price');
				$spriceId = $eavAttribute->getIdByCode('catalog_product', 'special_price');
				$spfromId = $eavAttribute->getIdByCode('catalog_product', 'special_from_date');
				$sptoId = $eavAttribute->getIdByCode('catalog_product', 'special_to_date');

				// Loop through the stores
				foreach (Mage::app()->getWebsites() as $website) {
					// Get the website id
					$websiteId = $website->getWebsiteId();
					foreach ($website->getGroups() as $group) {
						$stores = $group->getStores();
						foreach ($stores as $store) {

							// Get the store id
							$storeId = $store->getStoreId();

							// Init the store to be able to load the quote and the collections properly
							Mage::app()->init($storeId,'store');

							// Get the product collection
							$collection = Mage::getResourceModel('catalog/product_collection')->setStore($storeId);

							// Database TableNams
							$eavEntityType = Mage::getSingleton("core/resource")->getTableName('eav_entity_type');
							$eavAttribute = Mage::getSingleton("core/resource")->getTableName('eav_attribute');

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
													'quote_table.customer_group_id as customer_group'
													)
												)
									->joinInner(
										array('quote_items' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote_item')),
										'quote_items.product_id = e.entity_id AND quote_items.price > 0.00',
										null)
									->joinInner(
										array('quote_table' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote')),
										'quote_items.quote_id = quote_table.entity_id AND quote_table.items_count > 0 AND quote_table.is_active = 1 AND quote_table.customer_email IS NOT NULL AND quote_table.abandoned_sale_notified = 0 AND quote_table.store_id = '.$storeId,
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
													'quote_table.customer_group_id as customer_group'
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
										'quote_items.quote_id = quote_table.entity_id AND quote_table.items_count > 0 AND quote_table.is_active = 1 AND quote_table.customer_email IS NOT NULL AND quote_table.abandoned_sale_notified = 0 AND quote_table.store_id = '.$storeId,
										null)
									->joinInner(
										array('catalog_enabled'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_int')),
										'catalog_enabled.entity_id = e.entity_id AND catalog_enabled.attribute_id = '.$statusId.' AND catalog_enabled.value = 1',
										null)
									->joinInner(
										array('inventory' => Mage::getSingleton("core/resource")->getTableName('cataloginventory_stock_status')),
										'inventory.product_id = e.entity_id AND inventory.stock_status = 1 AND inventory.website_id = '.$websiteId,
										null)
									->order('quote_table.updated_at DESC');
							}

							//$collection->printlogquery(true,true);
							$collection->load();

							// Skip the rest of the code if the collection is empty
							if ($collection->getSize() == 0)    continue;

							// Call iterator walk method with collection query string and callback method as parameters
							// Has to be used to handle massive collection instead of foreach
							Mage::getSingleton('core/resource_iterator')->walk($collection->getSelect(), array(array($this, 'generateSaleRecipients')));
						}
					}
				}

				// Send the emails
				$this->_sendSaleEmails($dryrun,$testemail);
			}
		}
		catch (Exception $e)
		{
			Mage::helper('abandonedcarts')->log(__METHOD__ . " " . $e->getMessage());
		}
	}

    /**
     * Send notification email to customer with abandoned carts after the number of days specified in the config
     * @param bool $nodate
     * @param boolean $dryrun if dryrun is set to true, it won't send emails and won't alter quotes
     * @param string $testemail email to test
     */
	public function sendAbandonedCartsEmail($nodate = false, $dryrun = false, $testemail = null)
	{
		if (Mage::helper('abandonedcarts')->getDryRun()) $dryrun = true;
		if (Mage::helper('abandonedcarts')->getTestEmail()) $testemail = Mage::helper('abandonedcarts')->getTestEmail();
		// Set customer groups
		$this->_customerGroups = $this->_customerGroups ? $this->_customerGroups : Mage::helper('abandonedcarts')->getCustomerGroupsLimitation();

		try
		{
			if (Mage::helper('abandonedcarts')->isEnabled())
			{
				// Date handling
				$store = Mage_Core_Model_App::ADMIN_STORE_ID;
				$timezone = Mage::app()->getStore($store)->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
				date_default_timezone_set($timezone);

				// If the nodate parameter is set to false
				if (!$nodate)
				{
					// Get the delay provided and convert it to a proper date
					$delay = Mage::getStoreConfig('abandonedcartsconfig/options/notify_delay');
					$delay = date('Y-m-d H:i:s', time() - $delay * 24 * 3600);
				}
				else
				{
					// We create a date in the future to handle all abandoned carts
					$delay = date('Y-m-d H:i:s', strtotime("+7 day"));
				}

				// Get the attribute id for several attributes
				$eavAttribute = Mage::getModel('eav/entity_attribute');
				$statusId = $eavAttribute->getIdByCode('catalog_product', 'status');
				$nameId = $eavAttribute->getIdByCode('catalog_product', 'name');
				$priceId = $eavAttribute->getIdByCode('catalog_product', 'price');

				// Loop through the stores
				foreach (Mage::app()->getWebsites() as $website) {
					// Get the website id
					$websiteId = $website->getWebsiteId();
					foreach ($website->getGroups() as $group) {
						$stores = $group->getStores();
						foreach ($stores as $store) {

							// Get the store id
							$storeId = $store->getStoreId();
							// Init the store to be able to load the quote and the collections properly
							Mage::app()->init($storeId,'store');

							// Get the product collection
							$collection = Mage::getResourceModel('catalog/product_collection')->setStore($storeId);

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
													'quote_table.customer_group_id as customer_group'
													)
												)
									->joinInner(
										array('quote_items' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote_item')),
										'quote_items.product_id = e.entity_id AND quote_items.price > 0.00',
										null)
									->joinInner(
										array('quote_table' => Mage::getSingleton("core/resource")->getTableName('sales_flat_quote')),
										'quote_items.quote_id = quote_table.entity_id AND quote_table.items_count > 0 AND quote_table.is_active = 1 AND quote_table.customer_email IS NOT NULL AND quote_table.abandoned_notified = 0 AND quote_table.updated_at < "'.$delay.'" AND quote_table.store_id = '.$storeId,
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
													'quote_table.customer_group_id as customer_group'
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
										'quote_items.quote_id = quote_table.entity_id AND quote_table.items_count > 0 AND quote_table.is_active = 1 AND quote_table.customer_email IS NOT NULL AND quote_table.abandoned_notified = 0 AND quote_table.updated_at < "'.$delay.'" AND quote_table.store_id = '.$storeId,
										null)
									->joinInner(
										array('catalog_enabled'	=>	Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_int')),
										'catalog_enabled.entity_id = e.entity_id AND catalog_enabled.attribute_id = '.$statusId.' AND catalog_enabled.value = 1',
										null)
									->joinInner(
										array('inventory' => Mage::getSingleton("core/resource")->getTableName('cataloginventory_stock_status')),
										'inventory.product_id = e.entity_id AND inventory.stock_status = 1 AND website_id = '.$websiteId,
										null)
									->order('quote_table.updated_at DESC');
							}

							//$collection->printlogquery(true,true);
							$collection->load();

							// Skip the rest of the code if the collection is empty
							if ($collection->getSize() == 0)    continue;

							// Call iterator walk method with collection query string and callback method as parameters
							// Has to be used to handle massive collection instead of foreach
							Mage::getSingleton('core/resource_iterator')->walk($collection->getSelect(), array(array($this, 'generateRecipients')));
						}
					}
				}

				// Send the emails
				$this->_sendEmails($dryrun,$testemail);
			}
		}
		catch (Exception $e)
		{
			Mage::helper('abandonedcarts')->log(__METHOD__ . " " . $e->getMessage());
		}
	}
}