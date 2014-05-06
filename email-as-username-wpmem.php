<?php
/*
Plugin Name: Email as Username for WP-Members
Description: Requires WP-Members to be in use. Uses members' emails as their usernames. Removes the need to create a username (if wp-members is in use). Changes or removes appropriate items from forms, and adds the email address as the username. If WP-Members is no longer in use, there are plenty of plugins that offer this capability for WP's native registration and login functions
Author: New Tribes Mission (Stephen Narwold)
Plugin URI: https://github.com/newtribesmission/NTM-WPMem-Email-As-Username
Version: 1.0

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

/*****************************/
/****       Login         ****/
/*****************************/
//No real crucial changes here, since it's all done at registration. This is just to remind them to use their email address to log in
function ntmeau_wpmem_login_username_to_email($inputs) {
	//change the name of the username field to "Email" on the login form
	if ($inputs[0]['tag'] == 'log') {
		$inputs[0]['name'] = 'Email (Use your mission email address if applicable)';
	}
	return $inputs;
}
//WP-Members Filter on the array of input fields for the login form
add_filter('wpmem_inc_login_inputs', 'ntmeau_wpmem_login_username_to_email');


/*****************************/
/****  Password Reset     ****/
/*****************************/
function ntmeau_wpmem_pwd_reset_with_email_only($arr) {
	//Allow a password reset to be initiated without a username
	if (!isset($arr['user']) || $arr['user'] == '') {
		//If no username is given, assume the username is the same as the email
		$arr['user'] = $arr['email'];
	}
	return $arr;
}
//WP-Members Filter on the arguments from a password reset request
add_filter('wpmem_pwdreset_args', 'ntmeau_wpmem_pwd_reset_with_email_only');

function ntmeau_wpmem_pwd_reset_form_remove_user_field($inputs) {
	//Removes Username field from the "Reset Password" form
	if ($inputs[0]['tag'] == 'user') {
		$inputs[0] = $inputs[1];
		unset($inputs[1]);
	}
	return $inputs;
}
//WP-Members Filter on the array of input fields for the password reset form
add_filter('wpmem_inc_resetpassword_inputs', 'ntmeau_wpmem_pwd_reset_form_remove_user_field');


/*****************************/
/**** Registration/Update ****/
/*****************************/
function ntmeau_e2u_wpmem_reg_form($rows, $toggle) {
	//Edit the registration form
	if (isset($rows['username'])) {
		//Remove username field from the registration form
		unset($rows['username']);
	}
	if (is_user_logged_in()) {
		//If they're updating their info, disable the email field
		$rows['user_email']['field'] = '<p class="noinput">' . $rows['user_email']['value'] . '</p>';
	}
	return $rows;
}
//WP-Members Filter on the array of input fields for the registration form
add_filter('wpmem_register_form_rows', 'ntmeau_e2u_wpmem_reg_form');


function ntmeau_fill_user_with_email($fields) { 
	//Fill in the missing username field with the user's email, and protect email address from change
	
	if (is_user_logged_in()) {
		//They're updating their user. Since username is unchangeable, email needs to be as well.
		global $current_user;
		get_currentuserinfo();
		//Set their email address to the email address on file (unchanged)
		$fields['user_email'] =  $current_user->user_email;
	}
	if ($fields['username'] == '' || !isset($fields['username'])) {
		//If the username isn't set, set it to the email address
		$fields['username'] = $fields['user_email'];
	}
	return $fields;
}
//WP-Members Filter for registration and update data before WP-Mem's validation
add_filter( 'wpmem_pre_validate_form', 'ntmeau_fill_user_with_email' );


/*****************************/
/**** User Self-Delete    ****/
/*****************************/
//Allow subscribers to delete their own users (this would be the only way to change their email address)
function ntmeau_remove_logged_in_user() { 
	//First, make sure user is logged in and only a subscriber (protects admins from deleting themselves in testing)
	if (is_user_logged_in() && !current_user_can('edit_posts') && $_POST['ntmeau-delete-account'] == 'DELETE MY ACCOUNT') {
		require_once(ABSPATH.'wp-admin/includes/user.php' );
		$current_user = wp_get_current_user();
		wp_delete_user( $current_user->ID );
		wp_redirect('/missionary-services',302);
		//Once deleted, send them to the Missionary Services homepage
	}
}
add_action('init', 'ntmeau_remove_logged_in_user');

function ntmeau_block_admin_pages_for_subscribers() {
	//Don't allow subscriber-level users (non-admins) to access the admin area or admin bar
	if (!current_user_can('edit_posts')) {
		//For anyone who can't edit posts:
		//Hide the admin bar
		show_admin_bar(false);
		if (is_admin()) {
			//If they're accessing an admin page, redirect to the profile page
			wp_redirect("/missionary-services/profile",302);
		}
	}
}
add_action('init','ntmeau_block_admin_pages_for_subscribers',0);

?>
