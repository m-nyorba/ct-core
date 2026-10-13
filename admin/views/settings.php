<?php
/**
 * Settings page for ct-core admin interface.
 *
 * This page allows users to control ct-core features and extensions.
 *
 * @package Ct_Core
 * @subpackage Admin
 * @since 1.0.0
 */

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="ct-core-admin-content">
		<div class="ct-core-section">
			<h2><?php esc_html_e( 'Settings', 'ct-core' ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ct-core_settings' );
				do_settings_sections( 'ct-core_settings' );
				submit_button();
				?>
			</form>
		</div>
	</div>
</div>

<style>
.ct-core-section {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	margin: 20px 0;
}

.ct-core-section h3 {
	margin-top: 0;
}

.ct-core-admin-content table {
	border-bottom: 1px solid #ccd0d4;
}
</style>
