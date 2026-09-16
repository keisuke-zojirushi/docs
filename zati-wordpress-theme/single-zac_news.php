<?php

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url('/login/') );
    exit;
}

get_header('blank');
get_template_part('app-header');

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

        $terms = get_the_terms(
            get_the_ID(),
            'news_category'
        );
?>

<main class="zati-news-single">

    <div class="zati-news-single-container">

        <nav class="zati-breadcrumb">

            <a href="<?php echo esc_url( home_url('/') ); ?>">
                Home
            </a>

            <span>›</span>

            <a href="<?php echo esc_url(
                get_post_type_archive_link('zac_news')
            ); ?>">
                News Topics
            </a>

            <?php if (
                $terms &&
                ! is_wp_error($terms)
            ) : ?>

                <span>›</span>

                <span>
                    <?php echo esc_html(
                        $terms[0]->name
                    ); ?>
                </span>

            <?php endif; ?>

        </nav>


        <article class="zati-news-article">

            <header class="zati-news-article-header">

                <?php if (
                    $terms &&
                    ! is_wp_error($terms)
                ) : ?>

                    <div class="zati-news-article-category">
                        <?php echo esc_html(
                            $terms[0]->name
                        ); ?>
                    </div>

                <?php endif; ?>

                <h1>
                    <?php the_title(); ?>
                </h1>

                <div class="zati-news-article-meta">

                    <span class="zati-news-article-date">
                        <?php echo esc_html(
                            get_the_date('F j, Y')
                        ); ?>
                    </span>

                </div>

            </header>


            <div class="zati-news-article-body">

                <?php the_content(); ?>

            </div>


            <footer class="zati-news-article-footer">

                <a
                    href="<?php echo esc_url(
                        get_post_type_archive_link('zac_news')
                    ); ?>"
                    class="zati-news-back-link"
                >
                    ← Back to News Topics
                </a>

            </footer>

        </article>

    </div>

</main>

<?php
    endwhile;
endif;

get_template_part('app-footer');
get_footer('blank');