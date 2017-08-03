=== Eventbrite for Pods ===
Contributors: sc0ttkclark
Donate link: http://scottkclark.com/
Tags: eventbrite, events, ticketing, pods, pods cms, pods ui
Requires at least: 3.2
Tested up to: 3.3
Stable tag: 1.1

Eventbrite Event Registration on your site and API syncing to show Attendees on site.

== Description ==

**OFFICIAL SUPPORT** - Eventbrite for Pods - Support Forums: http://scottkclark.com/forums/eventbrite-for-pods/

This plugin requires the Pods CMS Framework plugin to be installed and activated - http://wordpress.org/extend/plugins/pods/

All you do is install the plugin, go into WP Admin and setup your API settings, add the events (referencing their corresponding Event IDs on Eventbrite) and sync up. Then add the shortcode

== Frequently Asked Questions ==

**What is a Pods-based plugin?**

Pods-based plugins are plugins that require Pods CMS Framework - http://wordpress.org/extend/plugins/pods/

They utilize Pods for managing / processing content, and serving the content through Pod Pages in most cases. They install themself during activation based on the Pods Package standard.

Eventually this plugin will not require the Pods framework, as the framework will have a plugin framework built for this kind of use that would be bundled without a Pods admin area.

== Changelog ==

= 1.1 =
* Code cleanup
* Pods 1.12 and WP 3.3 compatibility fixes
* More bug fixes!

= 1.0.5 =
* Bug fixes, requires latest Pods (1.11+) and WP (3.2+) to run the best - so ensure you stay updated

= 1.0.1 =
* Bug fix, some events weren't being synced properly

= 1.0 =
* First official release to the public as a plugin

== Upgrade Notice ==

= 1.0.5 =
* Bug fixes, requires latest Pods (1.11+) and WP (3.2+) to run the best - so ensure you stay updated

= 1.0.1 =
* Bug fix, some events weren't being synced properly

= 1.0 =
You aren't using the real plugin, upgrade and you enjoy what you originally downloaded this for!

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Official Support ==

Eventbrite for Pods - Support Forums: http://scottkclark.com/forums/eventbrite-for-pods/

== About the Plugin Author ==

Scott Kingsley Clark from SKC Development -- Scott specializes in WordPress and Pods CMS Framework development using PHP, MySQL, and AJAX. Scott is also a developer on the Pods CMS Framework plugin

== Features ==

= Administration =
* Create and Manage Events
* View Attendees by Event
* View Tickets by Event
* Sync Event / Attendee / Ticket data from Eventbrite

= Event Ticketing Shortcodes =
* iFrame Shortcodes - showing the Eventbrite Event page of your Event allowing for checkout within your site
* * `[eventbrite event_id="123456"]` (where 123456 is the Eventbrite Event ID)
* * `[eventbrite id="X"]` (where the id is the Eventbrite for Pods Event ID - the one reference internally within your site's DB)
* * `[eventbrite name="My Event"]` (where the name is the Eventbrite for Pods Event Name - the one saved internally within your site's DB)
* On-page Shortcodes - listing tickets and linking to their respective Eventbrite purchase URLs
* * `[eventbrite iframe="0" event_id="123456"]` (where 123456 is the Eventbrite Event ID)
* * `[eventbrite iframe="0" id="X"]` (where the id is the Eventbrite for Pods Event ID - the one reference internally within your site's DB)
* * `[eventbrite iframe="0" name="My Event"]` (where the name is the Eventbrite for Pods Event Name - the one saved internally within your site's DB)
* Attendee Shortcodes - outputting an HTML list of Attendees for your event
* * `[eventbrite attendees="1" event_id="123456"]` (where 123456 is the Eventbrite Event ID)
* * `[eventbrite attendees="1" id="X"]` (where the id is the Eventbrite for Pods Event ID - the one reference internally within your site's DB)
* * `[eventbrite attendees="1" name="My Event"]` (where the name is the Eventbrite for Pods Event Name - the one saved internally within your site's DB)