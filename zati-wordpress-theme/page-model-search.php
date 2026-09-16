<?php 
/**
 * Template Name: Model Search
 */

get_header('blank');
get_template_part('app-header');

// ここに検索用PHP処理

$raw = '';
if (!empty($_GET['q'])) {
    $raw = sanitize_text_field($_GET['q']);
}
$raw = trim($raw);

$raw = zati_normalize_search_text($raw);

$cat_id = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$subcat_id = isset($_GET['subcat']) ? intval($_GET['subcat']) : 0;
$paged = get_query_var('paged')
    ? get_query_var('paged')
    : 1;
if (isset($_GET['paged'])) {
    $paged = intval($_GET['paged']);
}
$allowed_per_page = [25, 50, 100];
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;

if (!in_array($per_page, $allowed_per_page, true)) {
    $per_page = 50;
}

function zati_normalize_search_text($text) {
    $text = trim($text);

    $text = str_replace(
        [
            "\u{2010}", // ‐
            "\u{2011}", // -
            "\u{2012}", // ‒
            "\u{2013}", // –
            "\u{2014}", // —
            "\u{2015}", // ―
            "\u{2212}", // −
            "\u{FF0D}", // －
        ],
        '-',
        $text
    );

    return $text;
}

function zati_render_pagination($paged, $total_pages, $total_items, $start_item, $end_item, $cat_id, $subcat_id, $per_page, $base_url) {
    ?>
    <div class="zati-pagination">

        <?php if ($paged > 1): ?>
            <a class="zati-page-link"
               href="<?php echo esc_url(add_query_arg([
                   'cat'      => $cat_id,
                   'subcat'   => $subcat_id,
                   'per_page' => $per_page,
                   'paged'    => $paged - 1,
               ], $base_url)); ?>">
                Forward
            </a>
        <?php else: ?>
            <span class="zati-page-disabled">Forward</span>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a class="zati-page-number <?php echo ($i === $paged) ? 'current' : ''; ?>"
               href="<?php echo esc_url(add_query_arg([
                   'cat'      => $cat_id,
                   'subcat'   => $subcat_id,
                   'per_page' => $per_page,
                   'paged'    => $i,
               ], $base_url)); ?>">
                <?php echo esc_html($i); ?>
            </a>
        <?php endfor; ?>

        <?php if ($paged < $total_pages): ?>
            <a class="zati-page-link"
               href="<?php echo esc_url(add_query_arg([
                   'cat'      => $cat_id,
                   'subcat'   => $subcat_id,
                   'per_page' => $per_page,
                   'paged'    => $paged + 1,
               ], $base_url)); ?>">
                Next
            </a>
        <?php else: ?>
            <span class="zati-page-disabled">Next</span>
        <?php endif; ?>

        <span class="zati-page-count">
            <?php echo esc_html($start_item); ?> - <?php echo esc_html($end_item); ?> / <?php echo esc_html($total_items); ?>
        </span>

        <span class="zati-page-label">Subject quantity on 1 page. :</span>

        <select class="zati-page-size" onchange="window.location.href=this.value;">
            <option value="<?php echo esc_url(add_query_arg(['cat'=>$cat_id,'subcat'=>$subcat_id,'per_page'=>25,'paged'=>1], $base_url)); ?>" <?php selected($per_page, 25); ?>>25</option>
            <option value="<?php echo esc_url(add_query_arg(['cat'=>$cat_id,'subcat'=>$subcat_id,'per_page'=>50,'paged'=>1], $base_url)); ?>" <?php selected($per_page, 50); ?>>50</option>
            <option value="<?php echo esc_url(add_query_arg(['cat'=>$cat_id,'subcat'=>$subcat_id,'per_page'=>100,'paged'=>1], $base_url)); ?>" <?php selected($per_page, 100); ?>>100</option>
        </select>

    </div>
    <?php
}

