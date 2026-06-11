<?php
/**
 * Preview tab panel.
 *
 * @package LayrShift
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="layrshift-preview">
	<p class="layrshift-panel__lead"><?php esc_html_e( 'Review generated block markup, tweak if needed, then publish a draft page.', 'layrshift' ); ?></p>

	<label class="layrshift-composer__label" for="layrshift-template-preview"><?php esc_html_e( 'Block markup', 'layrshift' ); ?></label>
	<textarea
		id="layrshift-template-preview"
		class="layrshift-preview__input"
		rows="18"
		placeholder="<?php esc_attr_e( 'Generate content on the Generate tab first, or paste your own Gutenberg block markup here…', 'layrshift' ); ?>"
	></textarea>

	<div class="layrshift-composer__actions">
		<button type="button" class="layrshift-btn layrshift-btn--primary" id="layrshift-create-draft" disabled>
			<?php esc_html_e( 'Create draft page', 'layrshift' ); ?>
		</button>
		<a href="#" class="layrshift-btn layrshift-btn--secondary" id="layrshift-open-editor" style="display:none;" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Open in editor', 'layrshift' ); ?>
		</a>
		<a class="layrshift-link-quiet" href="<?php echo esc_url( admin_url( 'admin.php?page=layrshift&tab=generate' ) ); ?>">
			<?php esc_html_e( '← Back to Generate', 'layrshift' ); ?>
		</a>
	</div>
	<div id="layrshift-preview-status" class="layrshift-status" aria-live="polite"></div>
</section>
