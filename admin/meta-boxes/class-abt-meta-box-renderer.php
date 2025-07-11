<?php
/**
 * Meta box renderer utility class.
 *
 * @link       https://github.com/academic-bloggers-toolkit
 * @since      1.0.0
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/meta-boxes
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta box renderer utility class.
 *
 * Provides utility methods for rendering meta box elements consistently
 * across the plugin's admin interface.
 *
 * @package    Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/admin/meta-boxes
 * @author     Academic Bloggers Toolkit Team
 */
class ABT_Meta_Box_Renderer {

	/**
	 * Render a text input field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public static function text_field( $args ) {
		$defaults = array(
			'id' => '',
			'name' => '',
			'value' => '',
			'class' => 'regular-text',
			'placeholder' => '',
			'required' => false,
			'readonly' => false,
			'description' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<input type="text"
			   id="<?php echo esc_attr( $args['id'] ); ?>"
			   name="<?php echo esc_attr( $args['name'] ); ?>"
			   value="<?php echo esc_attr( $args['value'] ); ?>"
			   class="<?php echo esc_attr( $args['class'] ); ?>"
			   <?php if ( $args['placeholder'] ) : ?>placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"<?php endif; ?>
			   <?php if ( $args['required'] ) : ?>required<?php endif; ?>
			   <?php if ( $args['readonly'] ) : ?>readonly<?php endif; ?> />

		<?php if ( $args['description'] ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a textarea field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public static function textarea_field( $args ) {
		$defaults = array(
			'id' => '',
			'name' => '',
			'value' => '',
			'class' => 'large-text',
			'rows' => 3,
			'placeholder' => '',
			'required' => false,
			'readonly' => false,
			'description' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<textarea id="<?php echo esc_attr( $args['id'] ); ?>"
				  name="<?php echo esc_attr( $args['name'] ); ?>"
				  class="<?php echo esc_attr( $args['class'] ); ?>"
				  rows="<?php echo esc_attr( $args['rows'] ); ?>"
				  <?php if ( $args['placeholder'] ) : ?>placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"<?php endif; ?>
				  <?php if ( $args['required'] ) : ?>required<?php endif; ?>
				  <?php if ( $args['readonly'] ) : ?>readonly<?php endif; ?>><?php echo esc_textarea( $args['value'] ); ?></textarea>

		<?php if ( $args['description'] ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a select dropdown field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public static function select_field( $args ) {
		$defaults = array(
			'id' => '',
			'name' => '',
			'value' => '',
			'class' => '',
			'options' => array(),
			'required' => false,
			'multiple' => false,
			'description' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<select id="<?php echo esc_attr( $args['id'] ); ?>"
				name="<?php echo esc_attr( $args['name'] ); ?><?php echo $args['multiple'] ? '[]' : ''; ?>"
				class="<?php echo esc_attr( $args['class'] ); ?>"
				<?php if ( $args['required'] ) : ?>required<?php endif; ?>
				<?php if ( $args['multiple'] ) : ?>multiple<?php endif; ?>>

			<?php foreach ( $args['options'] as $option_value => $option_label ) : ?>
				<option value="<?php echo esc_attr( $option_value ); ?>"
						<?php 
						if ( $args['multiple'] ) {
							selected( in_array( $option_value, (array) $args['value'] ), true );
						} else {
							selected( $args['value'], $option_value );
						}
						?>>
					<?php echo esc_html( $option_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<?php if ( $args['description'] ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a checkbox field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public static function checkbox_field( $args ) {
		$defaults = array(
			'id' => '',
			'name' => '',
			'value' => '1',
			'checked' => false,
			'label' => '',
			'description' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<label for="<?php echo esc_attr( $args['id'] ); ?>">
			<input type="checkbox"
				   id="<?php echo esc_attr( $args['id'] ); ?>"
				   name="<?php echo esc_attr( $args['name'] ); ?>"
				   value="<?php echo esc_attr( $args['value'] ); ?>"
				   <?php checked( $args['checked'] ); ?> />
			<?php echo esc_html( $args['label'] ); ?>
		</label>

		<?php if ( $args['description'] ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a number input field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public static function number_field( $args ) {
		$defaults = array(
			'id' => '',
			'name' => '',
			'value' => '',
			'class' => 'small-text',
			'min' => '',
			'max' => '',
			'step' => '',
			'required' => false,
			'description' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<input type="number"
			   id="<?php echo esc_attr( $args['id'] ); ?>"
			   name="<?php echo esc_attr( $args['name'] ); ?>"
			   value="<?php echo esc_attr( $args['value'] ); ?>"
			   class="<?php echo esc_attr( $args['class'] ); ?>"
			   <?php if ( $args['min'] !== '' ) : ?>min="<?php echo esc_attr( $args['min'] ); ?>"<?php endif; ?>
			   <?php if ( $args['max'] !== '' ) : ?>max="<?php echo esc_attr( $args['max'] ); ?>"<?php endif; ?>
			   <?php if ( $args['step'] !== '' ) : ?>step="<?php echo esc_attr( $args['step'] ); ?>"<?php endif; ?>
			   <?php if ( $args['required'] ) : ?>required<?php endif; ?> />

		<?php if ( $args['description'] ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a date input field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public static function date_field( $args ) {
		$defaults = array(
			'id' => '',
			'name' => '',
			'value' => '',
			'class' => 'regular-text',
			'required' => false,
			'description' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<input type="date"
			   id="<?php echo esc_attr( $args['id'] ); ?>"
			   name="<?php echo esc_attr( $args['name'] ); ?>"
			   value="<?php echo esc_attr( $args['value'] ); ?>"
			   class="<?php echo esc_attr( $args['class'] ); ?>"
			   <?php if ( $args['required'] ) : ?>required<?php endif; ?> />

		<?php if ( $args['description'] ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a URL input field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public static function url_field( $args ) {
		$defaults = array(
			'id' => '',
			'name' => '',
			'value' => '',
			'class' => 'regular-text',
			'placeholder' => 'https://',
			'required' => false,
			'description' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<input type="url"
			   id="<?php echo esc_attr( $args['id'] ); ?>"
			   name="<?php echo esc_attr( $args['name'] ); ?>"
			   value="<?php echo esc_attr( $args['value'] ); ?>"
			   class="<?php echo esc_attr( $args['class'] ); ?>"
			   placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
			   <?php if ( $args['required'] ) : ?>required<?php endif; ?> />

		<?php if ( $args['description'] ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a taxonomy checklist.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public static function taxonomy_checklist( $args ) {
		$defaults = array(
			'taxonomy' => '',
			'post_id' => 0,
			'name' => '',
			'selected_only' => false,
			'popular_cats' => false,
			'walker' => null
		);

		$args = wp_parse_args( $args, $defaults );

		$terms = get_terms( array(
			'taxonomy' => $args['taxonomy'],
			'hide_empty' => false,
		) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			echo '<p>' . __( 'No terms available.', 'academic-bloggers-toolkit' ) . '</p>';
			return;
		}

		$selected_terms = array();
		if ( $args['post_id'] ) {
			$selected_terms = wp_get_post_terms( 
				$args['post_id'], 
				$args['taxonomy'], 
				array( 'fields' => 'ids' ) 
			);
		}

		echo '<div class="abt-taxonomy-checklist" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 8px;">';
		
		foreach ( $terms as $term ) {
			$checked = in_array( $term->term_id, $selected_terms ) ? 'checked' : '';
			
			echo '<label style="display: block; margin-bottom: 5px;">';
			echo '<input type="checkbox" name="' . esc_attr( $args['name'] ) . '[]" ';
			echo 'value="' . esc_attr( $term->term_id ) . '" ' . $checked . ' /> ';
			echo esc_html( $term->name );
			echo '</label>';
		}
		
		echo '</div>';
	}

	/**
	 * Render a form table row.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Row arguments.
	 */
	public static function form_table_row( $args ) {
		$defaults = array(
			'label' => '',
			'field' => '',
			'required' => false,
			'description' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<tr>
			<th scope="row">
				<?php echo esc_html( $args['label'] ); ?>
				<?php if ( $args['required'] ) : ?>
					<span class="required" style="color: #d63638;">*</span>
				<?php endif; ?>
			</th>
			<td>
				<?php echo $args['field']; ?>
				<?php if ( $args['description'] ) : ?>
					<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a meta box section header.
	 *
	 * @since    1.0.0
	 * @param    string    $title       Section title.
	 * @param    string    $description Optional section description.
	 */
	public static function section_header( $title, $description = '' ) {
		?>
		<div class="abt-section-header">
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<style>
		.abt-section-header {
			margin: 20px 0 15px 0;
			padding-bottom: 10px;
			border-bottom: 1px solid #ddd;
		}
		.abt-section-header h3 {
			margin: 0 0 5px 0;
			color: #23282d;
		}
		.abt-section-header .description {
			margin: 0;
			font-style: italic;
			color: #666;
		}
		</style>
		<?php
	}

	/**
	 * Render a help tip icon with tooltip.
	 *
	 * @since    1.0.0
	 * @param    string    $text    Tooltip text.
	 */
	public static function help_tip( $text ) {
		?>
		<span class="abt-help-tip dashicons dashicons-editor-help" 
			  title="<?php echo esc_attr( $text ); ?>"
			  style="color: #666; cursor: help; font-size: 16px; margin-left: 5px;"></span>
		<?php
	}

	/**
	 * Render a notice/alert box.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Notice arguments.
	 */
	public static function notice( $args ) {
		$defaults = array(
			'type' => 'info', // info, success, warning, error
			'message' => '',
			'dismissible' => false
		);

		$args = wp_parse_args( $args, $defaults );

		$classes = array( 'notice', 'notice-' . $args['type'] );
		if ( $args['dismissible'] ) {
			$classes[] = 'is-dismissible';
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<p><?php echo esc_html( $args['message'] ); ?></p>
			<?php if ( $args['dismissible'] ) : ?>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'academic-bloggers-toolkit' ); ?></span>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render loading spinner.
	 *
	 * @since    1.0.0
	 * @param    string    $size    Spinner size (small, medium, large).
	 */
	public static function loading_spinner( $size = 'medium' ) {
		$sizes = array(
			'small' => '16px',
			'medium' => '20px',
			'large' => '24px'
		);

		$spinner_size = isset( $sizes[ $size ] ) ? $sizes[ $size ] : $sizes['medium'];

		?>
		<span class="abt-loading-spinner" style="
			display: inline-block;
			width: <?php echo esc_attr( $spinner_size ); ?>;
			height: <?php echo esc_attr( $spinner_size ); ?>;
			border: 2px solid #f3f3f3;
			border-top: 2px solid #0073aa;
			border-radius: 50%;
			animation: abt-spin 1s linear infinite;
		"></span>
		<style>
		@keyframes abt-spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		</style>
		<?php
	}

	/**
	 * Render a button with icon.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Button arguments.
	 */
	public static function button( $args ) {
		$defaults = array(
			'text' => '',
			'icon' => '',
			'type' => 'button',
			'class' => 'button',
			'id' => '',
			'onclick' => '',
			'disabled' => false
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<button type="<?php echo esc_attr( $args['type'] ); ?>"
				class="<?php echo esc_attr( $args['class'] ); ?>"
				<?php if ( $args['id'] ) : ?>id="<?php echo esc_attr( $args['id'] ); ?>"<?php endif; ?>
				<?php if ( $args['onclick'] ) : ?>onclick="<?php echo esc_attr( $args['onclick'] ); ?>"<?php endif; ?>
				<?php if ( $args['disabled'] ) : ?>disabled<?php endif; ?>>
			
			<?php if ( $args['icon'] ) : ?>
				<span class="dashicons dashicons-<?php echo esc_attr( $args['icon'] ); ?>"></span>
			<?php endif; ?>
			
			<?php echo esc_html( $args['text'] ); ?>
		</button>
		<?php
	}
}