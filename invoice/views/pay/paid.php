<div class="nailsapp-invoice paid container">
    <?=$this->load->view('invoice/_component/logo', array(), true)?>
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <h2 class="text-center">
                Invoice <?=$oInvoice->ref?>
            </h2>
            <hr>
            <div class="panel panel-success text-center">
                <div class="panel-heading">
                    <h3 class="panel-title">This invoice has been paid</h3>
                </div>
                <div class="panel-body">
                    Payment was received <?=$oInvoice->paid->formatted?>, many thanks for your business.
                </div>
            </div>
            <p class="text-center">
                <a href="<?=$oInvoice->urls->download?>" class="btn btn-primary btn-sm">
                    Download Invoice
                </a>
            </p>
        </div>
    </div>
</div>