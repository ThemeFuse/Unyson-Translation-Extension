<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Translate Terms', 'fw' );
$manifest['description'] = __( 'This extension translate terms', 'fw' );

$manifest['version'] = '1.0.0';

$manifest['display'] = 'translation';
$manifest['standalone'] = true;