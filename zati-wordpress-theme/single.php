<?php
echo '<pre>';
echo esc_html(get_post_type());
echo '</pre>';
exit;
?>
<?php get_header(); ?>

<main style="padding:20px; max-width:800px; margin:auto;">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<?php if (get_post_type() === 'parts') : ?>

<?php
$post_id = get_the_ID();

$model       = get_post_meta($post_id, 'model', true);
$diagram_no          = get_post_meta($post_id, 'diagram_no', true);
$partnumber  = get_post_meta($post_id, 'partnumber', true);
$description = get_post_meta($post_id, 'description', true);
$distributor_price = get_post_meta($post_id, 'distributor_price', true);
$jpnumber    = get_post_meta($post_id, 'jpnumber', true);
$display_partnumber = !empty($partnumber) ? $partnumber : get_the_title();
?>

<h1><?php echo esc_html($display_partnumber); ?></h1>

<p><strong>Model:</strong> <?php echo esc_html($model); ?></p>
<p><strong>No:</strong> <?php echo esc_html($diagram_no); ?></p>
<p><strong>Part Number:</strong> <?php echo esc_html($display_partnumber); ?></p>
<p><strong>Description:</strong> <?php echo esc_html($description); ?></p>
<p><strong>Distributor Price:</strong> <?php echo esc_html($distributor_price); ?></p>
<p><strong>JP Number:</strong> <?php echo esc_html($jpnumber); ?></p>

<?php else : ?>

<h1><?php the_title(); ?></h1>

<div>
    <?php the_content(); ?>
</div>

<?php endif; ?>

<?php endwhile; endif; ?>

</main>

<?php get_footer(); ?>