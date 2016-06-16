<?php

/**
 * Allow users to skip achievements
 *
 * @since  1.0.0
 */
function get_progress_map(){
    global $user_ID, $blog_id;

    // Setup our AJAX query vars
    $type       = isset( $_REQUEST['type'] )       ? $_REQUEST['type']       : false;
    $limit      = isset( $_REQUEST['limit'] )      ? $_REQUEST['limit']      : false;
    $offset     = isset( $_REQUEST['offset'] )     ? $_REQUEST['offset']     : false;
    $count      = isset( $_REQUEST['count'] )      ? $_REQUEST['count']      : false;
    $filter     = isset( $_REQUEST['filter'] )     ? $_REQUEST['filter']     : false;
    $search     = isset( $_REQUEST['search'] )     ? $_REQUEST['search']     : false;
    $user_id    = isset( $_REQUEST['user_id'] )    ? $_REQUEST['user_id']    : false;
    $orderby    = isset( $_REQUEST['orderby'] )    ? $_REQUEST['orderby']    : false;
    $order      = isset( $_REQUEST['order'] )      ? $_REQUEST['order']      : false;
    $wpms       = isset( $_REQUEST['wpms'] )       ? $_REQUEST['wpms']       : false;
    $include    = isset( $_REQUEST['include'] )    ? $_REQUEST['include']    : array();
    $exclude    = isset( $_REQUEST['exclude'] )    ? $_REQUEST['exclude']    : array();
    $meta_key   = isset( $_REQUEST['meta_key'] )   ? $_REQUEST['meta_key']   : '';
    $meta_value = isset( $_REQUEST['meta_value'] ) ? $_REQUEST['meta_value'] : '';

    // Convert $type to properly support multiple achievement types
    if ( 'all' == $type ) {
        $type = badgeos_get_achievement_types_slugs();
        // Drop steps from our list of "all" achievements
        $step_key = array_search( 'step', $type );
        if ( $step_key )
            unset( $type[$step_key] );
    } else {
        $type = explode( ',', $type );
    }

    // Get the current user if one wasn't specified
    if( ! $user_id )
        $user_id = $user_ID;

    // Build $include array
    if ( !is_array( $include ) ) {
        $include = explode( ',', $include );
    }

    // Build $exclude array
    if ( !is_array( $exclude ) ) {
        $exclude = explode( ',', $exclude );
    }

    // Initialize our output and counters
    $achievements = '';
    $achievement_count = 0;
    $query_count = 0;

    // Grab our hidden badges (used to filter the query)
    $hidden = badgeos_get_hidden_achievement_ids( $type );

    // If we're polling all sites, grab an array of site IDs
    if( $wpms && $wpms != 'false' )
        $sites = badgeos_get_network_site_ids();
    // Otherwise, use only the current site
    else
        $sites = array( $blog_id );

    // Loop through each site (default is current site only)
    foreach( $sites as $site_blog_id ) {

        // If we're not polling the current site, switch to the site we're polling
        if( $blog_id != $site_blog_id )
            switch_to_blog( $site_blog_id );

        // Query Achievements
        $args = array(
            'post_type'      =>	'achievement-type',
            'posts_per_page'   => -1,
            'orderby'        =>	$orderby,
            'order'          =>	$order,
            /*'posts_per_page' =>	$limit,
            'offset'         => $offset,*/
            'post_status'    => 'publish',
            'post__not_in'   => $hidden
        );

        if ( '' !== $meta_key && '' !== $meta_value ) {
            $args[ 'meta_key' ] = $meta_key;
            $args[ 'meta_value' ] = $meta_value;
        }

        // Include certain achievements
        if ( !empty( $include ) ) {
            $args[ 'post__not_in' ] = array_diff( $args[ 'post__not_in' ], $include );
            $args[ 'post__in' ] = array_merge( array( 0 ), array_diff( $include, $args[ 'post__in' ] ) );
        }

        // Exclude certain achievements
        if ( !empty( $exclude ) ) {
            $args[ 'post__not_in' ] = array_merge( $args[ 'post__not_in' ], $exclude );
        }

        // Loop Achievements
        $achievement_posts = new WP_Query( $args );

        while ( $achievement_posts->have_posts() ) : $achievement_posts->the_post();

            if ( !badgeos_is_completed_achievement_type( sanitize_title( get_the_title() ) ) && $filter == 'completed'){
                continue;
            }elseif(badgeos_is_completed_achievement_type( sanitize_title( get_the_title() ) ) && $filter == 'not-completed'){
                continue;
            }

            // Check specific achievement type display in interactive progress map
            if (get_post_meta(get_the_ID(), '_badgeos_display_in_progress_map', true))
                $achievements .= interactive_progress_map_render_form( sanitize_title( get_the_title()),get_the_ID());
        endwhile;

        // Display a message for no results
        if ( empty( $achievements ) ) {
            // If we have exactly one achivement type, get its plural name, otherwise use "achievements"
            $post_type_plural = 'achievements';

            // Setup our completion message
            $achievements .= '<div class="badgeos-no-results">';
            if ( 'completed' == $filter ) {
                $achievements .= '<p>' . sprintf( __( 'No completed %s to display at this time.', 'badgeos-interactive-progress-map' ), strtolower( $post_type_plural ) ) . '</p>';
            }else{
                $achievements .= '<p>' . sprintf( __( 'No %s to display at this time.', 'badgeos-interactive-progress-map' ), strtolower( $post_type_plural ) ) . '</p>';
            }
            $achievements .= '</div><!-- .badgeos-no-results -->';
        }
    }

    // Send back our successful response
    wp_send_json_success( array(
        'message'     => $achievements,
        'offset'      => $offset + $limit,
        'query_count' => $query_count,
        'badge_count' => $achievement_count,
        'type'        => $type,
    ) );
}
add_action( 'wp_ajax_get_progress_map', 'get_progress_map' );

