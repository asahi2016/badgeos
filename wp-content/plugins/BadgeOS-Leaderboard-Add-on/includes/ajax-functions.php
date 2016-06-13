<?php
/**
 * Leaderboard AJAX Helper Functions
 *
 * @package BadgeOS
 * @subpackage Leaderboard
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

// Setup our MVP AJAX actions
$badgeos_ajax_actions = array(
	'get-leaderboard-metrics',
);

// Register core Ajax calls.
foreach ( $badgeos_ajax_actions as $action ) {
	add_action( 'wp_ajax_' . $action, 'badgeos_leaderboard_ajax_' . str_replace( '-', '_', $action ), 1 );
	add_action( 'wp_ajax_nopriv_' . $action, 'badgeos_leaderboard_ajax_' . str_replace( '-', '_', $action ), 1 );
}

/**
 * Get leaderboard metric slug/label pairs via AJAX
 *
 * @since  1.0.0
 */
function badgeos_leaderboard_ajax_get_leaderboard_metrics() {

	// Get metric slugs and labels
	$leaderboard_id = isset( $_REQUEST['leaderboard_id'] ) ? absint( $_REQUEST['leaderboard_id'] ) : 0;

	// Return a successful response
	if ( $leaderboard_id && 'leaderboard' == get_post_type( $leaderboard_id ) )
		wp_send_json_success( badgeos_leaderboard_get_data( $leaderboard_id ) );
	else
		wp_send_json_error( array( 'reason' => 'No leaderboard ID', 'sort_metric' => '', 'metrics' => array() ) );
}
