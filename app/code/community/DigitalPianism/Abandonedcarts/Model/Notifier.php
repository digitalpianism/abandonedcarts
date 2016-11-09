<?php

/**
 * Class DigitalPianism_Abandonedcarts_Model_Notifier
 */
class DigitalPianism_Abandonedcarts_Model_Notifier extends Mage_Core_Model_Abstract
{
	const IMAGE_SIZE = 250;
	const CAMPAIGN_SOURCE = "abandonedcarts";
	const CAMPAIGN_MEDIUM = "email";
	/**
	 * Autologin links expiration in days
	 */
	const EXPIRATION = "2";

	/**
	 * @var array
	 */
	protected $_recipients = array();

	/**
	 * @var array
	 */
	protected $_saleRecipients = array();

	/**
	 * @var string
	 */
	protected $_today = "";

	/**
	 * @var array
	 */
	protected $_customerGroups = array();

	/**
	 * @var
	 */
	protected $_currentStoreId;

	/**
	 * @var
	 */
	protected $_originalStoreId;

	/**
	 * @throws Zend_Date_Exception
	 */
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

		$date = array(
			'year' => $year,
			'month' => $month,
			'day' => $day,
			'hour' => 23,
			'minute' => 59,
			'second' => 59
		);

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

	protected function _getProductImage($productId)
	{
		// Get product image via collection
		$_productCollection = Mage::getResourceModel('catalog/product_collection');
		// Add attributes to the collection
		$_productCollection->addAttributeToFilter('entity_id',array('eq' => $productId));
		// Add image to the collection
		$_productCollection->addAttributeToSelect('small_image');
		// Limit the collection to get the specific product
		$_productCollection->setPageSize(1);

		try {
			$productImg = (string)Mage::helper('catalog/image')->init($_productCollection->getFirstItem(), 'small_image')->resize(self::IMAGE_SIZE);
		} catch (Exception $e) {
			$productImg = false;
		}

		return $productImg;
	}

	/**
	 * @param $args
	 */
	public function generateRecipients($args)
	{
		// Customer group check
		if (array_key_exists('customer_group',$args['row'])
			&& !in_array($args['row']['customer_group'],$this->_customerGroups)) {
			return;
		}

		// Test if the customer is already in the array
		if (!array_key_exists($args['row']['customer_email'], $this->_recipients)) {
			// Create an array of variables to assign to template
			$emailTemplateVariables = array();

			// Array that contains the data which will be used inside the template
			$emailTemplateVariables['fullname'] = $args['row']['customer_firstname'].' '.$args['row']['customer_lastname'];
			$emailTemplateVariables['firstname'] = $args['row']['customer_firstname'];
			$emailTemplateVariables['productname'][] = $args['row']['product_name'];

			// Assign the values to the array of recipients
			$this->_recipients[$args['row']['customer_email']]['cartId'] = $args['row']['cart_id'];

			// Add product image
			$emailTemplateVariables['productimage'][] = $this->_getProductImage($args['row']['product_id']);

			// Add the link
			$token = "";
			// Autologin only applies to real customer (skip not logged in customer group)
			if (Mage::helper('abandonedcarts')->isAutologin()) {
				$token = urlencode($this->_generateToken($args['row']['customer_email']));
			}
			$emailTemplateVariables['link']	= $this->_generateUrl($token, $this->_currentStoreId);
		} else {
			// We create some extra variables if there is several products in the cart
			$emailTemplateVariables = $this->_recipients[$args['row']['customer_email']]['emailTemplateVariables'];
			// We increase the product count
			//$emailTemplateVariables['extraproductcount'] += 1;
			$emailTemplateVariables['productname'][] = $args['row']['product_name'];

			// Add product image
			$emailTemplateVariables['productimage'][] = $this->_getProductImage($args['row']['product_id']);
		}

		// Assign the array of template variables
		$this->_recipients[$args['row']['customer_email']]['emailTemplateVariables'] = $emailTemplateVariables;
		$this->_recipients[$args['row']['customer_email']]['store_id'] = $this->_currentStoreId;
	}

