<?php

/**
 * Model Detail Template
 *
 * Data architecture:
 * - model_part CPT: model-specific diagram rows (model, diagram_no, reference_no, partnumber, size, remarks)
 * - part CPT: Parts Master (partnumber, jpnumber, description, distributor_price)
 */

if (!function_exists('zati_get_part_master_by_partnumber')) {
    function zati_get_part_master_by_partnumber($partnumber) {
        $partnumber = trim((string) $partnumber);

        if ($partnumber === '') {
           return [
                'jpnumber'          => '',
                'description'       => '',
                'distributor_price' => '',
                'retail_price'      => '',
            ];     
            }

        $master_query = new WP_Query([
            'post_type'      => 'part',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => 'partnumber',
                    'value'   => $partnumber,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($master_query->posts)) {
           return [
                    'jpnumber'          => '',
                    'description'       => '',
                    'distributor_price' => '',
                    'retail_price'      => '',
                ];      
             }

        $part_master_id = (int) $master_query->posts[0];

              return [
                'jpnumber'          => get_post_meta($part_master_id, 'jpnumber', true),
                'description'       => get_post_meta($part_master_id, 'description', true),
                'distributor_price' => get_post_meta($part_master_id, 'distributor_price', true),
                'retail_price'      => get_post_meta($part_master_id, 'retail_price', true),
            ];
    }
}

if (!function_exists('zati_get_model_parts_rows')) {
    function zati_get_model_parts_rows($model_name) {
        $parts_query = new WP_Query([
            'post_type'      => 'model_part',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'model',
                    'value'   => $model_name,
                    'compare' => 'LIKE',
                ],
            ],
        ]);

        $rows = [];

        if ($parts_query->have_posts()) {
            while ($parts_query->have_posts()) {
                $parts_query->the_post();

                $pid = get_the_ID();

                $diagram_no = trim(get_post_meta($pid, 'diagram_no', true));
                $partnumber = trim(get_post_meta($pid, 'partnumber', true));
                $part_master = zati_get_part_master_by_partnumber($partnumber);

                $rows[] = [
                    'diagram_no'        => $diagram_no,
                    'reference_no'      => get_post_meta($pid, 'reference_no', true),
                    'partnumber'        => $partnumber,
                    'jpnumber'          => $part_master['jpnumber'],
                    'description'       => $part_master['description'],
                    'size'              => get_post_meta($pid, 'size', true),
                    'distributor_price' => $part_master['distributor_price'],
                    'retail_price'      => $part_master['retail_price'],
                    'remarks'           => get_post_meta($pid, 'remarks', true),
			// New
    		        'sort_order'        => get_post_meta($pid, 'sort_order', true),
                ];
            }

            wp_reset_postdata();
        }

                /*
        * Sorting priority:
        *
        * 1. sort_order if available
        * 2. diagram_no for legacy Model Parts
        * 3. unnumbered rows last
        */
        usort($rows, function ($a, $b) {

            $sort_a = isset($a['sort_order'])
                ? trim((string) $a['sort_order'])
                : '';

            $sort_b = isset($b['sort_order'])
                ? trim((string) $b['sort_order'])
                : '';

            /*
            * Both rows have sort_order
            */
            if ($sort_a !== '' && $sort_b !== '') {
                return intval($sort_a) <=> intval($sort_b);
            }

            /*
            * Only A has sort_order
            */
            if ($sort_a !== '' && $sort_b === '') {
                return -1;
            }

            /*
            * Only B has sort_order
            */
            if ($sort_a === '' && $sort_b !== '') {
                return 1;
            }

            /*
            * Legacy fallback:
            * neither row has sort_order
            */
            $diagram_a = trim((string) $a['diagram_no']);
            $diagram_b = trim((string) $b['diagram_no']);

            $a_has_diagram = (
                $diagram_a !== '' &&
                $diagram_a !== '-'
            );

            $b_has_diagram = (
                $diagram_b !== '' &&
                $diagram_b !== '-'
            );

            /*
            * Both have diagram number
            */
            if ($a_has_diagram && $b_has_diagram) {
                return intval($diagram_a) <=> intval($diagram_b);
            }

            /*
            * Numbered row before unnumbered row
            */
            if ($a_has_diagram && !$b_has_diagram) {
                return -1;
            }

            if (!$a_has_diagram && $b_has_diagram) {
                return 1;
            }

            return 0;
        });

        return $rows;
    }
}
if (isset($_GET['download_parts_csv']) && $_GET['download_parts_csv'] === '1') {

    $post_id = get_queried_object_id();
    $model_name = get_the_title($post_id);

    if (empty($model_name)) {
        $queried = get_queried_object();
        if ($queried && !empty($queried->post_title)) {
            $model_name = $queried->post_title;
        }
    }

    $rows = zati_get_model_parts_rows($model_name);

    $filename = sanitize_file_name($model_name . '-parts-list.csv');

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        'No.',
        'Part No.',
        'JPN Part No.',
        'Description',
        'Size',
        'Price (USD)',
        'Remarks',
    ]);

    foreach ($rows as $row) {

        $size_for_csv = $row['size'];

        if ($size_for_csv !== '') {
            $size_for_csv = '="' . $size_for_csv . '"';
        }

        fputcsv($output, [
            $row['diagram_no'],
            $row['partnumber'],
            $row['jpnumber'],
            $row['description'],
            $size_for_csv,
            $row['distributor_price'],
            $row['remarks'],
        ]);
    }

    fclose($output);
    exit;
}


