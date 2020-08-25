<?php // Register the post type
add_action( 'init', function() {

	$posttype_labels = array(
		'name' => __('Testimonials', 'mad'),
		'singular_name' => __('Testimonial', 'mad'),
		'add_new' => __('Add Testimonial', 'mad'),
		'add_new_item' => __('Add New Testimonial', 'mad'),
		'edit_item' => __('Edit Testimonial', 'mad'),
		'new_item' => __('New Testimonial', 'mad'),
		'all_items' => __('All Testimonials', 'mad'),
		'view_item' => __('View Testimonial', 'mad'),
		'search_items' => __('Search Testimonials', 'mad'),
		'not_found' =>  __('No Testimonials found', 'mad'),
		'not_found_in_trash' => __('No Testimonials found in Trash', 'mad'),
		'parent_item_colon' => '',
		'menu_name' => __('Testimonials', 'mad')
	);
	$posttype_args = array(
		'labels' => $posttype_labels,
		'public' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_icon'   => 'dashicons-editor-quote',
		'query_var' => true,
		'rewrite' => array( 'slug' => 'testimonials' ),
		'capability_type' => 'page',
		'has_archive' => true,
		'hierarchical' => true,
		'menu_position' => 20,
		'supports' => array( 'title', 'page-attributes', 'editor', 'thumbnail' )
	);
	register_post_type( 'mad360_testimonial', $posttype_args );

});

// Add the meta box for the author of the testimonial
add_action('add_meta_boxes', function() {
	$screens = array('mad360_testimonial');

	foreach ($screens as $screen) {
		add_meta_box(
			'testimonial-author',
			__('Testimonial Author', 'mad360-testimonials'),
			function($post) {
				ob_start(); ?>
				<input type="text" class="large-text" name="testimonial_author" value="<?php echo esc_attr(get_post_meta($post->ID, 'testimonial_author', true)); ?>">
				<?php echo ob_get_clean();
			},
			$screen,
			'side'
		);
	}
});

// Save the value of the author field to the testimonial postmeta
add_action('save_post_mad360_testimonial', function($post_id) {
	if (!isset($_POST['testimonial_author'])) {
		return;
	}

	$author = sanitize_text_field($_POST['testimonial_author']);

	update_post_meta($post_id, 'testimonial_author', $author);
});