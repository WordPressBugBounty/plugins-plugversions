=== PlugVersions - Easily roll back to previous versions of your plugins. ===

Contributors:      giuse
Requires at least: 4.6
Tested up to:      6.8
Requires PHP:      7.4
Stable tag:        0.1.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Tags:              restore, update, backup, rollback, plugin versions

Retains up to three versions when you update a plugin. It works with premium and custom plugins too.


== Description ==

PlugVersions lets you retain up to three versions each time you update a plugin, including premium and custom plugins.

If a new version doesn’t work as expected, you can easily roll back by selecting a previous version with a single click. It works similarly to post revisions, but for plugins.

While <a href="https://wordpress.org/plugins/wp-rollback/">WP Rollback</a> is a great solution for plugins hosted on the WordPress repository, PlugVersions supports all plugins, including those not available on the repo.

This broader compatibility is currently the key difference between PlugVersions and other rollback plugins.

Additional features, such as version previews, are on the roadmap.



== How to roll back to a previous version of a plugin ==
* Install and activate PlugVersions.
* Go to the Plugins page in your WordPress dashboard.
* For any plugin that has been updated, you'll see a "Revisions" action link. Hover over it to view the list of previously stored versions.
* Click on the version you want to restore.
* That’s it. Your plugin is rolled back!

There are no settings to configure.
This free version allows you to retain up to three previous plugin versions.
You’ll find them listed under the "Revisions" link on the Plugins page.



== Changelog ==

= 0.1.0 =
* Added: Replaced the PlugVersions backup filter to handle all updates, including manual plugin updates. A big thank you to <a href="https://github.com/vincenzocasu">Vincenzo Casu</a> for providing the code!

= 0.0.8 =
* Fix: Vulnerability when a new version is restored. Thanks to Arkadiusz Hydzik for finding the vulnerability.
* Fix: Revisions list not working in the page of plugins.

= 0.0.7 =
* Fix: PHP warning

= 0.0.6 =
* Enhanced: Replaced unzipped versions with zipped versions

= 0.0.5 =
* Fix: Fatal error on plugin deletion

= 0.0.4 =
* Fix: Plugin updates counter in the admin top bar on frontend

= 0.0.3 =
* Fixed: not possible to delete the plugin from the page of plugins

= 0.0.2 =
* ixed: plugin old versions were considered in the update notifications

= 0.0.1 =
* Initial release



== Screenshots ==

1. How to restore the previous version of a plugin
