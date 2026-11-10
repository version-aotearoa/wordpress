=== Contact Form 7 Submissions ===
Contributors: contact-form-7-submissions
Tags: contact form 7, submissions, entries, forms, csv
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Requires Plugins: contact-form-7
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Saves Contact Form 7 submissions to the database with an admin list, detail view and CSV export.

== Description ==

Extends Contact Form 7 to store a copy of every successfully sent submission in a custom database table, so entries are never lost to the email inbox.

* Saves successful submissions (sent mail) automatically.
* Admin list under Contact -> Submissions with a form filter, search and pagination.
* Single-submission detail view showing every field value plus IP address, page and user agent.
* Delete individual or multiple submissions.
* One-click CSV export of all data for the selected form (or all forms).

Requires the Contact Form 7 plugin to be installed and active.

== Installation ==

1. Install and activate Contact Form 7.
2. Upload the `cf7-submissions-va` folder to `/wp-content/plugins/`.
3. Activate the plugin through the Plugins screen.
4. Submissions are saved automatically; view them under Contact -> Submissions.

== Frequently Asked Questions ==

= Which submissions are saved? =

Only submissions whose mail was sent successfully are recorded. Validation failures and spam are not stored.

= Can I export submissions? =

Yes. The Submissions screen has an "Export CSV" button that exports all data for the currently selected form (or every form when "All forms" is selected).

= Where is the data stored? =

In a dedicated database table (`cf7s_submissions`). Deleting the plugin removes the table and its data.

== Changelog ==

= 1.1.4 =
* The submissions summary card now appears on the main WordPress dashboard as well as the Contact Form 7 dashboard, so roles that cannot reach the CF7 dashboard still see it.
* Submissions is registered as a top-level menu for users who are not administrators, instead of a submenu of the Contact Form 7 menu that may be hidden from them. Administrators keep it under Contact Form 7.

= 1.1.3 =
* Submissions now require the dedicated `cf7s_view_submissions` capability instead of site-administration rights, so other roles can be given access without plugin, theme or settings privileges. Administrators are granted it automatically on update.
* The Submissions page falls back to its own top-level menu when the Contact Form 7 menu is not available to the current user, instead of being unreachable.

= 1.1.2 =
* The Submissions card on the Contact Form 7 Dashboard now appears at the top of the left column by default.

= 1.1.1 =
* New "Submissions" card on the Contact Form 7 Dashboard (Contact -> Dashboard) showing total submissions, submissions in the last 7 days, the latest submission and a link to the Submissions screen.

= 1.1.0 =
* Renamed the plugin folder and main file to `cf7-submissions-va` to remove the collision with an unrelated plugin on WordPress.org, and removed the "View details" link that pointed at that other plugin.
* Fix: the Submissions menu item is visible again on Contact Form 7 6.2+, whose top-level admin menu slug changed from `wpcf7` to `wpcf7-dashboard`.

= 1.0.1 =
* Fixed a fatal error on form submission caused by calling WPCF7_Submission::get_meta() without the required argument.

= 1.0.0 =
* Initial release.
