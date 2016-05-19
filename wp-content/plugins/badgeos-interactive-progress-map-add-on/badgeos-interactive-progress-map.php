<?php
/**
 * Plugin Name: BadgeOS Interactive Progress Map Add-On
 * Plugin URI: http://www.badgeos.org/
 * Version: 1.0.0
 * Author: Learning Times
 * Description: This BadgeOS add-on integrates interactive progress map for users.
 * Text Domain: badgeos-interactive-progress-map
 */

define( 'IPM_SL_ITEM_NAME', 'Interactive Progress Map' );
define( 'IPM_SL_STORE_URL', 'http://badgeos.org/' );

class BadgeOS_Interactive_Progress_Map {

    public $basename;
    public $directory_path;
    public $directory_url;
    public $version = '1.0.0';


    function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
        $this->directory_url = plugin_dir_url( __FILE__ );

		// Load translations
		load_plugin_textdomain( 'badgeos-interactive-progress-map', false, 'badgeos-interactive-progress-map/languages' );

		// Run our activation
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

        // Hook in our dependent files and methods
        add_action( 'init', array( $this, 'updates' ) );

		add_action( 'plugins_loaded', array( $this, 'badgeos_progress_map_includes' ) );

        // Add interactive progress map menu for Super admin
        add_action( 'admin_menu', array( $this, 'badgeos_interactive_progress_map_menu' ) );

        // Register scripts and styles
        add_action( 'init', array( $this, 'progress_map_register_scripts_and_styles' ) );

