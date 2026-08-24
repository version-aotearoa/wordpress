=== Resources Library ===
Contributors: resources-library
Tags: resources, custom post type, ajax, library
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.17
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Resources custom post type with section tags and an AJAX-driven, filterable library page template.

== Description ==

Adds a public "Resources" custom post type (slug `resource`) with two taxonomies:

* Resource Tags (`resource_tag`) - flat tags used as the sidebar section navigation.
* Resource Formats (`resource_format`) - Video, Link, and Article, used to filter posts.

The plugin provides a "Resource Library" page template (selectable in the Page Attributes dropdown). The template renders a left sidebar listing all resource tags and a main area with:

* Format filter chips (All | Video | Link | Article).
* A posts grid that loads by tag via AJAX, without reloading the page.
* "Load more" pagination.

Selecting a tag or format updates the URL (e.g. `?tag=slug&format=video`), so views are shareable and browser back/forward works.

The main area defaults to Featured Resources - mark posts with the "Feature on library page" checkbox in the resource editor. An optional "Resource URL" field lets a card link out to an external destination (useful for Link-format resources).

For block themes (or any page), add the library with the `[resources_library]` shortcode instead of the page template - it renders the same sidebar and grid.

== Installation ==

1. Upload the `resources-library` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Add Resources and assign tags/formats in the admin.
4. Create your library page: pick the "Resource Library" template in the Page Attributes meta box (classic themes), or add a `[resources_library]` shortcode to any page.

== Frequently Asked Questions ==

= How do I filter the sidebar by format? =

Each resource is assigned a format (Video, Link, or Article) in the editor. The format chips above the post grid filter the current view.

= How does the sidebar stay available? =

The sidebar is rendered on the page and never reloaded; selecting a tag loads new posts into the main area with AJAX.

= Does the library work on block themes? =

Yes. Instead of the page template, add the `[resources_library]` shortcode to a page (or into a Shortcode block). It renders the identical sidebar + filterable grid and loads its styles/scripts automatically.

= Can I restrict the Resources section to members? =

If the Frontend User Registration plugin is active, enable "Resources" under Users -> Registration -> Settings -> Member Access to make the whole section Members-only.

== Changelog ==

= 1.1.17 =
* The Resource Tags and Resource Formats meta box tabs are now labelled "All" and "Recent".
* Removed the grab cursor from the plugin's meta boxes, which no longer drag.
* Fixed an empty "Meta boxes" footer area appearing in the block editor.
* The admin bar "Edit" link now follows in-page navigation to the resource being viewed and returns to the container page when navigating back.

= 1.1.16 =
* Fixed the meta box drag override removing metaboxes in the block editor - the plugin's three boxes are now excluded from dragging instead of being cancelled mid-drag.
* Removed the library page's built-in member restriction; the page is now protected by the Frontend User Registration "Member content page" setting.

= 1.1.15 =
* Resource Tags and Resource Formats meta boxes now use the classic "All / Recent" tabs again.
* Drag and move up/down reordering is disabled on the plugin's Resource Tags, Resource Formats and Library Options meta boxes.

= 1.1.14 =
* Fixed the sidebar tag arrows inheriting the theme's button margin, which caused an uneven bottom gap.
* Clicking a video's image on its page now opens the video in the lightbox, not just the play button.

= 1.1.13 =
* Tags with children now show an arrow in the sidebar; clicking it instantly expands/collapses the child tags (they are preloaded on the page).
* The Favourites view now shows a hint when it is empty, and empty results loaded via AJAX display a message instead of a blank grid.

= 1.1.12 =
* The sidebar now shows tag hierarchy: a tag's children appear only while that parent section is selected.
* New "Reorder Sections" page (Resources -> Reorder Sections) for drag-and-drop ordering of tags in the sidebar.

= 1.1.11 =
* The library title now shows the current section (tag) name instead of the page title when a section is selected.

= 1.1.10 =
* Format filtering no longer re-animates cards that stay - removed cards slide out and the remaining cards glide into their new positions.

= 1.1.9 =
* Single resource views now span the full width of the library container.
* Filter transitions now slide the resource grid out and in for a more visible animation.

