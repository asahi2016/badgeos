<?php


function badgeos_interactive_progress_map_license_page($slug = null) {

    $slug = basename($GLOBALS['badgeos_progress_map']->basename, '.php');

    $license 	= get_option( $slug . '-license_key' );
    $status 	= get_option( $slug . '-license_status' );
    $status_notify = ! empty( $status ) ? $status : 'inactive';
    
    ?>
    <div class="wrap">
    <h2><?php _e('Interactive Progress Map - License options'); ?></h2>
    <form method="post" action="options.php">

        <?php settings_fields('interactive_progress_map_license'); ?>

        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row" valign="top">
                    <?php _e('License Key'); ?>
                </th>
                <td>
                    <input id="<?php _e($slug); ?>-license_key" name="<?php _e($slug); ?>-license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
                    <span class="badgeos-license-status <?php _e($status_notify);?>">
                        <?php _e( 'License Status: <strong>'. ucfirst( $status_notify).'</strong>'); ?>
                    </span>
                    <br>
                    <p class="error" style="display: none;color: red;">License key field is required.</p>
                    <label class="description" for="<?php _e($slug); ?>-license_key" style="margin-top: 20px;float: left;"><?php _e('Enter your license key'); ?></label>
                </td>
            </tr>
            <?php if( false !== $license ) { ?>
                <?php if( $status !== false && $status == 'valid' ) { ?>
                <tr valign="top" class="activate_status">
                    <th scope="row" valign="top">
                        <?php _e('Activate License'); ?>
                    </th>
                    <td>
                        <span style="color:green;"><?php _e('active'); ?></span>
                    </td>
                </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
        </table>
        <?php
        wp_nonce_field( 'badgeos_settings_nonce', 'badgeos_settings_nonce' ); ?>
        <p><input type="submit" class="button-secondary" name="interactive_progress_map_license_activate" value="<?php _e('Activate License'); ?>"/>
        </p>
    </form>
    </div>
    <script type="application/javascript">
        (function($) {
            $(document).ready(function () {
                $('#<?php _e($slug); ?>-license_key').keypress(function () {
                    $('.activate_status').hide();
                    $('.badgeos-license-status').removeClass().addClass('badgeos-license-status inactive');
                    $('.badgeos-license-status').html('<?php _e( 'License Status: <strong>Inactive</strong>'); ?>');
                });
                $('#<?php _e($slug); ?>-license_key').focus(function () {
                    $('p.error').hide();
                });
                $('form').submit(function() {
                    var lkey = $('#<?php _e($slug); ?>-license_key');
                    if(!lkey.val()){
                        $('p.error').show();
                        return false;
                    }
                });
            });
        })(jQuery);
    </script>
    <?php
}

function interactive_progress_map_register_option() {

    $slug = basename($GLOBALS['badgeos_progress_map']->basename, '.php');
    // creates our settings in the options table
    register_setting('interactive_progress_map_license', $slug. '-license_key', 'interactive_progress_map_sanitize_license' );
}
add_action('admin_init', 'interactive_progress_map_register_option');


function interactive_progress_map_sanitize_license( $new ) {

    $slug = basename($GLOBALS['badgeos_progress_map']->basename, '.php');

    $old = get_option( $slug . '-license_key' );
    if( $old && $old != $new) {
        // new license has been entered, so must reactivate
        delete_option( $slug .'-license_status' );
        if(!empty($new)) {
            update_option($slug . '-license_status', 'inactive');
        }
    }else if (empty($old) && !empty($new)){
        //set as inactive status
        update_option($slug . '-license_status', 'inactive');
    }
    return $new;
}









