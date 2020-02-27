# Fraud Prevention Data Migration
Interactive script which can be used to migrate fraud records and blacklisting rules from Magento 1.x to Magento 2.x

## Prerequisites
Make sure that the Magento 1 and Magento 2 databases are on the same server, or that at least one of the databases is accessible remotely.

## Usage (Composer / M2)
1. Run `composer require plugincompany/fraud-prevention-migrate` in Magento 2 root directory
2. Run `php vendor/plugincompany/fraud-prevention-migrate/runMigration.php migrate` and follow instructions

## Usage (Git)
1. Clone this repository
2. Run `composer install` in the project directory
3. Run `php runMigration.php migrate` in project directory and follow instructions