function render_model_result_row($model_post_id) {
    $terms = get_the_terms($model_post_id, 'product_category');

    $category = '';
    $subcategory = '';

    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            if ($term->parent == 0) {
                $category = $term->name;
            } else {
                $subcategory = $term->name;

                $parent = get_term($term->parent, 'product_category');
                if ($parent && !is_wp_error($parent)) {
                    $category = $parent->name;
                }
            }
        }
    }

    $parts_pdf_url = add_query_arg(
    'download_parts_pdf',
    '1',
    get_permalink($model_post_id)
	);
    $service_manual_pdf = get_field('service_manual_pdf', $model_post_id);
    $user_manual_pdf    = get_field('user_manual_pdf', $model_post_id);
    $technical_info     = get_field('technical_info', $model_post_id);
    ?>
    <tr>
    <td>
        <a href="<?php echo esc_url(get_permalink($model_post_id)); ?>">
            <?php echo esc_html(get_the_title($model_post_id)); ?>
        </a>
    </td>

   <td><?php echo esc_html($category); ?></td>
<td><?php echo esc_html($subcategory); ?></td>

	<td>
    	<a class="zati-result-btn zati-btn-model"
       href="<?php echo esc_url(get_permalink($model_post_id)); ?>">
        <i class="fa-solid fa-list"></i>
        View Model / Parts
    	</a>
	</td>

	<td>
    <a class="zati-result-btn zati-btn-parts"
       href="<?php echo esc_url($parts_pdf_url); ?>"
       target="_blank"
       rel="noopener">
        <i class="fa-solid fa-file-pdf"></i>
        Parts Price List
    </a>
</td>
<td>
<?php if ($service_manual_pdf): ?>
    <a class="zati-result-btn zati-btn-service"
       href="<?php echo esc_url($service_manual_pdf); ?>"
       target="_blank">
        <i class="fa-solid fa-wrench"></i>
        Service Manual
    </a>
<?php endif; ?>
</td>
<td>
<?php if ($user_manual_pdf): ?>
    <a class="zati-result-btn zati-btn-user"
       href="<?php echo esc_url($user_manual_pdf); ?>"
       target="_blank"
       rel="noopener"
       title="User Manual">
        <i class="fa-solid fa-book-open"></i>
        User Manual
    </a>
<?php endif; ?>
</td>
<td class="zati-technical-info-cell">
<?php if ($technical_info): ?>
    <a class="zati-tech-icon"
       href="<?php echo esc_url($technical_info); ?>"
       target="_blank"
       rel="noopener"
       title="Technical Information">
        <i class="fa-solid fa-triangle-exclamation"></i>
    </a>
<?php endif; ?>
</td>
</tr>
    <?php
}



?>
<div class="app-page-wrapper">
<main class="app-main search-layout">

<aside class="sidebar">
    <?php get_template_part('sidebar-search'); ?>
</aside>

<div class="search-results">

<h1>Model Search Results</h1>

<?php if (empty($raw) && !$cat_id && !$subcat_id): ?>
<p>Please enter a keyword.</p>

<?php else: ?>

<?php if (!empty($raw)): ?>
    <p>Search keyword: <strong><?php echo esc_html($raw); ?></strong></p>
<?php elseif ($cat_id || $subcat_id): ?>
    <p>Search by category</p>
<?php endif; ?>

<?php
$keyword = strtoupper(trim($raw));
$keyword_clean = preg_replace('/[^A-Z0-9\-]/', '', $keyword);

/* =========================
   1. Model Search First
========================= */
$args = [
    'post_type'      => 'model_alias',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
];

if (!empty($keyword)) {
    $args['s'] = $keyword;
}

if ($subcat_id) {

    $args['tax_query'] = [
        [
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => [$subcat_id],
            'include_children' => false,
            'operator'         => 'IN',
        ]
    ];

} elseif ($cat_id) {

    $args['tax_query'] = [
        [
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => [$cat_id],
            'include_children' => true,
            'operator'         => 'IN',
        ]
    ];
}

$model_query = new WP_Query($args);

$model_posts = $model_query->posts;

