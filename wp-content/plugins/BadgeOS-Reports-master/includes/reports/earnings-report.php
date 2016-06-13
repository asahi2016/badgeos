<?php
/**
 * Earners Report
 *
 * @package BadgeOS Reports
 * @subpackage Reports
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register the earners report
 *
 * @since 1.0.0
 */
function badgeos_reports_register_earners_report() {

	// Setup the report
	$report                 = new BadgeOS_Report();
	$report->achievement_id = isset( $_GET['achievement_id'] ) ? $_GET['achievement_id'] : 0;
	$report->title          = sprintf( __( '"%s" Earnings Report', 'badgeos-reports' ), get_the_title( $report->achievement_id ) );
	$report->slug           = 'earnings-report';
	$report->show_in_menu   = false;
	add_action( "badgeos_reports_render_page_{$report->slug}", 'badgeos_reports_register_earners_report_data' );

}
add_action( 'init', 'badgeos_reports_register_earners_report' );

/**
 * Render the user earners page
 *
 * @since 1.0.0
 */
function badgeos_reports_register_earners_report_data( $report ) {
	global $wpdb;

	$date_range   = $report->__get_query_date_range();

    if(class_exists( 'BP_Groups_Group' ) && bp_is_active( 'groups' ) && class_exists("BadgeOS_Group_Management")){
        //$group_filter = " WHERE  groups.id = ".$_REQUEST['groups'];
        $group_filter_sub_query = ",(
			              SELECT GROUP_CONCAT(DISTINCT groups.name)
			              FROM  ".$wpdb->prefix."bp_groups_members as groups_members
			              INNER JOIN ".$wpdb->prefix."bp_groups as groups
			              WHERE groups.id = groups_members.group_id
			              AND groups_members.user_id = user.ID
			           ) as group_name";

        $group_filter_joins = ' LEFT JOIN '.$wpdb->prefix.'bp_groups_members AS groups_members
                                ON groups_members.user_id = user.ID
                                LEFT JOIN '.$wpdb->prefix.'bp_groups AS groups
                                ON groups.id = groups_members.group_id ';

        if(isset($_REQUEST['groups']) && !empty($_REQUEST['groups'])){
            $group_filter_joins .=' WHERE  groups.id ='.$_REQUEST['groups'];
        }

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
			SELECT     user.ID,
					   user.user_login as username,
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
									   AND meta.meta_value = %d
									   $date_range
					   ) as earned_count,
					   (
							SELECT     post.post_date
							FROM       $wpdb->postmeta as meta
							INNER JOIN $wpdb->posts as post
									   ON post.ID = meta.post_id
							WHERE      meta.meta_key = '_badgeos_log_achievement_id'
									   AND post.post_author = user.ID
									   AND post.post_title LIKE '%%unlocked%%'
									   AND meta.meta_value = %d
									   $date_range
							ORDER BY   post.post_date DESC
							LIMIT      1
					   ) as last_earned
					   $group_filter_sub_query
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
			INNER JOIN $wpdb->posts as post
					   ON post.post_author = user.ID
					   AND post.post_title LIKE '%%unlocked%%'
					   $date_range
			INNER JOIN $wpdb->postmeta as meta
					   ON post.id = meta.post_id
					   AND meta.meta_key = '_badgeos_log_achievement_id'
					   AND meta.meta_value = %d
                       $group_filter_joins
			GROUP BY   user.ID
			",
			$report->achievement_id,
			$report->achievement_id,
			$report->achievement_id
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
		'earned_count' => array(
			'title'         => __( 'Earned Count', 'badgeos-reports' ),
			'data-type'     => 'integer',
			'show_in_table' => true,
			'show_in_chart' => false,
			'show_in_csv'   => true,
		),
		'last_earned' => array(
			'title'         => __( 'Last Earned', 'badgeos-reports' ),
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

	// Add "total users" data point to page
	$total_users = count( $report->data );
	echo $report->render_data_point( __( 'Total Users', 'badgeos-reports' ), $total_users, 'integer' );

	// Add "total earnings" data point to page
	$total_earned = array_sum( wp_list_pluck( $report->data, 'earned_count' ) );
	echo $report->render_data_point( __( 'Total Earnings', 'badgeos-reports' ), $total_earned, 'integer' );

	// Add "avg earnings" data point to page
	$avg_earned = ( $total_users ) ? $total_earned / $total_users : 0;
	echo $report->render_data_point( __( 'Avg Earnings per User', 'badgeos-reports' ), $avg_earned, 'float' );

    //Display group lists
    echo $groups_drop_down;

	// Add report table to page
	echo $report->render_table();
}
