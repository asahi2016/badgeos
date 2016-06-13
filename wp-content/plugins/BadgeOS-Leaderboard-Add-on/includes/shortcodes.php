<?php
/**
 * Custom Shortcodes
 *
 * @package BadgeOS
 * @subpackage Leaderboard
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register the [badgeos_leaderboard] shortcode.
 *
 * @since 1.1.0
 */
function badgeos_register_leaderboard_shortcode() {
	badgeos_register_shortcode( array(
		'name'            => __( 'Leaderboard', 'badgeos-leaderboard' ),
		'slug'            => 'badgeos_leaderboard',
		'output_callback' => 'badgeos_leaderboard_display_shortcode',
		'description'     => __( 'Render an Achievements Leaderboard.', 'badgeos-leaderboard' ),
		'attributes'      => array(
			'id' => array(
				'name'        => __( 'Leaderboard ID', 'badgeos-leaderboard' ),
				'description' => __( 'ID of Leaderboard to display.', 'badgeos-leaderboard' ),
				'type'        => 'select',
				'values'      => badgeos_leaderboard_get_leaderboards_for_embedder(),
			),
			'limit' => array(
				'name'        => __( 'Limit', 'badgeos-leaderboard' ),
				'description' => __( 'User output limit (optional, cannot excede leaderboard limit). Default: Leaderboard limit.', 'badgeos-leaderboard' ),
				'type'        => 'text',
			),
			'orderby' => array(
				'name'        => __( 'Order by', 'badgeos-leaderboard' ),
				'description' => __( 'Ranking metric to use for default sort (optional). Default: to Leaderboard sort setting.', 'badgeos-leaderboard' ),
				'type'        => 'text',
			),
			'show_avatars' => array(
				'name'        => __( 'Show Avatars', 'badgeos-leaderboard' ),
				'description' => __( 'Display avatar beside username (optional). Default: true.', 'badgeos-leaderboard' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos-leaderboard' ),
					'false' => __( 'False', 'badgeos-leaderboard' )
					),
				'default'     => 'true',
			),
			'link_profiles' => array(
				'name'        => __( 'Link Profiles', 'badgeos-leaderboard' ),
				'description' => __( 'Link usernames to profiles, if using BuddyPress (optional). Default: true', 'badgeos-leaderboard' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos-leaderboard' ),
					'false' => __( 'False', 'badgeos-leaderboard' )
					),
				'default'     => 'true',
			),
		),
	) );
}
add_action( 'init', 'badgeos_register_leaderboard_shortcode', 11 );

/**
 * Get leaderboards array for use in the Shortcode Embedder.
 *
 * @since  1.1.0
 *
 * @return array Leaderboard titles keyed by ID.
 */
function badgeos_leaderboard_get_leaderboards_for_embedder() {
	$leaderboards      = badgeos_leaderboard_get_leaderboards();
	$leaderboard_ids   = wp_list_pluck( $leaderboards, 'ID' );
	$leaderboard_names = wp_list_pluck( $leaderboards, 'post_title' );
	return array_combine( $leaderboard_ids, $leaderboard_names );
}

/**
 * Leaderboard Display Short Code
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes
 * @return string 	   The concatenated markup
 */
function badgeos_leaderboard_display_shortcode( $atts = array () ){

	// Parse passed attributes
	$atts = shortcode_atts( array(
		'name'          => '',
		'id'            => '',
		'limit'         => '',
		'orderby'       => '',
		'show_avatars'  => 'true',
		'link_profiles' => 'true',
		),
		$atts
	);

	// Attempt to retrieve leaderboard ID from name
	if ( ! empty( $atts['name'] ) ) {
		$leaderboard = get_page_by_title( $atts['name'], 'OBJECT', 'leaderboard' );
		if ( is_object( $leaderboard ) ) {
			$leaderboard_id = $leaderboard->ID;
		}
	}

	// Otherwise, use a passed ID
	if ( ! isset( $leaderboard_id ) ) {
		$leaderboard_id = $atts['id'];
	}

	// Transpose atts to $args
	$args = array(
		'leaderboard_id' => $leaderboard_id,
		'user_count'     => $atts['limit'],
		'orderby'        => $atts['orderby'],
		'show_avatars'   => $atts['show_avatars'],
		'link_profiles'  => $atts['link_profiles'],
	);

	return badgeos_leaderboard_display_leaderboard( $args ) ;

}
