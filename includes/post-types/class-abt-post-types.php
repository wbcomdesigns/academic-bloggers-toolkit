<?php
/**
 * Register all custom post types and taxonomies
 *
 * @link       https://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 */

/**
 * Register all custom post types and taxonomies.
 *
 * This class defines all custom post types and taxonomies used by the plugin:
 * - abt_blog (Academic blog posts - public)
 * - abt_reference (Reference library - admin only)
 * - abt_citation (Citation instances - hidden)
 * - abt_footnote (Footnotes - hidden)
 * - abt_bibliography (Generated bibliographies - hidden)
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/includes/post-types
 * @author     Academic Blogger's Toolkit Team
 */
class ABT_Post_Types {

	/**
	 * Register all custom post types.
	 *
	 * @since    1.0.0
	 */
	public function register_post_types() {
		$this->register_academic_blog_post_type();
		$this->register_reference_post_type();
		$this->register_citation_post_type();
		$this->register_footnote_post_type();
		$this->register_bibliography_post_type();
	}

	/**
	 * Register all custom taxonomies.
	 *
	 * @since    1.0.0
	 */
	public function register_taxonomies() {
		$this->register_blog_category_taxonomy();
		$this->register_blog_tag_taxonomy();
		$this->register_subject_taxonomy();
		$this->register_reference_category_taxonomy();
	}

