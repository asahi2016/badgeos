<?php
/**
 * Plugin Name: BadgeOS Leaderboards Add-On
 * Plugin URI: http://www.badgeos.org/
 * Description: This BadgeOS add-on allows for the creation of Leaderboards, with various settings and display methods.
 * Author: LearningTimes, LLC
 * Version: 1.1.0
 * Author URI: https://learningtimes.com/
 * License: GNU AGPLv3
 * License URI: http://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * Our main plugin instantiation class
 *
 * This contains important things that our relevant to
 * our add-on running correctly. Things like registering
 * custom post types, taxonomies, posts-to-posts
 * relationships, and the like.
 *
 * @since 1.0.0
 */
class BadgeOS_Leaderboard {

	/**
	 * Get everything running.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( dirname( $this->basename ) );

		// Load translations
		load_plugin_textdomain( 'badgeos-leaderboard', false, dirname( $this->basename ) . '/languages' );

		// Run our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'init', array( $this, 'updates' ) );
		// Include our other plugin files
		add_action( 'plugins_loaded', array( $this, 'includes' ), 1 );
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_filter( 'cmb_meta_boxes', array( $this, 'metaboxes' ) );
		add_action( 'cmb_render_text_readonly', array( $this, 'cmb_render_text_readonly' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );

		//include our scripts and styles
		add_action( 'init', array( $this, 'register_scripts_and_styles' ) );

	} /* __construct() */

	public function updates() {
		if ( class_exists( 'BadgeOS_Plugin_Updater' ) ) {
			$badgeos_updater = new BadgeOS_Plugin_Updater( array(
					'plugin_file' => __FILE__,
					'item_name'   => 'Leaderboards',
					'author'      => 'Credly',
					'version'     => '1.1.0',
				)
			);
		}
	}


	/**
	 * Include our plugin dependencies
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// If BadgeOS is available...
		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . 'includes/ajax-functions.php' );
			require_once( $this->directory_path . 'includes/leaderboard-functions.php' );
			require_once( $this->directory_path . 'includes/user-functions.php' );
			require_once( $this->directory_path . 'includes/ranking-engine.php' );
			require_once( $this->directory_path . 'includes/display.php' );
			require_once( $this->directory_path . 'includes/shortcodes.php' );
			require_once( $this->directory_path . 'includes/widgets.php' );
		}

	} /* includes() */

	/**
	 * Register Leaderboard CPTs
	 *
	 * @since  1.0.0
	 */
	public function register_cpt() {
		// Register the Leaderboards CPT
		register_post_type( 'leaderboard', array(
			'labels'             => array(
				'name'               => __( 'Leaderboard', 'badageos' ),
				'singular_name'      => __( 'Leaderboards', 'badageos' ),
				'add_new'            => __( 'Add New', 'badageos' ),
				'add_new_item'       => __( 'Add New Leaderboard', 'badageos' ),
				'edit_item'          => __( 'Edit Leaderboard', 'badageos' ),
				'new_item'           => __( 'New Leaderboard', 'badageos' ),
				'all_items'          => __( 'Leaderboards', 'badageos' ),
				'view_item'          => __( 'View Leaderboards', 'badageos' ),
				'search_items'       => __( 'Search Leaderboards', 'badageos' ),
				'not_found'          => __( 'No leaderboards found', 'badageos' ),
				'not_found_in_trash' => __( 'No leaderboards found in Trash', 'badageos' ),
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Leaderboards', 'badageos' )
			),
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'badgeos_badgeos',
			'show_in_nav_menus'  => false,
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' )
		) );

	} /* register_cpt() */

