<?php

$installer = $this;
$installer->startSetup();

/** @var Mage_Core_Model_Config $coreConfig */
$coreConfig = Mage::getModel('core/config');

if ($data = Mage::getStoreConfig('abandonedcartsconfig/options/name')) {
    $coreConfig->saveConfig('abandonedcartsconfig/email/name', $data);
}

if ($data = Mage::getStoreConfig('abandonedcartsconfig/options/email')) {
    $coreConfig->saveConfig('abandonedcartsconfig/email/email', $data);
}

if ($data = Mage::getStoreConfig('abandonedcartsconfig/options/email_template')) {
    $coreConfig->saveConfig('abandonedcartsconfig/email/email_template', $data);
}

if ($data = Mage::getStoreConfig('abandonedcartsconfig/options/email_template_sale')) {
    $coreConfig->saveConfig('abandonedcartsconfig/email/email_template_sale', $data);
}

if ($data = Mage::getStoreConfig('abandonedcartsconfig/options/dryrun')) {
    $coreConfig->saveConfig('abandonedcartsconfig/test/dryrun', $data);
}

if ($data = Mage::getStoreConfig('abandonedcartsconfig/options/testemail')) {
    $coreConfig->saveConfig('abandonedcartsconfig/test/testemail', $data);
}

$installer->endSetup();