$category_priority = [
    'rice cookers' => [
        'pressure ih'  => 1,
        'ih'           => 2,
        'micom'        => 3,
        'conventional' => 4,
    ],
    'water boilers & warmers' => [
        've (vacuum-electric hybrid)' => 1,
        'micom'                       => 2,
    ],
    'breadmakers' => [
        '2 lb' => 1,
        '1 lb' => 2,
    ],
    'coffee makers' => [
        'automatic drip' => 1,
        'french press'   => 2,
    ],
    'cooking appliances' => [
        'grilled' => 1,
        'skillet' => 2,
        'other'   => 3,
    ],
];
function zati_sort_key($text) {
    $text = html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8');
    $text = trim($text);
    $text = preg_replace('/\s+/', ' ', $text);
    return strtolower($text);
}

usort($model_posts, function ($a, $b) use ($category_priority) {

    $cat_a = '';
    $cat_b = '';
    $sub_a = '';
    $sub_b = '';

    foreach (wp_get_post_terms($a->ID, 'product_category') as $term) {
        if ((int) $term->parent === 0) {
            $cat_a = $term->name;
        } else {
            $sub_a = $term->name;
            $parent = get_term($term->parent, 'product_category');
            if ($parent && !is_wp_error($parent)) {
                $cat_a = $parent->name;
            }
        }
    }

    foreach (wp_get_post_terms($b->ID, 'product_category') as $term) {
        if ((int) $term->parent === 0) {
            $cat_b = $term->name;
        } else {
            $sub_b = $term->name;
            $parent = get_term($term->parent, 'product_category');
            if ($parent && !is_wp_error($parent)) {
                $cat_b = $parent->name;
            }
        }
    }

    $cat_key_a = zati_sort_key($cat_a);
    $cat_key_b = zati_sort_key($cat_b);
    $sub_key_a = zati_sort_key($sub_a);
    $sub_key_b = zati_sort_key($sub_b);

    $order_a = $category_priority[$cat_key_a][$sub_key_a] ?? 99;
    $order_b = $category_priority[$cat_key_b][$sub_key_b] ?? 99;
    if ($order_a !== $order_b) {
        return $order_a <=> $order_b;
    }

    return strcasecmp(get_the_title($a), get_the_title($b));
});

$is_model_search = ($model_query->found_posts > 0);

$found_parts = [];

/* =========================
   2. Parts Search Only If Keyword Search And No Model Found
========================= */

if (!$is_model_search && !empty($keyword_clean)) {

    $parts_query = new WP_Query([
        'post_type'      => 'part',
        'posts_per_page' => -1
    ]);

    if ($parts_query->have_posts()):
        while ($parts_query->have_posts()): $parts_query->the_post();

            $pid = get_the_ID();

            $title       = get_the_title($pid);
            $model       = get_post_meta($pid, 'model', true);
            $no          = get_post_meta($pid, 'diagram_no', true);
            $partnumber  = get_post_meta($pid, 'partnumber', true);
            $description = get_post_meta($pid, 'description', true);
            $jpnumber    = get_post_meta($pid, 'jpnumber', true);

            $haystack = strtoupper($title . ' ' . $no . ' ' . $partnumber . ' ' . $description . ' ' . $jpnumber);
	    $haystack = zati_normalize_search_text($haystack);
	    $haystack_clean = preg_replace('/[^A-Z0-9\-]/', '', $haystack);
            if (strpos($haystack_clean, $keyword_clean) !== false) {
                $found_parts[] = $pid;
            }

        endwhile;
    endif;

    wp_reset_postdata();
}

$total = $is_model_search ? $model_query->found_posts : count($found_parts);

$total_pages = $is_model_search ? max(1, (int) $model_query->max_num_pages) : 1;
$total_items = $is_model_search ? (int) $model_query->found_posts : count($found_parts);

$start_item = $total_items > 0 ? (($paged - 1) * $per_page + 1) : 0;
$end_item   = min($paged * $per_page, $total_items);

$base_url = home_url('/model-search/');
?>

<?php
$result_label = '';

