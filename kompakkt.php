<?php
/**
 * Plugin Name:       Kompakkt
 * Description:       WordPress plugin to integrate the Kompakkt Viewer as a block
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Kai Niebes
 * License:           AGPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.en.html
 * Text Domain:       kompakkt
 *
 * @package           create-block
 */

/**
 * CORS Headers
 */
function kompakkt_add_cors_headers() {
	header( 'Access-Control-Allow-Origin: *' );
}

add_action( 'init', 'kompakkt_add_cors_headers' );

function kompakkt_viewer_dependency() {
	wp_enqueue_script(
		'kompakkt-external-dependency',
		'https://cdn.jsdelivr.net/gh/Kompakkt/StandaloneViewer/kompakkt-standalone.js',
		array(),
		null,
		false,
	);
}

add_action( 'enqueue_block_editor_assets', 'kompakkt_viewer_dependency' );
add_action( 'enqueue_block_assets', 'kompakkt_viewer_dependency' );

function create_block_kompakkt_block_init() {
	register_block_type( __DIR__ . '/build' );
}

add_action( 'init', 'create_block_kompakkt_block_init' );

function kompakkt_install() {
	global $wpdb;

	$tables = [
		'kompakkt_models' => "(
			id mediumint(9) NOT NULL AUTO_INCREMENT,
	        title text NOT NULL,
	        description text NOT NULL,
	        files text NOT NULL,
	        PRIMARY KEY (id)
	    )",
		'kompakkt_model_settings' => "(
			id mediumint(9) NOT NULL,
			settings text NOT NULL,
			PRIMARY KEY (id)
		)",
	];

	$sql = "";

	$charset_collate = $wpdb->get_charset_collate();
	foreach ($tables as $table_name => $schema) {
		$prefixed_table_name = $wpdb->prefix . $table_name;
		$statement = "CREATE TABLE $prefixed_table_name $schema $charset_collate;";
		$sql .= $statement;
	}

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

register_activation_hook( __FILE__, 'kompakkt_install' );

function kompakkt_allow_mimes( $mimes ) {
	$mimes['fbx']     = 'application/octet-stream';
	$mimes['bin']     = 'application/octet-stream';
	$mimes['gltf']    = 'model/gltf+json';
	$mimes['glb']     = 'model/gltf-binary';
	$mimes['obj']     = 'model/obj';
	$mimes['mtl']     = 'model/mtl';
	$mimes['stl']     = 'model/stl';
	$mimes['babylon'] = 'model/babylon+json';

	return $mimes;
}

add_filter( 'upload_mimes', 'kompakkt_allow_mimes' );

function get_mime_type_from_ext( $ext ) {
	$mime_types = [
		'fbx'     => 'application/octet-stream',
		'bin'     => 'application/octet-stream',
		'gltf'    => 'model/gltf+json',
		'glb'     => 'model/gltf-binary',
		'obj'     => 'model/obj',
		'mtl'     => 'model/mtl',
		'stl'     => 'model/stl',
		'babylon' => 'model/babylon+json',
	];

	return $mime_types[ $ext ] ?? 'application/octet-stream';
}

function kompakkt_fix_filetypes( $data, $file, $filename, $mimes, $real_mime ) {

	if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
		return $data;
	}

	$wp_file_type = wp_check_filetype( $filename, $mimes );
	$ext          = $wp_file_type['ext'];

	$data['ext']  = $ext;
	$data['type'] = get_mime_type_from_ext( $ext );

	return $data;
}

add_filter( 'wp_check_filetype_and_ext', 'kompakkt_fix_filetypes', 10, 5 );

function kompakkt_delete_model() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'kompakkt_models';

	// Check if the user is on the "manage-models" page
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'kompakkt-manage-models' ) {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['model'] ) ) {
			$wpdb->delete( $table_name, [ 'id' => $_GET['model'] ] );
			// Redirect to refresh the page
			wp_redirect( admin_url( 'admin.php?page=kompakkt-manage-models' ) );
			exit;
		}
	}
}

add_action( 'admin_init', 'kompakkt_delete_model' );

function kompakkt_register_settings() {
	register_setting( 'kompakkt_settings_group', 'instance_url' );
}

