<?php

/**
 * This class registers some handlers for Invoicing & Payment settings
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Invoice;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Invoice\Controller\BaseAdmin;

class Settings extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
        $oNavGroup->setLabel('Settings');
        $oNavGroup->setIcon('fa-wrench');

        if (userHasPermission('admin:invoice:settings:*')) {
            $oNavGroup->addAction('Invoices &amp; Payments');
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['misc']   = 'Can update miscallaneous settings';
        $permissions['driver'] = 'Can update driver settings';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Email settings
     * @return void
     */
    public function index()
    {
        //  Process POST
        if ($this->input->post()) {

            $aSettings = array(

                //  General Settings
                'saved_cards_enabled'     => (bool) $this->input->post('saved_cards_enabled'),
                'saved_addresses_enabled' => (bool) $this->input->post('saved_addresses_enabled'),

                //  Payment Drivers
                'enabled_payment_drivers' => $this->input->post('enabled_payment_drivers') ?: array(),
            );

            $aSettingsEncrypted = array(
            );

            // --------------------------------------------------------------------------

            //  Validation
            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules('enabled_payment_drivers', '', '');

            if ($oFormValidation->run()) {

                $oDb = Factory::service('Database');

                $oDb->trans_begin();
                $bRollback = false;

                //  Normal settings
                if (!$this->app_setting_model->set($aSettings, 'nailsapp/module-invoice')) {

                    $sError    = $this->app_setting_model->lastError();
                    $bRollback = true;
                }

                //  Encrypted settings
                if (!$this->app_setting_model->set($aSettingsEncrypted, 'nailsapp/module-invoice', null, true)) {

                    $sError    = $this->app_setting_model->lastError();
                    $bRollback = true;
                }

                if (empty($bRollback)) {

                    $oDb->trans_commit();
                    $this->data['success'] = 'Invoice &amp; Payment settings were saved.';

                } else {

                    $oDb->trans_rollback();
                    $this->data['error'] = 'There was a problem saving settings. ' . $sError;
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['settings'] = appSetting(null, 'nailsapp/module-invoice', true);

        //  Payment drivers
        $oDriverModel                          = Factory::model('PaymentDriver', 'nailsapp/module-invoice');
        $this->data['payment_drivers']         = $oDriverModel->getAll();
        $this->data['payment_drivers_enabled'] = $oDriverModel->getEnabledSlugs();

        Helper::loadView('index');
    }
}
