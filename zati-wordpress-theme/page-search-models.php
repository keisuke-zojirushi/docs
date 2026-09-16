<?php
/*
 * Template Name: Search Models
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
                Search Models
            </h1>

            <p>
                Search by model number or model family to find
                technical information, manuals and parts information.
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
                for="zati-model-search"
                class="zati-search-portal-label"
            >
                Model Number
            </label>

            <div class="zati-search-portal-row">

                <input
                    id="zati-model-search"
                    type="text"
                    name="q"
                    class="zati-search-portal-input"
                    placeholder="Enter model number"
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
                NS-TSC10
            </strong>

            <strong>
                NP-HCC10
            </strong>

            <strong>
                CV-DCC40
            </strong>

        </div>

    </section>

</main>

<?php
get_template_part('app-footer');
get_footer('blank');