/**
 * Getting Completed achievement types for a logged in User
 *
 * @since  1.0.0
 *
 * @param null $post_type
 * @return bool
 */
function badgeos_is_completed_achievement_type($post_type = NULL){

    // Grab our hidden badges (used to filter the query)
    $hidden = badgeos_get_hidden_achievement_ids( $post_type );

    // Arguments for fetching achievements
    $args = array(
        'posts_per_page'   => -1, // unlimited achievements
        'offset'           => 0,  // start from first row
        'post_type'        => $post_type, // Filter only achievement type posts from title
        'post_status'        => 'publish',
        'suppress_filters' => false,
        'achievement_relationsihp' => 'any',
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'fields' => 'ids',
        'post__not_in'   => $hidden
    );

    $all_achievements = get_posts( $args );

    if(empty($all_achievements))
        return false;

    $user_achievements = badgeos_get_user_achievements();
    $earned_ids = array();

    foreach ( $user_achievements as $user_achievement )
        $earned_ids[] = $user_achievement->ID;

    foreach($all_achievements as $all_achievement){

        if((!in_array($all_achievement, $earned_ids))) {
            return false;
        }
    }
    return true;
}


/**
 * Checking Completed achievement types for all achievements
 *
 * @since  1.0.0
 *
 * @param null $post_id
 * @return bool
 */
function badgeos_check_completed_achievement_type_for_all_achievements($post_id = NULL){

    global $wpdb;
    $step_ids = $wpdb->get_results( $wpdb->prepare( "SELECT p2p_from as step FROM $wpdb->p2p WHERE p2p_to = %d", $post_id ) );

    //Check this achievement type is trigger or submission
    $trigger = get_post_meta($post_id, '_badgeos_earned_by', true);

    //Get trigger type for completed steps
    $triggers = badgeos_get_activity_triggers();
    $types = array();
    foreach($triggers as $key => $value){
       array_push($types , $key);
    }

    if($trigger == 'triggers'){
        //Check trigger type and achievement type
       $all_steps_complete = array();
       foreach($step_ids as $res) {
           $trigger_type = get_post_meta($res->step , '_badgeos_trigger_type', true);
           $achievement_post_type = get_post_meta($res->step , '_badgeos_achievement_type', true);
           if($achievement_post_type && !empty($types)){
                if(in_array($trigger_type,$types)){
                    if (badgeos_is_completed_achievement_type($achievement_post_type)) {
                        array_push($all_steps_complete , true);
                    }else{
                        array_push($all_steps_complete , false);
                    }
                }
           }

       }

       if(!in_array(false,$all_steps_complete)){
           return true;
       }else{
           return false;
       }
    }
    return false;
}

/**
 * Render Interactive Progress Map based on Logged in User
 *
 * @since  1.0.0
 *
 * @param string $post_type
 * @param int $id
 *
 * @return string
 */