	/**
	 * @param $args
	 */
	public function generateSaleRecipients($args)
	{
		// Customer group check
		if (array_key_exists('customer_group',$args['row'])
			&& !in_array($args['row']['customer_group'],$this->_customerGroups)) {

			return;
		}

		// Double check if the special from date is set
		if (!array_key_exists('product_special_from_date',$args['row'])
			|| !$args['row']['product_special_from_date']) {

			// If not we use today for the comparison
			$fromDate = $this->_getToday();
		} else {
			$fromDate = $args['row']['product_special_from_date'];
		}

		// Do the same for the special to date
		if (!array_key_exists('product_special_to_date',$args['row'])
			|| !$args['row']['product_special_to_date']) {

			$toDate = $this->_getToday();
		} else {
			$toDate = $args['row']['product_special_to_date'];
		}

		// We need to ensure that the price in cart is higher than the new special price
		// As well as the date comparison in case the sale is over or hasn't started
		if ($args['row']['product_price_in_cart'] > 0.00
			&& $args['row']['product_special_price'] > 0.00
			&& ($args['row']['product_price_in_cart'] > $args['row']['product_special_price'])
			&& ($fromDate <= $this->_getToday())
			&& ($toDate >= $this->_getToday())) {

			// Test if the customer is already in the array
			if (!array_key_exists($args['row']['customer_email'], $this->_saleRecipients)) {

				// Create an array of variables to assign to template
				$emailTemplateVariables = array();

				// Array that contains the data which will be used inside the template
				$emailTemplateVariables['fullname'] = $args['row']['customer_firstname'].' '.$args['row']['customer_lastname'];
				$emailTemplateVariables['firstname'] = $args['row']['customer_firstname'];
				$emailTemplateVariables['productname'][] = $args['row']['product_name'];
				$emailTemplateVariables['cartprice'][] = Mage::helper('core')->currency(floatval(number_format(floatval($args['row']['product_price_in_cart']),2)), true, false);
				$emailTemplateVariables['specialprice'][] = Mage::helper('core')->currency(floatval(number_format(floatval($args['row']['product_special_price']),2)), true, false);

				// Assign the values to the array of recipients
				$this->_saleRecipients[$args['row']['customer_email']]['cartId'] = $args['row']['cart_id'];

				// Add product image
				$emailTemplateVariables['productimage'][] = $this->_getProductImage($args['row']['product_id']);

				// Add the link
				$token = "";
				// Autologin only applies to real customer (skip not logged in customer group)
				if (Mage::helper('abandonedcarts')->isAutologin()) {
					$token = urlencode($this->_generateToken($args['row']['customer_email']));
				}
				$emailTemplateVariables['link']	= $this->_generateUrl($token, $this->_currentStoreId);

				// If one product before
				$emailTemplateVariables['discount'] = number_format(floatval($args['row']['product_price_in_cart']),2) - number_format(floatval($args['row']['product_special_price']),2);
			} else {
				// We create some extra variables if there is several products in the cart
				$emailTemplateVariables = $this->_saleRecipients[$args['row']['customer_email']]['emailTemplateVariables'];
				// Discount amount
				// We add the discount on the second product
				$moreDiscount = number_format(floatval($args['row']['product_price_in_cart']),2) - number_format(floatval($args['row']['product_special_price']),2);
				$emailTemplateVariables['discount'] += $moreDiscount;

				$emailTemplateVariables['productname'][] = $args['row']['product_name'];
				$emailTemplateVariables['cartprice'][] = Mage::helper('core')->currency(floatval(number_format(floatval($args['row']['product_price_in_cart']),2)), true, false);
				$emailTemplateVariables['specialprice'][] = Mage::helper('core')->currency(floatval(number_format(floatval($args['row']['product_special_price']),2)), true, false);

				// Add product image
				$emailTemplateVariables['productimage'][] = $this->_getProductImage($args['row']['product_id']);
			}

			// Assign the array of template variables
			$this->_saleRecipients[$args['row']['customer_email']]['emailTemplateVariables'] = $emailTemplateVariables;
			$this->_saleRecipients[$args['row']['customer_email']]['store_id'] = $this->_currentStoreId;
		}
	}