add_action( 'admin_init', 'kompakkt_register_settings' );

/**
 * Menu
 */

function kompakkt_menu() {
	$menu_svg_encoded = 'data:image/svg+xml;base64,' . base64_encode( '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path fill-rule="evenodd" clip-rule="evenodd" d="M10.6317 0.526317C10.6317 0.235641 10.396 0 10.1053 0C9.81466 0 9.57902 0.235641 9.57902 0.526317V4C9.57902 4.29068 9.81466 4.52632 10.1053 4.52632C10.396 4.52632 10.6317 4.29068 10.6317 4V0.526317ZM17.0128 16.7097C16.7652 16.5573 16.441 16.6345 16.2887 16.8821C16.1363 17.1296 16.2135 17.4538 16.4611 17.6061L19.1979 19.2903C19.4455 19.4427 19.7697 19.3655 19.922 19.1179C20.0743 18.8704 19.9972 18.5462 19.7496 18.3939L17.0128 16.7097ZM3.71147 16.8821C3.55913 16.6345 3.23495 16.5573 2.98739 16.7097L0.250549 18.3939C0.00299213 18.5462 -0.0741942 18.8704 0.0781486 19.1179C0.230491 19.3655 0.554674 19.4427 0.802231 19.2903L3.53907 17.6061C3.78663 17.4538 3.86382 17.1296 3.71147 16.8821ZM10.3913 5.83807C10.1716 5.6932 9.8914 5.67644 9.65606 5.79411L6.39628 7.42399C6.13629 7.55398 6.03091 7.87012 6.1609 8.13011C6.2909 8.3901 6.60704 8.49548 6.86703 8.36549L9.96382 6.81711L13.9708 9.45998L9.71366 12.0994L5.61676 9.00395C5.12226 8.63032 4.41548 8.99696 4.43613 9.61639L4.62899 15.402C4.63564 15.6016 4.72299 15.7899 4.87106 15.9239L9.22591 19.864C9.40661 20.0275 9.67591 20.0455 9.87683 19.9076L15.1539 16.2862C15.3541 16.1487 15.4738 15.9215 15.4738 15.6786L15.4738 9.47369C15.4738 9.2968 15.3849 9.13174 15.2372 9.03434L10.3913 5.83807ZM10.0843 13.1081L14.4211 10.4193L14.4211 15.5123L10.1053 18.4741V14.9474C10.1053 14.6567 9.8697 14.4211 9.57902 14.4211C9.28835 14.4211 9.05271 14.6567 9.05271 14.9474V18.2877L5.67657 15.2331L5.51023 10.2428L9.25185 13.0698C9.49477 13.2533 9.82555 13.2685 10.0843 13.1081Z" fill="black"/>
	</svg>' );
	add_menu_page( "Kompakkt", "Kompakkt", "manage_options", "kompakkt-main", "kompakkt_main_page", $menu_svg_encoded );
	add_submenu_page( "kompakkt-main", "Upload a model", "Upload", "manage_options", "kompakkt-upload-model", "kompakkt_upload_model" );
	add_submenu_page( "kompakkt-main", "Manage models", "Manage", "manage_options", "kompakkt-manage-models", "kompakkt_manage_models" );
	add_submenu_page( "kompakkt-main", "Settings", "Settings", "manage_options", "kompakkt-settings", "kompakkt_settings_page" );
}

add_action( "admin_menu", "kompakkt_menu" );

/**
 * REST API
 */

add_action( 'rest_api_init', function () {
	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter( 'rest_pre_serve_request', function ( $value ) {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
		header( 'Access-Control-Allow-Credentials: true' );

		return $value;
	} );
}, 15 );

function kompakkt_get_models() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'kompakkt_models';
	$models     = $wpdb->get_results( "SELECT * FROM $table_name" );

	return $models;
}

