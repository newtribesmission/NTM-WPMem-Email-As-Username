<?php
/*
Plugin Name: Email as Username for WP-Members
Description: Requires WP-Members to be in use. Uses members' emails as their usernames. Removes the need to create a username (if wp-members is in use). Changes or removes appropriate items from forms, and adds the email address as the username. If WP-Members is no longer in use, there are plenty of plugins that offer this capability for WP's native registration and login functions
Author: New Tribes Mission (Stephen Narwold)
Plugin URI: https://github.com/newtribesmission/NTM-WPMem-Email-As-Username
Version: 1.2.2

    Copyright (C) 2014  New Tribes Mission

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

/**
 * Plugin init
 * @package ntmeau
 */
function ntmeau_plugin_init() {
	load_plugin_textdomain( 'ntmeau', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'init', 'ntmeau_plugin_init' );

/**
 * Login
 * If an email address is entered in the username box, then look up the matching username and authenticate as per normal, using that.
 *
 * @param WP_User $user
 * @param string $username
 * @param string $password
 *
 * @return WP_User|WP_Error
 */
function ntmeau_email_login_authenticate( $user, $username, $password ) {
	if ( is_a( $user, 'WP_User' ) ) {
		return $user;
	}

	if ( ! empty( $username ) ) {
		$username = str_replace( '&', '&amp;', stripslashes( $username ) );
		$user     = get_user_by( 'email', $username );
		if ( isset( $user, $user->user_login, $user->user_status ) && 0 == (int) $user->user_status ) {
			$username = $user->user_login;
		}
	}

	return wp_authenticate_username_password( null, $username, $password );
}

remove_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
add_filter( 'authenticate', 'ntmeau_email_login_authenticate', 20, 3 );

/**
 * Login Form Filter
 *
 * WP-Members Filter on the array of input fields for the login form
 *
 * @param array $inputs
 *
 * @return array
 */
function ntmeau_wpmem_login_username_to_email( $inputs ) {
	//change the name of the username field to "Email" on the login form
	if ( $inputs[0]['tag'] == 'log' ) {
		//$ntmeau_login_field_name is defined at the top of this file
		$inputs[0]['name'] = __( 'Email (Use your mission email address if applicable)', 'ntmeau' );
	}

	return $inputs;
}

add_filter( 'wpmem_inc_login_inputs', 'ntmeau_wpmem_login_username_to_email' );


/**
 * Password Reset
 *
 * WP-Members Filter on the arguments from a password reset request
 *
 * @param array $arr
 *
 * @return array
 */
function ntmeau_wpmem_pwd_reset_with_email_only( $arr ) {
	//Allow a password reset to be initiated without a username
	if ( ! isset( $arr['user'] ) || $arr['user'] == '' ) {
		//If no username is given, look up username by email
		if ( is_object( $user = get_user_by( 'email', $arr['email'] ) ) ) {
			//Set username to the correct user for the given email address
			$arr['user'] = $user->user_login;

			//If that didn't work, try looking up by username in case they entered that
		} elseif ( is_object( $user = get_user_by( 'login', $arr['email'] ) ) ) {
			//Set email to the correct user for the given username, and get the username into the user variable
			$arr['user']  = $user->user_login;
			$arr['email'] = $user->user_email;
		} else {
			//Last ditch effort, but if we get here, the email doesn't exist and the pass reset will fail
			$arr['user'] = $arr['email'];
		}
	}

	return $arr;
}

add_filter( 'wpmem_pwdreset_args', 'ntmeau_wpmem_pwd_reset_with_email_only' );

/**
 * Removes Username field from the "Reset Password" form
 *
 * WP-Members Filter on the array of input fields for the password reset form
 *
 * @param array $inputs
 *
 * @return array
 */
function ntmeau_wpmem_pwd_reset_form_remove_user_field( $inputs ) {
	//
	if ( $inputs[0]['tag'] == 'user' ) {
		$inputs[0] = $inputs[1];
		unset( $inputs[1] );
	}

	return $inputs;
}

add_filter( 'wpmem_inc_resetpassword_inputs', 'ntmeau_wpmem_pwd_reset_form_remove_user_field' );


/**
 * Edit the registration form
 *
 * WP-Members Filter on the array of input fields for the registration form
 *
 * @param array $rows
 *
 * @return array
 */
function ntmeau_e2u_wpmem_reg_form( $rows ) {
	if ( isset( $rows['username'] ) ) {
		//Remove username field from the registration form
		unset( $rows['username'] );
	}
	if ( is_user_logged_in() ) {
		//If they're updating their info, disable the email field
		$rows['user_email']['field'] = '<p class="noinput">' . $rows['user_email']['value'] . '</p>';
	}

	return $rows;
}

add_filter( 'wpmem_register_form_rows', 'ntmeau_e2u_wpmem_reg_form' );

/**
 * Fill in the missing username field with the user's email, and protect email address from change
 *
 * WP-Members Filter for registration and update data before WP-Mem's validation
 *
 * @param array $fields
 *
 * @return array
 */
function ntmeau_fill_user_with_email( $fields ) {
	if ( is_user_logged_in() ) {
		//They're updating their user. Since username is unchangeable, email needs to be as well.
		global $current_user;
		get_currentuserinfo();
		//Set their email address to the email address on file (unchanged)
		$fields['user_email'] = $current_user->user_email;
	}
	if ( $fields['username'] == '' || ! isset( $fields['username'] ) ) {
		//If the username isn't set, set it to the email address
		$fields['username'] = $fields['user_email'];
	}

	return $fields;
}

add_filter( 'wpmem_pre_validate_form', 'ntmeau_fill_user_with_email' );

/**
 * User Self-Delete
 *
 * Allow subscribers to delete their own users (this would be the only way to change their email address)
 */
function ntmeau_remove_logged_in_user() {
	// First, make sure user is logged in and only a subscriber (protects admins from deleting themselves in testing)
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) && $_POST['ntmeau-delete-account'] == 'DELETE MY ACCOUNT' ) {
		require_once( ABSPATH . 'wp-admin/includes/user.php' );
		$current_user = wp_get_current_user();
		wp_delete_user( $current_user->ID );

		// Once deleted, send them to the configured page
		$options = get_option( 'ntmeau_settings' );
		if ( $options['redirect_on_delete'] ) {
			$options['redirect_on_delete'] = apply_filters( 'ntmeau_redirect_on_delete', '/' );
		}
		wp_redirect( $options['redirect_on_delete'], 302 );
	}
}

