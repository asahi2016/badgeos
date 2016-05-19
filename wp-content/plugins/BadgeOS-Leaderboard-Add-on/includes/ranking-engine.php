<?php
/**
 * Ranking Engine: The brains behind the leaderboard ranking
 *
 * @package BadgeOS
 * @subpackage Leaderboard
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Check if user ranks in any leaderboard.
 *
 * @since 1.0.0
 *
 * @param integer $user_id User ID.
 */
function badgeos_leaderboard_check_user_rankings( $user_id = 0 ) {

	// Get all leaderboards
	$leaderboards = badgeos_leaderboard_get_leaderboards();

	// If we have leaderboards, attempt to rank the user in each of them
	if ( ! empty( $leaderboards ) ) {
		foreach ( $leaderboards as $leaderboard ) {
			badgeos_leaderboard_maybe_add_user( $leaderboard->ID, $user_id );
		}
	}

}
add_action( 'badgeos_award_achievement', 'badgeos_leaderboard_check_user_rankings', 1000 );

/**
 * Check if user ranks in any leaderboard after being awarded points.
 *
 * @since 1.0.0
 *
 * @param integer $user_id User ID.
 */
function badgeos_leaderboard_check_user_rankings_points_earned( $user_id, $new_points, $total_points, $admin_id ) {

	// Only check rankings if points were admin-awarded
	if ( absint( $admin_id ) )
		badgeos_leaderboard_check_user_rankings( $user_id );

}
add_action( 'badgeos_update_users_points', 'badgeos_leaderboard_check_user_rankings_points_earned', 10, 4 );

/**
 * Add user to leaderboard if they have ranking stats.
 *
 * @since 1.0.0
 *
 * @param integer $leaderboard_id Leaderboard post ID.
 * @param integer $user_id        User ID.
 * @return mixed                  Array if user ranks, false otherwise
 */
function badgeos_leaderboard_maybe_add_user( $leaderboard_id = 0, $user_id = 0 ) {

	// If user ranks, add them. Plain and simple!
	if ( badgeos_leaderboard_does_user_rank( $leaderboard_id, $user_id ) )
		return badgeos_leaderboard_add_user( $leaderboard_id, $user_id );
	else
		return false;

}

/**
 * Check if user is eligible to rank in a leaderboard.
 *
 * @since  1.0.0
 *
 * @param  integer $leaderboard_id Leaderboard post ID.
 * @param  integer $user_id        User ID.
 * @return bool                    True if user ranks, otherwise false.
 */
function badgeos_leaderboard_does_user_rank( $leaderboard_id = 0, $user_id = 0 ) {

	// Assume the user will NOT rank
	$rank = false;

	// Get user ranking data
	$user_stats = badgeos_leaderboard_get_user_metrics( $user_id, $leaderboard_id );

	// Get leaderboard data
	$leaderboard_data = badgeos_leaderboard_get_data( $leaderboard_id );
	$leaders = badgeos_leaderboard_get_leaders( $leaderboard_id );

	// If there are no leaders, user wins
	if ( empty( $leaders ) )
		$rank = true;

	// If there are fewer leaders than maximum, user wins
	if ( ! $rank && count( $leaders ) < $leaderboard_data['user_count'] )
		$rank = true;

	// See if the user will beat any existing leader
	if ( ! $rank ) {
		foreach ( $leaders as $leader_id => $leader_stats ) {

			// If user is already a leader, they win
			if ( $leader_id == $user_id ) {
				$rank = true;
				break;
			}

			// If any user's metric is greater than a leader's, they win
			foreach ( $leaderboard_data['metrics'] as $metric ) {
				if ( $user_stats[ $metric ] > $leader_stats[ $metric ] ) {
					$rank = true;
					break(2);
				}
			}

		}
	}

	// Return our filterable status
	return apply_filters( 'badgeos_leaderboard_does_user_rank', $rank, $leaderboard_id, $user_id );
}

/**
 * Add user to leaderboard and update stored leaders.
 *
 * @since  1.0.0
 *
 * @param  integer $leaderboard_id Leaderboard post ID.
 * @param  integer $user_id        User ID.
 * @return array                   Updated leaders.
 */
