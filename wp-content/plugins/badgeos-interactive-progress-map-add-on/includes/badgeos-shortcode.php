<?php

/**
 * Register [badgeos_interactive_progress_map] shortcode.
 *
 * @since 1.0.0
 */
function badgeos_register_interactive_progress_map_shortcode() {

    // Setup a custom array of achievement types
    $achievement_types = array_diff( badgeos_get_achievement_types_slugs(), array( 'step' ) );
    //print_r($achievement_types);
    array_unshift( $achievement_types, 'all' );

	badgeos_register_shortcode( array(
		'name'            => __( 'Interactive Progress Map', 'badgeos-interactive-progress-map' ),
		'description'     => __( 'Output a progress map of user.', 'badgeos-interactive-progress-map' ),
		'slug'            => 'badgeos_interactive_progress_map',
		'output_callback' => 'badgeos_interactive_progress_map_shortcode',
		'attributes'      => array(

			'limit' => array(
				'name'        => __( 'Limit', 'badgeos-interactive-progress-map' ),
				'description' => __( 'Number of achievements to display.', 'badgeos-interactive-progress-map' ),
				'type'        => 'text',
				'default'     => 10,
				),
            'status' => array(
                'name'        => __( 'Status', 'badgeos-interactive-progress-map' ),
                'description' => __( 'Achievement statuses to display.', 'badgeos-interactive-progress-map' ),
                'type'        => 'select',
                'values'      => array(
                    'all'      => __( 'All Achievements', 'badgeos-interactive-progress-map' ),
                    'completed' => __( 'Completed Achievements', 'badgeos-interactive-progress-map' ),
                    'not-completed'   => __( 'Incomplete Achievements', 'badgeos-interactive-progress-map' ),
                ),
                'default'     => 'all'
            ),

		),
	) );
}
add_action( 'init', 'badgeos_register_interactive_progress_map_shortcode' );

/**
 * interactive Progress Map Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_interactive_progress_map_shortcode( $atts = array () ){

    //Render scripts in admin dashboard
    wp_enqueue_script('progress_map_script');
    wp_enqueue_script('slick_script');
    wp_enqueue_script('front_script');


    //Render styles in admin dashboard
    wp_enqueue_style( 'font_awesome_css');
    wp_enqueue_style( 'slick_css');
    wp_enqueue_style( 'slick_theme_css');
    wp_enqueue_style( 'progress_map_front_css');

	// check if shortcode has already been run
	if ( isset( $GLOBALS['badgeos_interactive_progress_map'] ) )
		return '';

	global $user_ID;
	extract( shortcode_atts( array(
        'type'        => 'all',
        'limit'       => '10',
        'filter'       => 'all',
        'status'       => 'all',
        'show_search' => true,
        'group_id'    => '0',
        'user_id'     => '0',
        'wpms'        => false,
        'orderby'     => 'menu_order',
        'order'       => 'ASC',
        'include'     => array(),
        'exclude'     => array(),
        'meta_key'    => '',
        'meta_value'  => ''

	), $atts, 'badgeos_interactive_progress_map' ) );

	$data = array(
        'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
        'type'        => $type,
        'filter'        => $filter,
        'limit'       => $limit,
        'status'       => $status,
        'show_search' => $show_search,
        'group_id'    => $group_id,
        'user_id'     => $user_id,
        'wpms'        => $wpms,
        'orderby'     => $orderby,
        'order'       => $order,
        'include'     => $include,
        'exclude'     => $exclude,
        'meta_key'    => $meta_key,
        'meta_value'  => $meta_value
	);
	wp_localize_script( 'progress_map_script', 'badgeos_interactive_progress_map', $data );

	// If we're dealing with multiple achievement types
	if ( 'all' == $type ) {
		$post_type_plural = 'achievements';
	} else {
		$types = explode( ',', $type );
		$post_type_plural = ( 1 == count( $types ) ) ? get_post_type_object( $type )->labels->name : 'achievements';
	}


    if ( 'all' == $type ) {
        $type = badgeos_get_achievement_types_slugs();
        // Drop steps from our list of "all" achievements
        $step_key = array_search( 'step', $type );
        if ( $step_key )
            unset( $type[$step_key] );
    } else {
        $type = explode( ',', $type );
    }

    $badges = '';
    $badges .= '<div id="badgeos-progress-map-filters-wrap">';
    // Filter
    if ( $status != 'all' ) {
        $badges .= '<input type="hidden" name="progress_map_list_filter" id="progress_map_list_filter" value="'.$status.'">';
    }else{

        $badges .= '<div id="badgeos-progress-map-filter">';

        $badges .= __( 'Filter:', 'badgeos-interactive-progress-map' ) . '<select name="progress_map_list_filter" id="progress_map_list_filter">';

        $badges .= '<option value="all">' . sprintf( __( 'All %s', 'badgeos-interactive-progress-map' ), $post_type_plural );
        // If logged in
        if ( $user_ID >0 ) {
            $badges .= '<option value="completed">' . sprintf( __( 'Completed %s', 'badgeos-interactive-progress-map' ), $post_type_plural );
            $badges .= '<option value="not-completed">' . sprintf( __( 'Not Completed %s', 'badgeos-interactive-progress-map' ), $post_type_plural );
        }
        // TODO: if show_points is true "Badges by Points"
        // TODO: if dev adds a custom taxonomy to this post type then load all of the terms to filter by

        $badges .= '</select>';

        $badges .= '</div>';

    }

    $badges .= '</div><!-- #badgeos-progress-map-filters-wrap -->';

    // Content Container
    $badges .= '<div id="badgeos-progress-map-container" class="container"></div>';

    // Hidden fields and Load More button
    $badges .= '<input type="hidden" id="badgeos_progress_map_offset" value="0">';
    $badges .= '<input type="hidden" id="badgeos_progress_map_count" value="0">';
    $badges .= '<input type="button" id="progress_map_list_load_more" value="' . esc_attr__( 'Load More', 'badgeos-interactive-progress-map' ) . '" style="display:none;">';
    $badges .= '<div class="badgeos-spinner"></div>';

    // Reset Post Data
    wp_reset_postdata();
    //$badges_progress_map = "output";

    // Save a global to prohibit multiple shortcodes
	$GLOBALS['badgeos_interactive_progress_map'] = true;
	return $badges;

}

