<?php

/**
 * Migration:   12
 * Started:     23/07/2019
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Database\Migration;

use Nails\Common\Console\Migrate\Base;

class Migration12 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` ADD `sca_data` TEXT NULL AFTER `custom_data`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` CHANGE `fail_msg` `fail_msg` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;');
        $this->query('
            CREATE TABLE `{{NAILS_DB_PREFIX}}invoice_source` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) unsigned NOT NULL,
                `driver` varchar(150) DEFAULT NULL,
                `data` text,
                `label` varchar(150) DEFAULT NULL,
                `brand` varchar(150) DEFAULT NULL,
                `last_four` char(4) DEFAULT NULL,
                `expiry` date DEFAULT NULL,
                `is_default` tinyint(1) unsigned NOT NULL DEFAULT 0,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `customer_id` (`customer_id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}invoice_source_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `{{NAILS_DB_PREFIX}}invoice_customer` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}invoice_source_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}invoice_source_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        $this->query('DROP TABLE `{{NAILS_DB_PREFIX}}user_meta_invoice_card`;');
        $this->query('DROP TABLE `{{NAILS_DB_PREFIX}}user_meta_invoice_address`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` CHANGE `url_continue` `url_success` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` ADD `url_error` VARCHAR(255) NULL DEFAULT NULL AFTER `url_success`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` ADD `url_cancel` VARCHAR(255) NULL DEFAULT NULL AFTER `url_error`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` ADD `source_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `invoice_id`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` ADD FOREIGN KEY (`source_id`) REFERENCES `{{NAILS_DB_PREFIX}}invoice_source` (`id`) ON DELETE SET NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_invoice` ADD `payment_data` TEXT NULL AFTER `callback_data`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_invoice` ADD `payment_driver` VARCHAR(150) NULL DEFAULT NULL AFTER `payment_data`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` CHANGE `txn_id` `transaction_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_refund` CHANGE `txn_id` `transaction_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` ADD `customer_present` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `url_cancel`;');
    }
}
