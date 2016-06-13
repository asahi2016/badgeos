<?php
/**
 * Admin Meta Boxes
 *
 * @package BadgeOS Interactive Progress Map
 * @subpackage Admin
 * @author Krishna , Asahitechnologies Pvt ltd
 */

/**
 * Register custom meta boxes used throughout BadgeOS
 *
 * @since  1.0.0
 * @param  array  $meta_boxes The existing metabox array we're filtering
 * @return array              An updated array containing our new metaboxes
 */
function badgeos_interactive_progress_map_custom_metaboxes( array $meta_boxes ) {

    wp_enqueue_script('progress_map_admin_script');

    // Start with an underscore to hide fields from custom fields list
    $prefix = '_badgeos_';

    // Setup our $post_id, if available
    //$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

    // New Achievement Types
    $meta_boxes[] = array(
        'id'         => 'interactive_progress_map',
        'title'      => __( 'Interactive Progress Map', 'badgeos-interactive-progress-map' ),
        'pages'      => array( 'achievement-type' ), // Post type
        'context'    => 'normal',
        'priority'   => 'low',
        'show_names' => true, // Show field names on the left
        'fields'     => array(
            array(
                'name'    => __( 'Display in Progress Map', 'badgeos-interactive-progress-map' ),
                'desc' 	 => ' '.__( 'Display this activity type and all related achievements on the Interactive Progress Map.', 'badgeos-interactive-progress-map' ),
                'id'      => $prefix . 'display_in_progress_map',
                'type'	 => 'checkbox',
            ),
            array(
                'name' => __( 'Description', 'badgeos-interactive-progress-map' ),
                'desc' => ' '.__( 'Add a description for display on the Interactive Progress Map.', 'badgeos-interactive-progress-map' ),
                'id'   => $prefix . 'achievement_type_description',
                'type' => 'textarea_small',
            ),
        ),
    );

    return $meta_boxes;
}

add_filter( 'cmb_meta_boxes', 'badgeos_interactive_progress_map_custom_metaboxes' );

?>

 