        add_action('admin_init', array( $this, 'interactive_progress_map_activate_license'));
	}

    /**
     * Create BadgeOS Interactive Progress Map menu
     *
     * @since 1.0.0
     */
    public function badgeos_interactive_progress_map_menu(){
        //Add Badgeos Interactive Progress Map menu in admin dashboard
        add_menu_page('Badgeos Interactive Progress Map','BadgeOS Progress Map','administrator', 'interactive-progress-map', 'badgeos_interactive_progress_map_license_page',$GLOBALS['badgeos']->directory_url . 'images/badgeos_icon.png');
    }


    /**
     * Register hook for add scripts and styles.
     *
     * @since 1.0.0
     */
    public function progress_map_register_scripts_and_styles(){

        //Register scripts
        wp_register_script('colorpicker_script',plugins_url( '/js/colorpicker.js' , __FILE__ ),array( 'colorpicker' ),true);
        wp_register_script('front_script',plugins_url( '/js/front.js' , __FILE__ ),'1.0',true);
        wp_register_script('progress_map_script',plugins_url( '/js/interactive-progress-map.js' , __FILE__ ),'1.0',true);
        wp_register_script('progress_map_admin_script',plugins_url( '/js/admin.js' , __FILE__ ),'1.0',true);
        wp_register_script('slick_script',plugins_url( '/js/slick.min.js' , __FILE__ ),'1.0',true);


        //Register styles
        wp_register_style( 'colorpicker_css', plugins_url('css/colorpicker.css', __FILE__));
        wp_register_style( 'font_awesome_css', plugins_url('css/font-awesome/css/font-awesome.min.css', __FILE__));
        wp_register_style( 'progress_map_css', plugins_url('css/progress_map.css', __FILE__));
        wp_register_style( 'slick_css', plugins_url('css/slick.css', __FILE__));
        wp_register_style( 'slick_theme_css', plugins_url('css/slick-theme.css', __FILE__));
        wp_register_style( 'progress_map_front_css', plugins_url('css/progress_map_front.css', __FILE__));
    }

	/**
	 * Files to include for BadgeOS integration.
	 *
	 * @since  1.0.0
	 */
	public function badgeos_progress_map_includes() {


		if ( $this->meets_requirements() ) {

            require_once( $this->directory_path . 'includes/badgeos-ipm-license.php' );

            $slug = basename($this->basename, '.php');

            $status = get_option( $slug . '-license_status' );

            if(($status !== false) && $status == 'valid') {

                require_once( $this->directory_path . 'includes/admin/color-code-settings.php' );
                require_once($this->directory_path . 'includes/badgeos-shortcode.php');
                require_once($this->directory_path . 'includes/functions.php');

                $url = $_SERVER['SCRIPT_NAME'];
                $filename = basename($url);
                if (in_array($filename, array('post.php', 'post-new.php'))) {
                    require_once($this->directory_path . 'includes/admin/meta-boxes.php');
                }
            }
		}

	}

	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
        // Do some activation things.
	}

    /**
     * Deactivation hook for the plugin.
     *
     * @since 1.0.0
     */
    public function deactivate() {

        // Do some deactivation things. Note: this plugin may
        // auto-deactivate due to $this->maybe_disable_plugin()

    }

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists( 'BadgeOS' ))
			return true;
		else
			return false;
	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {
        if ( !$this->meets_requirements() ) {
            // Display our error
            echo '<div id="message" class="error">';

            if ( !class_exists( 'BadgeOS' ) || !function_exists( 'badgeos_get_user_earned_achievement_types' ) ) {
                echo '<p>' . sprintf( __( 'BadgeOS Interactive Progress Map requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-interactive-progress-map' ), admin_url( 'plugins.php' ) ) . '</p>';
            }
            echo '</div>';

            // Deactivate our plugin
            deactivate_plugins( $this->basename );

		}
	}


    /**
     * Register our add-on for automatic updates
     *
     * @since  1.0.0
     */
    public function updates() {

        if ( class_exists( 'BadgeOS_Plugin_Updater' ) ) {

            $badgeos_updater = new BadgeOS_Plugin_Updater( array(
                    'plugin_file' => __FILE__,
                    'item_name'   => IPM_SL_ITEM_NAME,
                    'author'      => 'Credly',
                    'version'     => '1.0.0',
                )
            );

            $slug = basename($this->basename, '.php');
            $license = get_option(  $slug. '-license_key' );
            $status = get_option(  $slug. '-license_status' );

            if(($license !== false) && ($status !== false)){

                $store_url = IPM_SL_STORE_URL;
                $item_name = IPM_SL_ITEM_NAME;

                $api_params = array(
                    'edd_action' => 'check_license',
                    'license' => $license,
                    'item_name' => urlencode( $item_name ),
                    'author'      => 'Credly',
                    'version'     => '1.0.1'
                );

                $response = wp_remote_get( add_query_arg( $api_params, $store_url ), array( 'timeout' => 15, 'sslverify' => false ) );
                if ( is_wp_error( $response ) )
                    return false;
                $license_data = json_decode( wp_remote_retrieve_body( $response ) );

                $license_data->license = ($license_data->license != 'valid') ? 'invalid' : $license_data->license;

                update_option($slug. '-license_status', $license_data->license);

            }
        }
    }

    public function interactive_progress_map_activate_license() {
        // listen for our activate button to be clicked
        if( isset( $_POST['interactive_progress_map_license_activate'] ) ) {

            // run a quick security check
            if( ! check_admin_referer( 'badgeos_settings_nonce', 'badgeos_settings_nonce' ) )
                return; // get out if we didn't click the Activate button


            if ( class_exists( 'BadgeOS_Plugin_Updater' ) ) {

                //passing custom params
                $badgeos_ipm_updater = new BadgeOS_Plugin_Updater( array(
                        'plugin_file' => __FILE__,
                        'item_name'   => IPM_SL_ITEM_NAME,
                        'author'      => 'Credly',
                        'version'     => '1.0.0',
                    )
                );
                $slug = basename($this->basename, '.php');
                $license = get_option(  $slug. '-license_key' );

                if(!empty($license)) {
                    $input['licenses'][$slug] = $license;

                    //Validate license key
                    $badgeos_ipm_updater->validate_license($input);
                }

            }

        }

    }

}
$GLOBALS['badgeos_progress_map'] = new BadgeOS_Interactive_Progress_Map();

