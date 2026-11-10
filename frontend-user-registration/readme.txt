=== Frontend User Registration ===
Contributors: frontend-user-registration
Tags: registration, members, magic login, user fields
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.22
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Front-end registration and magic-link login for Members, with custom fields, admin approval, and Members-only access control.

== Description ==

Provides a front-end registration experience with:

* Auto-created /account page combining a Login tab (request a magic login link by email) and a Register tab.
* Custom registration fields (text, email, textarea, select, radio, checkbox) managed from a field builder under Members -> Registration.
* New registrations are assigned the Member role and can require admin approval before they are allowed to log in.
* Single-use, expiring magic login links emailed to members on approval or on request.
* Members edit their own name and profile fields on the account page; no wp-admin access needed.
* Custom fields are editable on the profile screens, shown as columns on the Users list, and editable from the Members page.
* Members-only access control: restrict entire post types to logged-in Members.

== Installation ==

1. Upload the `frontend-user-registration` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Configure fields and settings under Members -> Registration.
4. The plugin creates an Account page with the `[fe_account_form]` shortcode.

== Frequently Asked Questions ==

= How do members log in? =

Members request a magic login link on the Login tab using their email address. The link is single-use and expires after the configured number of hours.

= What happens after registration? =

New accounts are created with the Members role. If admin approval is enabled, the account is pending until an administrator or Members Manager approves it from Members -> All Members; the member then receives their login link by email.

= Who are the new registration emails sent to? =

Two emails can be sent when someone registers:

* A confirmation goes to the email address the member registered with, telling them their account is awaiting approval. This is the "Pending notice" setting.
* A notification goes to the recipients chosen under "Notify who": either the site's Administration Email Address (set in Settings -> General), or all administrators and Members Managers. This is the "Admin notification" setting; when set to all administrators and Members Managers, one email is sent to each recipient.

Both can be turned on or off under Members -> Registration -> Settings -> Emails. After an administrator approves the account, the member is emailed their single-use login link (the "Magic link email" setting).

The same screen sets the "From name" and "From email" used by all of these emails, defaulting to "NZAHTA <noreply@nzahta.org.nz>". Only this plugin's mail uses them; the address domain must be authorised by your mail provider (Mailgun, SMTP relay) or the messages will be treated as spam.

= How do I delegate member management? =

Promote a member to Members Manager using "Promote to Manager" on the Members page. Members Managers can approve, deny, and set members pending; edit member names and custom fields; manage registration fields and settings; view Members-only content; and create, edit, publish and delete posts, pages and Resources, including categories and tags and the resource featured/reorder controls. They cannot manage plugins, themes, or other site settings, cannot change a member's role, and cannot change the default registration role (administrators only). Only administrators can demote a Members Manager, and nobody can demote themselves.

= Can I restrict content to members? =

Yes. Under Members -> Registration -> Settings, enable the "Members only" toggle for any public post type. Non-members are redirected to the account page.

== Changelog ==

= 1.1.22 =
* "Edit your profile" on the account page is a plain link again. The button styling added for it has been removed, restoring the original appearance of the form buttons.

= 1.1.21 =
* Members Managers no longer see the Contact Form 7 admin menu, and direct access to any wpcf7 admin page is blocked. They keep the Submissions screen, which the submissions plugin now registers as a top-level menu for non-administrators.

= 1.1.20 =
* Menu labels used to detect the Login and Join items are now filterable (`feur_nav_login_labels`, `feur_nav_join_labels`), so site-specific wording such as "Join NZAHTA" or "Members Area" can be matched without a code change. Matching is case-insensitive and substring-based, and both items still have to point at the account page.

= 1.1.19 =
* Classic menus now get the same treatment as block navigation for logged-in visitors: the Login item is removed and the Join item becomes "My Account" pointing at the account page.
* The account URL falls back to the default /account/ path when the stored page is missing, instead of silently using the homepage. Members -> Registration -> Settings now shows which URL is in use and warns when it is assumed.
* Admin bar: every profile link is redirected to the front-end editor in place, and duplicate "Edit profile" entries are removed. Visiting wp-admin/profile.php now opens the front-end editor rather than the dashboard.
* Members Managers can no longer reach the UX Blocks (`blocks`) admin screens, in addition to featured items. The list of hidden post types is filterable via `feur_manager_hidden_post_types`.
* Members Managers receive the `cf7s_view_submissions` capability used by the Contact Form 7 Submissions plugin.

= 1.1.18 =
* Block navigation now adapts for logged-in visitors: the "Join" registration button becomes "My Account" and links to the account page without the registration query string, and the "Login" link is removed. Logged-out menus are unchanged. Wording is filterable via `feur_nav_account_label`.

= 1.1.17 =
* Members Managers now get the separate page capabilities, so the Pages admin menu appears and pages can be created, published and edited.
* The wp-admin Profile screen is hidden and blocked for Members Managers, and the admin bar now points "Howdy" and "Edit profile" at the front-end profile editor instead of the blocked screen. Administrators are unaffected.
* "Edit your profile" on the account page is now a button.

