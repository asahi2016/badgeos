<?php
/**
 * User Report
 *
 * @package BadgeOS Reports
 * @subpackage Reports
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register the user report
 *
 * @since 1.0.0
 */
function badgeos_reports_register_active_user_report() {

	// Setup the report
	$report = new BadgeOS_Report();
	$report->title = __( 'Active Users Report', 'badgeos-reports' );
	$report->slug  = 'active-users-report';
	add_action( "badgeos_reports_render_page_{$report->slug}", 'badgeos_reports_register_active_user_report_data' );

}
add_action( 'init', 'badgeos_reports_register_active_user_report' );

/**
 * Render the user report page
 *
 * @since 1.0.0
 */
function badgeos_reports_register_active_user_report_data( $report ) {
	global $wpdb;

    // Groups list query based on user groups
    if(class_exists( 'BP_Groups_Group' ) && bp_is_active( 'groups' ) && class_exists("BadgeOS_Group_Management")){
        $group_filter_query = ",( SELECT GROUP_CONCAT(DISTINCT groups.name)
                                  FROM  ".$wpdb->prefix."bp_groups_members as groups_members
                                  INNER JOIN ".$wpdb->prefix."bp_groups as groups
                                  WHERE groups.id = groups_members.group_id
                                  AND groups_members.user_id = user.ID
                               ) as group_name";

        // Query filter for tables based on users
        if(badgeos_get_user_role()=="school_admin"){
            $usermeta_filter = "AND usermeta.meta_key = 'school_id' AND usermeta.meta_value =".get_current_user_id();
        }elseif(badgeos_get_user_role()=="author"){
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
            $groups_drop_down .= ' <select name="groups" id="groups" class="user-reports" style="max-width: 15%">';
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
			SELECT     user.ID,
			           user_login as username,
			           first_name.meta_value as first_name,
			           last_name.meta_value as last_name,
			           user.user_email as email,
			           (
			                SELECT     COUNT(*)
			                FROM       $wpdb->postmeta as meta
			                INNER JOIN $wpdb->posts as post
			                           ON post.ID = meta.post_id
			                WHERE      meta.meta_key = '_badgeos_log_achievement_id'
			                           AND post.post_author = user.ID
			                           AND post.post_title LIKE '%%unlocked%%'
			           ) as total_achievements,
			           (
			                SELECT     COUNT(*)
			                FROM       $wpdb->postmeta as meta
			                INNER JOIN $wpdb->posts as post
			                           ON post.ID = meta.post_id
			                WHERE      meta.meta_key = '_badgeos_log_achievement_id'
			                           AND post.post_author = user.ID
			                           AND achievements.post_date >= %s
			                           AND post.post_date < %s
			                           AND post.post_title LIKE '%%unlocked%%'
			           ) as recent_achievements,
			           (
			                SELECT     post.post_date
			                FROM       $wpdb->postmeta as meta
			                INNER JOIN $wpdb->posts as post
			                           ON post.ID = meta.post_id
			                WHERE      meta.meta_key = '_badgeos_log_achievement_id'
			                           AND post.post_author = user.ID
			                           AND post.post_title LIKE '%%unlocked%%'
			                ORDER BY   post.post_date DESC
			                LIMIT      1
			           ) as last_earned,
			           user_registered as join_date
                       $group_filter_query
			FROM       $wpdb->users as user
			INNER JOIN $wpdb->usermeta as first_name
			           ON first_name.user_id = user.ID
			           AND first_name.meta_key = 'first_name'
			INNER JOIN $wpdb->usermeta as last_name
			           ON last_name.user_id = user.ID
			           AND last_name.meta_key = 'last_name'
            INNER JOIN $wpdb->usermeta as usermeta
                        ON usermeta.user_id = user.ID
                        $usermeta_filter
			INNER JOIN $wpdb->posts as achievements
			           ON achievements.post_author = user.ID
			           AND achievements.post_type = 'badgeos-log-entry'
			INNER JOIN $wpdb->postmeta as achievement_meta
			           ON achievements.ID = achievement_meta.post_ID
			           AND achievement_meta.meta_key = '_badgeos_log_achievement_id'
			WHERE      achievements.post_date >= %s
			           AND achievements.post_date < %s
					   AND achievements.post_title LIKE '%%unlocked%%'
			GROUP BY   user.ID
			",
			date( 'Y-m-d', $report->get_start_date() ),
			date( 'Y-m-d', $report->get_end_date() + DAY_IN_SECONDS ),
			date( 'Y-m-d', $report->get_start_date() ),
			date( 'Y-m-d', $report->get_end_date() + DAY_IN_SECONDS )
		),
		'ARRAY_A'
	);
	$report->columns = array(
		'ID' => array(
			'title'         => __( 'User ID', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => false,
			'show_in_chart' => false,
			'show_in_csv'   => false,
		),
		'username' => array(
			'title'         => __( 'Username', 'badgeos-reports' ),
			'data-type'     => 'user_login',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'first_name' => array(
			'title'         => __( 'First Name', 'badgeos-reports' ),
			'data-type'     => 'string',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'last_name' => array(
			'title'         => __( 'Last Name', 'badgeos-reports' ),
			'data-type'     => 'string',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'email' => array(
			'title'         => __( 'Email', 'badgeos-reports' ),
			'data-type'     => 'email',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'total_achievements' => array(
			'title'         => __( 'Total Achievements', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'recent_achievements' => array(
			'title'         => __( 'Achievements Earned in Range', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'last_earned' => array(
			'title'         => __( 'Last Achievement Date', 'badgeos-reports' ),
			'data-type'     => 'date',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'join_date' => array(
			'title'         => __( 'Date Joined', 'badgeos-reports' ),
			'data-type'     => 'date',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
	);

    // Adding group column to the report
    if(class_exists( 'BP_Groups_Group' ) && bp_is_active( 'groups' )){
        $report->columns['group_name'] = array(
            'title'         => __( 'Groups', 'badgeos-reports' ),
            'data-type'     => 'string',
            'show_in_table' => true,
            'show_in_chart' => false,
            'show_in_csv'   => true,
        );
    }

	// Output report description
	echo '<h2>' . __( 'This report lists users who have earned achievements within this time period.', 'badgeos-reports' ) . '</h2>';

	// Add user count data points to page
	$user_count     = count_users();
	$active_users   = count( $report->data );
	$total_users    = $user_count['total_users'];
	$percent_active = $total_users ? ( $active_users / $total_users * 100 ) : 0;
	echo $report->render_data_point( __( 'Active Users', 'badgeos-reports' ), $active_users, 'integer' );
	echo $report->render_data_point( __( 'Total Users', 'badgeos-reports' ), $total_users, 'integer' );
	echo $report->render_data_point( __( 'Percent Active', 'badgeos-reports' ), $percent_active, 'percentage' );

    //Display group lists
    echo $groups_drop_down;

		// // Count up our join dates
	// $join_totals   = wp_list_pluck( $report->data, 'total_users' );
	// $join_dates    = array_map( 'badgeos_reports_reformat_date', wp_list_pluck( $report->data, 'join_date' ) );
	// $new_by_date   = array_count_values( $join_dates ); // Date => Count
	// $total_by_date = ( ! empty( $join_dates ) && ! empty( $join_totals ) ) ? array_combine( $join_dates, $join_totals ) : array();

	// // Setup our chart data array
	// if ( ! empty( $total_by_date ) ) {
	// 	$chart_data = array();
	// 	$chart_data['labels'] = array_unique( $join_dates );
	// 	$chart_data['datasets'][] = $total_by_date;
	// 	$chart_data['datasets'][] = $new_by_date;

	// 	// Add join chart to page
	// 	echo $report->render_chart( 'line', $chart_data );
	// }

	// Add report table to page
	echo $report->render_table();
}

/**
 * Rewrite a given date string into a given format
 *
 * @since  1.0.0
 * @param  string $date   The given date string
 * @param  string $format A PHP Date format
 * @return string         A reformatted date string
 */
function badgeos_reports_reformat_date( $date = '', $format = 'M d, Y' ) {
	return date( $format, strtotime( $date ) );
}