/*
 * Parts Price List PDF
 */
if (
    isset($_GET['download_parts_pdf']) &&
    $_GET['download_parts_pdf'] === '1'
) {

    $post_id = get_queried_object_id();
    $model_name = get_the_title($post_id);

    if (empty($model_name)) {
        $queried = get_queried_object();

        if (
            $queried &&
            !empty($queried->post_title)
        ) {
            $model_name = $queried->post_title;
        }
    }

    zati_download_parts_price_list_pdf(
        $model_name
    );
}

?>


<?php get_header(); ?>

<?php get_template_part('app-header'); ?>

<main class="model-detail-container">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<?php
$post_id = get_the_ID();

$title = get_the_title($post_id);
$diagram  = get_field('diagram_image', $post_id);
$diagram2 = get_field('diagram_image_2', $post_id);
$service_manual_pdf = get_field('service_manual_pdf', $post_id);
$user_manual_pdf    = get_field('user_manual_pdf', $post_id);
$technical_info     = get_field('technical_info', $post_id);

$category = '';
$category_link = '';
$subcategory = '';
$subcategory_link = '';

$terms = wp_get_post_terms($post_id, 'product_category');

if (!empty($terms) && !is_wp_error($terms)) {
    foreach ($terms as $term) {

        if ((int) $term->parent === 0) {
            $category = $term->name;
            $category_link = home_url('/model-search/?cat=' . $term->term_id . '&subcat=');
        } else {
            $subcategory = $term->name;

            $parent = get_term((int) $term->parent, 'product_category');

            if ($parent && !is_wp_error($parent)) {
                $category = $parent->name;
                $category_link = home_url('/model-search/?cat=' . $parent->term_id . '&subcat=');
                $subcategory_link = home_url('/model-search/?cat=' . $parent->term_id . '&subcat=' . $term->term_id);
            }
        }
    }
}
?>

<div class="model-topbar">

    <div class="model-category">
        <?php if ($category_link): ?>
            <a href="<?php echo esc_url($category_link); ?>">
                <?php echo esc_html($category); ?>
            </a>
        <?php else: ?>
            <?php echo esc_html($category); ?>
        <?php endif; ?>
    </div>

    <div class="model-subcategory">
        <?php if ($subcategory_link): ?>
            <a href="<?php echo esc_url($subcategory_link); ?>">
                <?php echo esc_html($subcategory); ?>
            </a>
        <?php else: ?>
            <?php echo esc_html($subcategory); ?>
        <?php endif; ?>
    </div>

    <div class="model-name">
        <?php echo esc_html($title); ?>
    </div>

    <div class="model-zoom-tools">

