# kirki-disable-pagebuilder

[![Packagist Version](https://img.shields.io/packagist/v/itinerisltd/kirki-disable-pagebuilder.svg?label=release&style=flat-square)](https://packagist.org/packages/itinerisltd/kirki-disable-pagebuilder)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/itinerisltd/kirki-disable-pagebuilder.svg?style=flat-square)](https://packagist.org/packages/itinerisltd/kirki-disable-pagebuilder)
[![Packagist Downloads](https://img.shields.io/packagist/dt/itinerisltd/kirki-disable-pagebuilder.svg?label=packagist%20downloads&style=flat-square)](https://packagist.org/packages/itinerisltd/kirki-disable-pagebuilder/stats)
[![GitHub License](https://img.shields.io/github/license/ItinerisLtd/kirki-disable-pagebuilder.svg?style=flat-square)](https://github.com/ItinerisLtd/kirki-disable-pagebuilder/blob/master/LICENSE)
[![Hire Itineris](https://img.shields.io/badge/Hire-Itineris-ff69b4.svg?style=flat-square)](https://www.itineris.co.uk/contact/)
[![Twitter Follow @itineris_ltd](https://img.shields.io/twitter/follow/itineris_ltd?style=flat-square&color=1da1f2)](https://twitter.com/itineris_ltd)

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Why](#why)
- [What it disables](#what-it-disables)
- [What it preserves](#what-it-preserves)
- [Minimum Requirements](#minimum-requirements)
- [Installation](#installation)
- [Credits](#credits)
- [License](#license)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Why

Kirki v6 introduced a full no-code page builder (formerly Droip) on top of the existing Kirki v5 Customizer field framework. If you use only the Customizer fields and want nothing to do with the page builder, install this mu-plugin. It removes the page builder entirely — install to disable, remove the package to re-enable.

## What it disables

### Admin UI

| Feature | How |
|---|---|
| "Design with Kirki" button in the Gutenberg editor toolbar | Dequeues the `custom_link` script on `admin_enqueue_scripts` |
| "Kirki" top-level admin menu (Canvas, Submissions, Role Managers, Settings) | Removes the `kirki` menu page on `admin_menu` |
| "Edit with Kirki" in the WordPress admin bar | Removes the `edit_with_kirki` node on `admin_bar_menu` |
| "Edit with Kirki" in post and page list row actions | Unsets the `edit_with_kirki` key from `post_row_actions` / `page_row_actions` |
| "Kirki Full Canvas" in the page template dropdown | Filters it out of `theme_page_templates` |

### Frontend and global hooks

These are removed on `plugins_loaded` priority 20, after Kirki registers its own hooks at priority 10.

| Class | Hooks removed | Why |
|---|---|---|
| `Kirki\ContentManager` | `parse_request`, `init` | Runs DB queries on every request to find Kirki content manager posts and register dynamic CPTs |
| `Kirki\Manager\PluginInitEvents` | `document_title_parts` | Runs a DB query on every page to look up Kirki SEO settings |
| `Kirki\Frontend\TheFrontend` | `wp`, `wp_head`, `the_content` (PHP_INT_MAX), `wp_footer` | `replace_content()` applies `html_entity_decode()` to **all** page content regardless of whether Kirki built it — corrupting HTML attribute escaping (e.g. shortcode attributes). Also runs DB queries on every page. |
| `Kirki\Frontend\TheFrontendHooks` | `wp_enqueue_scripts`, `wp_body_open`, `wp_footer`, `template_redirect` | Injects `window.wp_kirki` (nonce, AJAX URL, REST URL, site URL, post ID) as an inline script into every page footer. Starts `ob_start()` with a regex callback on every page. Runs DB queries on every page. |
| `Kirki\Manager\TemplateRedirection` | `template_include`, login/register redirects, `post_link`, `query_vars` | Hijacks template loading at `PHP_INT_MAX` and overrides WordPress login/register/lost-password page routing |

### Miscellaneous

| Setting | How |
|---|---|
| `big_image_size_threshold` set to `false` by Kirki | Restored to WordPress default (2560 px) by calling `remove_filter('big_image_size_threshold', '__return_false')` |

## What it preserves

The Kirki v5 Customizer field framework (`Kirki\Customizer` and everything under `customizer/`) is loaded independently in `KirkiBase::__construct()` and is not touched by this plugin. Any Kirki Customizer fields registered in your theme or plugins will continue to work normally.

The following Kirki v6 components are also left in place as they have no impact on regular page loads:

- `Kirki\Ajax` — admin AJAX handlers (`wp_ajax_kirki_*`), only fire on explicit AJAX requests
- `Kirki\API` — REST API routes, only registered on `rest_api_init`
- `Kirki\ElementVisibilityConditions` — only hooks onto Kirki-specific filters, never fires on non-Kirki pages
- `Kirki\Manager\PluginShortcode` — shortcodes are inert unless explicitly placed in content

## Minimum Requirements

- PHP v8.1
- WordPress v6.2

## Installation

```bash
composer require itinerisltd/kirki-disable-pagebuilder
```


## Credits

[kirki-disable-pagebuilder](https://github.com/ItinerisLtd/kirki-disable-pagebuilder) is a [Itineris Limited](https://www.itineris.co.uk/) project created by [Lee Hanbury-Pickett](https://github.com/codepuncher).

Full list of contributors can be found [here](https://github.com/ItinerisLtd/kirki-disable-pagebuilder/graphs/contributors).

## License

[kirki-disable-pagebuilder](https://github.com/ItinerisLtd/kirki-disable-pagebuilder) is released under the [MIT License](https://opensource.org/licenses/MIT).
