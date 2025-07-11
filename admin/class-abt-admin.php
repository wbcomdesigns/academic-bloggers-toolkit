<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in ABT_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The ABT_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( 
			$this->plugin_name, 
			ABT_PLUGIN_URL . 'admin/css/dist/admin-main.css', 
			array(), 
			$this->version, 
			'all' 
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in ABT_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The ABT_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( 
			$this->plugin_name, 
			ABT_PLUGIN_URL . 'admin/js/dist/admin-main.js', 
			array( 'jquery' ), 
			$this->version, 
			false 
		);

		// Localize script for AJAX
		wp_localize_script(
			$this->plugin_name,
			'abt_admin_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'abt_admin_nonce' ),
			)
		);
	}

	/**
	 * Add admin menu pages.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		// Main menu page
		add_menu_page(
			__( 'Academic Blogger\'s Toolkit', 'academic-bloggers-toolkit' ),
			__( 'ABT', 'academic-bloggers-toolkit' ),
			'manage_options',
			'academic-bloggers-toolkit',
			array( $this, 'display_references_page' ),
			'dashicons-book-alt',
			30
		);

		// References submenu (default page)
		add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'References', 'academic-bloggers-toolkit' ),
			__( 'References', 'academic-bloggers-toolkit' ),
			'manage_options',
			'academic-bloggers-toolkit',
			array( $this, 'display_references_page' )
		);

		// Statistics submenu
		add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'Statistics', 'academic-bloggers-toolkit' ),
			__( 'Statistics', 'academic-bloggers-toolkit' ),
			'manage_options',
			'abt-statistics',
			array( $this, 'display_statistics_page' )
		);

		// Settings submenu
		add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'Settings', 'academic-bloggers-toolkit' ),
			__( 'Settings', 'academic-bloggers-toolkit' ),
			'manage_options',
			'abt-settings',
			array( $this, 'display_settings_page' )
		);

		// Import/Export submenu
		add_submenu_page(
			'academic-bloggers-toolkit',
			__( 'Import/Export', 'academic-bloggers-toolkit' ),
			__( 'Import/Export', 'academic-bloggers-toolkit' ),
			'manage_options',
			'abt-import-export',
			array( $this, 'display_import_export_page' )
		);
	}

	/**
	 * Display the references management page.
	 *
	 * @since    1.0.0
	 */
	public function display_references_page() {
		require_once ABT_PLUGIN_DIR . 'admin/partials/pages/references-list.php';
	}

	/**
	 * Display the statistics page.
	 *
	 * @since    1.0.0
	 */
	public function display_statistics_page() {
		require_once ABT_PLUGIN_DIR . 'admin/partials/pages/statistics.php';
	}

	/**
	 * Display the settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		require_once ABT_PLUGIN_DIR . 'admin/partials/pages/settings.php';
	}

	/**
	 * Display the import/export page.
	 *
	 * @since    1.0.0
	 */
	public function display_import_export_page() {
		require_once ABT_PLUGIN_DIR . 'admin/partials/pages/import-export.php';
	}

	/**
	 * Add meta boxes to academic blog posts.
	 *
	 * @since    1.0.0
	 */
	public function add_meta_boxes() {
		// Academic blog post meta boxes
		add_meta_box(
			'abt_blog_citations',
			__( 'Citations & References', 'academic-bloggers-toolkit' ),
			array( $this, 'display_citations_metabox' ),
			'abt_blog',
			'normal',
			'high'
		);

		add_meta_box(
			'abt_blog_settings',
			__( 'Academic Settings', 'academic-bloggers-toolkit' ),
			array( $this, 'display_settings_metabox' ),
			'abt_blog',
			'side',
			'default'
		);

		add_meta_box(
			'abt_bibliography_preview',
			__( 'Bibliography Preview', 'academic-bloggers-toolkit' ),
			array( $this, 'display_bibliography_metabox' ),
			'abt_blog',
			'normal',
			'low'
		);

		// Reference meta boxes
		add_meta_box(
			'abt_reference_details',
			__( 'Reference Details', 'academic-bloggers-toolkit' ),
			array( $this, 'display_reference_metabox' ),
			'abt_reference',
			'normal',
			'high'
		);
	}

	/**
	 * Display citations meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function display_citations_metabox( $post ) {
		require_once ABT_PLUGIN_DIR . 'admin/partials/meta-boxes/blog-citations.php';
	}

	/**
	 * Display academic settings meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function display_settings_metabox( $post ) {
		require_once ABT_PLUGIN_DIR . 'admin/partials/meta-boxes/blog-settings.php';
	}

	/**
	 * Display bibliography preview meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function display_bibliography_metabox( $post ) {
		require_once ABT_PLUGIN_DIR . 'admin/partials/meta-boxes/bibliography-preview.php';
	}

	/**
	 * Display reference details meta box.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Current post object.
	 */
	public function display_reference_metabox( $post ) {
		require_once ABT_PLUGIN_DIR . 'admin/partials/meta-boxes/reference-form.php';
	}

	/**
	 * Save meta box data.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Current post ID.
	 */
	public function save_meta_boxes( $post_id ) {
		// Verify nonce
		if ( ! isset( $_POST['abt_meta_nonce'] ) || ! wp_verify_nonce( $_POST['abt_meta_nonce'], 'abt_save_meta' ) ) {
			return;
		}

		// Check if user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Get post type
		$post_type = get_post_type( $post_id );

		if ( 'abt_blog' === $post_type ) {
			$this->save_blog_meta( $post_id );
		} elseif ( 'abt_reference' === $post_type ) {
			$this->save_reference_meta( $post_id );
		}
	}

	/**
	 * Save academic blog meta data.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Current post ID.
	 */
	private function save_blog_meta( $post_id ) {
		// Academic settings
		if ( isset( $_POST['abt_citation_style'] ) ) {
			update_post_meta( $post_id, '_abt_citation_style', sanitize_text_field( $_POST['abt_citation_style'] ) );
		}

		if ( isset( $_POST['abt_enable_footnotes'] ) ) {
			update_post_meta( $post_id, '_abt_enable_footnotes', 1 );
		} else {
			delete_post_meta( $post_id, '_abt_enable_footnotes' );
		}

		if ( isset( $_POST['abt_auto_bibliography'] ) ) {
			update_post_meta( $post_id, '_abt_auto_bibliography', 1 );
		} else {
			delete_post_meta( $post_id, '_abt_auto_bibliography' );
		}

		// Subject areas
		if ( isset( $_POST['abt_subject_areas'] ) && is_array( $_POST['abt_subject_areas'] ) ) {
			$subject_areas = array_map( 'intval', $_POST['abt_subject_areas'] );
			wp_set_post_terms( $post_id, $subject_areas, 'abt_subject' );
		}
	}

	/**
	 * Save reference meta data.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Current post ID.
	 */
	private function save_reference_meta( $post_id ) {
		// Reference type
		if ( isset( $_POST['abt_reference_type'] ) ) {
			update_post_meta( $post_id, '_abt_reference_type', sanitize_text_field( $_POST['abt_reference_type'] ) );
		}

		// Authors
		if ( isset( $_POST['abt_authors'] ) ) {
			$authors = sanitize_textarea_field( $_POST['abt_authors'] );
			update_post_meta( $post_id, '_abt_authors', $authors );
		}

		// Publication year
		if ( isset( $_POST['abt_year'] ) ) {
			$year = intval( $_POST['abt_year'] );
			if ( $year > 0 ) {
				update_post_meta( $post_id, '_abt_year', $year );
			}
		}

		// Journal/Publisher
		if ( isset( $_POST['abt_journal'] ) ) {
			update_post_meta( $post_id, '_abt_journal', sanitize_text_field( $_POST['abt_journal'] ) );
		}

		// DOI
		if ( isset( $_POST['abt_doi'] ) ) {
			$doi = sanitize_text_field( $_POST['abt_doi'] );
			if ( $this->validate_doi( $doi ) ) {
				update_post_meta( $post_id, '_abt_doi', $doi );
			}
		}

		// URL
		if ( isset( $_POST['abt_url'] ) ) {
			$url = esc_url_raw( $_POST['abt_url'] );
			if ( $url ) {
				update_post_meta( $post_id, '_abt_url', $url );
			}
		}
	}

	/**
	 * Validate DOI format.
	 *
	 * @since    1.0.0
	 * @param    string    $doi    DOI to validate.
	 * @return   bool              True if valid DOI format.
	 */
	private function validate_doi( $doi ) {
		if ( empty( $doi ) ) {
			return true; // Allow empty DOI
		}

		// Basic DOI format validation
		return preg_match( '/^10\.\d{4,}\/\S+$/', $doi );
	}

	/**
	 * Add custom columns to academic blog posts list.
	 *
	 * @since    1.0.0
	 * @param    array    $columns    Existing columns.
	 * @return   array                Modified columns.
	 */
	public function add_blog_columns( $columns ) {
		// Add columns after title
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[$key] = $value;
			if ( 'title' === $key ) {
				$new_columns['abt_citations'] = __( 'Citations', 'academic-bloggers-toolkit' );
				$new_columns['abt_subject'] = __( 'Subject Areas', 'academic-bloggers-toolkit' );
				$new_columns['abt_citation_style'] = __( 'Citation Style', 'academic-bloggers-toolkit' );
			}
		}

		return $new_columns;
	}

	/**
	 * Display custom column content for academic blog posts.
	 *
	 * @since    1.0.0
	 * @param    string    $column     Column name.
	 * @param    int       $post_id    Post ID.
	 */
	public function display_blog_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'abt_citations':
				$citations = ABT_Citation::get_by_post( $post_id );
				echo count( $citations );
				break;

			case 'abt_subject':
				$terms = get_the_terms( $post_id, 'abt_subject' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$term_names = wp_list_pluck( $terms, 'name' );
					echo implode( ', ', $term_names );
				} else {
					echo '—';
				}
				break;

			case 'abt_citation_style':
				$style = get_post_meta( $post_id, '_abt_citation_style', true );
				echo $style ? esc_html( strtoupper( $style ) ) : 'APA';
				break;
		}
	}

	/**
	 * Add custom columns to references list.
	 *
	 * @since    1.0.0
	 * @param    array    $columns    Existing columns.
	 * @return   array                Modified columns.
	 */
	public function add_reference_columns( $columns ) {
		// Add columns after title
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[$key] = $value;
			if ( 'title' === $key ) {
				$new_columns['abt_ref_type'] = __( 'Type', 'academic-bloggers-toolkit' );
				$new_columns['abt_authors'] = __( 'Authors', 'academic-bloggers-toolkit' );
				$new_columns['abt_year'] = __( 'Year', 'academic-bloggers-toolkit' );
				$new_columns['abt_journal'] = __( 'Journal/Publisher', 'academic-bloggers-toolkit' );
				$new_columns['abt_usage_count'] = __( 'Used', 'academic-bloggers-toolkit' );
			}
		}

		// Remove date column
		unset( $new_columns['date'] );

		return $new_columns;
	}

	/**
	 * Display custom column content for references.
	 *
	 * @since    1.0.0
	 * @param    string    $column     Column name.
	 * @param    int       $post_id    Post ID.
	 */
	public function display_reference_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'abt_ref_type':
				$type = get_post_meta( $post_id, '_abt_reference_type', true );
				echo $type ? esc_html( ucfirst( $type ) ) : '—';
				break;

			case 'abt_authors':
				$authors = get_post_meta( $post_id, '_abt_authors', true );
				if ( $authors ) {
					// Show first author + "et al." if multiple
					$author_list = explode( ';', $authors );
					if ( count( $author_list ) > 1 ) {
						echo esc_html( trim( $author_list[0] ) ) . ' <em>et al.</em>';
					} else {
						echo esc_html( trim( $authors ) );
					}
				} else {
					echo '—';
				}
				break;

			case 'abt_year':
				$year = get_post_meta( $post_id, '_abt_year', true );
				echo $year ? esc_html( $year ) : '—';
				break;

			case 'abt_journal':
				$journal = get_post_meta( $post_id, '_abt_journal', true );
				echo $journal ? esc_html( $journal ) : '—';
				break;

			case 'abt_usage_count':
				$citations = ABT_Citation::get_by_reference( $post_id );
				$count = count( $citations );
				if ( $count > 0 ) {
					echo '<strong>' . $count . '</strong>';
				} else {
					echo '0';
				}
				break;
		}
	}

	/**
	 * Make custom columns sortable.
	 *
	 * @since    1.0.0
	 * @param    array    $columns    Sortable columns.
	 * @return   array                Modified sortable columns.
	 */
	public function make_columns_sortable( $columns ) {
		$columns['abt_year'] = 'abt_year';
		$columns['abt_ref_type'] = 'abt_ref_type';
		$columns['abt_citation_style'] = 'abt_citation_style';

		return $columns;
	}

	/**
	 * Handle custom column sorting.
	 *
	 * @since    1.0.0
	 * @param    WP_Query    $query    Current query.
	 */
	public function handle_column_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'abt_year' === $orderby ) {
			$query->set( 'meta_key', '_abt_year' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'abt_ref_type' === $orderby ) {
			$query->set( 'meta_key', '_abt_reference_type' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'abt_citation_style' === $orderby ) {
			$query->set( 'meta_key', '_abt_citation_style' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Add admin notices.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_notices() {
		// Check if plugin needs setup
		$needs_setup = get_option( 'abt_needs_setup', false );
		
		if ( $needs_setup ) {
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<?php 
					printf(
						__( 'Welcome to Academic Blogger\'s Toolkit! <a href="%s">Complete the setup</a> to get started.', 'academic-bloggers-toolkit' ),
						admin_url( 'admin.php?page=abt-settings' )
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Filter posts by custom taxonomies.
	 *
	 * @since    1.0.0
	 */
	public function add_taxonomy_filters() {
		global $typenow;

		if ( 'abt_blog' === $typenow ) {
			// Subject area filter
			$selected = isset( $_GET['abt_subject'] ) ? $_GET['abt_subject'] : '';
			wp_dropdown_categories( array(
				'show_option_all' => __( 'All Subject Areas', 'academic-bloggers-toolkit' ),
				'taxonomy'        => 'abt_subject',
				'name'           => 'abt_subject',
				'selected'       => $selected,
				'hierarchical'   => true,
				'value_field'    => 'slug',
			) );
		}

		if ( 'abt_reference' === $typenow ) {
			// Reference category filter
			$selected = isset( $_GET['abt_ref_category'] ) ? $_GET['abt_ref_category'] : '';
			wp_dropdown_categories( array(
				'show_option_all' => __( 'All Reference Types', 'academic-bloggers-toolkit' ),
				'taxonomy'        => 'abt_ref_category',
				'name'           => 'abt_ref_category',
				'selected'       => $selected,
				'hierarchical'   => true,
				'value_field'    => 'slug',
			) );
		}
	}

	/**
	 * Handle taxonomy filtering.
	 *
	 * @since    1.0.0
	 * @param    WP_Query    $query    Current query.
	 */
	public function handle_taxonomy_filtering( $query ) {
		global $pagenow;

		if ( 'edit.php' === $pagenow && is_admin() && $query->is_main_query() ) {
			if ( isset( $_GET['abt_subject'] ) && ! empty( $_GET['abt_subject'] ) ) {
				$query->set( 'abt_subject', sanitize_text_field( $_GET['abt_subject'] ) );
			}

			if ( isset( $_GET['abt_ref_category'] ) && ! empty( $_GET['abt_ref_category'] ) ) {
				$query->set( 'abt_ref_category', sanitize_text_field( $_GET['abt_ref_category'] ) );
			}
		}
	}
}