<button id="zoomIn" class="zoom-tool-btn" aria-label="Zoom in">
    <svg class="zoom-svg" viewBox="0 0 32 32">
        <circle cx="13" cy="13" r="10"></circle>
        <line x1="20" y1="20" x2="25" y2="25"></line>
        <line x1="13" y1="8.5" x2="13" y2="17.5"></line>
        <line x1="8.5" y1="13" x2="17.5" y2="13"></line>
    </svg>
</button>

<button id="zoomOut" class="zoom-tool-btn" aria-label="Zoom out">
    <svg class="zoom-svg" viewBox="0 0 32 32">
        <circle cx="13" cy="13" r="10"></circle>
        <line x1="20" y1="20" x2="25" y2="25"></line>
        <line x1="8.5" y1="13" x2="17.5" y2="13"></line>
    </svg>
</button>        <button id="zoomReset" class="zoom-reset-btn">
            Reset
        </button>

    </div>

<div class="model-buttons">

        <a href="#"
                class="zati-add-to-order-btn"
                id="zati-add-to-parts-order"
            >
                ADD TO PARTS ORDER
        </a>

        <a class="btn-file-icon btn-price-list"
            href="<?php echo esc_url(add_query_arg('download_parts_pdf', '1')); ?>"
            target="_blank"
            rel="noopener"
            title="Parts Price List"
            aria-label="Parts Price List">

        <svg class="csv-svg" viewBox="0 0 40 32">
            <path d="M10 4H24L30 10V26H10Z"></path>
            <path d="M24 4V10H30"></path>
            <line x1="20" y1="12" x2="20" y2="20"></line>
            <polyline points="16.5,17 20,20.5 23.5,17"></polyline>
        </svg>
    </a>

    <?php if ($service_manual_pdf): ?>
        <a class="btn-file-icon btn-service-manual"
           href="<?php echo esc_url($service_manual_pdf); ?>"
           target="_blank"
           rel="noopener"
           title="Service Manual"
           aria-label="Service Manual">
    <?php else: ?>
        <span class="btn-file-icon btn-service-manual btn-file-disabled"
              title="Service Manual not available"
              aria-label="Service Manual not available">
    <?php endif; ?>

            <svg class="manual-svg service-manual-svg" viewBox="0 0 40 32">
                <path d="M10 4H24L30 10V26H10Z"></path>
                <path d="M24 4V10H30"></path>
                <line x1="15" y1="15" x2="25" y2="15"></line>
                <line x1="15" y1="19" x2="23" y2="19"></line>
            </svg>

    <?php if ($service_manual_pdf): ?>
        </a>
    <?php else: ?>
        </span>
    <?php endif; ?>


    <?php if ($user_manual_pdf): ?>
        <a class="btn-file-icon btn-user-manual"
           href="<?php echo esc_url($user_manual_pdf); ?>"
           target="_blank"
           rel="noopener"
           title="User Manual"
           aria-label="User Manual">
    <?php else: ?>
        <span class="btn-file-icon btn-user-manual btn-file-disabled"
              title="User Manual not available"
              aria-label="User Manual not available">
    <?php endif; ?>

            <svg class="manual-svg user-manual-svg" viewBox="0 0 40 32">
                <path d="M10 4H24L30 10V26H10Z"></path>
                <path d="M24 4V10H30"></path>
                <line x1="15" y1="14" x2="25" y2="14"></line>
                <line x1="15" y1="18" x2="25" y2="18"></line>
                <line x1="15" y1="22" x2="22" y2="22"></line>
            </svg>

    <?php if ($user_manual_pdf): ?>
        </a>
    <?php else: ?>
        </span>
    <?php endif; ?>


    <?php if ($technical_info): ?>
        <a class="btn-file-icon btn-technical-info"
           href="<?php echo esc_url($technical_info); ?>"
           target="_blank"
           rel="noopener"
           title="Technical Information"
           aria-label="Technical Information">
    <?php else: ?>
        <span class="btn-file-icon btn-technical-info btn-file-disabled"
              title="Technical Information not available"
              aria-label="Technical Information not available">
    <?php endif; ?>

            <svg class="manual-svg technical-info-svg" viewBox="0 0 40 32">
                <path d="M10 4H24L30 10V26H10Z"></path>
                <path d="M24 4V10H30"></path>
                <circle cx="20" cy="12" r="1.3"></circle>
                <line x1="20" y1="15" x2="20" y2="22"></line>
            </svg>

    <?php if ($technical_info): ?>
        </a>
    <?php else: ?>
        </span>
    <?php endif; ?>

