<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action(
    'template_redirect',
    'zati_handle_parts_order_xlsx_download'
);

function zati_handle_parts_order_xlsx_download() {

    if (
        empty($_GET['zati_download_parts_order_xlsx']) ||
        empty($_GET['order_id'])
    ) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_die('Unauthorized.');
    }

    $order_id = absint($_GET['order_id']);

    if (
        !$order_id ||
        get_post_type($order_id) !== 'parts_order'
    ) {
        wp_die('Invalid order.');
    }

    $file_path =
        zati_create_parts_order_email_xlsx(
            $order_id
        );

    if (
        !$file_path ||
        !file_exists($file_path) ||
        !is_readable($file_path)
    ) {
        wp_die(
            'Excel file could not be generated.'
        );
    }

    $filename = basename($file_path);

    nocache_headers();

    header(
        'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    header(
        'Content-Disposition: attachment; filename="' .
        $filename .
        '"'
    );

    header(
        'Content-Length: ' .
        filesize($file_path)
    );

    readfile($file_path);

    @unlink($file_path);

    exit;
}