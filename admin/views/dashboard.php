<?php
/**
 * Dashboard page for ct-core admin interface.
 *
 * @package Ct_Core
 * @subpackage Admin
 * @since 1.0.0
 */

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="ct-core-admin-header">
		<div class="ct-core-header-info">
			<h2><?php esc_html_e( 'Welcome to CT Core', 'ct-core' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Extensions and functionality for themes that support the CT Core plugin.', 'ct-core' ); ?>
			</p>
		</div>
	</div>

	<div class="ct-core-admin-content">
		<div class="ct-core-section">
			<h3><?php esc_html_e( 'Theme Compatibility', 'ct-core' ); ?></h3>
			
			<?php
			$current_theme = wp_get_theme();
			$is_supported  = Ct_Core_API::instance()->theme_has_support();
			?>

			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Theme', 'ct-core' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ct-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<strong><?php echo esc_html( $current_theme->get( 'Name' ) ); ?></strong>
							<br>
							<small><?php echo esc_html( $current_theme->get( 'Version' ) ); ?></small>
						</td>
						<td>
							<?php if ( $is_supported ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
								<?php esc_html_e( 'Supported', 'ct-core' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
								<?php esc_html_e( 'Not Supported', 'ct-core' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php if ( $is_supported ) : ?>
			<div class="ct-core-section">
				<h3><?php esc_html_e( 'Active Features', 'ct-core' ); ?></h3>
				<p><?php esc_html_e( 'The following features are available for your theme:', 'ct-core' ); ?></p>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Advanced customization options', 'ct-core' ); ?></li>
					<li><?php esc_html_e( 'Additional blocks and patterns', 'ct-core' ); ?></li>
					<li><?php esc_html_e( 'Extended theme features', 'ct-core' ); ?></li>
				</ul>
			</div>
		<?php else : ?>
			<div class="notice notice-warning inline">
				<p>
					<?php esc_html_e( 'For this plugin to work, it is recommended to use a theme that supports integration with CT Core.', 'ct-core' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>
.ct-core-admin-header {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	margin: 20px 0;
}

.ct-core-section {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	margin: 20px 0;
}

.ct-core-section h3 {
	margin-top: 0;
}
</style>
