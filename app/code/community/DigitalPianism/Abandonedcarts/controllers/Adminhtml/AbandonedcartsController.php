<?php

/**
 * Class DigitalPianism_Abandonedcarts_Adminhtml_AbandonedcartsController
 */
class DigitalPianism_Abandonedcarts_Adminhtml_AbandonedcartsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/abandonedcartsconfig');
    }

    /**
     * Manually send the notifications
     *
     * @return void
     */
    public function sendAction()
    {
		$model = Mage::getModel('abandonedcarts/observer');
		$model->sendAbandonedCartsEmail(true);
		$model->sendAbandonedCartsSaleEmail();
		
        $result = 1;
        Mage::app()->getResponse()->setBody($result);
    }
}