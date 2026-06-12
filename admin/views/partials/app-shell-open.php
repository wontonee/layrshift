<?php
/**
 * App shell header + tab navigation.
 *
 * @package LayrShift
 *
 * @var string $active_tab   mcp|settings
 * @var string $shell_mode   app|dev
 * @var string $dev_title    Optional title for dev tool pages.
 */

defined( 'ABSPATH' ) || exit;

use LayrShift\Admin\Admin;

$active_tab = $active_tab ?? 'mcp';
$shell_mode = $shell_mode ?? 'app';
$dev_title  = $dev_title ?? '';

$tabs = array(
	'mcp'      => __( 'MCP', 'layrshift' ),
	'settings' => __( 'Settings', 'layrshift' ),
);

$tab_body_class = 'layrshift-tab-' . sanitize_html_class( $active_tab );
?>
<div class="wrap layrshift-app layrshift-wrap <?php echo esc_attr( $tab_body_class ); ?>">
	<header class="layrshift-app__header">
		<div class="layrshift-app__top">
			<div class="layrshift-app__brand">
				<span class="layrshift-app__wordmark"><?php esc_html_e( 'LayrShift', 'layrshift' ); ?></span>
				<?php if ( 'dev' === $shell_mode && '' !== $dev_title ) : ?>
					<span class="layrshift-app__context"><?php echo esc_html( $dev_title ); ?></span>
				<?php elseif ( 'app' === $shell_mode ) : ?>
					<span class="layrshift-app__tagline"><?php esc_html_e( 'Connect AI agents', 'layrshift' ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( 'dev' === $shell_mode ) : ?>
				<a class="layrshift-app__back" href="<?php echo esc_url( Admin::app_url( 'settings' ) ); ?>">
					<?php esc_html_e( '← Back to Settings', 'layrshift' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php if ( 'app' === $shell_mode ) : ?>
			<nav class="layrshift-tabs" aria-label="<?php esc_attr_e( 'LayrShift sections', 'layrshift' ); ?>">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a
						class="layrshift-tabs__item<?php echo $slug === $active_tab ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( Admin::app_url( $slug ) ); ?>"
						<?php echo $slug === $active_tab ? ' aria-current="page"' : ''; ?>
					><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</header>
	<main class="layrshift-panel">
