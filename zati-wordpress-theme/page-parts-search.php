<?php
/*
 * Template Name: Parts Search
 * Template Post Type: page
 */

if ( ! is_user_logged_in() ) {
    wp_safe_redirect(
        home_url('/login/')
    );
    exit;
}

get_header('blank');
get_template_part('app-header');
?>

<main class="zati-search-portal">

    <section class="zati-search-portal-wrap">

        <div class="zati-search-portal-header">

            <h1>
                Parts Search
            </h1>

            <p>
                Search by part number, JP number or description.
            </p>

        </div>

        <form
            class="zati-search-portal-form"
            method="get"
            action="<?php echo esc_url(
                home_url('/model-search/')
            ); ?>"
        >

            <label
                for="zati-parts-search"
                class="zati-search-portal-label"
            >
                Part Number / JP Number / Description
            </label>

            <div class="zati-search-portal-row">

                <input
                    id="zati-parts-search"
                    type="text"
                    name="q"
                    class="zati-search-portal-input"
                    placeholder="Enter part number or keyword"
                    autocomplete="off"
                    required
                >

                <button
                    type="submit"
                    class="zati-primary-btn"
                >
                    SEARCH
                </button>

            </div>

        </form>

        <div class="zati-search-portal-help">

            <span>
                Examples:
            </span>

            <strong>
                8-NHV-P190
            </strong>

            <strong>
                617686
            </strong>

        </div>

    </section>

</main>

<?php
get_template_part('app-footer');
get_footer('blank');