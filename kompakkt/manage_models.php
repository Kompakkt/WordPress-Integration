<?php
global $wpdb;
$table_name = $wpdb->prefix . 'kompakkt_models';
$models     = $wpdb->get_results( "SELECT * FROM $table_name" );
?>

<div class="wrap">
	<h1>Uploaded models</h1>

	<?php
	echo '<table class="widefat">';
	echo '<thead>';
	echo '<tr><th>Title</th><th>Description</th><th>Files</th><th>Action</th></tr>';
	echo '</thead>';
	echo '<tbody>';
	$index = 0;
	foreach ( $models as $model ) {
		$files = json_decode( $model->files );
		echo $index % 2 ? '<tr class="alternate">' : '<tr>';
		echo '<td>' . esc_html( $model->title ) . '</td>';
		echo '<td>' . esc_html( $model->description ) . '</td>';
		echo '<td><ul style="margin: 0; list-style: inside">';
		foreach ( $files as $file ) {
			echo '<li>' . esc_html( basename( $file ) ) . '</li>';
		}
		echo '</ul></td>';
		echo '<td>
			<a href="?page=edit-model-settings&model=' . $model->id . '" class="button button-primary">Settings</a>
			<a href="?page=manage-models&action=delete&model=' . $model->id . '" class="button button-secondary button-destructive" style="color: #DC3232; border-color: #DC3232">Delete</a>
		</td>';
		echo '</tr>';
		$index ++;
	}
	echo '</tbody>';
	echo '</table>';
	?>
</div>
