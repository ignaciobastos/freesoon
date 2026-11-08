=== FreeSoon — Coming Soon & Maintenance ===
Contributors: ignaciobastos
Tags: coming soon, maintenance, under construction, visual editor, countdown
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.8.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free visual coming soon and maintenance page builder with backgrounds, countdowns and optional contact forms.

== Description ==

FreeSoon is free software: no Pro upgrade, advertising, telemetry or locked features.

* Edit the page directly in the live preview.
* Four layouts, image/video backgrounds, gradients, fonts and custom CSS.
* Optional countdown and independent scheduled opening.
* Optional name, email and phone fields. Contact fields are off by default.
* Automatic discovery of privacy links, or custom legal text and page links.
* Subscriber records, CSV export, and WordPress personal-data export/erasure.
* Coming soon (HTTP 200) and maintenance (HTTP 503) modes.
* Private access links and path exclusions.

The interface follows the WordPress site language. English and Spanish interface translations are bundled. Public default content also includes French, German, Italian and Portuguese. Additional interface translations can be supplied using standard WordPress language packs. User-written text is never automatically translated.

FreeSoon combines direct text editing in the live preview with synchronized sidebar controls, automatic logo contrast and transparent-margin trimming, WordPress font discovery, and background framing. Countdown visibility and scheduled opening are independent. Legal links can be discovered from WordPress settings, menus, or published privacy pages/posts; detection does not verify legal compliance.

== Installation ==

1. Upload the ZIP through Plugins > Add Plugin > Upload Plugin.
2. Activate FreeSoon and open FreeSoon in the administration menu.
3. Configure the page, save it and enable the maintenance screen.
4. Test as a logged-out visitor. Logged-in users may bypass the screen by default.

== Frequently Asked Questions ==

= Does hiding the countdown cancel automatic opening? =
No. Show countdown controls visibility only. Open the site automatically independently disables the screen at the selected WordPress-local date and time, checked when a visitor requests the page. Exclude the maintenance page from full-page/CDN caches so an expired response is not served by an external cache.

= How are subscriber details handled? =
Records remain in your WordPress database until you delete them or uninstall the plugin. Optional notifications use wp_mail and your configured mail provider. No mailing or SMS campaign service is included. Email records support WordPress personal-data export and erasure. Phone-only records must be located and deleted manually in Subscribers. Choose a retention period appropriate to your activity and state it in your privacy policy.

= Does FreeSoon create a cookie-consent banner? =
No. It runs wp_head, wp_body_open and wp_footer on the public screen so compatible consent plugins can load. Configure and verify your chosen consent plugin separately. A private-access cookie lasts one day when a private link is used. Subscription rate limiting stores a salted IP hash in a transient for ten minutes.

= What happens on uninstall? =
Deleting FreeSoon permanently removes its settings and subscriber records, including on multisite. Export your records first. Deactivation retains data. Media-library files are not deleted.

= Which video formats work? =
Formats and codecs must be accepted by WordPress and supported by the visitor's browser. MP4 and WebM are typical choices. Animated GIF is supported as an image. Arbitrary video codecs cannot be guaranteed.

== Third-party resources ==

Lucide icons v0.468.0, ISC license. License included in assets/LUCIDE-LICENSE.
Source: https://github.com/lucide-icons/lucide/tree/0.468.0
Distributed UMD build: https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js
No build step is required for FreeSoon source. The Lucide source and build configuration are available at the versioned repository above; the included UMD build is loaded locally, never downloaded at runtime. All other plugin source is distributed unminified. FreeSoon branding and original assets are distributed under the plugin GPL license.

== Changelog ==

= 1.8.3 =
* Link the plugin author to https://ignaciobastos.com/.

= 1.8.2 =
* Correct MO catalog offsets for compatibility with WordPress 6.4 translation loading.
* Prepare the WordPress.org package with the freesoon text domain, contributor metadata, and full license text.

= 1.8.1 =
* Detect published privacy-policy pages and posts when no configured policy or menu link exists. Multiple matches require manual selection.
* Explain which privacy document is connected, with a persistent acknowledgment and review reminder. Notices follow WordPress translations.
* Preserve manually selected policies and custom policy text.

= 1.8.0 =
* Standard gettext interface catalog and Spanish language package.
* Preserve accepted site logos and custom text.
* Detect policies added after an empty legal-text fallback.
* Correct telephone-only default copy and render SEO descriptions.
* Add public WordPress hooks and personal-data tools.
* Harden output escaping and remove obsolete templates.
* Document retention, uninstall behavior, translations and third-party licenses.

= 1.7.0 =
* Visual editor, automatic opening, localized public defaults, forms and legal links.
