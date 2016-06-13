<?php
/**
 * fortunato Theme Customizer
 *
 * @package fortunato
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function fortunato_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
}
add_action( 'customize_register', 'fortunato_customize_register' );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function fortunato_customize_preview_js() {
	wp_enqueue_script( 'fortunato_customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20130508', true );
}
add_action( 'customize_preview_init', 'fortunato_customize_preview_js' );

/*
Enqueue Script for top buttons
*/
if ( ! function_exists( 'fortunato_customizer_controls' ) ){
	function fortunato_customizer_controls(){

		wp_register_script( 'fortunato_customizer_top_buttons', get_template_directory_uri() . '/js/theme-customizer-top-buttons.js', array( 'jquery' ), true  );
		wp_enqueue_script( 'fortunato_customizer_top_buttons' );

		wp_localize_script( 'fortunato_customizer_top_buttons', 'customBtns', array(
			'prodemo' => esc_html__( 'Demo PRO version', 'fortunato' ),
            'proget' => esc_html__( 'Get PRO Version', 'fortunato' )
		) );
	}
}//end if function_exists
add_action( 'customize_controls_enqueue_scripts', 'fortunato_customizer_controls' );