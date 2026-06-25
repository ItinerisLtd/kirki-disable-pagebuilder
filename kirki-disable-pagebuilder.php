<?php
/**
 * Plugin Name:     Kirki Disable Page Builder
 * Plugin URI:      https://github.com/ItinerisLtd/kirki-disable-pagebuilder/
 * Description:     Disables Kirki v6 page builder features, keeping only the Customizer field framework.
 * Version:         0.1.0
 * Author:          Itineris Limited
 * Author URI:      https://www.itineris.co.uk/
 * Text Domain:     kirki-disable-pagebuilder
 */

declare(strict_types=1);

namespace Itineris\KirkiDisablePageBuilder;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Remove "Design with Kirki" button from Gutenberg editor (dequeues the JS that injects it).
add_action('admin_enqueue_scripts', function (): void {
    wp_dequeue_script('custom_link');
}, 999);

// Remove Kirki top-level admin menu (Canvas, Submissions, Role Managers, Settings).
add_action('admin_menu', function (): void {
    remove_menu_page('kirki');
}, 999);

// Remove "Edit with Kirki" node from the admin bar (fires on both frontend and admin).
add_action('admin_bar_menu', function (\WP_Admin_Bar $wp_admin_bar): void {
    $wp_admin_bar->remove_node('edit_with_kirki');
}, 101);

// Remove "Edit with Kirki" link from post and page list row actions.
add_filter('post_row_actions', __NAMESPACE__ . '\remove_edit_with_kirki_row_action', PHP_INT_MAX, 1);
add_filter('page_row_actions', __NAMESPACE__ . '\remove_edit_with_kirki_row_action', PHP_INT_MAX, 1);

function remove_edit_with_kirki_row_action(array $actions): array
{
    unset($actions['edit_with_kirki']);

    return $actions;
}

// Remove "Kirki Full Canvas" from the page template dropdown.
add_filter('theme_page_templates', function (array $templates): array {
    return array_filter($templates, fn(string $label): bool => $label !== 'Kirki Full Canvas');
}, PHP_INT_MAX);

// Run after Kirki's plugins_loaded (priority 10) has fired and instantiated its classes.
add_action('plugins_loaded', function (): void {
    // Restore WordPress's default big-image size threshold.
    // Kirki registers add_filter('big_image_size_threshold', '__return_false') in PluginInitEvents::__construct(),
    // disabling the 2560 px cap for all uploads site-wide.
    remove_filter('big_image_size_threshold', '__return_false');

    // Remove ContentManager hooks that run DB queries on every request:
    // - parse_request: looks up Kirki content manager parent posts on every URL.
    // - init: registers dynamic CPTs from DB on every init.
    remove_kirki_hooks_by_class('Kirki\ContentManager');

    // Remove the document title DB query that PluginInitEvents runs on every page.
    // Scoped to 'document_title_parts' — if Kirki renames this hook in a future release, this call will silently no-op.
    remove_kirki_hooks_by_class('Kirki\Manager\PluginInitEvents', 'document_title_parts');

    // Frontend-only classes — only instantiated when !is_admin().
    if (is_admin()) {
        return;
    }

    // TheFrontend::replace_content() applies html_entity_decode() to ALL page content
    // regardless of whether Kirki built the page — corrupting HTML attribute escaping.
    // Removing this class also eliminates unnecessary DB queries on wp_head and wp action.
    remove_kirki_hooks_by_class('Kirki\Frontend\TheFrontend');

    // TheFrontendHooks::add_before_body_tag_end() injects window.wp_kirki (containing
    // nonce, AJAX URL, REST URL, site URL, post ID) into every page's footer.
    // may_be_change_header_footer() starts ob_start() on every page.
    remove_kirki_hooks_by_class('Kirki\Frontend\TheFrontendHooks');

    // TemplateRedirection hooks template_include at PHP_INT_MAX and runs DB checks
    // on every page. Also overrides WordPress login/register page routing.
    remove_kirki_hooks_by_class('Kirki\Manager\TemplateRedirection');
}, 20);

/**
 * Remove all WordPress hooks registered by instances of a given class.
 *
 * Iterates $wp_filter and calls remove_filter() for any callback whose object
 * is exactly of class $class_name (get_class() exact match, not instanceof —
 * subclasses are deliberately excluded). Optionally scoped to a single $hook_name.
 */
function remove_kirki_hooks_by_class(string $class_name, string $hook_name = ''): void
{
    global $wp_filter;

    $hooks = $hook_name !== ''
        ? array_filter([$hook_name => $wp_filter[$hook_name] ?? null])
        : $wp_filter;

    foreach ($hooks as $name => $hook) {
        if (! $hook) {
            continue;
        }

        foreach ($hook->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $fn = $callback['function'];

                if (is_array($fn) && is_object($fn[0]) && get_class($fn[0]) === $class_name) {
                    remove_filter($name, $fn, $priority);
                }
            }
        }
    }
}
