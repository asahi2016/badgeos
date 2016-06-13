<?php

/**
 * Render a specific leaderboard.
 *
 * @since  1.0.0
 * @param  array  $args Output parameters.
 * @return string       HTML markup of leaderboard.
 */
function badgeos_leaderboard_display_leaderboard( $args = array() ){

	// If no ID was given, bail here
	if ( ! isset( $args['leaderboard_id'] ) )
		return false;

	// Parse passed args against leaderboard data
	$leaderboard_data = badgeos_leaderboard_get_data( $args['leaderboard_id'] );
	$args = wp_parse_args( $args, $leaderboard_data );

	// If there are no leaderboard metrics, bail here
	if ( empty( $leaderboard_data['metrics'] ) ) {
		return false;
	}

	// Get leaders
	$leaders = badgeos_leaderboard_get_leaders( $args['leaderboard_id']);

	// Sort and trim leaders array
	if ( ! empty( $leaders ) ) {
		$leaders = badgeos_leaderboard_sort_leaders( $leaders, $args['orderby'] );
		$leaders = badgeos_leaderboard_trim_leaders( $leaders, $args['user_count'] );
	}

	// If there are no leaders, or no metrics, bail here
	if ( empty( $leaders ) ) {
		return false;
	}

	// Setup column names
	$columns = $leaderboard_data['metrics'];

	// Initialize output
	$output = '<table class="badgeos-leaderboard badgeos-leaderboard-' . $args['leaderboard_id'] . '">';

	// Setup column headers
	$output .= '<thead><tr>';
	$output .= '<th></th>';
	$output .= '<th>' . apply_filters( 'badgeos-leaderboard-table-user-name', 'User Name' ) . '</th>';
	foreach ( $columns as $column ) {
		$post_type = get_post_type_object( $column );
		if ( is_object( $post_type ) ){
			$output .= '<th';
			if ( $column == $args['sort_metric'] )
				$output .= ' class="default-sort"';
			$output .= '>' . apply_filters( 'badgeos-leaderboard-table-' . $column, $post_type->labels->name ) . '</th>';
		}else{
			$output .= '<th';
			if ( $column == $args['sort_metric'] )
				$output .= ' class="default-sort"';
			$output .= '>' . apply_filters( 'badgeos-leaderboard-table-' . $column, ucwords( $column ) ) . '</th>';
		}

	}
	$output .= '</tr></thead><tbody>';

	$count = 1;
	// Include each leader
	foreach ( $leaders as $user_metrics ) {
		// Only continue if we have non-corrupt user metrics
		if ( is_array( $user_metrics ) ) {
			$user_data = get_user_by( 'id', $user_metrics['user_id'] );
			// Only output data if user still exists
			if ( is_object( $user_data ) ) {
				$output .= '<tr>';
					$output .= '<td>' . $count++ . '</td>';

					$output .= '<td>';

					// If link profiles is on, and BP exists...
					if( true == $args['link_profiles'] && class_exists( 'BuddyPress' ) )
						$output .= '<a href="' . bp_core_get_userlink( $user_metrics['user_id'], false, true ) . '">';

					// If avatars are enabled
					if( true == $args['show_avatars'] )
						$output .=  get_avatar( $user_metrics['user_id'], 32 );

					$output .= apply_filters( 'badgeos_leaderboard_display_name', $user_data->user_login, $user_metrics['user_id'], $user_data );

					// If link profiles is on, and BP exists...
					if( true == $args['link_profiles'] && class_exists( 'BuddyPress' ) )
						$output .= '</a>';

					$output .= '</td>';

					foreach ( $columns as $column ) {
						$output .= '<td>' . $user_metrics[ $column ] . '</td>';
					}
				$output .= '</tr>';
			}
		}
	}

	// Close table
	$output .= '</tr></tbody></table>';

	// Include custom scripts and styles
	wp_enqueue_style( 'badgeos-leaderboard' );
	wp_enqueue_script( 'badgeos-leaderboard' );
	add_action('wp_footer', 'badgeos_leaderboard_initiate_table_sort');

	// Return filterable output
	return apply_filters( 'badgeos_leaderboard_display_leaderboard', $output, $args );
}
function badgeos_leaderboard_initiate_table_sort(){
	echo '<script>
			jQuery(document).ready(function($){
				$(".badgeos-leaderboard").tablesorter({debug: true});
			});
		</script>';
}
