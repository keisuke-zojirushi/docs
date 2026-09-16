<?php
/**
 * Template Name: Model Detail
 */

get_header();
?>

<main class="model-detail-container">

    <h1><?php the_title(); ?></h1>

    <div class="model-detail-wrapper">

        <!-- 左：図面 -->
        <div class="model-diagram">
            <?php
            $diagram = get_field('diagram_image');
            if ($diagram):
            ?>
                <img src="<?php echo esc_url($diagram['url']); ?>" alt="Diagram">
            <?php else: ?>
                <p>No diagram available</p>
            <?php endif; ?>
        </div>

        <!-- 右：パーツリスト -->
        <div class="model-parts">

            <h2>Parts List</h2>

            <?php if (have_rows('parts_list')): ?>
                <table class="parts-table">
                    <thead>
                        <tr>
                            <th>Part No</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php while (have_rows('parts_list')): the_row(); ?>
                        <tr>
                            <td><?php the_sub_field('part_no'); ?></td>
                            <td><?php the_sub_field('description'); ?></td>
                        </tr>
                    <?php endwhile; ?>

                    </tbody>
                </table>
            <?php else: ?>
                <p>No parts found.</p>
            <?php endif; ?>

        </div>

    </div>

</main>

<?php get_footer(); ?>