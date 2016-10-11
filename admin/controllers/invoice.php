<?php

/**
 * Manage invoices
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

class Invoice extends BaseAdmin
{
    protected $oInvoiceModel;
    protected $oInvoiceItemModel;
    protected $oTaxModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return \stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:invoice:invoice:manage')) {

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('Invoices &amp; Payments');
            $oNavGroup->setIcon('fa-credit-card');
            $oNavGroup->addAction('Manage Invoices', 'index', array(), 0);

            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['manage'] = 'Can manage invoices';
        $permissions['create'] = 'Can create invoices';
        $permissions['edit']   = 'Can edit invoices';
        $permissions['delete'] = 'Can delete invoices';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        $this->oInvoiceModel     = Factory::model('Invoice', 'nailsapp/module-invoice');
        $this->oInvoiceItemModel = Factory::model('InvoiceItem', 'nailsapp/module-invoice');
        $this->oTaxModel         = Factory::model('Tax', 'nailsapp/module-invoice');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse invoices
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:invoice:invoice:manage')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Invoices';

        // --------------------------------------------------------------------------

        $sTableAlias = $this->oInvoiceModel->getTableAlias();

        //  Get pagination and search/sort variables
        $oInput    = Factory::service('Input');
        $page      = $oInput->get('page')      ? $oInput->get('page')      : 0;
        $perPage   = $oInput->get('perPage')   ? $oInput->get('perPage')   : 50;
        $sortOn    = $oInput->get('sortOn')    ? $oInput->get('sortOn')    : $sTableAlias . '.created';
        $sortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'desc';
        $keywords  = $oInput->get('keywords')  ? $oInput->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            $sTableAlias . '.created'  => 'Created Date',
            $sTableAlias . '.modified' => 'Modified Date',
            $sTableAlias . '.state'    => 'Invoice State',
        );

        // --------------------------------------------------------------------------

        //  Define the filters
        $aCbFilters = array();

        //  States
        $aStateOptions = array();
        $aStates       = $this->oInvoiceModel->getStates();

        foreach ($aStates as $sState => $sLabel) {
            $aStateOptions[] = array(
                $sLabel,
                $sState,
                true
            );
        }

        $aCbFilters[] = Helper::searchFilterObject(
            $sTableAlias . '.state',
            'State',
            $aStateOptions
        );

        //  Currencies
        $oCurrency        = Factory::service('Currency', 'nailsapp/module-currency');
        $aCurrencyOptions = array();
        $aCurrencies      = $oCurrency->getAllEnabled();

        foreach ($aCurrencies as $oCurrency) {
            $aCurrencyOptions[] = array(
                $oCurrency->code,
                $oCurrency->code,
                true
            );
        }

        $aCbFilters[] = Helper::searchFilterObject(
            $sTableAlias . '.currency',
            'Currency',
            $aCurrencyOptions
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'includeCustomer' => true,
            'includePayments' => true,
            'keywords'        => $keywords,
            'cbFilters'       => $aCbFilters
        );

        //  Get the items for the page
        $totalRows                   = $this->oInvoiceModel->countAll($data);
        $this->data['invoices']      = $this->oInvoiceModel->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords, $aCbFilters);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        //  Add a header button
        if (userHasPermission('admin:invoice:invoice:create')) {

            Helper::addHeaderButton(
                'admin/invoice/invoice/create',
                'Create Invoice'
            );
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new invoice
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:invoice:invoice:create')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = 'Create Invoice';

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            if ($this->validatePost()) {

                $oInvoice = $this->oInvoiceModel->create($this->getObjectFromPost(), true);

                if (!empty($oInvoice)) {

                    //  Send invoice if needed
                    $this->sendInvoice($oInvoice);

                    $oSession = Factory::service('Session', 'nailsapp/module-auth');
                    $oSession->set_flashdata('success', 'Invoice created successfully.');

                    redirect('admin/invoice/invoice/index');

                } else {

                    $this->data['error'] = 'Failed to create invoice. ' . $this->oInvoiceModel->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $aItemUnits = $this->oInvoiceItemModel->getUnits();
        $aTaxes     = $this->oTaxModel->getAll();

        $this->data['invoiceStates'] = $this->oInvoiceModel->getSelectableStates();

        // --------------------------------------------------------------------------

        //  Invoice Items
        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $aItems = $oInput->post('items') ?: array();
            //  Tidy up post data as expected by JS
            foreach ($aItems as &$aItem) {

                $aItem['label'] = html_entity_decode($aItem['label'], ENT_QUOTES, 'UTF-8');
                $aItem['body']  = html_entity_decode($aItem['body'], ENT_QUOTES, 'UTF-8');

                $sUnitCost                     = $aItem['unit_cost'];
                $aItem['unit_cost']            = new \stdClass();
                $aItem['unit_cost']->formatted = new \stdClass();
                $aItem['unit_cost']->formatted = !empty($sUnitCost) ? $sUnitCost : null;

                $aItem['tax'] = new \stdClass();
                $aItem['tax']->id = !empty($aItem['tax_id']) ? (int) $aItem['tax_id'] : null;

                $sUnit             = !empty($aItem['unit']) ? $aItem['unit'] : null;
                $aItem['unit']     = new \stdClass();
                $aItem['unit']->id = $sUnit;
            }

        } else {

            $aItems = array();
        }

        // --------------------------------------------------------------------------

        $oCurrency                = Factory::service('Currency', 'nailsapp/module-currency');
        $aCurrencies              = $oCurrency->getAllEnabled();
        $this->data['currencies'] = $aCurrencies;

        // --------------------------------------------------------------------------

        $oAsset = Factory::service('Asset');
        $oAsset->load('invoice.edit.min.js', 'nailsapp/module-invoice');
        $oAsset->inline(
            'ko.applyBindings(
                new invoiceEdit(
                    ' . json_encode($aItemUnits) . ',
                    ' . json_encode($aTaxes) . ',
                    ' . json_encode($aItems) . ',
                    ' . json_encode($aCurrencies) . '
                )
            );',
            'JS'
        );

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit an invoice
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:invoice:invoice:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri   = Factory::service('Uri');
        $oInput = Factory::service('Input');

        $iInvoiceId            = (int) $oUri->segment(5);
        $this->data['invoice'] = $this->oInvoiceModel->getById($iInvoiceId, array('includeAll' => true));

        if (!$this->data['invoice'] || $this->data['invoice']->state->id != 'DRAFT') {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = 'Edit Invoice &rsaquo; ' . $this->data['invoice']->ref;

        // --------------------------------------------------------------------------

        if ($oInput->post()) {

            if ($this->validatePost()) {

                if ($this->oInvoiceModel->update($this->data['invoice']->id, $this->getObjectFromPost())) {

                    //  Send invoice if needed
                    $oInvoice = $this->oInvoiceModel->getById($this->data['invoice']->id);
                    $this->sendInvoice($oInvoice);

                    $oSession = Factory::service('Session', 'nailsapp/module-auth');
                    $oSession->set_flashdata('success', 'Invoice was saved successfully.');

                    redirect('admin/invoice/invoice/index');

                } else {

                    $this->data['error'] = 'Failed to update invoice. ' . $this->oInvoiceModel->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $aItemUnits = $this->oInvoiceItemModel->getUnits();
        $aTaxes     = $this->oTaxModel->getAll();

        $this->data['invoiceStates'] = $this->oInvoiceModel->getSelectableStates();

        // --------------------------------------------------------------------------

        //  Invoice Items
        if ($oInput->post()) {

            $aItems = $oInput->post('items') ?: array();
            //  Tidy up post data as expected by JS
            foreach ($aItems as &$aItem) {

                $aItem['label'] = html_entity_decode($aItem['label'], ENT_QUOTES, 'UTF-8');
                $aItem['body']  = html_entity_decode($aItem['body'], ENT_QUOTES, 'UTF-8');

                $sUnitCost                     = $aItem['unit_cost'];
                $aItem['unit_cost']            = new \stdClass();
                $aItem['unit_cost']->formatted = new \stdClass();
                $aItem['unit_cost']->formatted = !empty($sUnitCost) ? $sUnitCost : null;

                $aItem['tax'] = new \stdClass();
                $aItem['tax']->id = !empty($aItem['tax_id']) ? (int) $aItem['tax_id'] : null;

                $sUnit             = !empty($aItem['unit']) ? $aItem['unit'] : null;
                $aItem['unit']     = new \stdClass();
                $aItem['unit']->id = $sUnit;
            }

        } else {

            $aItems = $this->data['invoice']->items->data;
        }

        // --------------------------------------------------------------------------

        $oCurrency                = Factory::service('Currency', 'nailsapp/module-currency');
        $aCurrencies              = $oCurrency->getAllEnabled();
        $this->data['currencies'] = $aCurrencies;

        // --------------------------------------------------------------------------

        $oAsset = Factory::service('Asset');
        $oAsset->load('invoice.edit.min.js', 'nailsapp/module-invoice');
        $oAsset->inline(
            'ko.applyBindings(
                new invoiceEdit(
                    ' . json_encode($aItemUnits) . ',
                    ' . json_encode($aTaxes) . ',
                    ' . json_encode($aItems) . ',
                    ' . json_encode($aCurrencies) . '
                )
            );',
            'JS'
        );

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * View an invoice
     * @return void
     */
    public function view()
    {
        if (!userHasPermission('admin:invoice:invoice:edit')) {
            unauthorised();
        }

        $oUri       = Factory::service('Uri');
        $iInvoiceId = (int) $oUri->segment(5);
        $this->data['invoice'] = $this->oInvoiceModel->getById($iInvoiceId, array('includeAll' => true));

        if (!$this->data['invoice'] || $this->data['invoice']->state->id == 'DRAFT') {
            show_404();
        }

        $this->data['page']->title = 'View Invoice &rsaquo; ' . $this->data['invoice']->ref;

        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.invoice.view.min.js', 'nailsapp/module-invoice');

        Helper::loadView('view');
    }

    // --------------------------------------------------------------------------

    /**
     * Validate the POST data
     * @return boolean
     */
    protected function validatePost()
    {
        $oFormValidation = Factory::service('FormValidation');

        $aRules = array(
            'ref'             => 'xss_clean|trim',
            'state'           => 'xss_clean|trim|required',
            'dated'           => 'xss_clean|trim|required|valid_date',
            'currency'        => 'xss_clean|trim|required|callback__callbackValidCurrency',
            'terms'           => 'xss_clean|trim|is_natural',
            'customer_id'     => 'xss_clean|trim',
            'additional_text' => 'xss_clean|trim',
            'items'           => ''
        );

        $aRulesFV = array();
        foreach ($aRules as $sKey => $sRules) {
            $aRulesFV[] = array(
                'field' => $sKey,
                'label' => '',
                'rules' => $sRules
            );
        }

        $oFormValidation->set_rules($aRulesFV);

        $oFormValidation->set_message('required', lang('fv_required'));
        $oFormValidation->set_message('valid_date', lang('fv_valid_date'));
        $oFormValidation->set_message('is_natural', lang('fv_is_natural'));
        $oFormValidation->set_message('valid_email', lang('fv_valid_email'));

        return $oFormValidation->run($this);
    }

    // --------------------------------------------------------------------------

    public function _callbackValidCurrency($sCode)
    {
        $oFormValidation = Factory::service('FormValidation');
        $oFormValidation->set_message('_callbackValidCurrency', 'Invalid currency.');

        $oCurrency = Factory::service('Currency', 'nailsapp/module-currency');
        $aEnabled  = $oCurrency->getAllEnabled();

        foreach ($aEnabled as $oCurrency) {
            if ($oCurrency->code === $sCode) {
                return true;
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an object generated from the POST data
     * @return array
     */
    protected function getObjectFromPost()
    {
        $oInput = Factory::service('Input');
        $oUri   = Factory::service('Uri');
        $aData = array(
            'ref'             => $oInput->post('ref') ?: null,
            'state'           => $oInput->post('state') ?: null,
            'dated'           => $oInput->post('dated') ?: null,
            'currency'        => $oInput->post('currency') ?: null,
            'terms'           => (int) $oInput->post('terms') ?: 0,
            'customer_id'     => (int) $oInput->post('customer_id') ?: null,
            'additional_text' => $oInput->post('additional_text') ?: null,
            'items'           => array(),
            'currency'        => $oInput->post('currency')
        );

        if ($oInput->post('items')) {
            foreach ($oInput->post('items') as $aItem) {

                //  @todo convert to pence using a model
                $aData['items'][] = array(
                    'id'        => array_key_exists('id', $aItem) ? $aItem['id'] : null,
                    'quantity'  => array_key_exists('quantity', $aItem) ? $aItem['quantity'] : null,
                    'unit'      => array_key_exists('unit', $aItem) ? $aItem['unit'] : null,
                    'label'     => array_key_exists('label', $aItem) ? $aItem['label'] : null,
                    'body'      => array_key_exists('body', $aItem) ? $aItem['body'] : null,
                    'unit_cost' => array_key_exists('unit_cost', $aItem) ? intval($aItem['unit_cost']*100) : null,
                    'tax_id'    => array_key_exists('tax_id', $aItem) ? $aItem['tax_id'] : null
                );
            }
        }

        if ($oUri->rsegment(5) == 'edit') {
            unset($aData['ref']);
        }

        return $aData;
    }

    // --------------------------------------------------------------------------

    protected function sendInvoice($oInvoice)
    {
        if (empty($oInvoice)) {
            return false;
        }

        $sInvoiceClass = get_class($this->oInvoiceModel);

        if ($oInvoice->state->id !== $sInvoiceClass::STATE_OPEN) {
            return false;
        }

        $oNow   = Factory::factory('DateTime');
        $oDated = new \DateTime($oInvoice->dated->raw);

        if ($oNow->format('Y-m-d') != $oDated->format('Y-m-d')) {
            return false;
        }

        return $this->oInvoiceModel->send($oInvoice->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Make an invoice a draft
     * @return void
     */
    public function make_draft()
    {
        if (!userHasPermission('admin:invoice:invoice:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri     = Factory::service('Uri');
        $oInvoice = $this->oInvoiceModel->getById($oUri->segment(5));
        if (!$oInvoice) {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Allow getting a constant
        $oInvoiceModel = $this->oInvoiceModel;

        $aData = array(
            'state' => $oInvoiceModel::STATE_DRAFT,
        );
        if ($this->oInvoiceModel->update($oInvoice->id, $aData)) {

            $sStatus  = 'success';
            $sMessage = 'Invoice updated successfully!';

        } else {

            $sStatus  = 'error';
            $sMessage = 'Invoice failed to update invoice. ' . $this->oInvoiceModel->lastError();
        }

        $oSession = Factory::service('Session', 'nailsapp/module-auth');
        $oSession->set_flashdata($sStatus, $sMessage);

        redirect('admin/invoice/invoice/edit/' . $oInvoice->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete an invoice
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:invoice:invoice:delete')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri     = Factory::service('Uri');
        $oInvoice = $this->oInvoiceModel->getById($oUri->segment(5));
        if (!$oInvoice) {
            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->oInvoiceModel->delete($oInvoice->id)) {

            $sStatus  = 'success';
            $sMessage = 'Invoice deleted successfully!';

        } else {

            $sStatus  = 'error';
            $sMessage = 'Invoice failed to delete. ' . $this->oInvoiceModel->lastError();
        }

        $oSession = Factory::service('Session', 'nailsapp/module-auth');
        $oSession->set_flashdata($sStatus, $sMessage);

        redirect('admin/invoice/invoice/index');
    }
}