if (!empty($raw)) {
    $result_label = $raw;
} elseif ($subcat_id) {
    $term = get_term($subcat_id, 'product_category');
    if ($term && !is_wp_error($term)) {
        $result_label = $term->name;
    }
} elseif ($cat_id) {
    $term = get_term($cat_id, 'product_category');
    if ($term && !is_wp_error($term)) {
        $result_label = $term->name;
    }
}
?>

<div class="zati-result-summary">
    <span class="zati-result-count">
        <?php echo esc_html($start_item); ?>-<?php echo esc_html($end_item); ?>
        of <?php echo esc_html($total_items); ?> results for
    </span>

    <?php if ($result_label): ?>
        <span class="zati-result-keyword">
            "<?php echo esc_html($result_label); ?>"
        </span>
    <?php endif; ?>
</div>

<?php if ($total > 0): ?>

<?php if ($model_query->have_posts()): ?>

<?php zati_render_pagination($paged, $total_pages, $total_items, $start_item, $end_item, $cat_id, $subcat_id, $per_page, $base_url); ?>

<table class="zati-used-models-table">
<thead>
<tr>
<th>Product Nos.</th>
<th>Category</th>
<th>Subcategory</th>
<th>View Model / Parts</th>
<th>Parts Price List</th>
<th>Service Manual</th>
<th>User Manual</th>
<th>Technical Information</th>
</tr>
</thead>
<tbody>

<?php foreach ($model_posts as $post) :
    setup_postdata($post); ?>
    <?php
    $model_id = get_the_ID();

    if (get_post_status($model_id) !== 'publish') {
        continue;
    }
    ?>

    <?php render_model_result_row($model_id); ?>
<?php endforeach;
wp_reset_postdata(); ?>
</tbody>
</table>


<div class="zati-pagination">

<?php zati_render_pagination($paged, $total_pages, $total_items, $start_item, $end_item, $cat_id, $subcat_id, $per_page, $base_url); ?>
</div>
<?php endif; ?>



<?php wp_reset_postdata(); ?>

<?php if (!$is_model_search && !empty($keyword_clean) && !empty($found_parts)): ?>
<h2>Parts Results</h2>

<table class="zati-parts-results-table">
<thead>
<tr>
    <th>Part No</th>
    <th>JP No</th>
    <th>Description</th>
    <th>Size</th>
    <th>SVC Price</th>
   <th>Retail Price</th>
</tr>
</thead>

<tbody>
<?php $shown_partnumbers = []; ?>

<?php foreach ($found_parts as $pid): ?>
<?php
$partnumber  = get_post_meta($pid, 'partnumber', true);
$jpnumber    = get_post_meta($pid, 'jpnumber', true);
$description = get_post_meta($pid, 'description', true);

$part_key = strtoupper(trim($partnumber));

if (!empty($part_key)) {
    if (isset($shown_partnumbers[$part_key])) {
        continue;
    }

    $shown_partnumbers[$part_key] = true;
}

$distributor_price = get_post_meta(
    $pid,
    'distributor_price',
    true
);

$svc_price = zati_get_part_price_by_role(
    $distributor_price
);

$retail_price = get_post_meta(
    $pid,
    'retail_price',
    true
);

$display_partnumber = !empty($partnumber) ? $partnumber : get_the_title($pid);
?>
<tr>
    <td>
    <?php echo esc_html($display_partnumber); ?>
    </td>
    <td><?php echo esc_html($jpnumber); ?></td> 
    <td><?php echo esc_html($description); ?></td>
    <td><?php echo esc_html(get_post_meta($pid, 'size', true)); ?></td>
    <td>
   <?php
   if ($svc_price !== '' && is_numeric($svc_price)) {
    echo esc_html(
        '$' . number_format((float) $svc_price, 2)
    );
   } else {
    echo '-';
  }
   ?>
   </td>

  <td>
   <?php
  if ($retail_price !== '' && is_numeric($retail_price)) {
    echo esc_html(
        '$' . number_format((float) $retail_price, 2)
    );
  } else {
    echo '-';
}
?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>


<?php endif; // end model_query have_posts ?>


