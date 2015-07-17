<?php

/*
Plugin Name: ACF: MadMimi Audience List
Plugin URI: http://danielpataki.com
Description: Adds the ability to select audience lists from a dropdown, pulled straight from MadMimi via the API.
Version: 1.0.1
Author: Daniel Pataki
Author URI: http://danielpataki.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


include( 'vendor/madmimi-php/MadMimi.class.php' );

function mal_madmimi_username() {
	if( defined( 'MAL_MADMIMI_USERNAME' ) ) {
		return MAL_MADMIMI_USERNAME;
	}
	else {
		$access = get_option( 'mal_madmimi_access' );
		if( !empty( $access['username'] ) ) {
			return $access['username'];
		}
	}

	return false;

}

function mal_madmimi_api_key() {
	if( defined( 'MAL_MADMIMI_API_KEY' ) ) {
		return MAL_MADMIMI_API_KEY;
	}
	else {
		$access = get_option( 'mal_madmimi_access' );
		if( !empty( $access['api_key'] ) ) {
			return $access['api_key'];
		}
	}

	return false;
}


function mal_authenticate_credentials() {
	$authentication = get_transient( 'mal_authenticate_credentials' );
	if( empty( $authentication ) ) {
		$mimi = new MadMimi(mal_madmimi_username(), mal_madmimi_api_key() );
		$check = $mimi->Lists();
		$authentication = false;
		if( $check != 'Unable to authenticate' ) {
			$authentication = true;
		}

		set_transient( 'mal_authenticate_credentials', $authentication, HOUR_IN_SECONDS );
	}

	return $authentication;
}


add_action('admin_menu', 'mal_settings_page', 99);
/**
 * Add Setting Page
 *
 * Adds the settings page which contains the fields for the username
 * and API key. Also initializes the settings that hold these
 * values.
 *
 * @author Daniel Pataki
 * @since 1.0.0
 *
 */
function mal_settings_page() {
	$post_type = ( post_type_exists('acf-field-group') ) ? 'acf-field-group' : 'acf';
    add_submenu_page( 'edit.php?post_type=' . $post_type, _x( 'MadMimi Settings', 'In the title tag of the page', 'acf-madmimi_audience_list'  ), _x( 'MadMimi Settings', 'Menu title',  'acf-madmimi_audience_list' ), 'manage_options', 'acf-madmimi_audience_list-settings', 'mal_settings_page_content');

    add_action( 'admin_init', 'mal_register_settings' );

}


/**
 * Register Settings
 *
 * Registers plugin-wide settings, we use this for the username
 * and the API key
 *
 * @author Daniel Pataki
 * @since 1.0.0
 *
 */
function mal_register_settings() {
	register_setting( 'mal_settings', 'mal_madmimi_access' );
}


 /**
 * Settings Page Content
 *
 * The UI for the settings page. It contains the form, as well as
 * a quick check to make sure the given credentials work.
 *
 * @author Daniel Pataki
 * @since 1.0.0
 *
 */
function mal_settings_page_content() {

	delete_transient( 'mal_audience_lists' );
	delete_transient( 'mal_authenticate_credentials' );

	if( !empty( $_POST ) ) {
		update_option( 'mal_madmimi_access', $_POST['mal_madmimi_access'] );
	}
?>
<div class="wrap">
<h2><?php _e( 'MadMimi Settings', 'acf-madmimi_audience_list' ) ?></h2>

<?php
if( defined( 'MAL_MADMIMI_USERNAME' ) && defined( 'MAL_MADMIMI_API_KEY' ) ) {
	_e( 'Your MadMimi username and API key have been defined within constants. This is usually done in the wp-config file and is the better option. If you would like to store your username and API Key in the database remove the definitions and come back here to fill out a form.', 'acf-madmimi_audience_list' );
}
else {
?>
<form method="post">

	<?php
	_e( 'You can also set your username and API key by defining the MAL_MADMIMI_USERNAME and MAL_MADMIMI_API_KEY constants in the wp-config.php file for added safety.', 'acf-madmimi_audience_list' );
 	?>
	<?php $access = get_option( 'mal_madmimi_access' ); ?>
    <?php settings_fields( 'mal_twitter_settings' ); ?>
    <?php do_settings_sections( 'mal_twitter_settings' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row"><?php _e( 'MadMimi Account Username', 'acf-madmimi_audience_list' ) ?></th>
        <td><input type="text" name="mal_madmimi_access[username]" value="<?php echo esc_attr( $access['username'] ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row"><?php _e( 'MadMimi API Key', 'acf-madmimi_audience_list' ) ?></th>
		<td><input type="text" name="mal_madmimi_access[api_key]" value="<?php echo esc_attr( $access['api_key'] ); ?>" /></td>
        </tr>

    </table>



    <?php submit_button(); ?>

</form>
<?php } ?>

</div>
<?php
}


// 1. set text domain
// Reference: https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
load_plugin_textdomain( 'acf-madmimi_audience_list', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );


// 2. Include field type for ACF5
// $version = 5 and can be ignored until ACF6 exists
function include_field_types_madmimi_audience_list( $version ) {
	if( mal_authenticate_credentials() ) {
		include_once('acf-madmimi_audience_list-v5.php');
	}
}

add_action('acf/include_field_types', 'include_field_types_madmimi_audience_list');




// 3. Include field type for ACF4
function register_fields_madmimi_audience_list() {
	if( mal_authenticate_credentials() ) {
		include_once('acf-madmimi_audience_list-v4.php');
	}
}

add_action('acf/register_fields', 'register_fields_madmimi_audience_list');




?>
