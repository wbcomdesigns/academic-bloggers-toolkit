<?php
/**
 * Search form shortcode template
 *
 * @link https://github.com/navidkashani/academic-bloggers-toolkit
 * @since 1.0.0
 *
 * @package Academic_Bloggers_Toolkit
 * @subpackage Academic_Bloggers_Toolkit/public/partials/shortcodes
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Extract variables from shortcode attributes
extract( $args );

$form_id = 'abt-search-form-' . wp_rand( 1000, 9999 );
$field_id = 'abt-search-field-' . wp_rand( 1000, 9999 );
?>

<div class="abt-search-container <?php echo esc_attr( $class ); ?>">
    <form class="abt-search-form" id="<?php echo esc_attr( $form_id ); ?>" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
        <div class="abt-search-wrapper">
            <label for="<?php echo esc_attr( $field_id ); ?>" class="screen-reader-text">
                <?php echo esc_html( $placeholder ); ?>
            </label>
            
            <input type="search" 
                   id="<?php echo esc_attr( $field_id ); ?>"
                   name="s" 
                   placeholder="<?php echo esc_attr( $placeholder ); ?>"
                   value="<?php echo get_search_query(); ?>"
                   class="abt-search-field"
                   autocomplete="off"
                   aria-describedby="<?php echo esc_attr( $field_id ); ?>-desc">
            
            <input type="hidden" name="post_type" value="abt_blog">
            
            <button type="submit" class="abt-search-submit">
                <span class="abt-search-icon" aria-hidden="true">🔍</span>
                <span class="abt-search-text"><?php echo esc_html( $button_text ); ?></span>
            </button>
        </div>
        
        <div id="<?php echo esc_attr( $field_id ); ?>-desc" class="screen-reader-text">
            <?php _e( 'Search for academic blog posts', 'academic-bloggers-toolkit' ); ?>
        </div>
    </form>
    
    <!-- Advanced Search Toggle (Optional) -->
    <div class="abt-advanced-search-toggle">
        <button type="button" class="abt-toggle-advanced" aria-expanded="false" aria-controls="abt-advanced-options">
            <?php _e( 'Advanced Search', 'academic-bloggers-toolkit' ); ?>
            <span class="abt-toggle-icon" aria-hidden="true">▼</span>
        </button>
    </div>
    
    <!-- Advanced Search Options -->
    <div class="abt-advanced-options" id="abt-advanced-options" style="display: none;">
        <div class="abt-search-filters">
            <div class="abt-filter-row">
                <div class="abt-filter-group">
                    <label for="abt-subject-filter"><?php _e( 'Subject:', 'academic-bloggers-toolkit' ); ?></label>
                    <select id="abt-subject-filter" name="abt_subject">
                        <option value=""><?php _e( 'All Subjects', 'academic-bloggers-toolkit' ); ?></option>
                        <?php
                        $subjects = get_terms( array(
                            'taxonomy'   => 'abt_subject',
                            'hide_empty' => true,
                            'orderby'    => 'name',
                        ) );
                        
                        foreach ( $subjects as $subject ) {
                            printf(
                                '<option value="%s">%s (%d)</option>',
                                esc_attr( $subject->slug ),
                                esc_html( $subject->name ),
                                $subject->count
                            );
                        }
                        ?>
                    </select>
                </div>
                
                <div class="abt-filter-group">
                    <label for="abt-author-filter"><?php _e( 'Author:', 'academic-bloggers-toolkit' ); ?></label>
                    <select id="abt-author-filter" name="abt_author">
                        <option value=""><?php _e( 'All Authors', 'academic-bloggers-toolkit' ); ?></option>
                        <?php
                        $authors = get_users( array(
                            'who' => 'authors',
                            'has_published_posts' => array( 'abt_blog' ),
                            'orderby' => 'display_name',
                        ) );
                        
                        foreach ( $authors as $author ) {
                            $post_count = count_user_posts( $author->ID, 'abt_blog' );
                            if ( $post_count > 0 ) {
                                printf(
                                    '<option value="%s">%s (%d)</option>',
                                    esc_attr( $author->user_nicename ),
                                    esc_html( $author->display_name ),
                                    $post_count
                                );
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="abt-filter-row">
                <div class="abt-filter-group">
                    <label for="abt-date-from"><?php _e( 'From Date:', 'academic-bloggers-toolkit' ); ?></label>
                    <input type="date" id="abt-date-from" name="abt_date_from" class="abt-date-input">
                </div>
                
                <div class="abt-filter-group">
                    <label for="abt-date-to"><?php _e( 'To Date:', 'academic-bloggers-toolkit' ); ?></label>
                    <input type="date" id="abt-date-to" name="abt_date_to" class="abt-date-input">
                </div>
            </div>
            
            <div class="abt-filter-row">
                <div class="abt-filter-group abt-full-width">
                    <fieldset>
                        <legend><?php _e( 'Search in:', 'academic-bloggers-toolkit' ); ?></legend>
                        <div class="abt-checkbox-group">
                            <label>
                                <input type="checkbox" name="abt_search_fields[]" value="title" checked>
                                <?php _e( 'Title', 'academic-bloggers-toolkit' ); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="abt_search_fields[]" value="content" checked>
                                <?php _e( 'Content', 'academic-bloggers-toolkit' ); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="abt_search_fields[]" value="abstract">
                                <?php _e( 'Abstract', 'academic-bloggers-toolkit' ); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="abt_search_fields[]" value="keywords">
                                <?php _e( 'Keywords', 'academic-bloggers-toolkit' ); ?>
                            </label>
                        </div>
                    </fieldset>
                </div>
            </div>
            
            <div class="abt-filter-actions">
                <button type="button" class="abt-clear-filters">
                    <?php _e( 'Clear Filters', 'academic-bloggers-toolkit' ); ?>
                </button>
                <button type="submit" class="abt-apply-filters">
                    <?php _e( 'Search with Filters', 'academic-bloggers-toolkit' ); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Search Suggestions (populated via AJAX) -->
    <div class="abt-search-suggestions" id="abt-search-suggestions" style="display: none;">
        <ul class="abt-suggestions-list" role="listbox">
            <!-- Suggestions will be populated here via JavaScript -->
        </ul>
    </div>
</div>

<script>
(function() {
    // Toggle advanced search
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.querySelector('.abt-toggle-advanced');
        const advancedOptions = document.querySelector('.abt-advanced-options');
        const toggleIcon = document.querySelector('.abt-toggle-icon');
        
        if (toggleBtn && advancedOptions) {
            toggleBtn.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    advancedOptions.style.display = 'none';
                    this.setAttribute('aria-expanded', 'false');
                    toggleIcon.textContent = '▼';
                } else {
                    advancedOptions.style.display = 'block';
                    this.setAttribute('aria-expanded', 'true');
                    toggleIcon.textContent = '▲';
                }
            });
        }
        
        // Clear filters functionality
        const clearBtn = document.querySelector('.abt-clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const selects = advancedOptions.querySelectorAll('select');
                const dateInputs = advancedOptions.querySelectorAll('input[type="date"]');
                const checkboxes = advancedOptions.querySelectorAll('input[type="checkbox"]');
                
                selects.forEach(select => select.value = '');
                dateInputs.forEach(input => input.value = '');
                checkboxes.forEach(checkbox => {
                    if (checkbox.value === 'title' || checkbox.value === 'content') {
                        checkbox.checked = true;
                    } else {
                        checkbox.checked = false;
                    }
                });
            });
        }
    });
})();
</script>