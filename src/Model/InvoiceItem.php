<?php

/**
 * Invoice Item model
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

class InvoiceItem extends Base
{
    /**
     * The Currency library
     * @var Nails\Currency\Library\Currency
     */
    protected $oCurrency;

    // --------------------------------------------------------------------------

    /**
     * The various item quantity units
     */
    const UNIT_NONE   = 'NONE';
    const UNIT_MINUTE = 'MINUTE';
    const UNIT_HOUR   = 'HOUR';
    const UNIT_DAY    = 'DAY';
    const UNIT_WEEK   = 'WEEK';
    const UNIT_MONTH  = 'MONTH';
    const UNIT_YEAR   = 'YEAR';

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->table             = NAILS_DB_PREFIX . 'invoice_invoice_item';
        $this->tableAlias        = 'io';
        $this->defaultSortColumn = 'order';
        $this->oCurrency         = Factory::service('Currency', 'nailsapp/module-currency');

        $this->addExpandableField(
            array(
                'trigger'     => 'tax',
                'type'        => self::EXPANDABLE_TYPE_SINGLE,
                'property'    => 'tax',
                'model'       => 'Tax',
                'provider'    => 'nailsapp/module-invoice',
                'id_column'   => 'tax_id',
                'auto_expand' => true
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the item quantity units with human friendly names
     * @return array
     */
    public function getUnits()
    {
        return array(
            self::UNIT_NONE   => 'None',
            self::UNIT_MINUTE => 'Minutes',
            self::UNIT_HOUR   => 'Hours',
            self::UNIT_DAY    => 'Days',
            self::UNIT_WEEK   => 'Weeks',
            self::UNIT_MONTH  => 'Months',
            self::UNIT_YEAR   => 'Years'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve items which relate to a particular set of invoice IDs
     * @param  array $aInvoiceIds The invoice IDs
     * @return array
     */
    public function getForInvoices($aInvoiceIds)
    {
        $aData = array(
            'where_in' => array(
                array('invoice_id', $aInvoiceIds)
            )
        );

        return $this->getAll(null, null, $aData);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to getCountCommon, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
    ) {

        $aIntegers[] = 'invoice_id';
        $aIntegers[] = 'tax_id';
        $aIntegers[] = 'unit_cost';

        $aFloats[] = 'quantity';

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        //  Currency
        $oCurrency = $this->oCurrency->getByIsoCode($oObj->currency);
        unset($oObj->currency);

        //  Unit cost
        $oObj->unit_cost = (object) array(
            'raw'       => $oObj->unit_cost,
            'formatted' => $this->oCurrency->format(
                $oCurrency->code, $oObj->unit_cost / pow(10, $oCurrency->decimal_precision)
            )
        );

        //  Totals
        $oObj->totals = (object) array(
            'raw' => (object) array(
                'sub'        => (int) $oObj->sub_total,
                'tax'        => (int) $oObj->tax_total,
                'grand'      => (int) $oObj->grand_total
            ),
            'formatted' => (object) array(
                'sub' => $this->oCurrency->format(
                    $oCurrency->code, $oObj->sub_total / pow(10, $oCurrency->decimal_precision)
                ),
                'tax' => $this->oCurrency->format(
                    $oCurrency->code, $oObj->tax_total / pow(10, $oCurrency->decimal_precision)
                ),
                'grand' => $this->oCurrency->format(
                    $oCurrency->code, $oObj->grand_total / pow(10, $oCurrency->decimal_precision)
                )
            )
        );

        unset($oObj->sub_total);
        unset($oObj->tax_total);
        unset($oObj->grand_total);

        //  Units
        $sUnit  = $oObj->unit;
        $aUnits = $this->getUnits();

        $oObj->unit        = new \stdClass();
        $oObj->unit->id    = $sUnit;
        $oObj->unit->label = $aUnits[$sUnit];
    }
}
