<?php
/**
 * Statistics page functionality.
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
 * Statistics page functionality.
 *
 * Handles the plugin statistics dashboard.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/pages
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Statistics_Page {

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
	 * Display the statistics page.
	 *
	 * @since    1.0.0
	 */
	public function display() {
		// Get basic statistics
		$blog_count = wp_count_posts( 'abt_blog' );
		$reference_count = wp_count_posts( 'abt_reference' );
		
		$total_blogs = isset( $blog_count->publish ) ? $blog_count->publish : 0;
		$total_references = isset( $reference_count->publish ) ? $reference_count->publish : 0;
		
		?>
		<div class="wrap">
			<h1><?php _e( 'Academic Blogger\'s Toolkit Statistics', 'academic-bloggers-toolkit' ); ?></h1>
			
			<div class="abt-stats-grid">
				<div class="abt-stat-card">
					<h3><?php _e( 'Academic Posts', 'academic-bloggers-toolkit' ); ?></h3>
					<div class="abt-stat-number"><?php echo esc_html( $total_blogs ); ?></div>
					<p><?php _e( 'Total academic blog posts published', 'academic-bloggers-toolkit' ); ?></p>
				</div>
				
				<div class="abt-stat-card">
					<h3><?php _e( 'References', 'academic-bloggers-toolkit' ); ?></h3>
					<div class="abt-stat-number"><?php echo esc_html( $total_references ); ?></div>
					<p><?php _e( 'Total references in your library', 'academic-bloggers-toolkit' ); ?></p>
				</div>
				
				<div class="abt-stat-card">
					<h3><?php _e( 'Citations', 'academic-bloggers-toolkit' ); ?></h3>
					<div class="abt-stat-number">0</div>
					<p><?php _e( 'Total citations across all posts', 'academic-bloggers-toolkit' ); ?></p>
				</div>
				
				<div class="abt-stat-card">
					<h3><?php _e( 'Footnotes', 'academic-bloggers-toolkit' ); ?></h3>
					<div class="abt-stat-number">0</div>
					<p><?php _e( 'Total footnotes across all posts', 'academic-bloggers-toolkit' ); ?></p>
				</div>
			</div>
			
			<div class="abt-stats-section">
				<h2><?php _e( 'Recent Activity', 'academic-bloggers-toolkit' ); ?></h2>
				
				<?php
				$recent_posts = get_posts( array(
					'post_type' => 'abt_blog',
					'post_status' => 'publish',
					'posts_per_page' => 5,
					'orderby' => 'date',
					'order' => 'DESC'
				) );
				
				$recent_references = get_posts( array(
					'post_type' => 'abt_reference',
					'post_status' => 'publish',
					'posts_per_page' => 5,
					'orderby' => 'date',
					'order' => 'DESC'
				) );
				?>
				
				<div class="abt-activity-grid">
					<div class="abt-activity-column">
						<h3><?php _e( 'Recent Academic Posts', 'academic-bloggers-toolkit' ); ?></h3>
						<?php if ( ! empty( $recent_posts ) ) : ?>
							<ul>
								<?php foreach ( $recent_posts as $post ) : ?>
									<li>
										<a href="<?php echo get_edit_post_link( $post->ID ); ?>">
											<?php echo esc_html( $post->post_title ); ?>
										</a>
										<span class="abt-activity-date"><?php echo get_the_date( 'M j, Y', $post ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php _e( 'No academic posts found.', 'academic-bloggers-toolkit' ); ?></p>
						<?php endif; ?>
					</div>
					
					<div class="abt-activity-column">
						<h3><?php _e( 'Recent References', 'academic-bloggers-toolkit' ); ?></h3>
						<?php if ( ! empty( $recent_references ) ) : ?>
							<ul>
								<?php foreach ( $recent_references as $reference ) : ?>
									<li>
										<a href="<?php echo get_edit_post_link( $reference->ID ); ?>">
											<?php echo esc_html( $reference->post_title ); ?>
										</a>
										<span class="abt-activity-date"><?php echo get_the_date( 'M j, Y', $reference ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php _e( 'No references found.', 'academic-bloggers-toolkit' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		
		<style>
		.abt-stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
			margin: 20px 0;
		}
		
		.abt-stat-card {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 20px;
			text-align: center;
		}
		
		.abt-stat-card h3 {
			margin-top: 0;
			color: #23282d;
		}
		
		.abt-stat-number {
			font-size: 32px;
			font-weight: bold;
			color: #0073aa;
			margin: 10px 0;
		}
		
		.abt-stats-section {
			margin-top: 40px;
		}
		
		.abt-activity-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 30px;
			margin-top: 20px;
		}
		
		.abt-activity-column h3 {
			border-bottom: 1px solid #ddd;
			padding-bottom: 10px;
		}
		
		.abt-activity-column ul {
			list-style: none;
			padding: 0;
		}
		
		.abt-activity-column li {
			padding: 8px 0;
			border-bottom: 1px solid #f1f1f1;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		
		.abt-activity-date {
			color: #666;
			font-size: 13px;
		}
		</style>
		<?php
	}
}