add_action( 'init', 'ntmeau_remove_logged_in_user' );

/**
 * Don't allow subscriber-level users (non-admins) to access the admin area or admin bar
 */
function ntmeau_block_admin_pages_for_subscribers() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		// For anyone who can't edit posts:
		// Hide the admin bar
		show_admin_bar( false );
		if ( is_admin() ) {
			// If they're accessing an admin page, redirect to the profile page
			$options = get_option( 'ntmeau_settings' );
			if ( $options['redirect_on_admin_denial'] ) {
				$options['redirect_on_admin_denial'] = apply_filters( 'ntmeau_redirect_on_admin_denial', '/' );
			}
			wp_redirect( $options['redirect_on_admin_denial'], 302 );
		}
	}
}

add_action( 'init', 'ntmeau_block_admin_pages_for_subscribers', 0 );

/**
 * Add admin menu
 */
function ntmeau_add_admin_menu() {
	add_options_page(
		__( 'Email as Username for WP-Members', 'ntmeau' ),
		__( 'Email as Username for WP-Members', 'ntmeau' ),
		'manage_options',
		'email_as_username_for_wp-members',
		'ntmeau_options_page'
	);
}

add_action( 'admin_menu', 'ntmeau_add_admin_menu' );

/**
 * Settings init
 */
function ntmeau_settings_init() {
	register_setting( 'pluginPage', 'ntmeau_settings', 'ntmeau_sanitize_pluginPage' );

	add_settings_section(
		'ntmeau_pluginPage_section',
		__( 'Redirects', 'ntmeau' ),
		'ntmeau_settings_section_callback',
		'pluginPage'
	);

	add_settings_field(
		'ntmeau_text_field_0',
		__( 'Redirect on delete', 'ntmeau' ),
		'ntmeau_text_field_0_render',
		'pluginPage',
		'ntmeau_pluginPage_section'
	);

	add_settings_field(
		'ntmeau_text_field_1',
		__( 'Redirect on admin denial', 'ntmeau' ),
		'ntmeau_text_field_1_render',
		'pluginPage',
		'ntmeau_pluginPage_section'
	);


}

add_action( 'admin_init', 'ntmeau_settings_init' );

/**
 * Callback for Text field 0
 */
function ntmeau_text_field_0_render() {
	$options = get_option( 'ntmeau_settings' );
	if ( empty( $options['redirect_on_delete'] ) ) {
		$options['redirect_on_delete'] = '/';
	}

	?>
	<input id="redirect_on_delete" type="text" name="ntmeau_settings[redirect_on_delete]" value="<?php echo $options['redirect_on_delete']; ?>">
	<?php
}

/**
 * Callback for Text field 1
 */
function ntmeau_text_field_1_render() {
	$options = get_option( 'ntmeau_settings' );
	if ( empty( $options['redirect_on_admin_denial'] ) ) {
		$options['redirect_on_admin_denial'] = '/';
	}

	?>
	<input id="redirect_on_admin_denial" type="text" name="ntmeau_settings[redirect_on_admin_denial]" value="<?php echo $options['redirect_on_admin_denial']; ?>">
	<?php
}

/**
 * Settings Section Callback
 */
function ntmeau_settings_section_callback() {
	echo __( 'Where to send the users', 'ntmeau' );
}

/**
 * Settings Section Callback
 */
function ntmeau_sanitize_pluginPage( $in_array ) {
	if ( is_array( $in_array ) || is_object( $in_array ) ) {
		foreach( $in_array as $k => $v ) {
			$out_array[ $k ] = esc_url( $v );
		}
		return $out_array;
	} else {
		return esc_url( $in_array );
	}
}

/**
 * Options Page
 */
function ntmeau_options_page() {

	?>
	<form action='options.php' method='post'>

		<h2><?php _e( 'Email as Username for WP-Members', 'ntmeau' ); ?></h2>
		<?php

		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();

		?>
	</form>
<?php

}
