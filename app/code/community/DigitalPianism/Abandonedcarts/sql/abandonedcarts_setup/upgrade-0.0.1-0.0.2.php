<?php

$installer = $this;
$installer->startSetup();

// Add a notification column to the sales_flat_quote table 
$installer
	->getConnection()
	->addColumn(
		$this->getTable('sales/quote'), 'abandoned_sale_notified', 'tinyint(1) not null default 0'
	);

$installer->endSetup();
