<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Translations', 'fw' );
$manifest['description'] = __( 'This extension lets you translate your website in any language or even add multiple languages for your users to change at their will from the front-end.', 'fw' );

$manifest['version'] = '1.0.9';

$manifest['display'] = true;
$manifest['standalone'] = true;

$manifest['github_update'] = 'ThemeFuse/Unyson-Translation-Extension';

$manifest['github_repo'] = 'https://github.com/ThemeFuse/Unyson-Translation-Extension';
$manifest['uri'] = 'http://manual.unyson.io/en/latest/extension/translation/index.html#content';
$manifest['author'] = 'ThemeFuse';
$manifest['author_uri'] = 'http://themefuse.com/';
