<?php
/*
Template Name: ZAC Terms of Use
*/
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>

<body class="zac-forgot-page zac-terms-page">

<div class="zac-terms-wrapper">

    <img class="zac-login-logo"
         src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/zojirushi_logo.svg'); ?>"
         alt="Zojirushi">

    <h1>Zojirushi America Tech Information</h1>

    <h2 class="zac-terms-title">Terms of Use</h2>

    <div class="zac-terms-card">
        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        ?>
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