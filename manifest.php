<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Translations', 'fw' );
$manifest['description'] = __( 'This extension lets you translate your website in any language or even add multiple languages for your users to change at their will from the front-end.', 'fw' );

$manifest['version'] = '1.0.1';

$manifest['display'] = true;
$manifest['standalone'] = true;

$manifest['github_update'] = 'ThemeFuse/Unyson-Translation-Extension';
