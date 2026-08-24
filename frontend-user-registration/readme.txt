=== Frontend User Registration ===
Contributors: frontend-user-registration
Tags: registration, members, magic login, user fields
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Front-end registration and magic-link login for Members, with custom fields, admin approval, and Members-only access control.

== Description ==

Provides a front-end registration experience with:

* Auto-created /account page combining a Login tab (request a magic login link by email) and a Register tab.
* Custom registration fields (text, email, textarea, select, radio, checkbox) managed from a field builder under Users -> Registration.
* New registrations are assigned the Member role and can require admin approval before they are allowed to log in.
* Single-use, expiring magic login links emailed to members on approval or on request.
* Custom fields are editable on the profile screens, shown as columns on the Users list, and editable from the Users -> Members page.
* Members-only access control: restrict entire post types to logged-in Members.

== Installation ==

1. Upload the `frontend-user-registration` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Configure fields and settings under Users -> Registration.
4. The plugin creates an Account page with the `[fe_account_form]` shortcode.

== Frequently Asked Questions ==

= How do members log in? =

Members request a magic login link on the Login tab using their email address. The link is single-use and expires after the configured number of hours.

= What happens after registration? =

New accounts are created with the Members role. If admin approval is enabled, the account is pending until an administrator approves it from Users -> Members; the member then receives their login link by email.

= Can I restrict content to members? =

Yes. Under Users -> Registration -> Settings, enable the "Members only" toggle for any public post type. Non-members are redirected to the account page.

== Changelog ==

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
