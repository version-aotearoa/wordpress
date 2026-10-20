=== Guest Authors ===
Contributors: guest-authors
Tags: guest author, byline, custom post type, co-authors, authors
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Attribute posts published by staff to one or more guest author identities, each with their own profile, archive and single page.

== Description ==

Guest Authors lets staff publish posts normally while the public byline is shown
as one or more guest authors. It does **not** create WordPress users for guest
authors: the real staff user stays the post author (so ownership, revisions and
permissions are untouched), and a display layer swaps the byline at render time.

A "Guest Authors" custom post type (slug `guest_author`) stores each identity:

* Title - author name.
* Editor - author bio.
* Featured image - author avatar.
* Job title, company, website, X and LinkedIn URLs, and a private email used
  only as a Gravatar fallback.

On any supported post, a "Guest Authors" sidebar box lets you pick one or more
guest authors and drag them into order. The byline reads "A", "A and B" or
"A, B and C", with each name linking to that guest's page.

Public pages:

* Guest author archive at `/guest-authors/`.
* Guest author profile at `/guest-author/{slug}/`, showing the bio, links and
  that author's posts.

== Installation ==

1. Upload the `guest-authors` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to Guest Authors and create one or more guest authors (name, bio, avatar).
4. Edit a post, open the "Guest Authors" box, add the authors in the desired
   order, and update.

== Frequently Asked Questions ==

= Do guest authors need a WordPress account? =

No. Guest authors are a custom post type, not users. The real staff user remains
the post author internally.

= Can a post have more than one guest author? =

Yes. Add several in the "Guest Authors" box and drag them to set the byline
order.

= What happens if no guest author is selected? =

The real WordPress author is shown, unchanged.

= Where does the avatar come from? =

The guest author's featured image. If none is set, the private email (if
provided) is used for a Gravatar.

= How do I change "and" to "&" in the byline? =

Use the `ga_byline_join_word` filter:

`add_filter( 'ga_byline_join_word', fn() => '&' );`

== Filters ==

* `ga_supported_post_types` - post types a guest author can be attributed to
  (default `array( 'post' )`).
* `ga_byline_join_word` - the word between the final two names (default `and`).

== Template tags ==

* `ga_has_guest_authors( $post_id = null )` - bool.
* `ga_get_guest_authors( $post_id = null )` - array of `id`, `name`, `url`,
  `job_title`, `company`.
* `ga_get_byline( $post_id = null, $args = array() )` - linked byline HTML.
* `ga_get_avatar_url( $guest_id, $size = 96 )` - guest avatar URL.

Shortcodes: `[ga_byline]` and `[ga_author_box]` (both accept `post_id`).

== Changelog ==

= 1.0.0 =
* Initial release: guest author custom post type, co-author byline box,
  public archive and profile pages, byline/avatar/meta overrides, REST field
  and template tags.
