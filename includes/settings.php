<?php

/**
 * Settings → Ibracodes AVIF Converter: capability status, the two settings and the
 * one-click bulk converter with live progress. Follows the ibracodes admin
 * design language (gradient header, cards, switches).
 */

namespace IbraAvif;

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_options_page(
        __('Ibracodes AVIF Converter', 'ibra-avif'),
        __('AVIF Converter', 'ibra-avif'),
        'manage_options',
        'ibra-avif',
        __NAMESPACE__.'\\settings_page',
    );
});

add_action('admin_init', function () {
    register_setting('iaf', OPTION, ['sanitize_callback' => __NAMESPACE__.'\\sanitize']);
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_ibra-avif') {
        return;
    }

    wp_enqueue_style('iaf-admin', URL.'assets/admin.css', [], VERSION);
    wp_enqueue_script('iaf-admin', URL.'assets/admin.js', [], VERSION, true);
    wp_localize_script('iaf-admin', 'iafConfig', [
        'root' => esc_url_raw(rest_url('ibra-avif/v1/')),
        'nonce' => wp_create_nonce('wp_rest'),
        'labels' => [
            'progress' => __('Converting %1$d of %2$d…', 'ibra-avif'),
            'done' => __('Done — the whole library is converted.', 'ibra-avif'),
            'empty' => __('Nothing to convert — the library is already up to date.', 'ibra-avif'),
            'failed' => __('%d images failed — check that their files exist.', 'ibra-avif'),
        ],
    ]);
});

function sanitize($input): array
{
    $input = (array) $input;

    return [
        'enabled' => empty($input['enabled']) ? 0 : 1,
        'quality' => max(30, min(90, absint($input['quality'] ?? 70))),
    ];
}

function human_bytes(int $bytes): string
{
    return size_format($bytes, $bytes > MB_IN_BYTES ? 1 : 0) ?: '0 B';
}

