# API Extension to support CB App (App for CommonsBooking)

[CB App](https://printpagestopdf.github.io/cb_app/en/) is a muliplattform App written in Flutter. It is working as an alternative Frontend for a [CommonsBooking](https://commonsbooking.org/) website. If you want to know more about the App visit the [Website](https://printpagestopdf.github.io/cb_app/en/) or the [GitHub page](https://github.com/printpagestopdf/cb_app).

[CB App](https://printpagestopdf.github.io/cb_app/en/) is a stand-alone development independent of [CommonsBooking](https://commonsbooking.org/), but requires a [CommonsBooking](https://wordpress.org/plugins/commonsbooking/) installation as a prerequisite

This plugin extends the functionality of the CommonsBooking API.  If it is installed on the website, it is possible to log in and make bookings via the CB App.

Without this  plugin installed the CB App has only read-only access to the CommonsBooking site (and only if the site has enabled the CommonsAPI ).

Additionally this plugin supplies:

- choosing between different configured maps
- restrict App Login to Roles
- serving images to the web based App Version (if CORS is used on the site)
- restrict bookings for users (for CommonsBooking versions that don't supply this in Core)

#### **Installation**

- Ensure that the WP plugin CommonsBooking is installed
- Install the plugin either from the [Wordpress plugin directory](https://wordpress.org/plugins/api-for-cb-app/) or latest Version from here.
- Activate this plugin
- Configure settings if necessary (find settings under CommonsBooking Menu item "App API settings")
