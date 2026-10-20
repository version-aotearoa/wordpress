# Members login prompt and the stale AJAX nonce

**Status:** fixed in 1.1.35. This note exists so the nonce check is not
accidentally reintroduced.

## Symptom

On a cached Resource Library page, a logged-out visitor who selected a section,
opened Favourites, or opened a gated resource saw:

> No resources found.

instead of the "Members only" panel with the login form / login link. It looked
like an empty section rather than a permissions gate, and only started after the
cached page had been sitting around for a while.

## Root cause

1. `includes/class-page-template.php` localises a nonce into the page:

   ```php
   'nonce' => wp_create_nonce( 'rl_load_posts' ),
   ```

2. `assets/js/library.js` sent it with every AJAX request (`rl_load_posts` and
   `rl_load_single`).

3. Both handlers in `includes/class-ajax.php` started with:

   ```php
   check_ajax_referer( 'rl_load_posts', 'nonce' );
   ```

4. Page caching (Cloudways Varnish / Breeze) serves the same HTML, nonce
   included, to logged-out visitors. WordPress nonces are time-based:
   `wp_nonce_tick()` changes every 12 hours and verification accepts the current
   and previous tick, so a nonce is only valid for roughly 12–24 hours.

5. Once the cached nonce expired, `check_ajax_referer()` called
   `wp_die( '-1', 403 )`. The old JS treated that as "no results" and rendered
   `window.rl_library.no_results` — "No resources found." — replacing what should
   have been the members login prompt.

The results and the login prompt are read-only views, so the nonce added no
security; it only tied the cached page's lifetime to the nonce's.

## Fix (1.1.35)

- Removed `check_ajax_referer()` from `RL_Ajax::handle()` and
  `RL_Ajax::single()`. Both are registered for `wp_ajax_nopriv_*` as well, and
  neither writes anything.
- Added a gated-view early return that responds with
  `RL_Access::login_panel()` (`"login": true`), so the AJAX response renders the
  login prompt instead of a results grid.
- Renamed the JS `empty()` helper to `failed()` and changed its message to
  "Something went wrong. Please reload the page.", so real failures no longer
  masquerade as an empty library.

The JS still sends `nonce` with those requests; the handlers ignore it. That is
harmless and keeps the request shape unchanged.

## Guardrails

- Do **not** add `check_ajax_referer()` to `rl_load_posts` or `rl_load_single`.
  They are reads and must work from cached HTML.
- Do not make a cached page depend on any nonce for read-only behaviour. If a
  fresh value is ever needed, fetch it from a non-cached endpoint at runtime.
- Nonces remain appropriate for state-changing actions (see below).

## Regression test

While logged out, request both endpoints with a deliberately bad nonce:

```
/wp-admin/admin-ajax.php?action=rl_load_posts&tag=<slug>&nonce=bogus
/wp-admin/admin-ajax.php?action=rl_load_single&post_id=<id>&nonce=bogus
```

Expected: HTTP 200 with `{"success":true,...}` — cards, or `"login":true` with
the members panel when the section/resource is gated. A `-1` response or a 403
means a nonce check has crept back in.

For a realistic check, open a long-cached library page, overwrite
`window.rl_library.nonce` with garbage in the console, then select a section.
The content should still load (or show the login panel), not "No resources
found."

## Related nonce use (intentional)

- `rl_toggle_favourite` verifies the `rl_load_posts` nonce because it writes
  user meta. It only runs for logged-in users, whose pages are not normally
  served from the public page cache.
- `rl_toggle_featured_<post_id>` verifies a per-post nonce; it is an
  administrator-only action.
- `rl_library.nonce` is still localised for the favourite toggle — do not remove
  it from `wp_localize_script()`.