function settings_page(): void
{
    $s = settings();
    $target = target_mime();
    $stats = (array) get_option(STATS, []);
    $pending = count(pending_ids());
    // read-only display flag from WordPress's own settings redirect; the
    // save itself is nonce-checked by options.php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $saved = isset($_GET['settings-updated']) && sanitize_text_field(wp_unslash($_GET['settings-updated'])) === 'true';
    ?>
    <div class="wrap iaf-admin">
        <hr class="wp-header-end" style="display:none">
        <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
            <?php settings_fields('iaf'); ?>

            <div class="iaf-head">
                <div class="iaf-brand">
                    <span class="iaf-brand-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#141519" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="14" rx="2.5"></rect><circle cx="9" cy="10" r="1.6"></circle><path d="m3.5 16.5 4.5-4 3.5 3 3.5-3.5 5.5 5"></path></svg>
                    </span>
                    <span class="iaf-brand-name">
                        <img src="<?php echo esc_url(URL.'assets/logo.svg'); ?>" alt="ibracodes">
                        <strong><?php esc_html_e('AVIF Converter', 'ibra-avif'); ?></strong>
                    </span>
                </div>
                <div class="iaf-head-meta">
                    <span class="iaf-version" dir="ltr">v<?php echo esc_html(VERSION); ?></span>
                    <a class="iaf-docs" href="https://github.com/Ibracodes-agency/ibra-avif" target="_blank" rel="noopener"><?php esc_html_e('Documentation', 'ibra-avif'); ?></a>
                </div>
            </div>

            <?php if ($saved) : ?>
                <div class="iaf-notice"><span class="iaf-notice-check" aria-hidden="true">✓</span><?php esc_html_e('Settings saved.', 'ibra-avif'); ?></div>
            <?php endif; ?>

            <div class="iaf-body">
                <div class="iaf-card iaf-pad iaf-status">
                    <div>
                        <div class="iaf-card-title"><?php esc_html_e('Server capability', 'ibra-avif'); ?></div>
                        <div class="iaf-card-sub">
                            <?php if ($target === 'image/avif') {
                                esc_html_e('This server encodes AVIF — new image sizes are generated as AVIF.', 'ibra-avif');
                            } elseif ($target === 'image/webp') {
                                esc_html_e('This server cannot encode AVIF, so the plugin falls back to WebP — still a major saving over JPEG/PNG.', 'ibra-avif');
                            } else {
                                esc_html_e('This server can encode neither AVIF nor WebP. Ask your host to enable GD or Imagick with AVIF/WebP support — until then the plugin stays inactive.', 'ibra-avif');
                            } ?>
                        </div>
                    </div>
                    <?php if ($target === 'image/avif') : ?>
                        <span class="iaf-chip iaf-chip-ok">AVIF ✓</span>
                    <?php elseif ($target === 'image/webp') : ?>
                        <span class="iaf-chip iaf-chip-warn">WebP fallback</span>
                    <?php else : ?>
                        <span class="iaf-chip iaf-chip-off"><?php esc_html_e('Unsupported', 'ibra-avif'); ?></span>
                    <?php endif; ?>
                </div>

                <div class="iaf-card">
                    <label class="iaf-row iaf-flip">
                        <span class="iaf-row-copy">
                            <span class="iaf-row-label"><?php esc_html_e('Convert new uploads', 'ibra-avif'); ?></span>
                            <span class="iaf-row-help"><?php esc_html_e('Every generated image size is written in the modern format. The original upload is never modified — it stays available for social-preview scrapers and as a pristine source.', 'ibra-avif'); ?></span>
                        </span>
                        <input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[enabled]" value="1" <?php checked($s['enabled']); ?>>
                        <span class="iaf-switch" aria-hidden="true"></span>
                    </label>
                    <div class="iaf-row">
                        <span class="iaf-row-copy">
                            <span class="iaf-row-label"><?php esc_html_e('Quality', 'ibra-avif'); ?></span>
                            <span class="iaf-row-help"><?php esc_html_e('70 is the sweet spot. Below ~65, fine text in screenshots starts to smear.', 'ibra-avif'); ?></span>
                        </span>
                        <span class="iaf-quality">
                            <input type="number" name="<?php echo esc_attr(OPTION); ?>[quality]" value="<?php echo esc_attr($s['quality']); ?>" min="30" max="90" dir="ltr">
                        </span>
                    </div>
                </div>

                <div class="iaf-card iaf-pad">
                    <div class="iaf-bulk-head">
                        <div>
                            <div class="iaf-card-title"><?php esc_html_e('Convert the existing library', 'ibra-avif'); ?></div>
                            <div class="iaf-card-sub" data-iaf-pending-note>
                                <?php printf(
                                    /* translators: %d: number of images awaiting conversion */
                                    esc_html(_n('%d image is waiting for conversion.', '%d images are waiting for conversion.', $pending, 'ibra-avif')),
                                    (int) $pending,
                                ); ?>
                            </div>
                        </div>
                        <button type="button" class="iaf-run" data-iaf-run <?php disabled(! $target || ! $pending); ?>><?php esc_html_e('Convert entire library', 'ibra-avif'); ?></button>
                    </div>
                    <div class="iaf-progress" data-iaf-progress hidden>
                        <div class="iaf-progress-track"><div class="iaf-progress-fill" data-iaf-bar></div></div>
                        <div class="iaf-progress-label" data-iaf-label></div>
                    </div>
                    <div class="iaf-totals">
                        <span><?php printf(
                            /* translators: %d: number of converted images */
                            esc_html(_n('Converted so far: %d image', 'Converted so far: %d images', (int) ($stats['converted'] ?? 0), 'ibra-avif')),
                            (int) ($stats['converted'] ?? 0),
                        ); ?></span>
                        <span data-iaf-saved data-saved="<?php echo (int) ($stats['saved'] ?? 0); ?>"><?php printf(
                            /* translators: %s: human-readable bytes */
                            esc_html__('Visitors download %s less', 'ibra-avif'),
                            esc_html(human_bytes((int) ($stats['saved'] ?? 0))),
                        ); ?></span>
                    </div>
                    <p class="iaf-fineprint"><?php esc_html_e('Old JPEG/PNG size files are kept on disk so image links inside existing posts never break; “less” measures what visitors download from now on.', 'ibra-avif'); ?></p>
                </div>

                <div class="iaf-card iaf-pad">
                    <div class="iaf-bulk-head">
                        <div>
                            <div class="iaf-card-title"><?php esc_html_e('Update URLs in existing content', 'ibra-avif'); ?></div>
                            <div class="iaf-card-sub"><?php esc_html_e('Page builders like Elementor copy image URLs as text into their saved pages, so old pages keep serving JPEG even after conversion. This rewrites those URLs to the AVIF versions across posts, pages and builder data — a URL is only touched when its AVIF file actually exists.', 'ibra-avif'); ?></div>
                        </div>
                        <button type="button" class="iaf-run" data-iaf-run-rewrite <?php disabled(! $target); ?>><?php esc_html_e('Update all content', 'ibra-avif'); ?></button>
                    </div>
                    <div class="iaf-progress" data-iaf-rewrite-progress hidden>
                        <div class="iaf-progress-track"><div class="iaf-progress-fill" data-iaf-rewrite-bar></div></div>
                        <div class="iaf-progress-label" data-iaf-rewrite-label></div>
                    </div>
                    <div class="iaf-totals">
                        <span data-iaf-rewritten data-template="<?php esc_attr_e('URLs updated: %d', 'ibra-avif'); ?>"><?php printf(
                            /* translators: %d: number of rewritten URLs */
                            esc_html__('URLs updated: %d', 'ibra-avif'),
                            (int) ($stats['rewritten'] ?? 0),
                        ); ?></span>
                    </div>
                    <p class="iaf-fineprint"><?php esc_html_e('Run this after converting the library. It edits stored content — take a database backup first, as with any bulk content change.', 'ibra-avif'); ?></p>
                </div>
            </div>

            <div class="iaf-savebar">
                <button type="submit" class="iaf-save"><?php esc_html_e('Save settings', 'ibra-avif'); ?></button>
            </div>
        </form>
    </div>
    <?php
}
