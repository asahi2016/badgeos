<?php
/**
 * Leaderboard Support Functions
 *
 * @package BadgeOS
 * @subpackage Leaderboard
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Get an array of all published leaderboards.
 *
 * @since  1.0.0
 *
 * @return array       Leaderboard objects.
 */
function badgeos_leaderboard_get_leaderboards() {
	global $wpdb;

	$leaderboards = $wpdb->get_results(
		"
		SELECT *
		FROM   $wpdb->posts
		WHERE  post_type = 'leaderboard'
		       AND post_status = 'publish'
		"
	);

	// Return all found leaderboards
	return (array) apply_filters( 'badgeos_leaderboard_get_leaderboards', $leaderboards );
}

/**
 * Get all relevant data about a given leaderboard.
 *
 * @since  1.0.0
 *
 * @param  integer $leaderboard_id Leaderboard post ID.
 * @return array                   Leaderboard details.
 */
function badgeos_leaderboard_get_data( $leaderboard_id = 0 ) {

	// Build data array
	$leaderboard_data = array(
		'user_count'  => absint( get_post_meta( $leaderboard_id, '_badgeos_leaderboard_user_limit', true ) ),
		'metrics'     => get_post_meta( $leaderboard_id, '_badgeos_leaderboard_metrics' ),
		'sort_metric' => get_post_meta( $leaderboard_id, '_badgeos_leaderboard_sort_metric', true ),
	);

	// Return filterable data array
	return (array) apply_filters( 'badgeos_leaderboard_get_data', $leaderboard_data, $leaderboard_id );
}

/**
 * Get current leaders for a given leaderboard.
 *
 * Eample Format:
 * array(
 *     [0] => array(
 *         'user_id'    => $user_id,
 *         'time_added' => time(),
 *         'points'     => 0,
 *         'badges'     => 0,
 *         ...
 *     ), ...
 * );
 *
 * @since  1.0.0
 *
 * @param  integer $leaderboard_id Leaderboard post ID.
 * @return array                   Array of leaders and their stored metrics, keyed by user ID.
 */
function badgeos_leaderboard_get_leaders( $leaderboard_id = 0 ) {
	$leaders = get_post_meta( $leaderboard_id, '_badgeos_leaderboard_leaders', true );
	return ! empty( $leaders ) ? $leaders : array();
}

/**
 * Sort leaderboard leaders by a given metric
 *
 * @since  1.0.0
 *
 * @param  array  $leaders Leaders array.
 * @param  string $metric  Metric to use for sort column (e.g. 'points').
 * @return array           Sorted leaders array.
 */
function badgeos_leaderboard_sort_leaders( $leaders = array(), $metric = '' ) {

	// Sanity check to ensure metric exists
	if ( ! empty( $metric ) ) {
		// Grab an aray of sort column data
		$sort_column = ! empty( $leaders ) ? wp_list_pluck( $leaders, $metric ) : array();

		// Sort the array
		if ( ! empty( $sort_column ) ) {
			array_multisort( $sort_column, SORT_NUMERIC, SORT_DESC, $leaders );
		}
	}

	// Return the possibly re-sorted array
	return (array) apply_filters( 'badgeos_leaderboard_sort_leaders', $leaders, $metric );
}

/**
 * Trim leaderboard based on user limit.
 *
 * @since  1.0.0
 *
 * @param  array     $leaders    Current leaders array.
 * @param  integer   $user_limit Maximum number of leaders to include.
 * @return array                 Updated leaders array.
 */
function badgeos_leaderboard_trim_leaders( $leaders = array(), $user_limit = 0 ) {

	// Cache leader array for posterity
	$original_leaders = $leaders;

	// Only modify the array if a limit was specified
	if ( $user_limit && count( $leaders ) >= $user_limit )
		$leaders = array_slice( $leaders, 0, absint( $user_limit ) );

	return (array) apply_filters( 'badgeos_leaderboard_trim_leaders', $leaders, $original_leaders, $user_limit );
}

/**
 * Get trackable metrics for leaderboards.
 *
 * Format Example:
 * array(
 *     'points' => 'Points',
 *     'badges' => 'Badges',
 *     'level'  => 'Levels',
 *     ...
 * );
 *
 * @since  1.0.0
 *
 * @return array Metrics keyed by slug with name as value.
 */
function badgeos_leaderboard_get_metrics() {

	// Initialize metrics array
	$metrics = array();
	$metrics['points'] = __( 'Points', 'badgeos-leaderboard' );

	// Grab all registered achievement types
	$achievement_types = badgeos_get_achievement_types();

	// If there are achievement types...
	if ( ! empty( $achievement_types ) ) {

		// Ignore step achievements
		if ( isset( $achievement_types['step'] ) )
			unset ( $achievement_types['step'] );

		// Loop through each achievement type
		foreach ( $achievement_types as $slug => $data ) {
			// Build post type object
			$post_type = get_post_type_object( $slug );

			// Add post type to array
			$metrics[ $slug ] = $post_type->labels->name;
		}
	}

	// Return available metrics
	return (array) apply_filters( 'badgeos_leaderboard_get_metrics', $metrics );
}