</div>

</div>

<div class="split-container" id="splitContainer">

    <section class="left-pane" id="leftPane">

        <div class="diagram-container" id="diagramContainer">

            <?php if ($diagram || $diagram2): ?>

    <?php if ($diagram && $diagram2): ?>
        <div class="diagram-page-switch">
            <button type="button" class="diagram-page-btn active" data-diagram="1">1 / 2</button>
            <button type="button" class="diagram-page-btn" data-diagram="2">2 / 2</button>
        </div>
    <?php endif; ?>

    <div class="diagram-stage" id="diagramStage">

    <div class="zoom-layer" id="zoomLayer">

        <?php if ($diagram): ?>
            <img id="diagramImg1"
                 class="diagram-img diagram-page-image active"
                 data-diagram="1"
                 src="<?php echo esc_url($diagram['url']); ?>"
                 alt="<?php echo esc_attr($title); ?> Diagram 1">
        <?php endif; ?>

        <?php if ($diagram2): ?>
            <img id="diagramImg2"
                 class="diagram-img diagram-page-image"
                 data-diagram="2"
                 src="<?php echo esc_url($diagram2['url']); ?>"
                 alt="<?php echo esc_attr($title); ?> Diagram 2">
        <?php endif; ?>

        <?php
        $hotspots = get_field('diagram_hotspots');

        if ($hotspots) {
            $lines = preg_split('/\r\n|\r|\n/', trim($hotspots));

            foreach ($lines as $line) {
                $cols = array_map('trim', explode('|', $line));

                $hotspot_no = $cols[0] ?? '';
                $left       = $cols[1] ?? '';
                $top        = $cols[2] ?? '';

                if ($hotspot_no !== '' && $left !== '' && $top !== ''):
                    ?>
                    <button
                        class="diagram-hotspot"
                        data-no="<?php echo esc_attr($hotspot_no); ?>"
                        title="<?php echo esc_attr($hotspot_no); ?>"
                        style="left:<?php echo esc_attr($left); ?>%; top:<?php echo esc_attr($top); ?>%;"
                        aria-label="Part <?php echo esc_attr($hotspot_no); ?>"
                    ></button>
                    <?php
                endif;
            }
        }
        ?>

    </div>

