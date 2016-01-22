<!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="en"> <!--<![endif]-->
<head>
    <title><?=' Please wait while redirect you to our payment provider - ' . APP_NAME?></title>
    <?php

    include NAILS_COMMON_PATH . 'errors/components/styles.php';

    ?>
</head>
<body>
    <div id="container">
        <?php

        if (defined('FCPATH') && defined('BASE_URL')) {

            $paths   = array();
            $paths[] = array(
                FCPATH . 'assets/img/errors/404.png',
                BASE_URL . 'assets/img/errors/404.png'
            );
        }

        include NAILS_COMMON_PATH . 'errors/components/header.php';

        ?>
        <p>
            Please wait while we redirect you to our payment provider...
        </p>
        <form action="<?=$redirectUrl?>" method="POST" id="form">
            <?php

            foreach ($postFields as $sKey => $sValue) {
                echo '<input type="hidden" name="' . $sKey . '" value="' . $sValue . '" />';
            }

            ?>
        </form>
        <?php

        include NAILS_COMMON_PATH . 'errors/components/footer.php';

        ?>
    </div>
    <script type="text/javascript">
        // document.getElementById('form').submit();
    </script>
</body>
