<div class="group-invoice invoice edit">
    <?=form_open()?>
    <fieldset>
        <legend>Details</legend>
        <?php

        $aField = array(
            'key'         => 'ref',
            'label'       => 'Reference',
            'default'     => !empty($invoice->ref) ? $invoice->ref : '',
            'placeholder' => 'Leave blank to generate automatically',
        );
        echo form_field($aField);

        // --------------------------------------------------------------------------

        $aField = array(
            'key'      => 'state',
            'label'    => 'State',
            'default'  => !empty($invoice->state->id) ? $invoice->state->id : '',
            'class'    => 'select2',
            'required' => true,
            'id'       => 'invoice-state',
            'data'     => array(
                'bind' => 'event: {change: stateChanged()}'
            )
        );
        echo form_field_dropdown($aField, $invoiceStates);

        // --------------------------------------------------------------------------

        $aField = array(
            'key'      => 'dated',
            'label'    => 'Dated',
            'default'  => !empty($invoice->dated) ? $invoice->dated : date('Y-m-d'),
            'id'       => 'invoice-dated',
            'required' => true,
            'data'     => array(
                'bind' => 'event: {change: dateChanged()}'
            )
        );
        echo form_field_date($aField);

        // --------------------------------------------------------------------------

        $aField = array(
            'key'         => 'terms',
            'label'       => 'Payment Terms',
            'default'     => !empty($invoice->terms) ? $invoice->terms : appSetting('defaultTerms', 'nailsapp/module-invoice'),
            'info'        => '<span data-bind="html: termsText()"></span>',
            'id'          => 'invoice-terms',
            'placeholder' => 'Leave blank to set the invoice to be due on receipt',
            'data'        => array(
                'bind' => 'event: {change: termsChanged()}'
            )
        );
        echo form_field_number($aField);

        // --------------------------------------------------------------------------

        $aField = array(
            'key'     => 'user_id',
            'label'   => 'User',
            'default' => !empty($invoice->user->id) ? $invoice->user->id : '',
            'class'   => 'user-search'
        );
        echo form_field($aField);

        // --------------------------------------------------------------------------

        $aField = array(
            'key'     => 'user_email',
            'label'   => 'User Email',
            'default' => !empty($invoice->user_email) ? $invoice->user_email : '',
            'info'    => '<span class="alert alert-info">If a user is selected above, setting this field will ' .
                         'override the email address to which this invoice is sent. If no user is specified ' .
                         'above then this field is required.</span>'
        );
        echo form_field_email($aField);

        // --------------------------------------------------------------------------

        $aField = array(
            'key'         => 'additional_text',
            'label'       => 'Additional Text',
            'placeholder' => 'Any additional text you\'d like to show on the invoice',
            'default'     => !empty($invoice->additional_text) ? $invoice->additional_text : ''
        );
        echo form_field_textarea($aField);

        ?>
    </fieldset>
    <fieldset>
        <legend>Line Items</legend>
        <p>
            Enter discounts using a negative unit cost.
        </p>
        <div clas="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="quantity text-center">Quantity</th>
                        <th class="unit text-center">Unit</th>
                        <th>Details</th>
                        <th class="price text-center">Unit Cost</th>
                        <th class="tax text-center">Tax</th>
                        <th class="actions"></th>
                    </tr>
                </thead>
                <tbody data-bind="foreach: items">
                    <tr>
                        <td class="quantity text-center">
                            <input type="hidden" data-bind="attr: {name: 'items[' + $index() + '][id]'}, value: id" />
                            <input type="number" step="0.001" min="0" data-bind="attr: {name: 'items[' + $index() + '][quantity]'}, textInput: quantity" />
                        </td>
                        <td class="unit">
                            <select data-bind="
                                attr: {name: 'items[' + $index() + '][unit]'},
                                options: $root.units,
                                optionsText: 'label',
                                optionsValue: 'slug',
                                value: unit.id"></select>
                        </td>
                        <td>
                            <input type="text" placeholder="The line item's label" data-bind="attr: {name: 'items[' + $index() + '][label]'}, value: label" />
                            <textarea placeholder="The line item's description" data-bind="attr: {name: 'items[' + $index() + '][body]'}, html: body"></textarea>
                        </td>
                        <td class="price text-center">
                            <input type="number", step="0.01" min="0" data-bind="attr: {name: 'items[' + $index() + '][unit_cost]'}, textInput: unit_cost" />
                        </td>
                        <td class="tax">
                            <select data-bind="
                                attr: {name: 'items[' + $index() + '][tax_id]'},
                                options: $root.taxes,
                                optionsText: 'label',
                                optionsValue: 'id',
                                value: tax_id"></select>
                        </td>
                        <td class="actions text-center">
                            <!-- ko if: $index() != 0 -->
                            <a href="#" data-bind="click: $root.moveUp">
                                <i class="fa fa-caret-up"></i>
                            </a>
                            <!-- /ko -->
                            <a href="#" data-bind="click: $root.removeItem">
                                <b class="fa fa-times-circle text-danger"></b>
                            </a>
                            <!-- ko if: ($index() + 1) != $root.items().length -->
                            <a href="#" data-bind="click: $root.moveDown">
                                <i class="fa fa-caret-down"></i>
                            </a>
                            <!-- /ko -->
                        </td>
                    </tr>
                </tbody>
                <tfoot class="invoice-total" data-bind="visible: items().length">
                    <tr class="total-row">
                        <td colspan="6" class="text-right">
                            <strong>Sub Total:</strong>
                            <span data-bind="html: '&pound;' + _nails_admin.numberFormat(calculateSubTotal(), 2)"></span>
                        </td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">
                            <strong>Tax:</strong>
                            <span data-bind="html: '&pound;' + _nails_admin.numberFormat(calculateTax(), 2)"></span>
                        </td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">
                            <strong>Grand Total:</strong>
                            <span data-bind="html: '&pound;' + _nails_admin.numberFormat(calculateGrandTotal(), 2)"></span>
                        </td>
                    </tr>
                </tfoot>
                <tfoot class="add-item">
                    <tr>
                        <td colspan="6" class="add-item">
                            <button type="button" class="btn btn-block btn-sm btn-success" data-bind="click: addItem">
                                <b class="fa fa-plus"></b>
                                Add Line Item
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </fieldset>
    <p>
        <button type="submit" class="btn btn-primary" data-bind="click: save, html: submitText(), attr: {'class': submitClass()}">
            Save Changes
        </button>
        <button type="button" class="btn btn-default pull-right" data-bind="click: preview">
            Preview
        </button>
    </p>
    <?=form_close()?>
</div>