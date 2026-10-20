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

## Installation

Each plugin lives in its own folder and can be uploaded directly to `wp-content/plugins/` and activated from the WordPress admin. See each plugin's `readme.txt` for full instructions.

## License

GPL-2.0-or-later
