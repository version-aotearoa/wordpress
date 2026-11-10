=== WooCommerce Works ===
Contributors: woocommerce-works
Tags: woocommerce, portfolio, art, gallery, masonry
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 10.9
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A browse-first portfolio layer for WooCommerce: a Works page with a masonry grid, quick view and work details taken from product attributes.

== Description ==

WooCommerce Works turns a product catalogue into a portfolio you can browse. Products keep their normal WooCommerce data — this plugin only changes how they are presented.

* **Works page template.** Create a page, pick "Works" in Page Attributes, and the page renders the grid. Any text you type on the page appears as an intro above the grid. The same markup is available as the `[works_grid]` shortcode for block themes or extra pages.
* **Masonry grid.** Uneven image heights, shortest-column placement, no gaps in the flow. The image is never cropped, so each work keeps its real proportions.
* **Hover.** A dark shade and a "Quick View" button appear over the image, plus an optional swap to the second gallery image. On touch devices the Quick View button is always visible.
* **Work details from attributes.** Year, medium, dimensions and status are read from WooCommerce attributes. Global attributes named `year`, `medium`, `dimensions` and `status` are mapped automatically; anything else can be mapped in Settings.
* **Prices stay flexible.** A product with a price shows its price and an add-to-cart button in quick view. A product with no price — or a status of sold or reserved — shows an Enquire button instead. Nothing is hidden from the normal shop.
* **Quick view.** A native dialog with the full image, gallery thumbnails, details, description and add to cart or Enquire. Arrow keys move between works, Escape closes, focus returns to the card.
* **Filter facets.** Category chips plus optional attribute facets (Medium and Year by default), each written to the URL (`?wcw_cat=slug&wcw_medium=screenprint&wcw_year=2023`) so views are shareable and the back button works. Counts follow the other active filters. Works with no value get a "Not specified" chip instead of dropping out of the grid.
* **Load more.** Appends the next page and re-flows the masonry without a reload, keeping the active filters.
* **Single work.** Detail lines and status badge under the title, uncropped gallery images, the Enquire button when there is no price, and a related works grid built from the same cards.

No product data is duplicated: deactivating the plugin leaves your products, attributes and prices untouched.

== Installation ==

1. Upload the `woocommerce-works` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Go to WooCommerce → Works and check the attribute mapping.
4. Create a page (for example "Works") and choose the **Works** template in Page Attributes.
5. Optionally set an Enquiry target: a contact page URL, an email address or a `mailto:` link.

== Frequently Asked Questions ==

= Do I need a specific theme? =

No. The grid is its own markup with `wcw-` prefixed classes and inherits your theme's typography and colours. Every template can be overridden by copying it to `yourtheme/woocommerce-works/`.

= How are prices and the Enquire button decided? =

If the product has a price and is not sold or reserved, the price is shown and simple products can be added to the cart from quick view. Otherwise the Enquire button is shown, provided a target is set in Settings. Sold and reserved are detected from the mapped status attribute, or from WooCommerce stock status when "Treat out of stock products as sold" is enabled.

= Why do some works look cropped? =

The grid asks for uncropped images. If your WooCommerce "Single product image" size is set to crop, change it in Appearance → Customize → WooCommerce → Product Images, or pick a different card image size in Settings, then regenerate thumbnails.

= How do I control the order? =

Set "Order by" to Manual order and use the product Order field (the `menu_order` value) to arrange works exactly as you want them.

= How do the attribute filters work? =

Enable them under WooCommerce → Works → Filters. Each facet is single-select and they combine with AND: choosing "Screenprint" and "2023" shows screenprints from 2023. Counts on every chip are recalculated against the other active filters. Works with no value for a facet appear under "Not specified" rather than disappearing. A facet only appears once its attribute is mapped under Work details.

= How do I change the column count or gap? =

WooCommerce → Works → Works grid. Desktop, tablet and mobile columns are set separately. The gap is in pixels and also available to CSS as `--wcw-gap`.

= Can I use it alongside a normal shop? =

Yes. The Works template is a separate page; the standard shop, category archives and cart are untouched.

= How do I change the Quick View wording or colours? =

All strings are translatable with the `woocommerce-works` text domain. Colours and buttons are controlled by CSS custom properties on `.wcw-works` (`--wcw-button-bg`, `--wcw-button-color`, `--wcw-shade`, `--wcw-badge-bg`, `--wcw-badge-color`).

= How do I use it on a block theme? =

Create a page and choose the "Works" template in the editor's page settings, exactly as with a classic theme. The template renders the theme's block header and footer around the grid. If your theme's page settings don't list the template, drop the `[works_grid]` shortcode into a page instead.

Shortcode attributes:

* `cat` - limit the grid to one product category slug.
* `per_page` - how many works to load at a time.
* `chips` - `1` to show the category chips (hidden by default when `cat` is set), `0` to always hide them.

= What happens without JavaScript? =

The grid falls back to a plain CSS grid, category chips are ordinary links, and numbered pagination is shown instead of Load more. Quick View is a plain link to the work that JavaScript upgrades into a dialog, so every card still opens its work page.

== Changelog ==

= 1.1.0 =
* New attribute filter facets above the works grid: Medium and Year by default, configurable under WooCommerce → Works → Filters.
* Facet counts respect the other active filters, and update after filtering and Load more.
* Works without a value for a facet are reachable through a "Not specified" chip instead of being hidden.
* Filter state is written to the URL (`wcw_cat`, `wcw_medium`, `wcw_year`) and restored by browser back/forward.
* Facets work without JavaScript: chips are ordinary links, counts are rendered server-side, and numbered pagination keeps the filters.
* Settings: new Filters section with a warning when a facet has no attribute mapped.

= 1.0.0 =
* Initial release.
* Works page template and `[works_grid]` shortcode with a masonry grid of uncropped images.
* Hover shade, optional second-image swap and a Quick View button.
* Quick view dialog with gallery thumbnails, description, add to cart and Enquire.
* Category filter chips and Load more, with URL state and browser history support.
* Work details (year, medium, dimensions, status) read from mapped WooCommerce attributes, with automatic detection.
* Sold / reserved badges and the Enquire fallback for works without a price.
* Single product integration: detail lines, badge, uncropped gallery size and a related works grid.
* Settings under WooCommerce → Works, including an attribute diagnostic table.