	/**
	 * @param $dryrun
	 * @param $testemail
	 * @throws Exception
	 */
	protected function _sendSaleEmails($dryrun, $testemail)
	{
		// Send the emails via a loop
		foreach ($this->_getSaleRecipients() as $email => $recipient) {

			// Store Id
			Mage::app()->setCurrentStore($recipient['store_id']);
			// Get the transactional email template
			$templateId = Mage::getStoreConfig('abandonedcartsconfig/email/email_template_sale');
			// Get the sender
			$sender = array();
			$sender['email'] = Mage::getStoreConfig('abandonedcartsconfig/email/email');
			$sender['name'] = Mage::getStoreConfig('abandonedcartsconfig/email/name');
			$recipient['emailTemplateVariables']['email'] = $email;

			// Format discount with currency
			$recipient['emailTemplateVariables']['discount'] = Mage::helper('core')->currency($recipient['emailTemplateVariables']['discount'], true, false);

			// Don't send the email if dryrun is set
			if ($dryrun) {
				// If the test email is set and found
				if (isset($testemail)) {
					// Send to the test email
					Mage::getModel('core/email_template')
						->sendTransactional(
							$templateId,
							$sender,
							$testemail,
							$recipient['emailTemplateVariables']['fullname'] ,
							$recipient['emailTemplateVariables'],
							null);
				}
			} else {
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

			if (Mage::helper('abandonedcarts')->isLogEnabled()) {
				// Log the details
				$comment = sprintf(
					"Email sent to %s, product name: %s, cart price: %s, special price: %s, discount: %s, product image: %s, link: %s",
					$recipient['emailTemplateVariables']['fullname'],
					implode(',', $recipient['emailTemplateVariables']['productname']),
					implode(',', $recipient['emailTemplateVariables']['cartprice']),
					implode(',', $recipient['emailTemplateVariables']['specialprice']),
					$recipient['emailTemplateVariables']['discount'],
					implode(',', $recipient['emailTemplateVariables']['productimage']),
					$recipient['emailTemplateVariables']['link']
				);

				Mage::getModel('abandonedcarts/log')->setData(
					array(
						'customer_email'	=>	$email,
						'type'				=>	DigitalPianism_Abandonedcarts_Model_Log::TYPE_SALES,
						'comment'			=>	$comment,
						'store'				=>	$recipient['store_id'],
						'dryrun'			=>	$dryrun ? 1 : 0
					)
				)->save();
			}

			// Save only if dryrun is false
			if (!$dryrun) {

				// Load the quote
				$quote = Mage::getModel('sales/quote')->load($recipient['cartId']);

				// We change the notification attribute
				$quote->setAbandonedSaleNotified(1);

				$quote->getResource()->saveAttribute($quote,array('abandoned_sale_notified'));
			}
		}
	}

	/**
	 * @param $dryrun
	 * @param $testemail
	 * @throws Exception
	 */
	protected function _sendEmails($dryrun, $testemail)
	{
		// Send the emails via a loop
		foreach ($this->_getRecipients() as $email => $recipient) {

			// Store ID
			Mage::app()->setCurrentStore($recipient['store_id']);
			// Get the transactional email template
			$templateId = Mage::getStoreConfig('abandonedcartsconfig/email/email_template');
			// Get the sender
			$sender = array();
			$sender['email'] = Mage::getStoreConfig('abandonedcartsconfig/email/email');
			$sender['name'] = Mage::getStoreConfig('abandonedcartsconfig/email/name');
			$recipient['emailTemplateVariables']['email'] = $email;

			// Don't send the email if dryrun is set
			if ($dryrun) {
				// If the test email is set and found
				if (isset($testemail)) {
					// Send the email to the test email
					Mage::getModel('core/email_template')
						->sendTransactional(
							$templateId,
							$sender,
							$testemail,
							$recipient['emailTemplateVariables']['fullname'] ,
							$recipient['emailTemplateVariables'],
							null);
				}
			} else {
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

			if (Mage::helper('abandonedcarts')->isLogEnabled()) {
				// Log the details
				$comment = sprintf(
				//"Email sent to %s, product name: %s, product image: %s, extra product count: %s, link: %s",
					"Email sent to %s, product name: %s, product image: %s, link: %s",
					$recipient['emailTemplateVariables']['fullname'],
					implode(',', $recipient['emailTemplateVariables']['productname']),
					implode(',', $recipient['emailTemplateVariables']['productimage']) ? implode(',', $recipient['emailTemplateVariables']['productimage']) : "none",
					$recipient['emailTemplateVariables']['link']
				);

				Mage::getModel('abandonedcarts/log')->setData(
					array(
						'customer_email'	=>	$email,
						'type'				=>	DigitalPianism_Abandonedcarts_Model_Log::TYPE_NORMAL,
						'comment'			=>	$comment,
						'store'				=>	$recipient['store_id'],
						'dryrun'			=>	$dryrun ? 1 : 0
					)
				)->save();
			}

			// Save only if dryrun is false
			if (!$dryrun) {
				// Load the quote
				$quote = Mage::getModel('sales/quote')->load($recipient['cartId']);

				// We change the notification attribute
				$quote->setAbandonedNotified(1);

				$quote->getResource()->saveAttribute($quote,array('abandoned_notified'));
			}
		}
	}

	/**
	 * Send notification email to customer with abandoned cart containing sale products
	 * If dryrun is set to true, it won't send emails and won't alter quotes
	 * @param boolean
	 * @param string
	 */
	public function sendAbandonedCartsSaleEmail($dryrun = false, $testemail = null, $emails = array())
	{
		if (Mage::helper('abandonedcarts')->getDryRun()) {
			$dryrun = true;
		}

		if (Mage::helper('abandonedcarts')->getTestEmail()) {
			$testemail = Mage::helper('abandonedcarts')->getTestEmail();
		}

		// Set customer groups
		$this->_customerGroups = $this->_customerGroups ? $this->_customerGroups : Mage::helper('abandonedcarts')->getCustomerGroupsLimitation();
		// Original store id
		$this->_originalStoreId = Mage::app()->getStore()->getId();
		try
		{
			if (Mage::helper('abandonedcarts')->isSaleEnabled()) {

				$this->_setToday();

				// Loop through the stores
				foreach (Mage::app()->getWebsites() as $website) {
					// Get the website id
					$websiteId = $website->getWebsiteId();
					foreach ($website->getGroups() as $group) {
						$stores = $group->getStores();
						foreach ($stores as $store) {

							// Get the store id
							$storeId = $store->getStoreId();
							$this->_currentStoreId = $storeId;

							// Init the store to be able to load the quote and the collections properly
							Mage::app()->init($storeId,'store');

							// Get the collection
							$collection = Mage::getModel('abandonedcarts/collection')->getSalesCollection($storeId, $websiteId, $emails);

							//$collection->printlogquery(true,true);
							$collection->load();

							// Skip the rest of the code if the collection is empty
							if ($collection->getSize() == 0) {
								continue;
							}

							// Call iterator walk method with collection query string and callback method as parameters
							// Has to be used to handle massive collection instead of foreach
							Mage::getSingleton('core/resource_iterator')->walk($collection->getSelect(), array(array($this, 'generateSaleRecipients')));
						}
					}
				}
				$this->_sendSaleEmails($dryrun, $testemail);
			}
			Mage::app()->setCurrentStore($this->_originalStoreId);

			return count($this->_getSaleRecipients());
		}
		catch (Exception $e)
		{
			Mage::app()->setCurrentStore($this->_originalStoreId);
			Mage::helper('abandonedcarts')->log(sprintf("%s->Error: %s", __METHOD__, $e->getMessage()));
			return 0;
		}
	}

	/**
	 * Send notification email to customer with abandoned carts after the number of days specified in the config
	 * @param bool $nodate
	 * @param bool $dryrun
	 * @param string $testemail
	 * @internal param if $boolean dryrun is set to true, it won't send emails and won't alter quotes
	 */
	public function sendAbandonedCartsEmail($nodate = false, $dryrun = false, $testemail = null, $emails = array())
	{
		if (Mage::helper('abandonedcarts')->getDryRun()) {
			$dryrun = true;
		}

		if (Mage::helper('abandonedcarts')->getTestEmail()) {
			$testemail = Mage::helper('abandonedcarts')->getTestEmail();
		}

		// Set customer groups
		$this->_customerGroups = $this->_customerGroups ? $this->_customerGroups : Mage::helper('abandonedcarts')->getCustomerGroupsLimitation();
		// Original store id
		$this->_originalStoreId = Mage::app()->getStore()->getId();
		try
		{
			if (Mage::helper('abandonedcarts')->isEnabled()) {

				// Date handling
				$store = Mage_Core_Model_App::ADMIN_STORE_ID;
				$timezone = Mage::app()->getStore($store)->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
				date_default_timezone_set($timezone);

				// If the nodate parameter is set to false
				if (!$nodate) {
					// Get the delay provided and convert it to a proper date
					$delay = Mage::getStoreConfig('abandonedcartsconfig/options/notify_delay');
					$delay = date('Y-m-d H:i:s', time() - $delay * 24 * 3600);
				}  else	{
					// We create a date in the future to handle all abandoned carts
					$delay = date('Y-m-d H:i:s', strtotime("+7 day"));
				}

				// Loop through the stores
				foreach (Mage::app()->getWebsites() as $website) {
					// Get the website id
					$websiteId = $website->getWebsiteId();
					foreach ($website->getGroups() as $group) {
						$stores = $group->getStores();
						foreach ($stores as $store) {

							// Get the store id
							$storeId = $store->getStoreId();
							$this->_currentStoreId = $storeId;
							// Init the store to be able to load the quote and the collections properly
							Mage::app()->init($storeId, 'store');

							// Get the collection
							$collection = Mage::getModel('abandonedcarts/collection')->getCollection($delay, $storeId, $websiteId, $emails);

							//$collection->printlogquery(false,true);
							$collection->load();

							// Skip the rest of the code if the collection is empty
							if ($collection->getSize() == 0) {
								continue;
							}

							// Call iterator walk method with collection query string and callback method as parameters
							// Has to be used to handle massive collection instead of foreach
							Mage::getSingleton('core/resource_iterator')->walk($collection->getSelect(), array(array($this, 'generateRecipients')));
						}
					}
				}
				// Send the emails
				$this->_sendEmails($dryrun, $testemail);
			}
			Mage::app()->setCurrentStore($this->_originalStoreId);

			return count($this->_getRecipients());
		}
		catch (Exception $e)
		{
			Mage::app()->setCurrentStore($this->_originalStoreId);
			Mage::helper('abandonedcarts')->log(sprintf("%s->Error: %s", __METHOD__, $e->getMessage()));
			return 0;
		}
	}

	/**
	 * @return mixed|string
	 */
	protected function _generateUrl($token = "", $storeId = 0)
	{
		if (!Mage::helper('abandonedcarts')->isCampaignEnabled()) {
			return Mage::getUrl('abandonedcarts',
				array(
					'_store'	=>	$storeId,
					'_nosid'	=>	true,
					'_query'	=>	($token ? "token=" . $token : ''),
					'_secure'	=>	true
				)
			);
		}

		return Mage::getUrl('abandonedcarts', array(
				'_store'		=>	$storeId,
				'_nosid'		=>	true,
				'_query'		=>	"utm_source=" . self::CAMPAIGN_SOURCE . "&utm_medium=" . self::CAMPAIGN_MEDIUM . "&utm_campaign=" . Mage::helper('abandonedcarts')->getCampaignName() . ($token ? "&token=" . $token : ''),
				'_secure'		=>	true
			)
		);
	}

	/**
	 * @param $customerEmail
	 * @return string
	 */
	protected function _generateToken($customerEmail)
	{
		// Generate the token
		$token = openssl_random_pseudo_bytes(9, $cstrong);
		// Generate the token hash
		$hash = hash("sha256", $token);

		// Generate the expiration date
		$expiration = new Zend_Date(Mage::getModel('core/date')->timestamp());
		$expiration->addDay(self::EXPIRATION);

		// Create the autologin link
		Mage::getModel('abandonedcarts/link')->setData(
			array(
				'token_hash'		=>	$hash,
				'customer_email'	=>	$customerEmail,
				'expiration_date'	=>	$expiration->toString('YYYY-MM-dd HH:mm:ss')
			)
		)->save();

		return $token;
	}
}