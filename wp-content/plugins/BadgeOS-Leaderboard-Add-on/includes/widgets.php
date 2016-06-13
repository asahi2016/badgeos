<?php
class BadgeOS_Leaderboard_Widget extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @var array
	 */
	public $defaults = array();

	/**
	 * Constructor. Set the default widget options and create widget.
	 */
	public function __construct() {

		$this->defaults = array(
			'title'         => '',
			'name'          => '',
			'ID'            => '',
			'user_count'    => '',
			'orderby'       => '',
			'order'         => '',
			'show_avatars'  => '',
			'link_profiles' => '',
		);

		$widget_ops = array(
			'classname'    =>  'badgeos-leaderboard-widget',
			'description'  =>  __( 'Displays a BadgeOS Leaderboard.', 'badgeos-leaderboard-widget' )
		);

		parent::__construct( 'badgeos-leaderboard-widget', __( 'BadgeOS Leaderboard Widget', 'badgeos-leaderboard-widget' ), $widget_ops );
	}

	/**
	 * Echo the widget content.
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	public function widget( $args, $instance ) {
		extract( $args );

		echo $before_widget;

		if ( ! empty( $instance['title'] ) )
			echo $before_title . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $after_title;

		echo badgeos_leaderboard_display_leaderboard( array(
			'leaderboard_id' => $instance['ID'],
			'user_count'     => $instance['user_count'],
			'orderby'        => $instance['orderby'],
			'show_avatars'   => $instance['show_avatars'],
			'link_profiles'  => $instance['link_profiles']
		) );

		echo $after_widget;
	}

	/**
	 * Update a particular instance.
 	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved / updated.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form().
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']         = esc_html( $new_instance['title'] );
		$instance['ID']            = absint( $new_instance['ID'] );
		$instance['user_count']    = absint( $new_instance['user_count'] );
		$instance['orderby']       = esc_html( $new_instance['orderby'] );
		$instance['show_avatars']  = ! empty( $new_instance['show_avatars'] ) ? true : false;
		$instance['link_profiles'] = ! empty( $new_instance['link_profiles'] ) ? true : false;

		return $instance;
	}

	/**
	 * Echo the settings update form.
	 *
	 * @param array $instance Current settings
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
		wp_enqueue_script( 'badgeos-leaderboard-admin' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Widget Title: ', 'badgeos-leaderboard-widget' ) ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'ID' ) ); ?>"><?php _e( 'Leaderboard: ', 'badgeos-leaderboard-widget' ) ?></label>
			<select class="widefat leaderboard-select" id="<?php echo esc_attr( $this->get_field_id( 'ID' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'ID' ) ); ?>">
			<option></option>
			<?php
				// Get our leaderboards posts
				$badgeos_leaderboards = badgeos_leaderboard_get_leaderboards();
				foreach ( $badgeos_leaderboards as $leaderboard ) { ?>
					<option <?php selected( $instance['ID'], $leaderboard->ID ); ?> value="<?php echo $leaderboard->ID; ?>"><?php echo $leaderboard->post_title; ?></option>
				<?php }
			?>
		</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'user_count' ) ); ?>"><?php _e( 'User Limit: ', 'badgeos-leaderboard-widget' ) ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'user_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'user_count' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['user_count'] ); ?>" />
			<span class="desc description"><?php _e( 'Will use leaderboard default if left blank. Cannot exceed leaderboard limit.', 'badgeos-leaderboard' ); ?></span>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php _e( 'Rank Metric: ', 'badgeos-leaderboard-widget' ) ?></label>
			<select class="widefat leaderboard-rank-metric" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>">
				<option></option>
				<?php
					$metrics = badgeos_leaderboard_get_metrics();
					foreach ($metrics as $metric => $title ) {
						?><option value="<?php echo $metric; ?>" <?php selected( $instance['orderby'], $metric ); ?> ><?php echo $title ?></option><?php
					}
				?>
			</select>
			<span class="desc description"><?php _e( 'Will use leaderboard default sort order if left blank.', 'badgeos-leaderboard' ); ?></span>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_avatars' ) ); ?>"><?php _e( 'Display User Avatar: ', 'badgeos-leaderboard-widget' ) ?></label>
			<input id="<?php echo $this->get_field_id('show_avatars'); ?>" name="<?php echo $this->get_field_name('show_avatars'); ?>" type="checkbox" <?php checked( $instance['show_avatars'], true ); ?>  value="true" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link_profiles' ) ); ?>"><?php _e( 'Link to BuddyPress Profile: ', 'badgeos-leaderboard-widget' ) ?></label>
			<input id="<?php echo $this->get_field_id('link_profiles'); ?>" name="<?php echo $this->get_field_name('link_profiles'); ?>" type="checkbox" <?php checked( $instance['link_profiles'], true ); ?>  value="true" />
		</p>
		<?php
	}
}

/**
 * Register the widget.
 *
 * @since 1.0.0
 */
function register_badgeos_leaderboard_widget() {
	register_widget( 'BadgeOS_Leaderboard_Widget' );
}
add_action( 'widgets_init', 'register_badgeos_leaderboard_widget' );
