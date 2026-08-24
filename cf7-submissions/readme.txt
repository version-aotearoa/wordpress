=== Contact Form 7 Submissions ===
Contributors: contact-form-7-submissions
Tags: contact form 7, submissions, entries, forms, csv
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Requires Plugins: contact-form-7
Stable tag: 1.0.1
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
2. Upload the `cf7-submissions` folder to `/wp-content/plugins/`.
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

= 1.0.1 =
* Fixed a fatal error on form submission caused by calling WPCF7_Submission::get_meta() without the required argument.

= 1.0.0 =
* Initial release.
