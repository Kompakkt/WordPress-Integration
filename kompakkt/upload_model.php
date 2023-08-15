<?php
global $wpdb;

$table_name = $wpdb->prefix . 'kompakkt_models';

$errors = [];

if ( isset( $_POST['upload_model'] ) ) {
	check_admin_referer( 'kompakkt_upload_model' );

	$title          = sanitize_text_field( $_POST['title'] );
	$description    = sanitize_textarea_field( $_POST['description'] );
	$uploaded_files = [];

	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );

	foreach ( $_FILES['file']['name'] as $key => $value ) {
		if ( $_FILES['file']['name'][ $key ] ) {
			$file = [
				'name'     => $_FILES['file']['name'][ $key ],
				'type'     => $_FILES['file']['type'][ $key ],
				'tmp_name' => $_FILES['file']['tmp_name'][ $key ],
				'error'    => $_FILES['file']['error'][ $key ],
				'size'     => $_FILES['file']['size'][ $key ]
			];

			$upload_overrides = [ 'test_form' => false ];
			$movefile         = wp_handle_upload( $file, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				$uploaded_files[] = $movefile['url'];
			} else {
				$errors[] = $movefile['error']; // Capture the error
			}
		}
	}

	if ( empty( $errors ) ) {
		// Save the title, description, and file paths to the database.
		$wpdb->insert(
			$table_name,
			[
				'title'       => $title,
				'description' => $description,
				'files'       => json_encode( $uploaded_files ), // Store the file paths as a JSON string
			]
		);
	}
}
?>

<script>
	// Convert file size from bytes to human-readable format
	function formatFileSize(size) {
		const byteValueNumberFormatter = Intl.NumberFormat("en", {
			notation: "compact",
			style: "unit",
			unit: "byte",
			unitDisplay: "narrow",
		});
		return byteValueNumberFormatter.format(size);
	}

	function formatFileAsListItem(file, isAlternate) {
		return `<tr ${isAlternate ? 'class="alternate"' : ''}>
			<td>${file.name}</td>
			<td>${formatFileSize(file.size)}</td>
		</tr>`
	}

	function onFilesChanged(event) {
		const files = Array.from(event.target.files);
		const filesList = document.getElementById('file-list');
		filesList.innerHTML = '';
		files.sort((a, b) => b.size - a.size);

		for (let i = 0; i < files.length; i++) {
			filesList.innerHTML += formatFileAsListItem(files[i], i % 2 === 0);
		}
	}
</script>

<div class="wrap">
	<h2>Upload a model</h2>

	<?php if ( ! empty( $errors ) ): ?>
		<div class="notice notice-error">
			<p><?php echo implode( '<br>', array_map( 'esc_html', $errors ) ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" enctype="multipart/form-data" class="form-table">
		<table>
			<tr>
				<th><label for="title">Title:</label></th>
				<td><input required type="text" id="title" name="title" class="regular-text"></td>
			</tr>

			<tr>
				<th><label for="description">Description:</label></th>
				<td><textarea required id="description" name="description" class="large-text"></textarea></td>
			</tr>

			<tr>
				<th><label for="file">3D Models:</label></th>
				<td><input required type="file" id="file" name="file[]" multiple onchange="onFilesChanged(event)"></td>
			</tr>

			<tr>
				<th><span>File list</span></th>
				<td>
					<table class="widefat">
						<thead>
						<tr>
							<td>File name</td>
							<td>File size</td>
						</tr>
						</thead>
						<tbody id="file-list">

						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<th><?php wp_nonce_field( 'kompakkt_upload_model' ); ?></th>
				<td><input type="submit" name="upload_model" value="Upload" class="button button-primary"></td>
			</tr>
		</table>
	</form>
</div>
