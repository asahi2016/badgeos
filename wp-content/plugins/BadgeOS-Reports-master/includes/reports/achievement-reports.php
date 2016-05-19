<?php
/**
 * Achievement Type Reports
 *
 * @package BadgeOS Reports
 * @subpackage Reports
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register a report for all achievement types
 *
 * @since 1.0.0
 */
function badgeos_reports_register_achievement_reports() {
	// Grab all of our achievement type posts
	$achievement_types = get_posts( array(
		'post_type'      =>	'achievement-type',
		'posts_per_page' =>	-1,
	) );

	foreach ($achievement_types as $achievement ) {
		badgeos_reports_register_achievement_report( $achievement );
	}
}
add_action( 'init', 'badgeos_reports_register_achievement_reports' );

/**
 * Register a report for a given achievement type
 *
 * @since 1.0.0
 * @param object $achievement The achievement type post object
 */
function badgeos_reports_register_achievement_report( $achievement = null ) {

	// Setup the report
	$report = new BadgeOS_Report();

	// Grab the singular and plural names for the achievement
	$report->achievement = $achievement;
	$report->singular    = $achievement->post_title;
	$report->plural      = get_post_meta( $achievement->ID, '_badgeos_plural_name', true );

	// Continue setting up the report
	$report->title = $report->plural;
	$report->slug  = $achievement->post_name;
	add_action( "badgeos_reports_render_page_{$report->slug}", 'badgeos_reports_register_achievement_report_data' );

}

/**
 * Render the achievement report page
 *
 * @since 1.0.0
 */
