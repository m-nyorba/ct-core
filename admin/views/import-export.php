<?php
/**
 * Import / Export page for ct-core admin interface.
 *
 * @package Ct_Core
 * @subpackage Admin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="ct-core-admin-content">
		<?php if ( ! empty( $import_message ) ) : ?>
			<div class="notice notice-success inline">
				<p><?php echo esc_html( $import_message ); ?></p>
			</div>
		<?php endif; ?>

		<div class="ct-core-section">
			<h3><?php esc_html_e( 'Export Services', 'ct-core' ); ?></h3>
			<p><?php esc_html_e( 'You can export all service items to a CSV file.', 'ct-core' ); ?></p>
			<p>
				<a class="button button-primary" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'ct_core_export_services' => '1',
							'page'                    => 'ct-core-import-export',
						),
						admin_url( 'edit.php?post_type=service' )
					)
				);
				?>
				">
					<?php esc_html_e( 'Export Services CSV', 'ct-core' ); ?>
				</a>
			</p>
		</div>

		<div class="ct-core-section">
			<h3><?php esc_html_e( 'Import Services', 'ct-core' ); ?></h3>
			<p><?php esc_html_e( 'Upload a CSV file containing all service elements to update and replace existing ones.', 'ct-core' ); ?></p>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'ct_core_import_services', 'ct_core_import_services_nonce' ); ?>
				<p>
					<input type="file" name="ct_core_import_services_file" accept=".csv" />
				</p>
				<p>
					<input type="submit" class="button button-primary" value="<?php echo esc_attr__( 'Import Services', 'ct-core' ); ?>" />
				</p>
			</form>
		</div>
	</div>
</div>