</div>
            <?php else: ?>

                <p>No diagram available</p>

            <?php endif; ?>

        </div>

    </section>

    <div class="divider" id="divider"></div>

    <section class="right-pane" id="rightPane">

        <div class="parts-container">

            <table class="parts-table">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>No.</th>
                        <th>Part No.</th>
                        <th>JPN Part No.</th>
                        <th>Description</th>
                        <th>Size</th>
                        <th>SVC Price</th>
                                                <th>Retail Price</th>
                        <th>Remarks</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $current_model = get_the_title();
                $display_parts = zati_get_model_parts_rows($current_model);
                ?>

                <?php foreach ($display_parts as $part): ?>
                    <tr data-part-no="<?php echo esc_attr($part['diagram_no']); ?>">
                        <td class="zati-part-select-cell">
                            <input
                                type="checkbox"
                                class="zati-part-select"
                                value="<?php echo esc_attr($part['partnumber']); ?>"
                            >
                            </td>
                        <td><?php echo esc_html($part['diagram_no']); ?></td>
                        <td><?php echo esc_html($part['partnumber']); ?></td>
                        <td><?php echo esc_html($part['jpnumber']); ?></td>
                        <td><?php echo esc_html($part['description']); ?></td>
                        <td><?php echo esc_html($part['size']); ?></td>
                        <td class="price-col">
                        <?php
                        $svc_price = zati_get_part_price_by_role(
                            $part['distributor_price']
                        );

                        if ($svc_price !== '' && is_numeric($svc_price)) {
                            echo '$' . number_format((float) $svc_price, 2);
                        } else {
                            echo '-';
                        }
                        ?>
                        </td>

                        <td class="price-col">
                        <?php
                        $retail_price = $part['retail_price'] ?? '';

                        if ($retail_price !== '' && is_numeric($retail_price)) {
                            echo '$' . number_format((float) $retail_price, 2);
                        } else {
                            echo '-';
                        }
                        ?>
                        </td>  
                        <td><?php echo esc_html($part['remarks']); ?></td>
                                </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const zoomLayer = document.getElementById('zoomLayer');
    const diagramContainer = document.getElementById('diagramContainer');

    const zoomInBtn = document.getElementById('zoomIn');
    const zoomOutBtn = document.getElementById('zoomOut');
    const zoomResetBtn = document.getElementById('zoomReset');

    const divider = document.getElementById('divider');
    const splitContainer = document.getElementById('splitContainer');
    const rightPane = document.getElementById('rightPane');

    let scale = 1;
    let posX = 0;
    let posY = 0;

    let isPanning = false;
    let startX = 0;
    let startY = 0;

    let draggingDivider = false;

    function updateTransform() {
        if (!zoomLayer) return;

        zoomLayer.style.transform =
            `translate(${posX}px, ${posY}px) scale(${scale})`;
    }

    if (diagramContainer && zoomLayer) {

        diagramContainer.addEventListener('wheel', function (e) {
    e.preventDefault();

    const rect = zoomLayer.getBoundingClientRect();

    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;

    const oldScale = scale;

    if (e.deltaY < 0) {
        scale = Math.min(scale + 0.1, 2.5);
    } else {
        scale = Math.max(scale - 0.1, 0.5);
    }

    const scaleRatio = scale / oldScale;

    posX = mouseX - (mouseX - posX) * scaleRatio;
    posY = mouseY - (mouseY - posY) * scaleRatio;

    updateTransform();
}, { passive: false });
        diagramContainer.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;

            e.preventDefault();

            isPanning = true;
            startX = e.clientX - posX;
            startY = e.clientY - posY;

            diagramContainer.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', function (e) {
            if (!isPanning) return;

            posX = e.clientX - startX;
            posY = e.clientY - startY;

            updateTransform();
        });

        document.addEventListener('mouseup', function () {
            if (!isPanning) return;

            isPanning = false;
            diagramContainer.style.cursor = 'grab';
        });
             document.querySelectorAll('.diagram-page-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const target = this.dataset.diagram;

        document.querySelectorAll('.diagram-page-btn').forEach(function (b) {
            b.classList.remove('active');
        });

        this.classList.add('active');

        document.querySelectorAll('.diagram-page-image').forEach(function (img) {
            img.classList.remove('active');

            if (img.dataset.diagram === target) {
                img.classList.add('active');
            }
        });

        // scale = 1;
        // posX = 0;
        // posY = 0;
        // updateTransform();
    });
});

    }

    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function () {
            scale = Math.min(scale + 0.25, 2.5);
            updateTransform();
        });
    }

    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function () {
            scale = Math.max(scale - 0.25, 0.5);
            updateTransform();
        });
    }

    if (zoomResetBtn) {
        zoomResetBtn.addEventListener('click', function () {
            scale = 1;
            posX = 0;
            posY = 0;
            updateTransform();
        });
    }

    if (divider && splitContainer) {

        divider.addEventListener('mousedown', function (e) {
            e.preventDefault();

            draggingDivider = true;

            divider.classList.add('dragging');

            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mouseup', function () {
            if (!draggingDivider) return;

            draggingDivider = false;

            divider.classList.remove('dragging');

            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        });

        document.addEventListener('mousemove', function (e) {
            if (!draggingDivider) return;

            const rect = splitContainer.getBoundingClientRect();
            const leftWidth = e.clientX - rect.left;

            const minLeft = 350;
            const maxLeft = rect.width - 350;

            if (leftWidth > minLeft && leftWidth < maxLeft) {
                splitContainer.style.setProperty('--left-width', leftWidth + 'px');
            }
        });

    }

    document.querySelectorAll('.diagram-hotspot').forEach(function (hotspot) {
        hotspot.addEventListener('click', function (e) {
            e.stopPropagation();

            const no = this.dataset.no;

            highlightPartRow(no);
        });
    });

    function highlightPartRow(no) {

        document.querySelectorAll('.parts-table tr').forEach(function (row) {
            row.classList.remove('part-highlight');
        });

        const targetRows = document.querySelectorAll(
            '.parts-table tr[data-part-no="' + CSS.escape(no) + '"]'
        );

        targetRows.forEach(function (row) {
            row.classList.add('part-highlight');

            if (rightPane) {
                const paneRect = rightPane.getBoundingClientRect();
                const rowRect = row.getBoundingClientRect();

                rightPane.scrollTop +=
                    rowRect.top - paneRect.top - rightPane.clientHeight / 2 + rowRect.height / 2;
            }
        });
    }

});