function kompakkt_get_model_files($id, $filename) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'kompakkt_models';
	$model      = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = $id" );

	// Decode the JSON-encoded files array
	$files = json_decode( $model->files );

	// Check if there are any files
	if ( ! empty( $files ) ) {
		$file_url = array_filter( $files, function ( $file ) use ( $filename ) {
			return str_contains( $file, $filename );
		} )[0];

		if ( empty( $file_url ) ) {
			return new WP_Error( 'file_not_found', 'File not found', array( 'status' => 404 ) );
		}

		$file_path = WP_CONTENT_DIR . str_replace( content_url(), '', $file_url );

		if ( file_exists( $file_path ) ) {
			$file_ext  = pathinfo( $file_path, PATHINFO_EXTENSION );
			$file_type = get_mime_type_from_ext( $file_ext );
			$file_size = filesize( $file_path );

			header( "Content-Type: $file_type" );
			header( "Content-Length: " . $file_size );
			header( "Content-Disposition: attachment; filename=\"$filename\"" ); // Add this line

			$handle = fopen( $file_path, 'rb' );

			if ( $handle === false ) {
				return new WP_Error( 'file_not_found', 'File not found', array( 'status' => 404 ) );
			}

			while ( ! feof( $handle ) ) {
				echo fread( $handle, 8192 );
				flush();
			}

			fclose( $handle );
			exit;
		} else {
			return new WP_Error( 'file_not_found', 'File not found', array( 'status' => 404 ) );
		}
	} else {
		return new WP_Error( 'no_files', 'No files found for this model', array( 'status' => 404 ) );
	}
}

function kompakkt_get_model_by_id( WP_REST_Request $request ) {
	$id_with_filename = $request->get_param( 'id' );
	// Split the id_with_filename into id and filename at the /
	$id_and_filename = explode( '/', $id_with_filename );
	$id              = $id_and_filename[0];
	$filename        = $id_and_filename[1];

	if ($filename == "settings.json") {
		global $wpdb;
		$table_name = $wpdb->prefix . 'kompakkt_model_settings';
		$entry = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = $id" );
		return json_decode(json_decode($entry->settings));
	}

	return kompakkt_get_model_files($id, $filename);
}

function kompakkt_get_instance_url() {
	return get_option( 'instance_url' );
}

function kompakkt_set_model_settings(WP_REST_Request $request) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'kompakkt_model_settings';

	$id = $request->get_param( 'id' );
	$settings = $request->get_body();

	$wpdb->delete(
		$table_name,
		[ 'id' => $id ]
	);

	$wpdb->insert(
		$table_name,
		[
			'id'    => $id,
			'settings'  => $settings
		]
	);

	return ['status' => 'OK', 'message' => 'Settings saved', 'settings' => json_decode(json_decode($settings))];
}

function kompakkt_get_model_settings(WP_REST_Request $request) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'kompakkt_model_settings';

	try {
		$id = $request->get_param( 'id' );
		$entry = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = $id" );

		if (!$entry || empty($entry->settings)) {
			return ['status' => 'error', 'message' => 'No settings found', 'settings' => []];
		}

		return json_decode(json_decode($entry->settings));
	}
	catch (Exception $e) {
		return ['status' => 'error', 'message' => 'No settings found', 'settings' => []];
	}
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'kompakkt/v1', '/models', [
		'methods'             => 'GET',
		'callback'            => 'kompakkt_get_models',
		'permission_callback' => '__return_true',
	] );
	register_rest_route( 'kompakkt/v1', '/model', [
		'methods'             => 'GET',
		'callback'            => 'kompakkt_get_model_by_id',
		'permission_callback' => '__return_true',
	] );
	register_rest_route( 'kompakkt/v1', '/instance-url', [
		'methods'             => 'GET',
		'callback'            => 'kompakkt_get_instance_url',
		'permission_callback' => '__return_true',
	] );
	register_rest_route( 'kompakkt/v1', '/model-settings', [
		'methods'             => 'GET',
		'callback'            => 'kompakkt_get_model_settings',
		'permission_callback' => '__return_true',
	]);
	register_rest_route( 'kompakkt/v1', '/model-settings', [
		'methods'             => 'POST',
		'callback'            => 'kompakkt_set_model_settings',
		'permission_callback' => '__return_true',
	]);
} );

/**
 * Pages
 */

function kompakkt_main_page() {
	include "kompakkt/main_page.php";
}

function kompakkt_settings_page() {
	include "kompakkt/settings.php";
}

function kompakkt_manage_models() {
	include "kompakkt/manage_models.php";
}

function kompakkt_upload_model() {
	include "kompakkt/upload_model.php";
}