	/**
	 * Register custom meta boxes used throughout BadgeOS Leaderboards
	 *
	 * @since  1.0.0
	 * @param  array  $meta_boxes The existing metabox array we're filtering
	 * @return array              An updated array containing our new metaboxes
	 */
	public function metaboxes( array $meta_boxes ) {
		// Setup tracking and sorting metrics
		$sort_metrics = array();
		$tracking_metrics = badgeos_leaderboard_get_metrics();
		foreach ( $tracking_metrics as $slug => $name ) {
			$sort_metrics[] = array( 'name' => $name, 'value' => $slug );
		}

		// Start with an underscore to hide fields from custom fields list
		$prefix = '_badgeos_leaderboard_';

		// Attempt to get the post ID
		$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

		// Setup primary leaderboard metabox
		$meta_boxes[] = array(
			'id'         => 'leaderboard_data',
			'title'      => __( 'Leaderboard Settings', 'badgeos-leaderboard' ),
			'pages'      => array( 'leaderboard' ), // Post type
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true, // Show field names on the left
			'fields'     => array(
				array(
					'name'    => __( 'Number of Users', 'badgeos-leaderboard' ),
					'desc'    => __( 'Maximum number of users to rank in this leaderboard.', 'badgeos-leaderboard' ),
					'id'      => $prefix . 'user_limit',
					'type'    => 'text_small',
					'std'     => '15',
				),
				array(
					'name'    => __( 'Metrics to Track', 'badgeos-leaderboard' ),
					'desc'    => __( 'Columns to display in leaderboard.', 'badgeos-leaderboard' ),
					'id'      => $prefix . 'metrics',
					'type'    => 'multicheck',
					'options' => $tracking_metrics,
				),
				array(
					'name'    => __( 'Default Rank Metric', 'badgeos-leaderboard' ),
					'desc'    => __( 'The default metric to use for sorting user rank.', 'badgeos-leaderboard' ),
					'id'      => $prefix . 'sort_metric',
					'type'    => 'select',
					'options' => $sort_metrics,
				),
				array(
					'name'    => __( 'Shortcode', 'badgeos-leaderboard' ),
					'desc'    => __( 'Paste this shortcode anywhere you want this leaderboard to appear.', 'badgeos-leaderboard' ),
					'id'      => $prefix . 'shortcode',
					'type'    => 'text_readonly',
					'std'     => ( ( $title = get_the_title( $post_id ) ) ? '[badgeos_leaderboard name=&quot;' . esc_attr( $title ) . '&quot;]' : __( 'Please give this leaderboard a title and click Publish.', 'badgeos-leaderboard' ) )
				),
				array(
					'name'    => __( 'Rebuild Leaderboard', 'badgeos-leaderboard' ),
					'desc'    => ( ( $title = get_the_title( $post_id ) )
									? sprintf(
										'<a class="button secondary" href="%1$s" onclick="return confirm(\'%2$s\') ? true : false;">%3$s</a> %4$s',
										wp_nonce_url( add_query_arg( 'rebuild_leaderboard', $post_id ), 'rebuild_leaderboard'),
										esc_js( __('Rebuilding will drop all current leaders and re-rank ALL eligible users. Are you sure you want to rebuild?', 'startbox') ),
										__( 'Rebuild Leaderboard', 'badgeos-leaderboard' ),
										( isset( $_GET['rebuild_leaderboard'] ) ? __( ' &ndash; Leaderboard successfully rebuilt.', 'badgeos-leaderboard' ) : '' )
									)
									: __( 'Leaderboard will automatically rebuild on first publish.', 'badgeos-leaderboard' )
								),
					'id'      => $prefix . 'rebuild',
					'type'    => 'text_only',
				),
			)
		);
		// Return metaboxes
		return $meta_boxes;

	} /* metaboxes() */


	/**
	 * Render a read-only text input field type for our CMB integration.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $field Field data array.
	 * @param  string $meta Stored meta for this field (will always be blank).
	 * @return string       HTML markup for the field.
	 */
	function cmb_render_text_readonly( $field = array(), $meta = '' ) {
		echo '<input class="text urlfield widefat" readonly="readonly" value="' . esc_attr( $field['std'] ) . '" type="text">';
		echo $field['desc'];
	} /* cmb_render_text_readonly() */

	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {

		// If BadgeOS is available, run our activation functions
		if ( $this->meets_requirements() ) {
			flush_rewrite_rules();
		}else{
			deactivate_plugins( $this->basename );
		}

	} /* activate() */

	/**
	 * Deactivation hook for the plugin.
	 *
	 * Note: this plugin may auto-deactivate due
	 * to $this->maybe_disable_plugin()
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		flush_rewrite_rules();

	} /* deactivate() */

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists( 'BadgeOS' ) && version_compare( BadgeOS::$version, '1.4.0', '>=' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
		// Display our error
	    echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'BadgeOS Leaderboards requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-addon' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';


	    // Deactivate our plugin
	    deactivate_plugins( $this->basename );
		}
	} /* maybe_disable_plugin() */


	/**
	 * Frontend scripts and styles
	 *
	 * @since 1.0.0
	 */
	function register_scripts_and_styles() {

		// If BadgeOS is available...
		if ( $this->meets_requirements() ) {
			// Admin scripts/styles
			wp_register_script( 'badgeos-leaderboard-admin', $this->directory_url . '/js/admin.js', array( 'jquery' ), '1.0.0', true );
			wp_localize_script( 'badgeos-leaderboard-admin', 'leaderboard', array( 'metricLabels' => badgeos_leaderboard_get_metrics() ) );

			// Front-end scripts/styles
			wp_register_script( 'badgeos-leaderboard', $this->directory_url . '/js/badgeos-leaderboard.js', array( 'jquery' ), '1.0.0', true );
			$css_file = file_exists( get_stylesheet_directory() .'/badgeos-leaderboard.css' )
				? get_stylesheet_directory_uri() .'/badgeos-leaderboard.css' :
				$this->directory_url . '/css/badgeos-leaderboard.css';

			wp_register_style( 'badgeos-leaderboard', $css_file, null, '1.0.0' );
		}

	} /* register_scripts_and_styles() */

	/**
	 * Load up admin scripts
	 *
	 * @since  1.0.0
	 */
	function load_admin_scripts() {

		// Attempt to get post ID or post type from URL
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$new_leaderboard_post = ( isset( $_GET['post_type'] ) && 'leaderboard' == $_GET['post_type'] ) ? true : false;

		// If creating a new leaderboard, or editing an existing one
		if ( $new_leaderboard_post || 'leaderboard' == get_post_type( $post_id ) ) {
			wp_enqueue_script( 'badgeos-leaderboard-admin' );
		}

	}


} /* BadgeOS_Addon */

// Instantiate our class to a global variable that we can access elsewhere
$GLOBALS['badgeos_leaderboard'] = new BadgeOS_Leaderboard();