function badgeos_leaderboard_add_user( $leaderboard_id = 0, $user_id = 0 ) {

	// Get and cache current leaders
	$leaders = $original_leaders = badgeos_leaderboard_get_leaders( $leaderboard_id );

	// Re-key leaders array with user IDs
	if ( ! empty( $leaders ) ) {
		$user_ids = array_values( wp_list_pluck( $leaders, 'user_id') );
		$leaders = array_combine( $user_ids, $leaders );
	}

	// Get user's metrics
	$user_data = badgeos_leaderboard_get_user_metrics( $user_id, $leaderboard_id );

	// Add user to leaders array
	$leaders[ $user_id ] = $user_data;
	$leaders[ $user_id ]['time_added'] = time();

	// Update the leaderboard
	$leaders = badgeos_leaderboard_update_leaders( $leaderboard_id, $leaders );

	// Return new leaders array
	return (array) apply_filters( 'badgeos_leaderboard_add_user', $leaders, $leaderboard_id, $user_id, $original_leaders );
}

/**
 * Update leaders for a given leaderboard
 *
 * @since 1.0.0
 *
 * @param integer $leaderboard_id Leaderboard post ID
 * @param array   $leaders        Array of leaders
 * @return array                  Filterable array of leaders
 */
function badgeos_leaderboard_update_leaders( $leaderboard_id = 0, $leaders = array() ) {

	// Bail early if either piece of data is missing
	if ( empty( $leaderboard_id ) || empty( $leaders ) )
		return false;

	// Cache current leaders
	$original_leaders = badgeos_leaderboard_get_leaders( $leaderboard_id );

	// Get leaderboard data
	$leaderboard_data = badgeos_leaderboard_get_data( $leaderboard_id );

	// Sort array based on key metric
	$leaders = badgeos_leaderboard_sort_leaders( $leaders, $leaderboard_data['sort_metric'] );

	// Trim array to size
	$leaders = badgeos_leaderboard_trim_leaders( $leaders, $leaderboard_data['user_count'] );

	// Update leaderboard leaders meta
	update_post_meta( $leaderboard_id, '_badgeos_leaderboard_leaders', $leaders );

	// Return leaders
	return (array) apply_filters( 'badgeos_leaderboard_update_leaders', $leaders, $leaderboard_id, $original_leaders );
}

/**
 * Populate a leaderboard for the very first time.
 *
 * @since 1.0.0
 *
 * @param integer $post_id Post ID.
 */
function badgeos_leaderboard_setup_leaderboard( $post_id = 0 ) {

	// Only continue if we meet all requirements
	if (
		! empty( $_POST ) // Confirm post data
		&& isset( $_POST['post_type'] ) // Confirm post type exists
		&& ( 'leaderboard' == $_POST['post_type'] ) // Confirm Leaderboard post type
		&& isset( $_POST['wp_meta_box_nonce'] ) // Confirm nonce exists
		&& wp_verify_nonce( $_POST['wp_meta_box_nonce'], 'init.php' ) // Confirm nonce matches
		&& current_user_can( 'edit_post', $post_id ) // Confirm user's permissions
		//&& ! badgeos_leaderboard_get_leaders( $post_id ) // Confirm leaderboard is empty
	) {
		badgeos_leaderboard_rebuild_leaderboard( $post_id );
	}

}
add_action( 'wp_insert_post', 'badgeos_leaderboard_setup_leaderboard' );

/**
 * Rebuild a given leaderboard
 *
 * @since 1.0.0
 *
 * @param integer $leaderboard_id Leaderboard post ID.
 * @return array                  Array of leaders
 */
function badgeos_leaderboard_rebuild_leaderboard( $leaderboard_id = 0 ) {

	// Wipe leaderboard data
	delete_post_meta( $leaderboard_id, '_badgeos_leaderboard_leaders' );

	// Get all users
	$users = get_users();

	// Attempt to add each user to the leaderboard
	foreach ($users as $user ) {
		$leaders = badgeos_leaderboard_maybe_add_user( $leaderboard_id, $user->ID );
	}

	// Return the final list of leaders
	return $leaders;

}

/**
 * Trigger leaderboard rebuild when CPT rebuild link is clicked.
 *
 * @since  1.0.0
 */
function badgeos_leaderboard_trigger_rebuild() {

	// If not rebuilding a leaderboard, or secuirty fails, bail
	if (
		! isset( $_GET['rebuild_leaderboard'] )
		|| ! wp_verify_nonce( $_GET['_wpnonce'], 'rebuild_leaderboard' )
		|| ! current_user_can( 'edit_post', absint( $_GET['rebuild_leaderboard'] ) )
	)
		return;

	// Rebuild leaderboard
	badgeos_leaderboard_rebuild_leaderboard( absint( $_GET['rebuild_leaderboard'] ) );

}
add_action( 'admin_init', 'badgeos_leaderboard_trigger_rebuild' );
