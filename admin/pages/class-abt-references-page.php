<?php
/**
 * References management page functionality.
 *
 * @link       https://github.com/wbcomdesigns
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/pages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * References management page functionality.
 *
 * Handles the main references management interface including
 * listing, searching, filtering, and bulk operations.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/pages
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_References_Page {

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
	 * Display the references management page.
	 *
	 * @since    1.0.0
	 */
	public function display() {
		// Handle bulk actions
		$this->handle_bulk_actions();

		// Get current page parameters
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = 20;
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$ref_type = isset( $_GET['ref_type'] ) ? sanitize_text_field( $_GET['ref_type'] ) : '';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'date';
		$order = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

		// Build query arguments
		$args = array(
			'post_type' => 'abt_reference',
			'post_status' => 'publish',
			'posts_per_page' => $per_page,
			'paged' => $current_page,
			'orderby' => $orderby,
			'order' => $order,
		);

		// Add search
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Add reference type filter
		if ( ! empty( $ref_type ) ) {
			$args['meta_query'] = array(
				array(
					'key' => '_abt_reference_type',
					'value' => $ref_type,
					'compare' => '='
				)
			);
		}

		// Handle custom ordering
		if ( 'year' === $orderby ) {
			$args['meta_key'] = '_abt_year';
			$args['orderby'] = 'meta_value_num';
		} elseif ( 'authors' === $orderby ) {
			$args['meta_key'] = '_abt_authors';
			$args['orderby'] = 'meta_value';
		}

		// Execute query
		$references_query = new WP_Query( $args );
		$references = $references_query->posts;
		$total_items = $references_query->found_posts;

		// Calculate pagination
		$total_pages = ceil( $total_items / $per_page );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php _e( 'References', 'academic-bloggers-toolkit' ); ?>
			</h1>
			
			<a href="<?php echo admin_url( 'post-new.php?post_type=abt_reference' ); ?>" class="page-title-action">
				<?php _e( 'Add New Reference', 'academic-bloggers-toolkit' ); ?>
			</a>

			<hr class="wp-header-end">

			<?php $this->display_admin_notices(); ?>

			<div class="abt-references-page">
				<!-- Search and Filters -->
				<div class="abt-references-filters">
					<form method="get" class="abt-search-form">
						<input type="hidden" name="page" value="academic-bloggers-toolkit" />
						
						<div class="abt-search-box">
							<input type="search" 
								   name="s" 
								   value="<?php echo esc_attr( $search ); ?>" 
								   placeholder="<?php _e( 'Search references...', 'academic-bloggers-toolkit' ); ?>" 
								   class="abt-search-input" />
							<input type="submit" 
								   value="<?php _e( 'Search', 'academic-bloggers-toolkit' ); ?>" 
								   class="button" />
						</div>

						<div class="abt-filter-box">
							<select name="ref_type">
								<option value=""><?php _e( 'All Types', 'academic-bloggers-toolkit' ); ?></option>
								<?php foreach ( $this->get_reference_types() as $type => $label ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $ref_type, $type ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							
							<select name="orderby">
								<option value="date" <?php selected( $orderby, 'date' ); ?>><?php _e( 'Date Added', 'academic-bloggers-toolkit' ); ?></option>
								<option value="title" <?php selected( $orderby, 'title' ); ?>><?php _e( 'Title', 'academic-bloggers-toolkit' ); ?></option>
								<option value="authors" <?php selected( $orderby, 'authors' ); ?>><?php _e( 'Authors', 'academic-bloggers-toolkit' ); ?></option>
								<option value="year" <?php selected( $orderby, 'year' ); ?>><?php _e( 'Year', 'academic-bloggers-toolkit' ); ?></option>
							</select>
							
							<select name="order">
								<option value="DESC" <?php selected( $order, 'DESC' ); ?>><?php _e( 'Descending', 'academic-bloggers-toolkit' ); ?></option>
								<option value="ASC" <?php selected( $order, 'ASC' ); ?>><?php _e( 'Ascending', 'academic-bloggers-toolkit' ); ?></option>
							</select>
							
							<input type="submit" value="<?php _e( 'Filter', 'academic-bloggers-toolkit' ); ?>" class="button" />
							
							<?php if ( ! empty( $search ) || ! empty( $ref_type ) ) : ?>
								<a href="<?php echo admin_url( 'admin.php?page=academic-bloggers-toolkit' ); ?>" class="button">
									<?php _e( 'Clear Filters', 'academic-bloggers-toolkit' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</form>
				</div>

				<!-- Bulk Actions and Stats -->
				<div class="abt-references-toolbar">
					<div class="abt-bulk-actions">
						<form method="post" id="abt-bulk-form">
							<?php wp_nonce_field( 'abt_bulk_action', 'abt_bulk_nonce' ); ?>
							<select name="action" id="bulk-action-selector-top">
								<option value=""><?php _e( 'Bulk Actions', 'academic-bloggers-toolkit' ); ?></option>
								<option value="export"><?php _e( 'Export Selected', 'academic-bloggers-toolkit' ); ?></option>
								<option value="delete"><?php _e( 'Delete Selected', 'academic-bloggers-toolkit' ); ?></option>
							</select>
							<input type="submit" class="button action" value="<?php _e( 'Apply', 'academic-bloggers-toolkit' ); ?>" />
						</form>
					</div>

					<div class="abt-references-stats">
						<span class="abt-total-count">
							<?php 
							printf( 
								_n( '%s reference', '%s references', $total_items, 'academic-bloggers-toolkit' ), 
								number_format_i18n( $total_items ) 
							);
							?>
						</span>
					</div>

					<div class="abt-import-export-actions">
						<a href="#" class="button" id="abt-import-references">
							<?php _e( 'Import References', 'academic-bloggers-toolkit' ); ?>
						</a>
						<a href="#" class="button" id="abt-export-all">
							<?php _e( 'Export All', 'academic-bloggers-toolkit' ); ?>
						</a>
					</div>
				</div>

				<!-- References Table -->
				<?php if ( ! empty( $references ) ) : ?>
					<form method="post" id="abt-references-form">
						<?php wp_nonce_field( 'abt_bulk_action', 'abt_bulk_nonce' ); ?>
						
						<table class="wp-list-table widefat fixed striped abt-references-table">
							<thead>
								<tr>
									<td class="manage-column column-cb check-column">
										<input type="checkbox" id="cb-select-all-1" />
									</td>
									<th scope="col" class="manage-column column-title column-primary">
										<a href="<?php echo $this->get_sort_url( 'title', $order ); ?>">
											<?php _e( 'Title', 'academic-bloggers-toolkit' ); ?>
											<?php $this->display_sort_indicator( 'title', $orderby, $order ); ?>
										</a>
									</th>
									<th scope="col" class="manage-column column-type">
										<?php _e( 'Type', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-authors">
										<a href="<?php echo $this->get_sort_url( 'authors', $order ); ?>">
											<?php _e( 'Authors', 'academic-bloggers-toolkit' ); ?>
											<?php $this->display_sort_indicator( 'authors', $orderby, $order ); ?>
										</a>
									</th>
									<th scope="col" class="manage-column column-year">
										<a href="<?php echo $this->get_sort_url( 'year', $order ); ?>">
											<?php _e( 'Year', 'academic-bloggers-toolkit' ); ?>
											<?php $this->display_sort_indicator( 'year', $orderby, $order ); ?>
										</a>
									</th>
									<th scope="col" class="manage-column column-journal">
										<?php _e( 'Journal/Publisher', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-used">
										<?php _e( 'Used', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-date">
										<a href="<?php echo $this->get_sort_url( 'date', $order ); ?>">
											<?php _e( 'Date Added', 'academic-bloggers-toolkit' ); ?>
											<?php $this->display_sort_indicator( 'date', $orderby, $order ); ?>
										</a>
									</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $references as $reference ) : ?>
									<?php $this->display_reference_row( $reference ); ?>
								<?php endforeach; ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="manage-column column-cb check-column">
										<input type="checkbox" id="cb-select-all-2" />
									</td>
									<th scope="col" class="manage-column column-title column-primary">
										<?php _e( 'Title', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-type">
										<?php _e( 'Type', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-authors">
										<?php _e( 'Authors', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-year">
										<?php _e( 'Year', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-journal">
										<?php _e( 'Journal/Publisher', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-used">
										<?php _e( 'Used', 'academic-bloggers-toolkit' ); ?>
									</th>
									<th scope="col" class="manage-column column-date">
										<?php _e( 'Date Added', 'academic-bloggers-toolkit' ); ?>
									</th>
								</tr>
							</tfoot>
						</table>
					</form>

					<!-- Pagination -->
					<?php if ( $total_pages > 1 ) : ?>
						<div class="abt-pagination">
							<?php
							echo paginate_links( array(
								'base' => add_query_arg( 'paged', '%#%' ),
								'format' => '',
								'prev_text' => '&laquo; ' . __( 'Previous', 'academic-bloggers-toolkit' ),
								'next_text' => __( 'Next', 'academic-bloggers-toolkit' ) . ' &raquo;',
								'total' => $total_pages,
								'current' => $current_page,
								'show_all' => false,
								'end_size' => 1,
								'mid_size' => 2,
								'type' => 'plain',
							) );
							?>
						</div>
					<?php endif; ?>

				<?php else : ?>
					<div class="abt-no-references">
						<?php if ( ! empty( $search ) || ! empty( $ref_type ) ) : ?>
							<h3><?php _e( 'No references found matching your criteria.', 'academic-bloggers-toolkit' ); ?></h3>
							<p>
								<a href="<?php echo admin_url( 'admin.php?page=academic-bloggers-toolkit' ); ?>" class="button">
									<?php _e( 'Clear Filters', 'academic-bloggers-toolkit' ); ?>
								</a>
							</p>
						<?php else : ?>
							<h3><?php _e( 'No references found.', 'academic-bloggers-toolkit' ); ?></h3>
							<p><?php _e( 'Start building your reference library by adding your first reference.', 'academic-bloggers-toolkit' ); ?></p>
							<p>
								<a href="<?php echo admin_url( 'post-new.php?post_type=abt_reference' ); ?>" class="button button-primary">
									<?php _e( 'Add Your First Reference', 'academic-bloggers-toolkit' ); ?>
								</a>
							</p>
							<div class="abt-quick-import">
								<h4><?php _e( 'Or import existing references:', 'academic-bloggers-toolkit' ); ?></h4>
								<p>
									<a href="#" class="button" id="abt-import-bibtex"><?php _e( 'Import BibTeX', 'academic-bloggers-toolkit' ); ?></a>
									<a href="#" class="button" id="abt-import-ris"><?php _e( 'Import RIS', 'academic-bloggers-toolkit' ); ?></a>
									<a href="#" class="button" id="abt-import-csv"><?php _e( 'Import CSV', 'academic-bloggers-toolkit' ); ?></a>
								</p>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<style>
		.abt-references-page {
			margin-top: 20px;
		}

		.abt-references-filters {
			background: #fff;
			border: 1px solid #ccd0d4;
			padding: 15px;
			margin-bottom: 20px;
		}

		.abt-search-form {
			display: flex;
			gap: 15px;
			align-items: center;
			flex-wrap: wrap;
		}

		.abt-search-box {
			display: flex;
			gap: 5px;
		}

		.abt-search-input {
			width: 250px;
		}

		.abt-filter-box {
			display: flex;
			gap: 10px;
			align-items: center;
		}

		.abt-references-toolbar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 15px;
			padding: 10px 0;
		}

		.abt-references-stats {
			font-style: italic;
			color: #666;
		}

		.abt-import-export-actions {
			display: flex;
			gap: 10px;
		}

		.abt-references-table .column-cb {
			width: 2.2em;
		}

		.abt-references-table .column-type {
			width: 100px;
		}

		.abt-references-table .column-year {
			width: 80px;
		}

		.abt-references-table .column-used {
			width: 60px;
		}

		.abt-references-table .column-date {
			width: 120px;
		}

		.abt-reference-authors {
			color: #666;
			font-size: 13px;
		}

		.abt-reference-type {
			background: #0073aa;
			color: #fff;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			text-transform: uppercase;
		}

		.abt-usage-count {
			font-weight: bold;
		}

		.abt-usage-count.unused {
			color: #999;
		}

		.abt-no-references {
			text-align: center;
			background: #fff;
			border: 1px solid #ccd0d4;
			padding: 40px 20px;
		}

		.abt-quick-import {
			margin-top: 30px;
			padding-top: 20px;
			border-top: 1px solid #ddd;
		}

		.abt-pagination {
			text-align: center;
			margin-top: 20px;
		}

		.abt-sort-indicator {
			color: #666;
		}
		</style>
		<?php
	}

	/**
	 * Display a single reference row.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $reference    Reference post object.
	 */
	private function display_reference_row( $reference ) {
		$ref_type = get_post_meta( $reference->ID, '_abt_reference_type', true );
		$authors = get_post_meta( $reference->ID, '_abt_authors', true );
		$year = get_post_meta( $reference->ID, '_abt_year', true );
		$journal = get_post_meta( $reference->ID, '_abt_journal', true );
		
		// Get usage count
		$citations = ABT_Citation::get_by_reference( $reference->ID );
		$usage_count = count( $citations );

		?>
		<tr>
			<th scope="row" class="check-column">
				<input type="checkbox" name="reference_ids[]" value="<?php echo esc_attr( $reference->ID ); ?>" />
			</th>
			<td class="column-title column-primary">
				<strong>
					<a href="<?php echo get_edit_post_link( $reference->ID ); ?>">
						<?php echo esc_html( $reference->post_title ); ?>
					</a>
				</strong>
				<?php if ( $authors ) : ?>
					<div class="abt-reference-authors">
						<?php 
						$author_list = explode( ';', $authors );
						if ( count( $author_list ) > 1 ) {
							echo esc_html( trim( $author_list[0] ) ) . ' <em>et al.</em>';
						} else {
							echo esc_html( trim( $authors ) );
						}
						?>
					</div>
				<?php endif; ?>
				<div class="row-actions">
					<span class="edit">
						<a href="<?php echo get_edit_post_link( $reference->ID ); ?>"><?php _e( 'Edit', 'academic-bloggers-toolkit' ); ?></a> |
					</span>
					<span class="view">
						<a href="#" class="abt-preview-reference" data-ref-id="<?php echo esc_attr( $reference->ID ); ?>"><?php _e( 'Preview', 'academic-bloggers-toolkit' ); ?></a> |
					</span>
					<span class="duplicate">
						<a href="#" class="abt-duplicate-reference" data-ref-id="<?php echo esc_attr( $reference->ID ); ?>"><?php _e( 'Duplicate', 'academic-bloggers-toolkit' ); ?></a> |
					</span>
					<span class="delete">
						<a href="<?php echo get_delete_post_link( $reference->ID ); ?>" class="submitdelete" onclick="return confirm('<?php _e( 'Are you sure you want to delete this reference?', 'academic-bloggers-toolkit' ); ?>')"><?php _e( 'Delete', 'academic-bloggers-toolkit' ); ?></a>
					</span>
				</div>
			</td>
			<td class="column-type">
				<?php if ( $ref_type ) : ?>
					<span class="abt-reference-type">
						<?php echo esc_html( ucfirst( $ref_type ) ); ?>
					</span>
				<?php else : ?>
					—
				<?php endif; ?>
			</td>
			<td class="column-authors">
				<?php echo $authors ? esc_html( wp_trim_words( $authors, 3, '...' ) ) : '—'; ?>
			</td>
			<td class="column-year">
				<?php echo $year ? esc_html( $year ) : '—'; ?>
			</td>
			<td class="column-journal">
				<?php echo $journal ? esc_html( wp_trim_words( $journal, 4, '...' ) ) : '—'; ?>
			</td>
			<td class="column-used">
				<span class="abt-usage-count <?php echo $usage_count === 0 ? 'unused' : 'used'; ?>">
					<?php echo $usage_count; ?>
				</span>
			</td>
			<td class="column-date">
				<?php echo get_the_date( 'Y/m/d', $reference ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get available reference types.
	 *
	 * @since    1.0.0
	 * @return   array    Reference types array.
	 */
	private function get_reference_types() {
		return array(
			'journal' => __( 'Journal Article', 'academic-bloggers-toolkit' ),
			'book' => __( 'Book', 'academic-bloggers-toolkit' ),
			'chapter' => __( 'Book Chapter', 'academic-bloggers-toolkit' ),
			'conference' => __( 'Conference Paper', 'academic-bloggers-toolkit' ),
			'thesis' => __( 'Thesis/Dissertation', 'academic-bloggers-toolkit' ),
			'website' => __( 'Website', 'academic-bloggers-toolkit' ),
			'report' => __( 'Report', 'academic-bloggers-toolkit' ),
			'patent' => __( 'Patent', 'academic-bloggers-toolkit' ),
			'newspaper' => __( 'Newspaper Article', 'academic-bloggers-toolkit' ),
			'magazine' => __( 'Magazine Article', 'academic-bloggers-toolkit' ),
			'software' => __( 'Software', 'academic-bloggers-toolkit' ),
			'dataset' => __( 'Dataset', 'academic-bloggers-toolkit' ),
			'manuscript' => __( 'Manuscript', 'academic-bloggers-toolkit' ),
			'presentation' => __( 'Presentation', 'academic-bloggers-toolkit' ),
			'other' => __( 'Other', 'academic-bloggers-toolkit' ),
		);
	}

	/**
	 * Get sort URL for table headers.
	 *
	 * @since    1.0.0
	 * @param    string    $column    Column name.
	 * @param    string    $current_order    Current order.
	 * @return   string                       Sort URL.
	 */
	private function get_sort_url( $column, $current_order ) {
		$args = array(
			'page' => 'academic-bloggers-toolkit',
			'orderby' => $column,
			'order' => ( $current_order === 'ASC' ) ? 'DESC' : 'ASC',
		);

		// Preserve current filters
		if ( isset( $_GET['s'] ) ) {
			$args['s'] = sanitize_text_field( $_GET['s'] );
		}
		if ( isset( $_GET['ref_type'] ) ) {
			$args['ref_type'] = sanitize_text_field( $_GET['ref_type'] );
		}

		return admin_url( 'admin.php?' . http_build_query( $args ) );
	}

	/**
	 * Display sort indicator for table headers.
	 *
	 * @since    1.0.0
	 * @param    string    $column       Column name.
	 * @param    string    $current_col  Current orderby column.
	 * @param    string    $current_order Current order.
	 */
	private function display_sort_indicator( $column, $current_col, $current_order ) {
		if ( $column === $current_col ) {
			if ( $current_order === 'ASC' ) {
				echo ' <span class="abt-sort-indicator">▲</span>';
			} else {
				echo ' <span class="abt-sort-indicator">▼</span>';
			}
		}
	}

	/**
	 * Handle bulk actions.
	 *
	 * @since    1.0.0
	 */
	private function handle_bulk_actions() {
		if ( ! isset( $_POST['abt_bulk_nonce'] ) || ! wp_verify_nonce( $_POST['abt_bulk_nonce'], 'abt_bulk_action' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
		$reference_ids = isset( $_POST['reference_ids'] ) ? array_map( 'intval', $_POST['reference_ids'] ) : array();

		if ( empty( $action ) || empty( $reference_ids ) ) {
			return;
		}

		switch ( $action ) {
			case 'delete':
				$this->bulk_delete_references( $reference_ids );
				break;
			case 'export':
				$this->bulk_export_references( $reference_ids );
				break;
		}
	}

	/**
	 * Bulk delete references.
	 *
	 * @since    1.0.0
	 * @param    array    $reference_ids    Reference IDs to delete.
	 */
	private function bulk_delete_references( $reference_ids ) {
		$deleted_count = 0;

		foreach ( $reference_ids as $reference_id ) {
			if ( wp_delete_post( $reference_id, true ) ) {
				$deleted_count++;
			}
		}

		if ( $deleted_count > 0 ) {
			add_action( 'admin_notices', function() use ( $deleted_count ) {
				echo '<div class="notice notice-success is-dismissible">';
				printf( 
					_n( 
						'%d reference deleted successfully.', 
						'%d references deleted successfully.', 
						$deleted_count, 
						'academic-bloggers-toolkit' 
					), 
					$deleted_count 
				);
				echo '</div>';
			});
		}
	}

	/**
	 * Bulk export references.
	 *
	 * @since    1.0.0
	 * @param    array    $reference_ids    Reference IDs to export.
	 */
	private function bulk_export_references( $reference_ids ) {
		// This will be implemented in Phase 3 - Citation Processing Engine
		// For now, just show a notice
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p>' . __( 'Export functionality will be available in the next update.', 'academic-bloggers-toolkit' ) . '</p>';
			echo '</div>';
		});
	}

	/**
	 * Display admin notices.
	 *
	 * @since    1.0.0
	 */
	private function display_admin_notices() {
		// Check for success messages
		if ( isset( $_GET['message'] ) ) {
			$message = sanitize_text_field( $_GET['message'] );
			switch ( $message ) {
				case 'imported':
					echo '<div class="notice notice-success is-dismissible">';
					echo '<p>' . __( 'References imported successfully.', 'academic-bloggers-toolkit' ) . '</p>';
					echo '</div>';
					break;
				case 'exported':
					echo '<div class="notice notice-success is-dismissible">';
					echo '<p>' . __( 'References exported successfully.', 'academic-bloggers-toolkit' ) . '</p>';
					echo '</div>';
					break;
			}
		}
	}
}