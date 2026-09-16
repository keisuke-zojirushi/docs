<?php get_header('blank'); ?>
<?php get_template_part('app-header'); ?>

<div class="app-page-wrapper">

<main class="zac-home app-main front-page-main">

  <section class="zac-hero">
    <h1>Zojirushi America Tech Information</h1>
    <p>Service parts information and technical documents for authorized service centers.</p>
  </section>

  <div class="zac-layout">

    <aside class="zac-sidebar">
      <h2>Model Search</h2>
      <?php get_template_part('sidebar-search'); ?>
    </aside>

    <section class="zac-main-panel">

      <div class="zac-card">
        <h2>News Topics</h2>

        <div class="zac-news-list">
          <?php
          $news_query = new WP_Query(array(
            'post_type' => 'zac_news',
            'posts_per_page' => 5,
            'post_status' => 'publish',
          ));
          ?>

          <?php if ($news_query->have_posts()) : ?>
            <?php while ($news_query->have_posts()) : $news_query->the_post(); ?>
              <div class="zac-news-item">
                <span><?php echo get_the_date('Y/m/d'); ?></span>

                <div>
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                  <p><?php echo wp_trim_words(get_the_content(), 24); ?></p>
                </div>
              </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
          <?php else : ?>
            <div class="zac-news-item">
              <span></span>
              <div>No news topics available.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
<section class="zati-quick-access-section">
 <section class="zati-quick-access">

    <h2 class="zati-quick-access-title">
        Quick Access
    </h2>

    <div class="zati-quick-access-grid">

        <a
            href="<?php echo esc_url(
                home_url('/warranty-claim/')
            ); ?>"
            class="zati-quick-link"
        >
            <span>Warranty Claim</span>
            <span class="zati-quick-arrow">→</span>
        </a>

        <a
            href="<?php echo esc_url(
                home_url('/parts-order/')
            ); ?>"
            class="zati-quick-link"
        >
            <span>Parts Order</span>
            <span class="zati-quick-arrow">→</span>
        </a>

        <a
            href="<?php echo esc_url(
                home_url('/warranty-claim-archive/')
            ); ?>"
            class="zati-quick-link"
        >
            <span>Warranty Claim Archive</span>
            <span class="zati-quick-arrow">→</span>
        </a>

        <a
            href="<?php echo esc_url(
                home_url('/parts-order-archive/')
            ); ?>"
            class="zati-quick-link"
        >
            <span>Parts Order Archive</span>
            <span class="zati-quick-arrow">→</span>
        </a>

    </div>

</section>
   </section> 

 <section id="category" class="zac-category-section">
  <h2>Category</h2>

  <div class="zac-category-grid">
    <div>
      <h3>Rice Cookers</h3>
      <a href="#">Pressure IH + IH + Micom</a>
      <a href="#">IH + Micom</a>
      <a href="#">Micom</a>
      <a href="#">Conventional</a>
    </div>

    <div>
      <h3>Water Boilers & Warmers</h3>
      <a href="#">VE</a>
      <a href="#">Micom</a>
    </div>

    <div>
      <h3>Breadmakers</h3>
      <a href="#">Breadmakers</a>

      <h3>Coffee Makers</h3>
      <a href="#">Coffee Makers</a>
    </div>

    <div>
      <h3>Vacuum Insulated Mugs & Bottles</h3>
      <a href="#">Flip-Open Mug</a>
      <a href="#">Twist-Off Mug</a>
      <a href="#">Bottle with Cup</a>
      <a href="#">Tumbler</a>
      <a href="#">Other</a>
    </div>

    <div>
      <h3>Other Electric Products</h3>
      <a href="#">Electric Griddle</a>
      <a href="#">Electric Skillet</a>
      <a href="#">Indoor Grill</a>
      <a href="#">Toaster Oven</a>
      <a href="#">Other</a>
    </div>

    <div>
      <h3>Commercial Products</h3>
      <a href="#">Rice Cooker & Warmer</a>
      <a href="#">Rice Warmer</a>
      <a href="#">Beverage Dispenser</a>
      <a href="#">Water Boiler</a>
    </div>
  </div>
</section>
</section>

  </div>

</main>
<?php get_template_part('app-footer'); ?>

</div>
<?php get_footer('blank'); ?>