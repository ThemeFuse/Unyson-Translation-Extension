<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Translation', 'fw' );
$manifest['description'] = __( 'This extension translate your blog', 'fw' );

$manifest['version'] = '1.0.0';

$manifest['display'] = true;
$manifest['standalone'] = true;
