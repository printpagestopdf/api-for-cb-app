=== API Extension to support CB App ===
Contributors: theripper
Tags: videoconference, lectures, conferences, meetings
Requires at least: 4.7
Tested up to: 6.4.2
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
This plugin extends the functionality of the CommonsBooking API.  With this plugin it is possible to log in as a user and make bookings from CB App.

== Description ==

# API Extension to support CB App (App for CommonsBooking)

[CB App](https://printpagestopdf.github.io/cb_app/) is a muliplattform App written in Flutter. It is working as an alternative Frontend for a CommonsBooking website. CB App is a stand-alone development independent of [CommonsBooking](https://commonsbooking.org/), but requires a [CommonsBooking](https://wordpress.org/plugins/commonsbooking/) installation as a prerequisite

This plugin extends the functionality of the CommonsBooking API.  With this plugin it is possible to log in as a user and make bookings from CB App. Without this  plugin the CB App has only read-only access to the CommonsBooking site (and only if the site has enabled the CommonsAPI ).

Additionally this plugin supplies:

- choosing between different configured maps
- restrict App Login to Roles
- serving images to the web based App Version (if CORS is used on the site)
- restrict bookings for users (for CommonsBooking versions that don't supply this in Core)

= Demo =
None

== Installation ==

* Ensure that the WP plugin CommonsBooking is installed
* Install and activate this plugin by one of usual wordpress methods
* Configure settings if necessary (find settings under CommonsBooking Menu item "App API settings")

== Screenshots ==

1. Settings screen.

== Changelog ==
= 0.5.0 =

* Initial release.

== Upgrade Notice ==
None

== License ==
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

== Version history ==
0.5.0 - Initial release.