=== Frontend User Registration ===
Contributors: frontend-user-registration
Tags: registration, members, magic login, user fields
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Front-end registration and magic-link login for Members, with custom fields, admin approval, and Members-only access control.

== Description ==

Provides a front-end registration experience with:

* Auto-created /account page combining a Login tab (request a magic login link by email) and a Register tab.
* Custom registration fields (text, email, textarea, select, radio, checkbox) managed from a field builder under Members -> Registration.
* New registrations are assigned the Member role and can require admin approval before they are allowed to log in.
* Single-use, expiring magic login links emailed to members on approval or on request.
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

= How do I delegate member management? =

Promote a member to Members Manager using "Promote to Manager" on the Members page. Members Managers can approve, deny, and set members pending; edit member names and custom fields; manage registration fields and settings; and view Members-only content. They cannot manage plugins, themes, or other site settings, cannot change a member's role, and cannot change the default registration role (administrators only). Only administrators can demote a Members Manager, and nobody can demote themselves.

= Can I restrict content to members? =

Yes. Under Members -> Registration -> Settings, enable the "Members only" toggle for any public post type. Non-members are redirected to the account page.

== Changelog ==

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