function interactive_progress_map_render_form($post_type = '', $id = 0){

    $form = '';

    if($post_type){

        // Grab our hidden badges (used to filter the query)
        $hidden = badgeos_get_hidden_achievement_ids( $post_type );

        // Arguments for fetching achievements
        $args = array(
            'posts_per_page'   => -1, // unlimited achievements
            'offset'           => 0,  // start from first row
            'post_type'        => $post_type, // Filter only achievement type posts from title
            'post_status'        => 'publish',
            'suppress_filters' => false,
            'achievement_relationsihp' => 'any',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post__not_in'   => $hidden
        );

        $achievements_all = get_posts( $args );

        if($achievements_all){

            //Get color codes to related post status
            $colors = get_interactive_progress_map_color_codes();

            $status = null;
            $icon = null;
            $style = null;
            $show_achievement_icon = "none";
            $compeleted = false;

            //Build the html form to display the achievement lists
            if(badgeos_is_completed_achievement_type($post_type)){
                $status = 'success';
                $icon = 'fa-check';
                $style = $colors['completed'];
                $show_achievement_icon = "block";
            }elseif(!badgeos_is_completed_achievement_type($post_type)){
                $status = 'lock';
                $icon = 'fa-lock';
                $style = $colors['pending'];
            }

            //Build structure of achievement links and thumb image
            //$achievement_type = get_post( $id );
            $achievement_type_permalink  = get_permalink( $id );
            $achievement_type_title      = (strlen(get_the_title( $id ))>15)?substr( get_the_title( $id ), 0, 15) .'..' : get_the_title( $id );
            $achievement_type_img        = badgeos_get_achievement_post_thumbnail( $id, array( 80, 80 ), 'wp-post-image' );
            $achievement_type_thumb      = $achievement_type_img ? '<a style="margin-top: -25px;" class="badgeos-item-thumb" href="'. esc_url( $achievement_type_permalink ) .'">' . $achievement_type_img .'</a>' : '';
            $achievement_type_class      = 'widget-badgeos-item-title';

            /*
             * Enable achivement type status
             * <div class="achi_status" style="display:'.$show_achievement_icon.'">
                               <div class="status '.$status.'" '.$style.'>
                                   <i class="fa '.$icon.'"></i>
                               </div>
                             </div>
             */
            $desc = get_imp_decription_block_content($id);
            $form = '<div class="outerContainer">';
            $form .= $desc;
            $form .= '<div class="achi_container"  id="post-'.$id.'">
                              <div class="achievement">
                                <div class="achi_img">
                                    '.$achievement_type_img.'
                                </div>
                              <div class="achi_text">
                                 <span>'.$achievement_type_title.'</span>
                              </div>
                              </div>
                      </div>';

            $form .= '<div class="questionContainer">';
            $form .= '<div class="scroll">';

            $user_id = get_current_user_id();
            //Get all earned achievements based on current user
            $earned_achievements = get_user_meta( absint( $user_id ), '_badgeos_achievements', true );
            $earned_achievements_ids = array();
            if ( is_array( $earned_achievements) && ! empty( $earned_achievements ) ) {
                foreach ( $earned_achievements as $key => $earned_achievement ) {
                    foreach($earned_achievement as $achievement_ids){
                        $earned_achievements_ids[] = $achievement_ids->ID;
                    }
                }
            }

            //Get all skipped achievements based on current user
            $skiped_achievement_ids = array();
            $skiped_achievement_ids[] = get_user_meta( absint( $user_id ), '_badgeos_skipped_achievements', true );

            $status = null;
            $icon = null;
            $style = null;

            foreach($achievements_all as $k => $achievement){

                //Completed -  status = success, icon = fa-check
                //Skipped -  status = warning, icon = fa-warning
                //Pending -  status = lock, icon = fa-lock

                //Check completed step with all achievement type option based on specific achievement type
                $compeleted = badgeos_check_completed_achievement_type_for_all_achievements($achievement->ID);

                $show_icon = "none";
                $css_class = "";

                if(in_array($achievement->ID, $earned_achievements_ids) || $compeleted){
                    $status = 'success';
                    $icon = 'fa-check';
                    $style = $colors['completed'];
                    $show_icon = "block";
                }elseif(in_array($achievement->ID, $skiped_achievement_ids)){
                    $status = 'warning';
                    $icon = 'fa-warning';
                    $style = $colors['skipped'];
                    $css_class = 'badgeos-item-pending';
                }else{
                    $status = 'lock';
                    $icon = 'fa-lock';
                    $style = $colors['pending'];
                    $css_class = 'badgeos-item-pending';
                }

                //Build structure of achievement links and thumb image
                $permalink  = get_permalink( $achievement->ID );
                $title      = (strlen(get_the_title( $achievement->ID ))>20)?substr( get_the_title( $achievement->ID ), 0, 20 ) .'..' : get_the_title( $achievement->ID );
                $img        = badgeos_get_achievement_post_thumbnail( $achievement->ID, array( 50, 50 ), 'wp-post-image' );
                $thumb      = $img ? '<a class="badgeos-item-thumb '.$css_class.'" href="'. esc_url( $permalink ) .'">' . $img .'</a>' : '';
                $class      = 'widget-badgeos-item-title';

                $form .= '<div class="ques">
                                <div class="ques_img">
                                '.$thumb.'
                                </div>
                                <div class="ques_text">
                                    <a class="widget-badgeos-item-title '. esc_attr( $class ) .'" href="'. esc_url( $permalink ) .'">'. esc_html( $title ) .'</a>
                                </div>
                                <div class="ques_status" style="display:'.$show_icon.'">
                                    <div class="status '.$status.'" '.$style.'>
                                        <i class="fa '.$icon.'"></i>
                                    </div>
                                </div>
                            </div>';
            }

            $form .='</div>'; //end scroll
            $form .='</div>'; //end questionContainer
            $form .='</div>'; //end outerContainer
        }
    }

    return $form;
}

function get_imp_decription_block_content($post_id = null){

    $content = '';

    if ($desc = get_post_meta($post_id , '_badgeos_achievement_type_description', true)){

        $content .='<div class="imp_achiev_desc"><p>'.$desc.'</p></div>';

    }

    return $content;
}