<?php if (
    !$is_model_search &&
    !empty($keyword_clean) &&
    !empty($found_parts)
) : ?>

<?php

/*
 * ========================================
 * Used in Models
 *
 * Search Model Parts by Part Number,
 * then collect related models.
 * ========================================
 */

$used_models = [];

/*
 * 1. Get unique Part Numbers
 *    from the Parts search results.
 */
$searched_partnumbers = [];

foreach ( $found_parts as $pid ) {

    $partnumber = sanitize_text_field(
        get_post_meta(
            $pid,
            'partnumber',
            true
        )
    );

    if ( $partnumber === '' ) {
        continue;
    }

    $part_key = strtoupper(
        trim($partnumber)
    );

    $searched_partnumbers[$part_key] =
        $partnumber;
}


/*
 * 2. Search Model Parts records
 *    using each Part Number.
 */
foreach (
    $searched_partnumbers
    as $partnumber
) {

    $model_part_query = new WP_Query([
        'post_type'      => 'model_part',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => 'partnumber',
                'value'   => $partnumber,
                'compare' => '=',
            ],
        ],
    ]);

    if (
        $model_part_query->have_posts()
    ) {

        foreach (
            $model_part_query->posts
            as $model_part_post
        ) {

            $model_name =
                sanitize_text_field(
                    get_post_meta(
                        $model_part_post->ID,
                        'model',
                        true
                    )
                );


            if ( $model_name === '' ) {
                continue;
            }

            /*
             * One model may contain
             * the same part more than once.
             *
             */
$used_key = strtoupper(
    trim($model_name)
);

if (
    ! isset(
        $used_models[$used_key]
    )
) {

    $used_models[$used_key] = [
        'model' => $model_name,
    ];
}        }
    }

    wp_reset_postdata();
}


/*
 * Sort by Model name,
 * then Diagram No.
 */
uasort(
    $used_models,
    static function ($a, $b) {

        $model_compare =
            strcasecmp(
                $a['model'],
                $b['model']
            );

        if ( $model_compare !== 0 ) {
            return $model_compare;
        }

        return strnatcasecmp(
            $a['diagram_no'],
            $b['diagram_no']
        );
    }
);

?>


