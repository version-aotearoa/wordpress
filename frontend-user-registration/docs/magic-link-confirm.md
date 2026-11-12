# Magic links are two clicks, and the first one must not consume

**Status:** introduced in 1.2.0. This note exists so the GET handler is not
"simplified" back into a single click.

## The problem it solves

Magic links are bearer tokens: anyone who opens the URL gets the account. The
token is single use, so the *first* request wins.

Email security gateways (Microsoft Defender Safe Links, Proofpoint, Barracuda,
and some antivirus clients) **prefetch every URL in an email** to check it.
That request consumed the token, and the member then arrived at a link that had
already been used — "invalid or expired" — with no way to tell why.

## The design

1. The email links to the account page with `fe_auth`, `fe_token` (and optional
   `redirect_to`):
   `/account/?fe_auth=12&fe_token=abc…`
2. That GET renders a **Confirm sign in** panel. It calls
   `FEUR_Magic_Link::check()`, which validates the token but **does not consume
   it**.
3. Pressing **Sign in** submits a POST with `fe_confirm=1` and the same values.
4. Only that POST consumes the token (`revoke()`), sets the auth cookie and
   redirects.

## Why POST rather than a link

A second GET would still be followable by a scanner that renders the page: the
refresh would need to put the token in a clickable `href`. Scanners do not
submit forms, so POST is the reliable signal that a person acted.

## Guardrails

- **Never consume a token on GET.** Not in `template_redirect`, not in
  `login_init`, not in a REST route.
- `/wp-login.php?fe_auth=…&fe_token=…` (older emails, bookmarks) redirects to
  the confirmation page without consuming: `maybe_redirect_to_confirm()`.
- No nonce on the confirm form. WordPress nonces expire in 12–24 hours while
  links last up to 48, so a nonce would reject members opening email the next
  day. The token is the credential: 32 random characters, stored hashed.
- `Referrer-Policy: no-referrer` is sent with the account page so a token in
  the query string is not leaked to third parties.

## Testing

- `GET /account/?fe_auth=<id>&fe_token=<token>` → confirmation panel, token
  **still valid** afterwards (request it twice; both should offer the button).
- Submit the form → logged in, and a second POST with the same token fails.
- Expired token, pending member, denied member → status message, no login.
- Request a new link → the old token is invalid (issue() overwrites the hash).