	/**
	 * Register academic blog post type.
	 * 
	 * Public-facing academic blog posts with citation support.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_academic_blog_post_type() {
		$labels = array(
			'name'                  => _x( 'Academic Posts', 'Post Type General Name', 'academic-bloggers-toolkit' ),
			'singular_name'         => _x( 'Academic Post', 'Post Type Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'             => __( 'Academic Blog', 'academic-bloggers-toolkit' ),
			'name_admin_bar'        => __( 'Academic Post', 'academic-bloggers-toolkit' ),
			'archives'              => __( 'Academic Post Archives', 'academic-bloggers-toolkit' ),
			'attributes'            => __( 'Academic Post Attributes', 'academic-bloggers-toolkit' ),
			'parent_item_colon'     => __( 'Parent Academic Post:', 'academic-bloggers-toolkit' ),
			'all_items'             => __( 'All Academic Posts', 'academic-bloggers-toolkit' ),
			'add_new_item'          => __( 'Add New Academic Post', 'academic-bloggers-toolkit' ),
			'add_new'               => __( 'Add New', 'academic-bloggers-toolkit' ),
			'new_item'              => __( 'New Academic Post', 'academic-bloggers-toolkit' ),
			'edit_item'             => __( 'Edit Academic Post', 'academic-bloggers-toolkit' ),
			'update_item'           => __( 'Update Academic Post', 'academic-bloggers-toolkit' ),
			'view_item'             => __( 'View Academic Post', 'academic-bloggers-toolkit' ),
			'view_items'            => __( 'View Academic Posts', 'academic-bloggers-toolkit' ),
			'search_items'          => __( 'Search Academic Posts', 'academic-bloggers-toolkit' ),
			'not_found'             => __( 'Not found', 'academic-bloggers-toolkit' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'academic-bloggers-toolkit' ),
			'featured_image'        => __( 'Featured Image', 'academic-bloggers-toolkit' ),
			'set_featured_image'    => __( 'Set featured image', 'academic-bloggers-toolkit' ),
			'remove_featured_image' => __( 'Remove featured image', 'academic-bloggers-toolkit' ),
			'use_featured_image'    => __( 'Use as featured image', 'academic-bloggers-toolkit' ),
			'insert_into_item'      => __( 'Insert into academic post', 'academic-bloggers-toolkit' ),
			'uploaded_to_this_item' => __( 'Uploaded to this academic post', 'academic-bloggers-toolkit' ),
			'items_list'            => __( 'Academic posts list', 'academic-bloggers-toolkit' ),
			'items_list_navigation' => __( 'Academic posts list navigation', 'academic-bloggers-toolkit' ),
			'filter_items_list'     => __( 'Filter academic posts list', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'label'                 => __( 'Academic Post', 'academic-bloggers-toolkit' ),
			'description'           => __( 'Academic blog posts with citation support', 'academic-bloggers-toolkit' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields' ),
			'taxonomies'            => array( 'abt_blog_category', 'abt_blog_tag', 'abt_subject' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-book-alt',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
			'rest_base'             => 'academic-posts',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'rewrite'               => array(
				'slug'       => 'academic-blog',
				'with_front' => false,
				'feeds'      => true,
				'pages'      => true,
			),
		);

		register_post_type( 'abt_blog', $args );
	}

	/**
	 * Register reference post type.
	 * 
	 * Admin-only reference library for managing citations.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_reference_post_type() {
		$labels = array(
			'name'                  => _x( 'References', 'Post Type General Name', 'academic-bloggers-toolkit' ),
			'singular_name'         => _x( 'Reference', 'Post Type Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'             => __( 'References', 'academic-bloggers-toolkit' ),
			'name_admin_bar'        => __( 'Reference', 'academic-bloggers-toolkit' ),
			'archives'              => __( 'Reference Archives', 'academic-bloggers-toolkit' ),
			'attributes'            => __( 'Reference Attributes', 'academic-bloggers-toolkit' ),
			'parent_item_colon'     => __( 'Parent Reference:', 'academic-bloggers-toolkit' ),
			'all_items'             => __( 'All References', 'academic-bloggers-toolkit' ),
			'add_new_item'          => __( 'Add New Reference', 'academic-bloggers-toolkit' ),
			'add_new'               => __( 'Add New', 'academic-bloggers-toolkit' ),
			'new_item'              => __( 'New Reference', 'academic-bloggers-toolkit' ),
			'edit_item'             => __( 'Edit Reference', 'academic-bloggers-toolkit' ),
			'update_item'           => __( 'Update Reference', 'academic-bloggers-toolkit' ),
			'view_item'             => __( 'View Reference', 'academic-bloggers-toolkit' ),
			'view_items'            => __( 'View References', 'academic-bloggers-toolkit' ),
			'search_items'          => __( 'Search References', 'academic-bloggers-toolkit' ),
			'not_found'             => __( 'Not found', 'academic-bloggers-toolkit' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'academic-bloggers-toolkit' ),
			'featured_image'        => __( 'Featured Image', 'academic-bloggers-toolkit' ),
			'set_featured_image'    => __( 'Set featured image', 'academic-bloggers-toolkit' ),
			'remove_featured_image' => __( 'Remove featured image', 'academic-bloggers-toolkit' ),
			'use_featured_image'    => __( 'Use as featured image', 'academic-bloggers-toolkit' ),
			'insert_into_item'      => __( 'Insert into reference', 'academic-bloggers-toolkit' ),
			'uploaded_to_this_item' => __( 'Uploaded to this reference', 'academic-bloggers-toolkit' ),
			'items_list'            => __( 'References list', 'academic-bloggers-toolkit' ),
			'items_list_navigation' => __( 'References list navigation', 'academic-bloggers-toolkit' ),
			'filter_items_list'     => __( 'Filter references list', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'label'                 => __( 'Reference', 'academic-bloggers-toolkit' ),
			'description'           => __( 'Reference library for citation management', 'academic-bloggers-toolkit' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'author', 'custom-fields', 'revisions' ),
			'taxonomies'            => array( 'abt_ref_category' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 6,
			'menu_icon'             => 'dashicons-book',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
			'rest_base'             => 'references',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		);

		register_post_type( 'abt_reference', $args );
	}

	/**
	 * Register citation post type.
	 * 
	 * Hidden post type for individual citation instances.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_citation_post_type() {
		$labels = array(
			'name'                  => _x( 'Citations', 'Post Type General Name', 'academic-bloggers-toolkit' ),
			'singular_name'         => _x( 'Citation', 'Post Type Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'             => __( 'Citations', 'academic-bloggers-toolkit' ),
			'name_admin_bar'        => __( 'Citation', 'academic-bloggers-toolkit' ),
			'all_items'             => __( 'All Citations', 'academic-bloggers-toolkit' ),
			'add_new_item'          => __( 'Add New Citation', 'academic-bloggers-toolkit' ),
			'add_new'               => __( 'Add New', 'academic-bloggers-toolkit' ),
			'new_item'              => __( 'New Citation', 'academic-bloggers-toolkit' ),
			'edit_item'             => __( 'Edit Citation', 'academic-bloggers-toolkit' ),
			'update_item'           => __( 'Update Citation', 'academic-bloggers-toolkit' ),
			'search_items'          => __( 'Search Citations', 'academic-bloggers-toolkit' ),
			'not_found'             => __( 'Not found', 'academic-bloggers-toolkit' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'label'                 => __( 'Citation', 'academic-bloggers-toolkit' ),
			'description'           => __( 'Individual citation instances', 'academic-bloggers-toolkit' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'custom-fields' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => false,
			'show_in_menu'          => false,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => false,
		);

		register_post_type( 'abt_citation', $args );
	}

	/**
	 * Register footnote post type.
	 * 
	 * Hidden post type for footnote management.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_footnote_post_type() {
		$labels = array(
			'name'                  => _x( 'Footnotes', 'Post Type General Name', 'academic-bloggers-toolkit' ),
			'singular_name'         => _x( 'Footnote', 'Post Type Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'             => __( 'Footnotes', 'academic-bloggers-toolkit' ),
			'name_admin_bar'        => __( 'Footnote', 'academic-bloggers-toolkit' ),
			'all_items'             => __( 'All Footnotes', 'academic-bloggers-toolkit' ),
			'add_new_item'          => __( 'Add New Footnote', 'academic-bloggers-toolkit' ),
			'add_new'               => __( 'Add New', 'academic-bloggers-toolkit' ),
			'new_item'              => __( 'New Footnote', 'academic-bloggers-toolkit' ),
			'edit_item'             => __( 'Edit Footnote', 'academic-bloggers-toolkit' ),
			'update_item'           => __( 'Update Footnote', 'academic-bloggers-toolkit' ),
			'search_items'          => __( 'Search Footnotes', 'academic-bloggers-toolkit' ),
			'not_found'             => __( 'Not found', 'academic-bloggers-toolkit' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'label'                 => __( 'Footnote', 'academic-bloggers-toolkit' ),
			'description'           => __( 'Footnote management system', 'academic-bloggers-toolkit' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'custom-fields' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => false,
			'show_in_menu'          => false,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => false,
		);

		register_post_type( 'abt_footnote', $args );
	}

	/**
	 * Register bibliography post type.
	 * 
	 * Hidden post type for generated bibliographies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_bibliography_post_type() {
		$labels = array(
			'name'                  => _x( 'Bibliographies', 'Post Type General Name', 'academic-bloggers-toolkit' ),
			'singular_name'         => _x( 'Bibliography', 'Post Type Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'             => __( 'Bibliographies', 'academic-bloggers-toolkit' ),
			'name_admin_bar'        => __( 'Bibliography', 'academic-bloggers-toolkit' ),
			'all_items'             => __( 'All Bibliographies', 'academic-bloggers-toolkit' ),
			'add_new_item'          => __( 'Add New Bibliography', 'academic-bloggers-toolkit' ),
			'add_new'               => __( 'Add New', 'academic-bloggers-toolkit' ),
			'new_item'              => __( 'New Bibliography', 'academic-bloggers-toolkit' ),
			'edit_item'             => __( 'Edit Bibliography', 'academic-bloggers-toolkit' ),
			'update_item'           => __( 'Update Bibliography', 'academic-bloggers-toolkit' ),
			'search_items'          => __( 'Search Bibliographies', 'academic-bloggers-toolkit' ),
			'not_found'             => __( 'Not found', 'academic-bloggers-toolkit' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'label'                 => __( 'Bibliography', 'academic-bloggers-toolkit' ),
			'description'           => __( 'Generated bibliographies for academic posts', 'academic-bloggers-toolkit' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'custom-fields' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => false,
			'show_in_menu'          => false,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => false,
		);

		register_post_type( 'abt_bibliography', $args );
	}

	/**
	 * Register academic blog category taxonomy.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_blog_category_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Academic Categories', 'Taxonomy General Name', 'academic-bloggers-toolkit' ),
			'singular_name'              => _x( 'Academic Category', 'Taxonomy Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'                  => __( 'Categories', 'academic-bloggers-toolkit' ),
			'all_items'                  => __( 'All Categories', 'academic-bloggers-toolkit' ),
			'parent_item'                => __( 'Parent Category', 'academic-bloggers-toolkit' ),
			'parent_item_colon'          => __( 'Parent Category:', 'academic-bloggers-toolkit' ),
			'new_item_name'              => __( 'New Category Name', 'academic-bloggers-toolkit' ),
			'add_new_item'               => __( 'Add New Category', 'academic-bloggers-toolkit' ),
			'edit_item'                  => __( 'Edit Category', 'academic-bloggers-toolkit' ),
			'update_item'                => __( 'Update Category', 'academic-bloggers-toolkit' ),
			'view_item'                  => __( 'View Category', 'academic-bloggers-toolkit' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'academic-bloggers-toolkit' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'academic-bloggers-toolkit' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'academic-bloggers-toolkit' ),
			'popular_items'              => __( 'Popular Categories', 'academic-bloggers-toolkit' ),
			'search_items'               => __( 'Search Categories', 'academic-bloggers-toolkit' ),
			'not_found'                  => __( 'Not Found', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'labels'                => $labels,
			'hierarchical'          => true,
			'public'                => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_nav_menus'     => true,
			'show_tagcloud'         => true,
			'show_in_rest'          => true,
			'rest_base'             => 'academic-categories',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'rewrite'               => array(
				'slug'         => 'academic-category',
				'with_front'   => false,
				'hierarchical' => true,
			),
		);

		register_taxonomy( 'abt_blog_category', array( 'abt_blog' ), $args );
	}

	/**
	 * Register academic blog tag taxonomy.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_blog_tag_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Academic Tags', 'Taxonomy General Name', 'academic-bloggers-toolkit' ),
			'singular_name'              => _x( 'Academic Tag', 'Taxonomy Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'                  => __( 'Tags', 'academic-bloggers-toolkit' ),
			'all_items'                  => __( 'All Tags', 'academic-bloggers-toolkit' ),
			'parent_item'                => __( 'Parent Tag', 'academic-bloggers-toolkit' ),
			'parent_item_colon'          => __( 'Parent Tag:', 'academic-bloggers-toolkit' ),
			'new_item_name'              => __( 'New Tag Name', 'academic-bloggers-toolkit' ),
			'add_new_item'               => __( 'Add New Tag', 'academic-bloggers-toolkit' ),
			'edit_item'                  => __( 'Edit Tag', 'academic-bloggers-toolkit' ),
			'update_item'                => __( 'Update Tag', 'academic-bloggers-toolkit' ),
			'view_item'                  => __( 'View Tag', 'academic-bloggers-toolkit' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'academic-bloggers-toolkit' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'academic-bloggers-toolkit' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'academic-bloggers-toolkit' ),
			'popular_items'              => __( 'Popular Tags', 'academic-bloggers-toolkit' ),
			'search_items'               => __( 'Search Tags', 'academic-bloggers-toolkit' ),
			'not_found'                  => __( 'Not Found', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'labels'                => $labels,
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_nav_menus'     => true,
			'show_tagcloud'         => true,
			'show_in_rest'          => true,
			'rest_base'             => 'academic-tags',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'rewrite'               => array(
				'slug'       => 'academic-tag',
				'with_front' => false,
			),
		);

		register_taxonomy( 'abt_blog_tag', array( 'abt_blog' ), $args );
	}

	/**
	 * Register subject area taxonomy.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_subject_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Subject Areas', 'Taxonomy General Name', 'academic-bloggers-toolkit' ),
			'singular_name'              => _x( 'Subject Area', 'Taxonomy Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'                  => __( 'Subject Areas', 'academic-bloggers-toolkit' ),
			'all_items'                  => __( 'All Subject Areas', 'academic-bloggers-toolkit' ),
			'parent_item'                => __( 'Parent Subject', 'academic-bloggers-toolkit' ),
			'parent_item_colon'          => __( 'Parent Subject:', 'academic-bloggers-toolkit' ),
			'new_item_name'              => __( 'New Subject Area Name', 'academic-bloggers-toolkit' ),
			'add_new_item'               => __( 'Add New Subject Area', 'academic-bloggers-toolkit' ),
			'edit_item'                  => __( 'Edit Subject Area', 'academic-bloggers-toolkit' ),
			'update_item'                => __( 'Update Subject Area', 'academic-bloggers-toolkit' ),
			'view_item'                  => __( 'View Subject Area', 'academic-bloggers-toolkit' ),
			'separate_items_with_commas' => __( 'Separate subjects with commas', 'academic-bloggers-toolkit' ),
			'add_or_remove_items'        => __( 'Add or remove subjects', 'academic-bloggers-toolkit' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'academic-bloggers-toolkit' ),
			'popular_items'              => __( 'Popular Subject Areas', 'academic-bloggers-toolkit' ),
			'search_items'               => __( 'Search Subject Areas', 'academic-bloggers-toolkit' ),
			'not_found'                  => __( 'Not Found', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'labels'                => $labels,
			'hierarchical'          => true,
			'public'                => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_nav_menus'     => true,
			'show_tagcloud'         => true,
			'show_in_rest'          => true,
			'rest_base'             => 'subject-areas',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'rewrite'               => array(
				'slug'         => 'subject',
				'with_front'   => false,
				'hierarchical' => true,
			),
		);

		register_taxonomy( 'abt_subject', array( 'abt_blog' ), $args );
	}

	/**
	 * Register reference category taxonomy.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_reference_category_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Reference Categories', 'Taxonomy General Name', 'academic-bloggers-toolkit' ),
			'singular_name'              => _x( 'Reference Category', 'Taxonomy Singular Name', 'academic-bloggers-toolkit' ),
			'menu_name'                  => __( 'Reference Categories', 'academic-bloggers-toolkit' ),
			'all_items'                  => __( 'All Reference Categories', 'academic-bloggers-toolkit' ),
			'parent_item'                => __( 'Parent Category', 'academic-bloggers-toolkit' ),
			'parent_item_colon'          => __( 'Parent Category:', 'academic-bloggers-toolkit' ),
			'new_item_name'              => __( 'New Reference Category Name', 'academic-bloggers-toolkit' ),
			'add_new_item'               => __( 'Add New Reference Category', 'academic-bloggers-toolkit' ),
			'edit_item'                  => __( 'Edit Reference Category', 'academic-bloggers-toolkit' ),
			'update_item'                => __( 'Update Reference Category', 'academic-bloggers-toolkit' ),
			'view_item'                  => __( 'View Reference Category', 'academic-bloggers-toolkit' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'academic-bloggers-toolkit' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'academic-bloggers-toolkit' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'academic-bloggers-toolkit' ),
			'popular_items'              => __( 'Popular Reference Categories', 'academic-bloggers-toolkit' ),
			'search_items'               => __( 'Search Reference Categories', 'academic-bloggers-toolkit' ),
			'not_found'                  => __( 'Not Found', 'academic-bloggers-toolkit' ),
		);

		$args = array(
			'labels'                => $labels,
			'hierarchical'          => true,
			'public'                => false,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_nav_menus'     => false,
			'show_tagcloud'         => false,
			'show_in_rest'          => true,
			'rest_base'             => 'reference-categories',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
		);

		register_taxonomy( 'abt_ref_category', array( 'abt_reference' ), $args );
	}
}