<?php if ( ! empty($used_models) ) : ?>

    <h2>Used in Models</h2>

    <table class="zati-used-models-table">

        <thead>
            <tr>
                <th>Product Nos.</th>
                <th>Category</th>
                <th>Subcategory</th>
                <th>View Model / Parts</th>
                <th>Parts Price List</th>
                <th>Service Manual</th>
                <th>User Manual</th>
                <th>Technical Information</th>
            </tr>
            </thead>

        <tbody>

        <?php foreach (
            $used_models
            as $used_model
        ) : ?>

            <?php

            $model_name =
                $used_model['model'];


            /*
             * Find matching Model Alias.
             */
            $related_model_query =
                new WP_Query([
                    'post_type'      =>
                        'model_alias',

                    'post_status'    =>
                        'publish',

                    'posts_per_page' =>
                        1,

                    'title' =>
                        $model_name,
                ]);


            if (
                $related_model_query
                    ->have_posts()
            ) :

                $model_page =
                    $related_model_query
                        ->posts[0];

                $model_id =
                    $model_page->ID;


                /*
                 * Category / Subcategory
                 */
                $terms =
                    get_the_terms(
                        $model_id,
                        'product_category'
                    );

                $category = '';
                $subcategory = '';

                if (
                    ! empty($terms) &&
                    ! is_wp_error($terms)
                ) {

                    foreach (
                        $terms as $term
                    ) {

                        if (
                            (int) $term->parent
                            === 0
                        ) {

                            $category =
                                $term->name;

                        } else {

                            $subcategory =
                                $term->name;

                            $parent =
                                get_term(
                                    $term->parent,
                                    'product_category'
                                );

                            if (
                                $parent &&
                                ! is_wp_error(
                                    $parent
                                )
                            ) {

                                $category =
                                    $parent->name;
                            }
                        }
                    }
                }


                /*
                 * Links
                 */
                $parts_pdf_url =
                    add_query_arg(
                        'download_parts_pdf',
                        '1',
                        get_permalink(
                            $model_id
                        )
                    );

                $service_manual_pdf =
                    get_field(
                        'service_manual_pdf',
                        $model_id
                    );

                $user_manual_pdf =
                    get_field(
                        'user_manual_pdf',
                        $model_id
                    );

                $technical_info =
                    get_field(
                        'technical_info',
                        $model_id
                    );

            ?>

                <tr>

                    <td>

                        <a
                            href="<?php
                                echo esc_url(
                                    get_permalink(
                                        $model_id
                                    )
                                );
                            ?>"
                        >
                            <?php
                            echo esc_html(
                                $model_name
                            );
                            ?>
                        </a>

                    </td>

                    <td>
                        <?php
                        echo esc_html(
                            $category
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo esc_html(
                            $subcategory
                        );
                        ?>
                    </td>

                    <td>

                        <a
                            class="
                                zati-result-btn
                                zati-btn-model
                            "
                            href="<?php
                                echo esc_url(
                                    get_permalink(
                                        $model_id
                                    )
                                );
                            ?>"
                        >
                            <i
                                class="
                                    fa-solid
                                    fa-list
                                "
                            ></i>

                            View Model / Parts
                        </a>

                    </td>

                    <td>

                        <a
                            class="
                                zati-result-btn
                                zati-btn-parts
                            "
                            href="<?php
                                echo esc_url(
                                    $parts_pdf_url
                                );
                            ?>"
                            target="_blank"
                            rel="noopener"
                            >
                            <i
                                class="
                                    fa-solid
                                    fa-file-pdf
                                "
                            ></i>

                            Parts Price List
                        </a>

                    </td>


                    <td>

                        <?php if (
                            $service_manual_pdf
                        ) : ?>

                            <a
                                class="
                                    zati-result-btn
                                    zati-btn-service
                                "
                                href="<?php
                                    echo esc_url(
                                        $service_manual_pdf
                                    );
                                ?>"
                                target="_blank"
                                rel="noopener"
                            >
                                <i
                                    class="
                                        fa-solid
                                        fa-wrench
                                    "
                                ></i>

                                Service Manual
                            </a>

                        <?php endif; ?>

                    </td>


                    <td>

                        <?php if (
                            $user_manual_pdf
                        ) : ?>

                            <a
                                class="
                                    zati-result-btn
                                    zati-btn-user
                                "
                                href="<?php
                                    echo esc_url(
                                        $user_manual_pdf
                                    );
                                ?>"
                                target="_blank"
                                rel="noopener"
                            >
                                <i
                                    class="
                                        fa-solid
                                        fa-book-open
                                    "
                                ></i>

                                User Manual
                            </a>

                        <?php endif; ?>

                    </td>


                    <td
                        class="
                            zati-technical-info-cell
                        "
                    >

                        <?php if (
                            $technical_info
                        ) : ?>

                            <a
                                class="
                                    zati-tech-icon
                                "
                                href="<?php
                                    echo esc_url(
                                        $technical_info
                                    );
                                ?>"
                                target="_blank"
                                rel="noopener"
                                title="
                                    Technical Information
                                "
                            >
                                <i
                                    class="
                                        fa-solid
                                        fa-triangle-exclamation
                                    "
                                ></i>
                            </a>

                        <?php endif; ?>

                    </td>

                </tr>


            <?php else : ?>

                <tr>

                    <td>
                        <?php
                        echo esc_html(
                            $model_name
                        );
                        ?>
                    </td>

                    <td colspan="7">
                        Model information not found.
                    </td>

                </tr>

            <?php endif; ?>

            <?php
            wp_reset_postdata();
            ?>

        <?php endforeach; ?>

        </tbody>

    </table>

<?php endif; ?>

<?php endif; ?>


<?php else: ?>

<p>No matching results found.</p>

<?php endif; ?>

<?php endif; ?>

</div>
</main>

<?php get_template_part('app-footer'); ?>
</div>
<?php get_footer('blank'); ?>