<?php

return array(
    'models' => array(
        'Invoice' => function () {
            if (class_exists('\App\Invoice\Model\Invoice')) {
                return new \App\Invoice\Model\Invoice();
            } else {
                return new \Nails\Invoice\Model\Invoice();
            }
        },
        'InvoiceItem' => function () {
            if (class_exists('\App\Invoice\Model\InvoiceItem')) {
                return new \App\Invoice\Model\InvoiceItem();
            } else {
                return new \Nails\Invoice\Model\InvoiceItem();
            }
        },
        'Payment' => function () {
            if (class_exists('\App\Invoice\Model\Payment')) {
                return new \App\Invoice\Model\Payment();
            } else {
                return new \Nails\Invoice\Model\Payment();
            }
        },
        'PaymentDriver' => function () {
            if (class_exists('\App\Invoice\Model\PaymentDriver')) {
                return new \App\Invoice\Model\PaymentDriver();
            } else {
                return new \Nails\Invoice\Model\PaymentDriver();
            }
        },
        'Tax' => function () {
            if (class_exists('\App\Invoice\Model\Tax')) {
                return new \App\Invoice\Model\Tax();
            } else {
                return new \Nails\Invoice\Model\Tax();
            }
        },
        'PaymentEventHandler' => function () {
            if (class_exists('\App\Invoice\PaymentEventHandler')) {
                return new \App\Invoice\PaymentEventHandler();
            } else {
                return new \Nails\Invoice\PaymentEventHandler();
            }
        }
    ),
    'factories' => array(
        'Card' => function () {
            if (class_exists('\App\Invoice\Model\Card')) {
                return new \App\Invoice\Model\Card();
            } else {
                return new \Nails\Invoice\Model\Card();
            }
        }
    )
);
