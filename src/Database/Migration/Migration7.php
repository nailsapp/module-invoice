<?php

/**
 * Migration:   7
 * Started:     30/01/2018
 * Finalised:   30/01/2018
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Database\Migration;

use Nails\Common\Console\Migrate\Base;

class Migration7 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` CHANGE `fail_code` `fail_code` VARCHAR(150) NULL DEFAULT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_refund` CHANGE `fail_code` `fail_code` VARCHAR(150) NULL DEFAULT NULL;");
    }
}