= 1.1.16 =
* New front-end profile editor on the account page: members edit their first and last name plus any fields marked "editable in profile", without visiting wp-admin. The login email is shown read-only and the password is not editable, since login is by magic link.
* Members Managers no longer see the Comments, Tools or featured item (portfolio) admin menus, and direct access to those screens is blocked. Administrators, including users who also hold the Members Manager role, are unaffected.

= 1.1.15 =
* The "login link sent" and "awaiting approval" messages now prompt members to check their spam or junk folder if the email does not arrive, since mail can be delayed or filtered before the sending domain passes DKIM/DMARC checks.

= 1.1.14 =
* Logging out now returns to the account page and shows "You have been logged out." This covers the account page link, the admin bar and wp-admin, which previously fell through to the WordPress login form.
* A logout link that requests its own destination is still honoured.

= 1.1.13 =
* New "From name" and "From email" settings under Emails, applied to Frontend User Registration emails only. Defaults to "NZAHTA <noreply@nzahta.org.nz>". Leave the address blank to fall back to the WordPress default sender; an invalid address is rejected so login email cannot break silently.
* The sender is applied at filter priority 99, so an SMTP plugin's "force from" setting does not override it for these emails.

= 1.1.12 =
* Members Managers can now manage content as well as members: they receive the default post capabilities (create, edit, publish and delete posts and pages, upload files) and can manage categories and tags.
* Registration notification emails link to the account page, which now shows shortcuts for administrators and Members Managers: the number of members awaiting approval with a link to the Members screen, plus "Add resource" and "Manage resources" links when the Resources Library post type is present.

= 1.1.11 =
* Registration notification emails now link to the account page instead of Members -> All Members, so recipients who are logged out sign in normally rather than landing on the WordPress login form.
* Added a review shortcut to the logged-in account page for administrators and Members Managers: the number of members awaiting approval with a link to the Members screen.
* Logged-out visitors bounced out of wp-admin (a redirect_to pointing at wp-admin) are now sent to the account page login tab instead of the stock WordPress login form. Bare wp-login.php requests, password resets, logout, and reauthentication prompts are unaffected.

= 1.1.10 =
* Fix: the member role now displays as "Member" (singular) on the Users screen and role dropdown when an older install had stored the plural label "Members".

= 1.1.9 =
* New Members Manager role for delegated member management: approve, deny, and set pending; edit member names and custom fields; manage registration fields and settings; and view Members-only content - without full administrator access.
* Members Managers can promote a member to Members Manager. Only administrators can demote a Members Manager, and self-demotion is blocked.
* New "Notify who" setting under Emails: send new-registration notifications to the Administration Email Address, or to all administrators and Members Managers (one email per recipient, de-duplicated).
* Scoped member editor on the Members page for editing a member's name and custom fields; the login email remains read-only.
* The default registration role can only be changed by administrators.
* Fix: registration and login no longer fail silently (no confirmation, no email) when the account page or a page hosting the login form is served from a full-page cache with a stale nonce. The public register and login forms no longer depend on a cached nonce; the login form adds a per-email rate limit.
* The account page (and any page rendering a Frontend User Registration form) is now excluded from page caching, so confirmation and error messages always render.
* The honeypot field is hardened against browser/password-manager autofill and now shows a visible message instead of a silent dead-end.
* Existing cached copies of the account page are purged on update (Cloudways Breeze/Varnish when available).

= 1.1.7 =
* Added `feur_skip_access_page` and `feur_skip_post_type_restriction` filters so other plugins can allow specific requests through the Members-only redirects (used by Resources Library to show public Featured Resources with an in-page login prompt).

= 1.1.6 =
* Admin UI now lives under a new top-level "Members" menu (All Members + Registration) instead of being nested under Users.

= 1.1.5 =
* New "Member content page" setting (Member Access): choose an existing page or create one that is restricted to logged-in Members; non-members are redirected to the login page as before.

= 1.1.4 =
* Fixed Deny actually approving members: denied members now have a distinct Denied status and cannot log in.
* Denying a member now revokes their active sessions and invalidates any pending login link.
* Users -> Members now shows Pending / Approved / Denied status and lets admins change a member's status at any time.

= 1.1.3 =
* Administrators can now access Members-only content.
* Logged-out visitors redirected for restricted content see a "members only, please log in" message on the account page.

= 1.1.2 =
* The account page now shows a Member Details section (name, email, and custom fields) for logged-in Members.

= 1.1.1 =
* The account page tab can now be set via ?tab=reg or ?tab=login (e.g. a "Join" button linking to ?tab=reg).

= 1.1.0 =
* Added required First Name and Last Name fields to the registration form; display name is now "First Last".
* Removed the username field; logins are generated from the member's email address.
* Renamed the Members role label to "Member".
* Users -> Members now shows member field values as read-only text.

= 1.0.1 =
* Renamed the Users -> User Data page to Users -> Members and limited it to the Members role.

= 1.0.0 =
* Initial release.
