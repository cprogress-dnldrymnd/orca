# Orca Theme

A custom WordPress theme ("Orca Theme" by Digitally Disruptive) for the ORCA charity's
e-learning site. Built on **WooCommerce** (shop/checkout) + **LearnDash** (`sfwd-courses`
and related CPTs) with **Carbon Fields** for custom meta, **Bootstrap 5.3.3** and
**Swiper** as front-end vendor libraries.

This is a plain (non-child) theme — there is no build step, package manager, or test
suite. PHP files are loaded directly by WordPress. Edits are made straight to the theme
files in this repo and deployed as-is.

## Structure

- `functions.php` — theme bootstrap: defines path constants (`theme_dir`, `assets_dir`,
  `image_dir`, `vendor_dir`), enqueues `style.css`/Bootstrap/Swiper, registers
  WooCommerce support, registers extra checkout opt-in checkboxes
  (`orca-learn/training-opt-in`, `orca-learn/communications-opt-in`) with
  `orca_get_order_opt_in_value()` / `orca_get_training_opt_in_value()` /
  `orca_get_communications_opt_in_value()` reading them back off an order (checking the
  field ID, a `_`-prefixed key, the label, and finally a full meta-key scan, since
  WooCommerce's Additional Checkout Fields can persist under any of these), and the
  classic-checkout UTM/click-id capture pipeline: a `wp_footer` script
  (`disruptive_retain_utm_parameters`) that persists `utm_source/medium/campaign/term/
  content`, `gclid`, `fbclid`, `msclkid` in `sessionStorage` and appends them to
  same-site link clicks, an `init` handler that also stores them in cookies on
  landing, hidden fields injected into checkout (`woocommerce_after_order_notes`) from
  those cookies, and a `woocommerce_checkout_create_order` handler that saves them onto
  the order as `_utm_*`/`_gclid`-style meta (this is the meta
  `orca_get_attribution_value()` in `beacon-orders-export.php` reads first, before
  falling back to WooCommerce's native order attribution meta from the block
  checkout). `functions.php` then `require_once`s everything in `includes/`. The old
  temporary "Beacon Data Export" admin tool (remapping legacy
  `_beacon_courses_data`/`_beacon_id` product meta to LearnDash courses) has been
  removed now that migration is complete.
- `includes/`
  - `post-types.php` — registers custom post types (Testimonials, Course Custom
    Emails, Beacon CRM Logs) via a small `newPostType`/`newTaxonomy` helper class.
  - `post-meta.php` — Carbon Fields `post_meta` containers, mainly the "Course
    Settings" fields (banner, CTA, outcomes, highlight, breakdown, certification) for
    `sfwd-courses`.
  - `learndash.php` — LearnDash integration: access/purchase checks
    (`_user_has_access`, `_can_be_purchased`), linking LearnDash courses to WooCommerce
    products via the `_related_course` post meta, and ~18 shortcodes for course UI
    (`_learndash_course_meta`, `_learndash_course_button`, `_learndash_image`,
    `_course_cta`, `_course_banner`, `_course_testimonial`, `_ld_certificate`, etc.).
  - `shortcodes.php` — general layout shortcodes (breadcrumbs, headings, images, etc.).
  - `woocommerce.php` — WooCommerce hooks: cart redirect-after-error tied to
    `_related_course`, extra billing first/last name fields on registration.
  - `wc-redirect-manager.php` — `DD_WC_Redirect_Manager` class. Admin UI under
    WooCommerce → "Redirect Rules" lets admins map products/variations to a post-purchase
    redirect (internal page or external URL); applied on the `order-received` endpoint
    via `template_redirect`. Rules stored in the `dd_wc_redirect_rules` option.
  - `beacon-orders-export.php` — adds a "Download Beacon Orders CSV" button (admin
    notice) to WooCommerce → Orders. Exports all orders to CSV with order/customer
    info, the two checkout opt-ins, and attribution data (UTM params plus
    gclid/fbclid/msclkid) via `orca_get_attribution_value()`, which reads the theme's
    `_utm_*`/`_gclid`-style order meta (classic checkout) and falls back to
    WooCommerce's native `_wc_order_attribution_*` meta (block-based checkout). Also
    adds "Training Opt-In"/"Comms Opt-In" columns to the WooCommerce → Orders list
    table. Despite the "beacon" name (kept for continuity with the removed Beacon
    Data Export tool), this is a general orders/attribution export, unrelated to the
    old Beacon CRM migration.
  - `ajax.php` — `archive_ajax` handler (`wp_ajax_archive_ajax` /
    `wp_ajax_nopriv_archive_ajax`) powering "load more" / filtering on course & shop
    archives, used by `assets/javascripts/archive-course.js`.
  - `ajax2` — **stray duplicate** of `ajax.php`'s logic (not `require_once`d, not a
    `.php` file — likely leftover, safe to ignore/remove).
  - `hooks.php` — misc admin tweaks: disables comments site-wide, custom archive title
    filter.
  - `theme-widgets.php` — registers footer widget areas (Footer Left, Footer Right
    Column 1/2).
  - `menus.php` — registers `header-menu` and `footer-menu` nav locations.
  - `bootstrap-navwalker.php` — standard Bootstrap nav walker for `wp_nav_menu`.
- `woocommerce/single-product.php` — overridden WooCommerce single product template.
- Templates: `front-page.php`, `archive-product.php`, `archive-sfwd-courses.php`,
  `single-sfwd-courses.php`, `taxonomy-ld_course_category.php`, `search.php`,
  `header.php`, `footer.php`, `index.php`, `page.php`, `single.php`, `404.php`.
- `assets/` — `javascripts/` (archive-course.js, single-course.js), `stylesheets/`
  (SCSS sources compiled into `style.css`/`style.css.map`), `vendors/bootstrap`,
  `vendors/swiper`, `images/`.

## Key conventions / gotchas

- Course ↔ product linking is done via the `_related_course` post meta on WooCommerce
  products (serialized array of LearnDash course IDs), checked with `LIKE` queries —
  see `_learndash_has_linked_product()` / `_learndash_included_in_bundle()` in
  `includes/learndash.php`.
- "Bundles", "online-courses", and "wps_wgm_giftcard" are `product_cat` slugs with
  special-cased behaviour throughout `learndash.php` and `ajax.php`.
- Custom meta helpers prefix the meta key with `_`: `get__post_meta()`,
  `get__term_meta()`, `get__post_meta_by_id()`, `get__theme_option()` (defined in
  `functions.php`).
- The legacy Beacon CRM integration (`includes/beacon.php`) and its one-off
  `_beacon_*` migration tool have both been removed; the only "beacon" code left is
  `includes/beacon-orders-export.php`'s unrelated orders/attribution CSV export
  (name kept for continuity, not a migration tool).
- Comments are disabled site-wide (`includes/hooks.php`).
