=== Email as Username for WP-Members ===
Contributors: stevish
Donate link: http://ntm.org/give
Tags: email as username, users
Requires at least: 3.5
Tested up to: 3.9
Stable tag: 1.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Requires WP-Members to be in use. Uses members' emails as their usernames. Removes the need to create a username (if wp-members is in use).

== Description ==

Requires WP-Members to be in use. Uses members' emails as their usernames. Removes the need to create a username (if wp-members is in use). Changes or removes appropriate items from forms, and adds the email address as the username. If WP-Members is no longer in use, there are plenty of plugins that offer this capability for WP's native registration and login functions

**How it works:**
This plugin doesn't do much of anything with the login form. It deals with the WP-Members registration form, removing username as a field to fill out and filling it automatically with the email entered. For backward compatibility (for users that were created before this plugin was installed), the plugin will try looking up the username by email if they log in with that instead of their username.

The plugin also does some funky things with subscribers:

1. It doesn't allow them access to the dashboard. This is to keep them from changing their email address (and un-syncing their username/email)
1. It turns off the admin bar for them. Let's face it, they don't need that anyways.

Props to [Beau Lebens](http://dentedreality.com.au/) for his [Email Login](http://dentedreality.com.au/projects/wp-plugin-email-login/) plugin. I stole his function (from version 4.6.3) to help with backward compatibility.

== Installation ==

1. Make sure the WP-Members plugin is installed and activated
1. Turn the WordPress native Registration off (users can still register through WP-Members)
1. Upload the `/email-as-username-wpmem` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Will this work without WP Members activated? =

I highly doubt it. This plugin does not attempt to control the native WordPress registration

== Changelog ==

= 1.2 =
* Add ability to login with email even if username is different (Thanks to [Beau Lebens](http://dentedreality.com.au/))

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 1.2 =
New version allows for backward compatibility... if username and email don't match, user can still log in with their emails.

= 1.0 =
Please update to the newest version