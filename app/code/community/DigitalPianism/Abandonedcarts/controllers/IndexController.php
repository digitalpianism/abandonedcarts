<?php

/**
 * Class DigitalPianism_Abandonedcarts_IndexController
 */
class DigitalPianism_Abandonedcarts_IndexController extends Mage_Core_Controller_Front_Action {

    /**
     *
     */
    public function indexAction()
    {
        // Get the token
        if ($token = $this->getRequest()->getParam('token')) {

            // Find corresponding hash entry in the database
            $link = Mage::getResourceModel('abandonedcarts/link_collection')
                ->addFieldToSelect(array('link_id','customer_email'))
                ->addFieldToFilter('token_hash', hash('sha256', $token))
                ->setPageSize(1);

            if ($link->getSize()) {

                $customerEmail = $link->getFirstItem()->getCustomerEmail();
                /**
                 * @TODO add an entry in the log
                 */
                // Delete so it's one use only
                $link->getFirstItem()->delete();

                /** @var Mage_Customer_Model_Customer $customer */
                $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());

                // Load the customer
                $customer->loadByEmail($customerEmail);

                // Log the customer in
                if ($customer->getId()) {
                    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                    Mage::getSingleton('customer/session')->renewSession();
                    // Redirects to cart
                    $this->_redirect('checkout/cart');
                }
            }
        }

        $this->_redirect('checkout/cart');
    }
}