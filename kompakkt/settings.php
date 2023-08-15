<div class="wrap">
	<h1>Settings</h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'kompakkt_settings_group' ); ?>
		<?php do_settings_sections( 'kompakkt_settings_group' ); ?>
		<table class="form-table">
			<tr>
				<td colspan="2">
					<p>The instance URL should point to an instance of the Kompakkt Viewer. If you don't
						provide any instance, the default Kompakkt Viewer will be used
						<a href="https://kompakkt.de/viewer/index.html" alt="Kompakkt Viewer" target="_blank"
						   rel="noopener noreferrer">(https://kompakkt.de/viewer/index.html)</a>
					</p>
				</td>
			</tr>
			<tr>
				<th>Instance URL</th>
				<td>
					<input type="text" name="instance_url"
						   value="<?php echo esc_attr( get_option( 'instance_url' ) ); ?>"
						   placeholder="e.g. https://kompakkt.de/viewer/index.html"
						   style="width: 100%"
					/>
				</td>

			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
