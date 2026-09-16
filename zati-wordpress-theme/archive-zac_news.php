<?php

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url('/login/') );
    exit;
}

get_header('blank');
get_template_part('app-header');
?>

<main class="zati-news-archive">

    <div class="zati-news-container">

        <nav class="zati-breadcrumb">
            <a href="<?php echo esc_url( home_url('/') ); ?>">
                Home
            </a>
            <span>›</span>
            <span>News Topics</span>
        </nav>

        <header class="zati-news-archive-header">

            <h1>News Topics</h1>

            <p>
                Technical updates, service information,
                new model and parts announcements.
            </p>

        </header>

        <div class="zati-news-list-page">

            <?php if ( have_posts() ) : ?>

                <?php while ( have_posts() ) : the_post(); ?>

                    <?php
                    $terms = get_the_terms(
                        get_the_ID(),
                        'news_category'
                    );
                    ?>

                    <article class="zati-news-list-item">

                        <div class="zati-news-list-date">
                            <?php echo esc_html(
                                get_the_date('Y/m/d')
                            ); ?>
                        </div>

                        <div class="zati-news-list-content">

                            <?php if (
                                $terms &&
                                ! is_wp_error($terms)
                            ) : ?>

                                <div class="zati-news-category">
                                    <?php echo esc_html(
                                        $terms[0]->name
                                    ); ?>
                                </div>

                            <?php endif; ?>

                            <h2>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <div class="zati-news-list-excerpt">
                                <?php
                                echo esc_html(
                                    wp_trim_words(
                                        get_the_excerpt(),
                                        24,
                                        '...'
                                    )
                                );
                                ?>
                            </div>

                        </div>

                    </article>

                <?php endwhile; ?>

            <?php else : ?>

                <p>No news topics found.</p>

            <?php endif; ?>

        </div>

        <?php
        the_posts_pagination([
            'mid_size'  => 2,
            'prev_text' => 'Previous',
            'next_text' => 'Next',
        ]);
        ?>

    </div>

</main>

<?php
get_template_part('app-footer');
get_footer('blank');