function badgeos_reports_register_achievement_report_data( $report ) {
	global $wpdb;

	$date_range   = $report->__get_query_date_range();

    // If Buddy Press is activated, only then display Group Filters
    if( class_exists( 'BP_Groups_Group' ) && bp_is_active( 'groups' ) && class_exists("BadgeOS_Group_Management")){

        // Query filter for tables based on groups
        if(isset($_REQUEST['groups']) && !empty($_REQUEST['groups']))
        $group_filter = "AND groups.id = ".$_REQUEST['groups'];

        // Query filter for tables based on users
        if(badgeos_get_user_role()=="school_admin"){
            $usermeta_filter = "AND usermeta.meta_key = 'school_id' AND usermeta.meta_value =".get_current_user_id();
        }elseif(badgeos_get_user_role()=="author"){
            /*$usermeta_filter = " AND (usermeta.meta_key = 'school_id' AND usermeta.meta_value =".badgeos_get_school_id()."
                               OR usermeta.meta_key = 'teacher_id' AND usermeta.meta_value =".get_current_user_id().")";   */
            $usermeta_filter = " AND usermeta.meta_key = 'teacher_id' AND usermeta.meta_value =".get_current_user_id();
        }

        // Load Groups drop down based on user roles
        $user_role = badgeos_get_user_role( absint( get_current_user_id() ));
        $user_id = ($user_role=="author")?absint( get_current_user_id() ):'';
        $group_meta_query = ($user_role=="school_admin")?array ( array ('key' => 'school_id','value' => absint( get_current_user_id() ) ) ):'';

        $bp_public_groups = groups_get_groups(
            array(
                'user_id' => $user_id,
                'meta_query' => $group_meta_query,
                'orderby' => 'name',
                'order'   => 'ASC'
            )
        );

        $selected_id = isset( $_REQUEST['groups'] ) ? absint( $_REQUEST['groups'] ) : 0;

        if ( $bp_public_groups['total'] > 0 ) {
            $groups_drop_down = '<h3>Groups:</h3>';
            $groups_drop_down .= ' <select name="groups" id="groups" class="achievement-reports" onchange="this.form.submit()" style="max-width: 15%" form="report-filter">';
            $groups_drop_down .= '<option value="">' . __( 'All', 'badgeos-reports' ) . '</option>';
            foreach( $bp_public_groups['groups'] as $group ) {

                if($user_role=="author" && !groups_is_user_admin($user_id,$group->id))
                    continue;

                $groups_drop_down .= '<option value="' . absint( $group->id ) . '" ' . selected( $selected_id, $group->id, false ) . '>' . esc_attr( $group->name ) . '</option>';
            }
            $groups_drop_down .= '</select>';
        }
    }


    $report->data = $wpdb->get_results(
        $wpdb->prepare(
            "
			SELECT     achievement.ID as ID,
			           achievement.post_title as title,
			           (
			               SELECT     COUNT(DISTINCT post.ID)
			               FROM       $wpdb->postmeta as meta
			               INNER JOIN $wpdb->posts as post
			                          ON post.ID = meta.post_id
                           INNER JOIN $wpdb->users as user
                                      ON post.post_author = user.ID
                           LEFT JOIN ".$wpdb->prefix."bp_groups_members AS groups_members
	                                  ON groups_members.user_id = user.ID
                           LEFT JOIN ".$wpdb->prefix."bp_groups AS groups
                                      ON groups.id = groups_members.group_id
                           LEFT JOIN  wp_usermeta AS usermeta
                                      ON usermeta.user_id = user.ID
			               WHERE      meta.meta_key = '_badgeos_log_achievement_id'
			                          AND meta.meta_value = achievement.ID
			                          AND post.post_title LIKE '%%unlocked%%'
			                          $group_filter
                                      $usermeta_filter
                                      $date_range
			           ) as earned_count,
			           (
			               SELECT     post.post_date
			               FROM       $wpdb->postmeta as meta
			               INNER JOIN $wpdb->posts as post
			                          ON post.ID = meta.post_id
			               WHERE      meta.meta_key = '_badgeos_log_achievement_id'
			                          AND meta.meta_value = achievement.ID
			                          AND post.post_title LIKE '%%unlocked%%'
			                          $date_range
			               LIMIT      1
			           ) as last_earned_date
			FROM       $wpdb->posts as achievement
			WHERE      achievement.post_type = %s
			",
            $report->achievement->post_name
        ),
        'ARRAY_A'
    );
	$report->columns = array(
		'ID' => array(
			'title'         => __( 'Achievement ID', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => false,
			'show_in_chart' => false,
			'show_in_csv'   => false,
		),
		'title' => array(
			'title'         => sprintf( __( '%s Name', 'badgeos-reports' ), $report->singular ),
			'data-type'     => 'post_title',
			'show_in_table' => true,
			'show_in_chart' => true,
			'show_in_csv'   => true,
		),
		'earned_count' => array(
			'title'         => __( 'Earning Count', 'badgeos-reports' ),
			'data-type'     => 'earnings',
			'show_in_table' => true,
			'show_in_chart' => true,
			'show_in_csv'   => true,
		),
		'last_earned_date' => array(
			'title'         => __( 'Last Earned', 'badgeos-reports' ),
			'data-type'     => 'date',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
	);

	// Add "total achievements" data point to page
	$total_achievements = count( $report->data );
	echo $report->render_data_point( sprintf( __( 'Total %s', 'badgeos-reports' ), $report->plural ), $total_achievements, 'integer' );

	// Add "total awarded" data point to page
	$total_earned = array_sum( wp_list_pluck( $report->data, 'earned_count' ) );
	echo $report->render_data_point( sprintf( __( 'Total %s Awarded', 'badgeos-reports' ), $report->plural ), $total_earned, 'integer' );

	// Add "avg awarded" data point to page
	$avg_earned = ( $total_achievements ) ? $total_earned / $total_achievements : 0;
	echo $report->render_data_point( sprintf( __( 'Avg Earnings per %s', 'badgeos-reports' ), $report->singular ), $avg_earned, 'float' );

    // Output groups drop down field
    echo $groups_drop_down;

	// Add report table to page
	echo $report->render_table();
}
