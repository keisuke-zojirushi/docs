<?php
/*
 * ZAC Custom 404 Page
 */
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>

<body class="zac-forgot-page zac-404-page">

<div class="zac-forgot-wrapper">

    <img class="zac-login-logo"
         src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/zojirushi_logo.svg'); ?>"
         alt="Zojirushi">

    <h1>Zojirushi America Tech Information</h1>

    <h2 class="zac-404-title">Page not found</h2>

    <div class="zac-forgot-info">
        The page you’re looking for doesn’t exist or may have been moved.

        <br><br>

        Please check the URL, or use the link below to continue.
    </div>

    <a class="zac-back-login-link" href="<?php echo esc_url(home_url('/login/')); ?>">
        Back to Login
    </a>

</div>

<footer class="zac-login-footer">
    <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/zojirushi_logo.svg'); ?>" alt="Zojirushi">

    <span>
        Copyright © ZOJIRUSHI AMERICA CORPORATION. All Rights Reserved.
    </span>
</footer>

<?php wp_footer(); ?>
</body>
</html>