document.addEventListener('DOMContentLoaded', function () {

    const addToOrderButton =
        document.getElementById('zati-add-to-parts-order');

    if (!addToOrderButton) {
        return;
    }

    addToOrderButton.addEventListener('click', function (event) {

        event.preventDefault();

        const selectedParts = Array.from(
            document.querySelectorAll(
                '.zati-part-select:checked'
            )
        ).map(function (checkbox) {
            return checkbox.value;
        });

        if (selectedParts.length === 0) {
            alert('Please select at least one part.');
            return;
        }

                const storageKey =
            'zati_parts_order_selected';

        let storedParts = [];

        try {

            storedParts =
                JSON.parse(
                    sessionStorage.getItem(
                        storageKey
                    )
                ) || [];

        } catch (error) {

            storedParts = [];
        }


        /*
        * Add newly selected parts
        * without duplicates.
        */
        /*
 * Convert old stored data
 * to the new object format.
 */
storedParts =
    storedParts.map(
        function (item) {

            if (
                typeof item === 'string'
            ) {
                return {
                    partnumber: item,
                    quantity: 1
                };
            }

            return {
                partnumber:
                    item.partnumber || '',
                quantity:
                    parseInt(
                        item.quantity,
                        10
                    ) || 1
            };
        }
    )
    .filter(
        function (item) {
            return item.partnumber;
        }
    );


        /*
        * Add newly selected parts
        * without duplicates.
        */
        selectedParts.forEach(
            function (partnumber) {

                const exists =
                    storedParts.some(
                        function (item) {
                            return (
                                item.partnumber
                                === partnumber
                            );
                        }
                    );

                if (!exists) {

                    storedParts.push(
                        {
                            partnumber: partnumber,
                            quantity: 1
                        }
                    );
                }

            }
        );


        sessionStorage.setItem(
            storageKey,
            JSON.stringify(
                storedParts
            )
        );


        const partsOrderUrl =
            '<?php echo esc_url(home_url('/parts-order/')); ?>';

        window.location.href =
            partsOrderUrl;



            });

});
</script>

<?php endwhile; endif; ?>

</main>

<?php get_footer(); ?>
