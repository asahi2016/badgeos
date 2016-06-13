<?php


/**
 * Interactive progress map - Add sub menu page for color code setting
 *
 * @since  1.0.0
 */
function badgeos_interactive_progress_map_color(){
    add_submenu_page('interactive-progress-map','BadgeOS Progress Map Settings' , 'BadgeOS Progress Map Settings' ,'administrator', 'interactive-progress-map-color', 'interactive_progress_map_settings');
}
add_action( 'admin_menu', 'badgeos_interactive_progress_map_color');


/**
 * Interactive progress map - render form of control color codes based on post status (like - completed, pending and skipped)
 *
 * @since  1.0.0
 */
function interactive_progress_map_settings(){

    wp_enqueue_script('colorpicker_script');
    wp_enqueue_script('progress_map_admin_script');

    //Render styles in admin dashboard
    wp_enqueue_style( 'colorpicker_css');
    wp_enqueue_style( 'font_awesome_css');
    wp_enqueue_style( 'progress_map_css');

    $colors = get_interactive_progress_map_color_codes();

 ?>
    <div class="container wrap">
        <h2 class="progressMap_title">Interactive Progress Map</h2>
        <div class="colorPicker_cirle">
            <div class="section_circle">
                <div class="lbl_circle">
                    <p>Completion Color</p>
                </div>
                <div class="colorPicker_cir impColor" <?php echo $colors['completed'];?>>
                    <i class="fa fa-check"></i>
                </div>
                 <span id="completedCircle" class="colorPicker_circle lbl_span" <?php echo $colors['completed'];?>><?php echo $colors['colorcode'];?></span>
            </div>
        </div>
        <div class="colorPicker_cirle btn">
            <button class="button button-primary button-large color_picker_save_btn">Save</button>
        </div>
    </div>
<?php
}


/**
 * Get Interactive progress map color code
 * @return array
 */
function get_interactive_progress_map_color_codes(){
    $option_name = 'interactive_progress_map_settings';

    //Default colors
    $completed_style = 'style="background-color: #1CBE30;"';
    if(get_option($option_name) !== false){
        //Get user defined colors
        $color_codes = get_option($option_name);
        $colors = unserialize($color_codes);
        if($colors){
            //Replace default background colors to user defined background colors
            $completed_style = 'style="background-color:'.$colors['completed'].'"';
        }
    }
    $style = array(
        'completed'=> $completed_style,
        'colorcode'=> $colors['colorcode'],
    );

    return $style;
}


/**
 * Interactive progress map - update color codes based on post status (like - completed, pending and skipped)
 *
 * @since  1.0.0
 */
function interactive_progress_map_color_codes(){

    if($_GET['action'] == 'interactive_progress_map_color_codes'){

        $option_name = 'interactive_progress_map_settings';

        $color_codes = array('completed' => $_GET['completed'],
            'colorcode' => $_GET['colorcode']
            /*'pending' => $_GET['pending'],
            'skipped' => $_GET['skipped']*/);

        $colors = serialize($color_codes);

        $message = '';

        //Check ineteractive progress map setting value - if exists or not
        if(get_option($option_name) !== false){
            //Update the color code settings
            update_option($option_name, $colors);
            $message = 'Interactive progress map color codes updated successfully';

        }else{
            //Insert the color code settings
            add_option( $option_name, $colors, '', 'yes' );
            $message = 'Interactive progress map color codes saved successfully';
        }

        if($message){
            $message = '<div class="badgeos-interactive-progress-map"><p>'.$message.'</p></div>';
        }

        //Send back a successful response
        $response = array('message' => $message);
        wp_send_json_success( $response );

    }
}
add_action( 'wp_ajax_interactive_progress_map_color_codes', 'interactive_progress_map_color_codes' );