# WordPress Plugins

A collection of WordPress plugins.

## Plugins

### Frontend User Registration
Self-service frontend registration, login and user profile management with approval workflow, custom roles, email notification, magic login links and member-only access controls.

- Auto-created `/account` page with Login and Register tabs; members never need wp-admin
- Two-step magic links: the emailed link opens a "Confirm sign in" page, so email gateways and link scanners cannot use up the single-use token
- Front-end profile editor for name and custom fields, with the login email read-only
- Admin approval workflow with single-use, expiring login links and a configurable sender address
- Members Manager role: approve, deny, edit members, manage Resources and Pages, without site-admin access
- Logged-in navigation switches automatically: Join becomes "My Account" and the Login link is hidden
- Members-only access control per post type, with non-members redirected to the account page

### Resources Library
A "Resources" custom post type with section tags and an AJAX-driven, filterable library page template (Video | Link | Article).

Features include:

- Filterable resource grid with format chips, tag/section navigation and a hierarchical sidebar
- Sort by newest, title or recently updated, with a configurable default
- Video lightbox playback (YouTube, Vimeo and direct files) from cards and single pages
- Featured resources, member Favourites, and share buttons
- Animated filtering with slide in/out transitions
- Drag-and-drop section ordering (Resources -> Reorder Sections)
- Featured toggle and section reordering follow resource edit rights, so content roles can use them
- Members-only support via the Frontend User Registration plugin

### Contact Form 7 Submissions
Saves Contact Form 7 submissions to the database with a list, detail view and CSV export. Supports Contact Form 7 6.2+.

- Submissions screen sits under Contact Form 7 for administrators, and as its own menu for roles without CF7 access
- Summary card on both the WordPress dashboard and the Contact Form 7 dashboard
- Access is controlled by a dedicated `cf7s_view_submissions` capability, so Members Managers can read submissions without site-administration rights

### Guest Authors
Attribute posts to one or more guest author identities without creating WordPress users. Guest authors are a custom post type with their own profile, archive and single pages; the real staff user stays the post author and only the public byline is swapped.

- Reorderable multi-author byline box on posts
- Public archive at `/guest-authors/` and profiles at `/guest-author/{slug}/`
- Avatar from featured image, with Gravatar fallback
- Template tags and `[ga_byline]` / `[ga_author_box]` shortcodes

### WooCommerce Works
A browse-first portfolio layer for WooCommerce. Adds a "Works" page template with an uncropped masonry grid, category filters, load more and a quick view dialog, with year / medium / dimensions / status read from product attributes.

- Masonry grid with hover shade, optional second-image swap and Quick View
- Quick view dialog: gallery, details, description, add to cart or Enquire
- Category, medium and year filter facets with live counts, URL state and browser history
- Load more with masonry re-flow; "Not specified" chips keep works without an attribute reachable
- Sold / reserved badges; works without a price fall back to an Enquire button
- Single product integration: detail lines, uncropped gallery and related works
- Settings under WooCommerce → Works; `[works_grid]` shortcode for any page

## Local test site

`.test-site/` is a gitignored WordPress + WooCommerce sandbox seeded from the captured
Weston Frizzell catalogue (41 products, 60 images).

```sh
cd .test-site
./setup.sh      # download WP/WC, install, seed (idempotent)
./start.sh      # http://127.0.0.1:8899/works/  — admin / password
./stop.sh
php seed.php --reset   # re-import the catalogue
```

The plugin is symlinked into the sandbox, so edits to `woocommerce-works/` are live on refresh.

## Installation

Each plugin lives in its own folder and can be uploaded directly to `wp-content/plugins/` and activated from the WordPress admin. See each plugin's `readme.txt` for full instructions.

## License

GPL-2.0-or-later
