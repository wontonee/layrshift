<?php
/**
 * Generate tab panel.
 *
 * @package LayrShift
 *
 * @var array<string, mixed> $pro_settings
 */

defined( 'ABSPATH' ) || exit;

use LayrShift\Pro\EditorDetector;
use LayrShift\Pro\ProSettings;

$configured  = ProSettings::is_configured();
$has_api_key = ProSettings::has_api_key();
?>
<?php if ( ! $configured ) : ?>
	<div class="layrshift-callout layrshift-callout--warning">
		<?php esc_html_e( 'Enable Template Studio and save a Gemini API key below to start generating.', 'layrshift' ); ?>
	</div>
<?php endif; ?>

<section class="layrshift-strip" aria-label="<?php esc_attr_e( 'AI settings', 'layrshift' ); ?>">
	<div class="layrshift-strip__head">
		<label class="layrshift-strip__toggle">
			<input type="checkbox" id="layrshift-pro-enabled" value="1" <?php checked( ! empty( $pro_settings['enabled'] ) ); ?> />
			<span><?php esc_html_e( 'Template Studio', 'layrshift' ); ?></span>
		</label>
		<a class="layrshift-strip__link" href="https://aistudio.google.com/apikey" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Gemini API key →', 'layrshift' ); ?></a>
	</div>
	<div class="layrshift-strip__grid">
		<div class="layrshift-strip__field">
			<label for="layrshift-studio-api-key"><?php esc_html_e( 'API key', 'layrshift' ); ?></label>
			<input
				type="password"
				id="layrshift-studio-api-key"
				class="layrshift-input"
				autocomplete="off"
				placeholder="<?php echo esc_attr( $has_api_key ? '••••••••••••••••' : __( 'Paste your key', 'layrshift' ) ); ?>"
			/>
		</div>
		<div class="layrshift-strip__field">
			<label for="layrshift-studio-model"><?php esc_html_e( 'Model', 'layrshift' ); ?></label>
			<input type="text" id="layrshift-studio-model" class="layrshift-input" value="<?php echo esc_attr( (string) $pro_settings['gemini_model'] ); ?>" placeholder="gemini-2.0-flash" />
		</div>
		<div class="layrshift-strip__field">
			<label for="layrshift-studio-default-editor"><?php esc_html_e( 'Default editor', 'layrshift' ); ?></label>
			<select id="layrshift-studio-default-editor" class="layrshift-select">
				<option value="auto" <?php selected( 'auto', (string) $pro_settings['default_editor'] ); ?>><?php esc_html_e( 'Auto-detect', 'layrshift' ); ?></option>
				<option value="gutenberg" <?php selected( 'gutenberg', (string) $pro_settings['default_editor'] ); ?>><?php esc_html_e( 'Gutenberg', 'layrshift' ); ?></option>
				<option value="elementor" <?php selected( 'elementor', (string) $pro_settings['default_editor'] ); ?>><?php esc_html_e( 'Elementor (soon)', 'layrshift' ); ?></option>
			</select>
		</div>
	</div>
	<div class="layrshift-strip__foot">
		<button type="button" class="layrshift-btn layrshift-btn--secondary" id="layrshift-save-studio-settings"><?php esc_html_e( 'Save AI settings', 'layrshift' ); ?></button>
		<span class="spinner layrshift-settings-spinner"></span>
		<span id="layrshift-settings-status" class="layrshift-status" aria-live="polite"></span>
	</div>
</section>

<section class="layrshift-composer">
	<label class="layrshift-composer__label" for="layrshift-template-prompt"><?php esc_html_e( 'What should this page look like?', 'layrshift' ); ?></label>
	<textarea
		id="layrshift-template-prompt"
		class="layrshift-composer__input"
		rows="10"
		placeholder="<?php esc_attr_e( 'Landing page for a bakery with hero image, featured products grid, customer testimonials, and a newsletter signup…', 'layrshift' ); ?>"
	></textarea>

	<div class="layrshift-composer__meta">
		<div class="layrshift-field">
			<label for="layrshift-template-title"><?php esc_html_e( 'Page title', 'layrshift' ); ?></label>
			<input type="text" id="layrshift-template-title" class="layrshift-input" placeholder="<?php esc_attr_e( 'Optional — AI will suggest one', 'layrshift' ); ?>" />
		</div>
		<div class="layrshift-field">
			<label for="layrshift-template-editor"><?php esc_html_e( 'Editor', 'layrshift' ); ?></label>
			<select id="layrshift-template-editor" class="layrshift-select">
				<option value="auto"><?php esc_html_e( 'Auto-detect', 'layrshift' ); ?></option>
				<?php foreach ( EditorDetector::list_editors() as $editor ) : ?>
					<option value="<?php echo esc_attr( $editor['slug'] ); ?>" <?php disabled( ! $editor['available'] ); ?>>
						<?php echo esc_html( $editor['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="layrshift-composer__actions">
		<button type="button" class="layrshift-btn layrshift-btn--primary layrshift-btn--large" id="layrshift-generate-template" <?php disabled( ! $configured ); ?>>
			<?php esc_html_e( 'Generate preview', 'layrshift' ); ?>
		</button>
		<span class="spinner layrshift-studio-spinner"></span>
	</div>
	<div id="layrshift-studio-status" class="layrshift-status" aria-live="polite"></div>
</section>
