<?php

/**
 * Manages customer entities
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Model;

use Nails\Factory;
use Nails\Common\Model\Base;

class Customer extends Base
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();
        $this->table             = NAILS_DB_PREFIX . 'invoice_customer';
        $this->tablePrefix       = 'c';
        $this->defaultSortColumn = 'first_name';
        $this->destructiveDelete = false;
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve all customers from the databases
     * @param  int     $iPage           The page number to return
     * @param  int     $iPerPage        The number of results per page
     * @param  array   $aData           Data to pass _to getcount_common()
     * @param  boolean $bIncludeDeleted Whether to include deleted results
     * @return array
     */
    public function getAll($iPage = null, $iPerPage = null, $aData = array(), $bIncludeDeleted = false)
    {
        $aItems = parent::getAll($iPage, $iPerPage, $aData, $bIncludeDeleted);

        if (!empty($aItems)) {

            if (!empty($aData['includeAll']) || !empty($aData['includeInvoices'])) {
                $this->getManyAssociatedItems(
                    $aItems,
                    'invoices',
                    'customer_id',
                    'Invoice',
                    'nailsapp/module-invoice'
                );
            }
        }

        return $aItems;
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new customer
     * @param  array   $aData         The data to create the customer with
     * @param  boolean $bReturnObject Whether to return the complete customer object
     * @return mixed
     */
    public function create($aData = array(), $bReturnObject = false)
    {
        try {

            if (empty($aData['organisation']) && empty($aData['first_name']) && empty($aData['last_name'])) {
                throw new InvoiceException('"organisation", "first_name" or "last_name" must be supplied.', 1);
            }

            //  Compile the label
            $aData['label'] = $this->compileLabel($aData);

            return parent::create($aData, $bReturnObject);

        } catch (\Exception $e) {

            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update an existing customer
     * @param  integer $iCustomerId The ID of the customer to update
     * @param  array   $aData       The data to update the customer with
     * @return mixed
     */
    public function update($iCustomerId, $aData = array())
    {
        try {

            if (array_key_exists('organisation', $aData) && array_key_exists('first_name', $aData) && array_key_exists('last_name', $aData)) {
                if (empty($aData['organisation']) && empty($aData['first_name']) && empty($aData['last_name'])) {
                    throw new InvoiceException('"organisation", "first_name" and "last_name" cannot all be empty.', 1);
                }
            }

            //  Compile the label
            $aData['label'] = $this->compileLabel($aData);

            return parent::update($iCustomerId, $aData);

        } catch (\Exception $e) {

            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Compile the customer label
     * @param  array $aData The data passed to create() or update()
     * @return string
     */
    protected function compileLabel($aData)
    {
        if (!empty($aData['organisation'])) {

            $sLabel = trim($aData['organisation']);

        } else {

            $aLabel   = array();
            $aLabel[] = !empty($aData['first_name']) ? trim($aData['first_name']) : '';
            $aLabel[] = !empty($aData['last_name']) ? trim($aData['last_name']) : '';

            $aLabel = array_filter($aLabel);
            $sLabel = implode(' ', $aLabel);
        }

        return $sLabel;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats the business object
     * @param object $oObj An object containing business data
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
    ) {
        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        //  Address
        $aAddress = array(
            $oObj->billing_address_line_1,
            $oObj->billing_address_line_2,
            $oObj->billing_address_town,
            $oObj->billing_address_county,
            $oObj->billing_address_postcode,
            $oObj->billing_address_country
        );
        $aAddress = array_filter($aAddress);

        $oObj->billing_address           = new \stdClass();
        $oObj->billing_address->line_1   = $oObj->billing_address_line_1;
        $oObj->billing_address->line_2   = $oObj->billing_address_line_2;
        $oObj->billing_address->town     = $oObj->billing_address_town;
        $oObj->billing_address->county   = $oObj->billing_address_county;
        $oObj->billing_address->postcode = $oObj->billing_address_postcode;
        $oObj->billing_address->country  = $oObj->billing_address_country;

        unset($oObj->billing_address_line_1);
        unset($oObj->billing_address_line_2);
        unset($oObj->billing_address_town);
        unset($oObj->billing_address_county);
        unset($oObj->billing_address_postcode);
        unset($oObj->billing_address_country);
    }
}