<?php
/**
 * The template used to display file form fields.
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.6.13
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

if ( array_key_exists( 'uploader', $view_args['field'] ) && ! $view_args['field']['uploader'] ) {
	return charitable_template( 'form-fields/file.php', $view_args );
}

$form          = $view_args['form'];
$field         = $view_args['field'];
$classes       = $view_args['classes'];
$is_required   = isset( $field['required'] ) ? $field['required'] : false;
$placeholder   = isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
$size          = isset( $field['size'] ) ? $field['size'] : 'thumbnail';
$max_uploads   = isset( $field['max_uploads'] ) ? $field['max_uploads'] : 1;
$max_file_size = isset( $field['max_file_size'] ) ? $field['max_file_size'] : wp_max_upload_size();
$pictures      = isset( $field['value'] ) ? $field['value'] : array();

if ( wp_is_mobile() ) {
	$classes .= ' mobile';
}

if ( ! is_array( $pictures ) ) {
	$pictures = array( $pictures );
}

foreach ( $pictures as $i => $picture ) {
	if ( false === strpos( $picture, 'img' ) && ! wp_attachment_is_image( $picture ) ) {
		unset( $pictures[ $i ] );
	}
}

$has_max_uploads = count( $pictures ) >= $max_uploads;
$params          = array(
	'runtimes'            => 'html5,silverlight,flash,html4',
	'file_data_name'      => 'async-upload',
	'container'           => $field['key'] . '-dragdrop',
	'browse_button'       => $field['key'] . '-browse-button',
	'drop_element'        => $field['key'] . '-dragdrop-dropzone',
	'multiple_queues'     => true,
	'url'                 => admin_url( 'admin-ajax.php' ),
	'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
	'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
	'multipart'           => true,
	'urlstream_upload'    => true,
	'filters'             => array(
		array(
			'title'      => _x( 'Allowed Image Files', 'image upload', 'charitable' ),
			'extensions' => 'jpg,jpeg,gif,png',
		),
	),
	'multipart_params'    => array(
		'field_id'    => $field['key'],
		'action'      => 'charitable_plupload_image_upload',
		'_ajax_nonce' => wp_create_nonce( "charitable-upload-images-{$field[ 'key' ]}" ),
		'post_id'     => isset( $field['parent_id'] ) && strlen( $field['parent_id'] ) ? $field['parent_id'] : '0',
		'size'        => $size,
		'max_uploads' => $max_uploads,
	),
);

wp_enqueue_script( 'charitable-plup-fields' );
wp_enqueue_style( 'charitable-plup-styles' );

?>
<div id="charitable_field_<?php echo $field['key']; ?>" class="<?php echo $classes; ?>">
	<?php if ( isset( $field['label'] ) ) : ?>
		<label>
			<?php echo $field['label']; ?>
			<?php if ( $is_required ) : ?>
				<abbr class="required" title="required">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<?php if ( isset( $field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo $field['help']; ?></p>
	<?php endif ?>
	<div id="<?php echo $field['key']; ?>-dragdrop"
		class="charitable-drag-drop"
		data-max-size="<?php echo $max_file_size; ?>"
		data-images="<?php echo $field['key']; ?>-dragdrop-images"
		data-params="<?php echo esc_attr( wp_json_encode( $params ) ); ?>">
		<div id="<?php echo $field['key']; ?>-dragdrop-dropzone" class="charitable-drag-drop-dropzone" <?php echo $has_max_uploads ? 'style="display:none;"' : ''; ?>>
			<p class="charitable-drag-drop-info"><?php echo 1 == $max_uploads ? _x( 'Drop image here', 'image upload', 'charitable' ) : _x( 'Drop images here', 'image upload plural', 'charitable' ); ?></p>
			<p><?php _ex( 'or', 'image upload', 'charitable' ); ?></p>
			<p class="charitable-drag-drop-buttons">
				<button id="<?php echo $field['key']; ?>-browse-button" class="button" type="button"><?php _ex( 'Select Files', 'image upload', 'charitable' ); ?></button>
			</p>
		</div>
		<div class="charitable-drag-drop-image-loader" style="display: none;">
			<p class="loader-title"><?php _e( 'Uploading...', 'charitable' ); ?></p>
			<ul class="images"></ul>
		</div>
		<ul id="<?php echo $field['key']; ?>-dragdrop-images" class="charitable-drag-drop-images charitable-drag-drop-images-<?php echo $max_uploads; ?>">
			<?php
			foreach ( $pictures as $image ) :
				charitable_template(
					'form-fields/picture-preview.php',
					array(
						'image' => $image,
						'field' => $field,
					)
				);
			endforeach;
			?>
		</ul>
	</div>
</div>
