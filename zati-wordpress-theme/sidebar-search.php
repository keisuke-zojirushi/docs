<div class="zac-search-box">

  <form class="zac-search-form" action="<?php echo esc_url(home_url('/model-search/')); ?>" method="get">

    <h3>Search by Keyword</h3>

    <input type="search" name="q"
      placeholder="Enter model"
      value="<?php echo isset($_GET['q']) ? esc_attr($_GET['q']) : ''; ?>">

    <div class="zac-form-buttons">
      <button type="submit">Search</button>
      <button type="button" class="reset_btn"
        onclick="window.location.href='<?php echo esc_url(home_url('/model-search/')); ?>'">
        Clear
      </button>
    </div>

  </form>

  <hr>

  <form class="zac-search-form" action="<?php echo esc_url(home_url('/model-search/')); ?>" method="get">

    <?php
    $selected_cat = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
    $selected_subcat = isset($_GET['subcat']) ? intval($_GET['subcat']) : 0;
    ?>

    <h3>Search by Category</h3>

    <select name="cat" id="category-select">
      <option value="">Select Category</option>

      <?php
      $terms = get_terms([
        'taxonomy' => 'product_category',
        'hide_empty' => false,
        'parent' => 0,
        'orderby' => 'term_id',
        'order' => 'ASC'
      ]);

      foreach ($terms as $term): ?>
        <option value="<?php echo $term->term_id; ?>"
          <?php selected($selected_cat, $term->term_id); ?>>
          <?php echo esc_html($term->name); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="subcat" id="subcat-select"
      <?php echo ($selected_cat === 0) ? 'disabled' : ''; ?>>

      <?php
      if ($selected_cat === 0) {
        echo '<option>Select Category First</option>';
      } else {
        echo '<option value="">All</option>';

        $sub_terms = get_terms([
          'taxonomy' => 'product_category',
          'hide_empty' => false,
          'parent' => $selected_cat,
          'orderby' => 'term_id',
          'order' => 'ASC'
        ]);

        foreach ($sub_terms as $term): ?>
          <option value="<?php echo $term->term_id; ?>"
            <?php selected($selected_subcat, $term->term_id); ?>>
            <?php echo esc_html($term->name); ?>
          </option>
        <?php endforeach;
      }
      ?>

    </select>

    <div class="zac-form-buttons">
      <button type="submit">Search</button>
           <button type="button" class="reset_btn"
        onclick="window.location.href='<?php echo esc_url(home_url('/model-search/')); ?>'">
        Clear
      </button>
    </div>

  </form>

</div>

<script>
document.getElementById('category-select').addEventListener('change', function () {
  this.form.submit();
});
</script>