= 1.1.8 =
* Video card titles now go to the video's page instead of opening the video in the lightbox (the play thumbnail still opens the modal).
* Slowed the filter fade animation so the transition is clearly visible when filtering by tag, format or favourites.

= 1.1.7 =
* Added temporary console logging to diagnose why the latest fade animation and video lightbox changes were not appearing after update.

= 1.1.6 =
* Added a fade animation when filtering resources by tag, format or favourites.
* Video cards now open in the lightbox modal from the title for any resource with a video URL (YouTube, Vimeo or direct video file), regardless of its format.

= 1.1.5 =
* New "Accent colour" setting (Resources -> Settings) that themes active tags, format filters and buttons across the library.
* Fixed the Favourites section staying highlighted when another tag is selected.
* Fixed the accent colour not saving (invalid hex was being stored).
* Fixed the Share/Favourites buttons rendering as ovals on card images.

= 1.1.4 =
* Share/Favourites buttons now float at the top-right of each card over the image.
* Video resources no longer auto-open on load; a play triangle on the image opens the video modal (cards and single pages).

= 1.1.3 =
* New Featured column on the resource list with an inline AJAX toggle.
* Cards have a Share button (copy link / native share) and a Favourites toggle for members, with a Favourites view in the sidebar.
* Video resources open in a modal automatically when reached via a direct/shared link.
* Administrators can access Members-only content; restricted visitors are redirected with a message.

= 1.1.2 =
* The Resources library page is now also Members-only when the "Resources" type is restricted by the Frontend User Registration plugin (single resources were already covered).

= 1.1.1 =
* New Settings page (Resources -> Settings): cards per page and recent tags count.
* Format filtering now hides cards instantly client-side when everything is loaded, and falls back to AJAX when more pages remain.
* Cards always display a 16:9 landscape image (new 640x360 cropped size + CSS crop fallback).
* Empty meta-boxes sections are reliably hidden in the block editor.

= 1.1.0 =
* "Resource" now appears first in the admin-bar "+ New" menu.
* Empty meta-boxes sections are hidden in the block editor.

= 1.0.9 =
* Tag/format editing is now a normal, JS-free meta box (Recent tags as checkboxes, Name + Slug add-new, full checklist) so it works in the classic editor and the block editor's meta-box/sidebar area.
* Removed the duplicate/native "Resource Tags"/"Resource Formats" panels in the block editor sidebar (show_in_rest off).

= 1.0.8 =
* The Resource Tags box is explicitly pinned to the custom editor meta box (Recent picker, Name + Slug, no parent dropdown) at the top of the side column.

= 1.0.7 =
* Custom tag/format editor meta box: no parent dropdown, a "Recent" picker of recently used tags, and Name + Slug fields for adding new tags on save.

= 1.0.6 =
* Hidden the "Parent" dropdown in the Resource Tags and Resource Formats add-new boxes (tags are used flat).
* Resource tag/format labels no longer fall back to "Category" wording in the editor.
* Resources now appears before Posts in the admin menu.
* Admin list columns reordered to Image, Tags, Format.

= 1.0.5 =
* Fixed the video lightbox (openVideo was defined in the wrong scope, causing a JavaScript ReferenceError).
* The back link on a single resource now reads "Show all <Tag>" for the current section, or "Show all Resources" when no tag is active.

= 1.0.4 =
* Resource admin list now shows Tags, Format, and Featured image columns, plus a filter dropdown by tag.
* Video-format resources with a Resource URL open in a lightbox video player instead of navigating away.

= 1.0.3 =
* Single resource pages now include the sidebar tag navigation, linking back to the library page.

= 1.0.2 =
* Clicking a resource card now loads the single resource via AJAX so the sidebar stays in place (Link-format cards still open externally).

= 1.0.1 =
* Format chips now show only formats with posts in the current view (tag or featured).
* Resource tags and formats now use WordPress's built-in checkbox list with "Add New" input when editing.
* Disabled the Resources auto-archive so a library page can use the /resources slug.

= 1.0.0 =
* Initial release.
