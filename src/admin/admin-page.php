<table class="widefat striped">
	<thead>
		<tr>
			<th><span class="screen-reader-text">Seleccionar</span></th>
			<th>Página</th>
			<th>Última actualización</th>
			<th>Tiene enlaces entrantes</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $items as $item ) : ?>
		<tr>
			<td>
				<input type="checkbox" name="" id="">
			</td>
			<td>
				<?php echo get_the_title( $item ); ?>
			</td>
			<td>
				<?php echo get_post_field( 'post_modified', $item ); ?>
			</td>
			<td>
				<span class="dashicons dashicons-cross"></span>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
