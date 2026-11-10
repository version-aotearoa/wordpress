# WordPress Plugins

A collection of WordPress plugins.

## Plugins

### Frontend User Registration
Self-service frontend registration, login and user profile management with approval workflow, custom roles, email notification, magic login links and member-only access controls.

### Resources Library
A "Resources" custom post type with section tags and an AJAX-driven, filterable library page template (Video | Link | Article).

Features include:

- Filterable resource grid with format chips, tag/section navigation and a hierarchical sidebar
- Video lightbox playback (YouTube, Vimeo and direct files) from cards and single pages
- Featured resources, member Favourites, and share buttons
- Animated filtering with slide in/out transitions
- Drag-and-drop section ordering (Resources -> Reorder Sections)
- Members-only support via the Frontend User Registration plugin

### Contact Form 7 Submissions
Saves Contact Form 7 submissions to the database with an admin list under Contact -> Submissions, a detail view, CSV export and a summary card on the Contact Form 7 Dashboard. Supports Contact Form 7 6.2+.

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
