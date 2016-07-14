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
        return Mage::getSingleton('admin/session')->isAllowed('digitalpianism_menu/abandonedcarts');
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('digitalpianism_menu/abandonedcarts');

        return $this;
    }

    public function logsAction()
    {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('abandonedcarts/adminhtml_logs'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function indexAction()
    {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('abandonedcarts/adminhtml_abandonedcarts'));
        $this->renderLayout();
    }

    public function salegridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function saleAction()
    {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('abandonedcarts/adminhtml_saleabandonedcarts'));
        $this->renderLayout();
    }

    public function notifyAllAction()
    {
        try {
            $count = Mage::getModel('abandonedcarts/notifier')->sendAbandonedCartsEmail();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('abandonedcarts')->__(
                    '%sTotal of %d customer(s) were successfully notified', (Mage::helper('abandonedcarts')->getDryRun() ? "!DRY RUN! " : ""), $count
                )
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }

    public function notifySaleAllAction()
    {
        try {
            $count = Mage::getModel('abandonedcarts/notifier')->sendAbandonedCartsSaleEmail();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('abandonedcarts')->__(
                    '%sTotal of %d customer(s) were successfully notified', (Mage::helper('abandonedcarts')->getDryRun() ? "!DRY RUN! " : ""), $count
                )
            );
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/sale');
    }

    /**
     *
     */
    public function notifySaleAction()
    {
        $emails = $this->getRequest()->getParam('abandonedcarts');
        if (!is_array($emails)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('abandonedcarts')->__('Please select email(s)'));
        } else {
            try {
                Mage::getModel('abandonedcarts/notifier')->sendAbandonedCartsSaleEmail(false, false, $emails);
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('abandonedcarts')->__(
                        '%sTotal of %d customer(s) were successfully notified', (Mage::helper('abandonedcarts')->getDryRun() ? "!DRY RUN! " : ""), count($emails)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/sale');
    }

    /**
     *
     */
    public function notifyAction()
    {
        $emails = $this->getRequest()->getParam('abandonedcarts');
        if (!is_array($emails)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('abandonedcarts')->__('Please select email(s)'));
        } else {
            try {
                Mage::getModel('abandonedcarts/notifier')->sendAbandonedCartsEmail(false, false, null, $emails);
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('abandonedcarts')->__(
                        '%sTotal of %d customer(s) were successfully notified', (Mage::helper('abandonedcarts')->getDryRun() ? "!DRY RUN! " : ""), count($emails)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
}