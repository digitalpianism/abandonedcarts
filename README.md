# DigitalPianism_Abandonedcarts

Abandoned Carts Notification module for Magento

# Documentation

Everything you need to know can be found here: http://www.digital-pianism.com/en/magento-modules/abandoned-carts-notification.html

# Magento Connect

http://www.magentocommerce.com/magento-connect/abandoned-carts-notifications.html

# Release Notes

## 1.0.7
Thanks to Septoctobre for all the bug reports and pull requests

- Fix a bug where the delay would not be taken into consideration when the cron ran: https://github.com/digitalpianism/abandonedcarts/issues/17
- Fix a bug where the area would not be properly loaded: https://github.com/digitalpianism/abandonedcarts/issues/8 and https://github.com/digitalpianism/abandonedcarts/issues/7
- Fix a bug where the count of total would be wrong because of the quantity : https://github.com/digitalpianism/abandonedcarts/issues/13
- Implement prices columns with currencies : https://github.com/digitalpianism/abandonedcarts/issues/13
- Fix a bug where the sale abandoned carts report would display nothing when flat catalog is enabled : https://github.com/digitalpianism/abandonedcarts/issues/15

## 1.0.6
- Fix a bug where an error would be triggered when filtering the grid by one date (from OR to) : https://github.com/digitalpianism/abandonedcarts/issues/9
- Fix a bug where the count in the grid would be wrong: https://github.com/digitalpianism/abandonedcarts/issues/11

## 1.0.5
- Fix a bug where the small image would not be picked: https://github.com/digitalpianism/abandonedcarts/issues/3

## 1.0.4
- Fix a bug where the default email template would not be picked: https://github.com/digitalpianism/abandonedcarts/issues/4

## 1.0.3
- Fix a bug where it was impossible to preview the email templates in the backend

## 1.0.2
- Fix a bug where the admin URL would be used when notifying from the backend
- Fix a bug where admin users store would not remain on a multistore install

## 1.0.1
- Fix a bug where the data script would never run

## 1.0.0
- Full refactor of the module
- Add two grids to the backend to see the abandoned carts
- Add a log database table to easily see what's going on
- Implement an autologin link system
- Implement Google Campaign tags
- Improve the templates to list all items
- Change the way dryrun and test email behaves
- Add notification flags columns to the native abandoned carts report

## 0.3.6
- Fix a bug where an error would be logged if the product image was missing

## 0.3.5
- Fix a bug where the product image would not be retrieved in the email.

## 0.3.4
- Fix a bug where the email would not be reflect the right store when sharing customers account globally.
