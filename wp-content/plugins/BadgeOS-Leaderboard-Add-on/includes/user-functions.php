<?php
/**
 * User Support Functions
 *
 * @package BadgeOS
 * @subpackage Leaderboard
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Get an individual user's metrics.
 *
 * Requires $leaderboard_id to only collect metrics
 * that are relevant to a given leaderboard.
 *
 * @since  1.0.0
 *
 * @param  integer $user_id        User ID.
 * @param  integer $leaderboard_id Leaderboard post ID.
 * @return array                   User's metrics
 */
function badgeos_leaderboard_get_user_metrics( $user_id = 0, $leaderboard_id = 0 ) {

	// Get leaderboard data
	$leaderboard_data = badgeos_leaderboard_get_data( $leaderboard_id );

	// Initialize user metrics array
	$user_metrics = array();

	// Populate user metrics from leaderboard metrics
	if ( ! empty( $leaderboard_data['metrics'] ) ) {
		foreach ( $leaderboard_data['metrics'] as $metric ) {
			// Switch between each type of metric
			switch ( $metric ) {
				case 'points' :
					$user_metrics[ $metric ] = badgeos_get_users_points( $user_id );
					break;
				default :
					$user_metrics[ $metric ] = count( badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_type' => $metric ) ) );
					break;
			}
		}
	}

	// Add the user ID to the array for safe-keeping
	$user_metrics['user_id'] = $user_id;

	// Return user metrics
	return (array) apply_filters( 'badgeos_leaderboard_get_user_metrics', $user_metrics, $user_id, $leaderboard_id );

}
