<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
$post_types = array();//fw_ext_translation_get_custom_post_types(); //uncomment if filter from checkboxes
$taxonomies = array();//fw_ext_translation_get_custom_taxonomies(); //uncomment if filter from checkboxes
$collector  = array();
if ( ! empty( $post_types ) ) {
	$collector = array_merge( $collector, array(
		'post-types' => array(
			'label'   => __( 'Custom Post Types', 'fw' ),
			'type'    => 'checkboxes',
			'choices' => fw_ext_translation_get_custom_post_types(),
			'desc'    => __( 'Activate translations for custom post types.', 'fw' )
		)
	) );
}
if ( ! empty( $taxonomies ) ) {
	$collector = array_merge( $collector, array(
		'taxonomies' => array(
			'label'   => __( 'Custom Taxonomies', 'fw' ),
			'type'    => 'checkboxes',
			'choices' => fw_ext_translation_get_custom_taxonomies(),
			'desc'    => __( 'Activate translations for custom post types.', 'fw' )
		)
	) );
}

$merged_options = array_merge( array(
	'default-language' => array(
		'type'    => 'select',
		'label'   => __( 'Default Language', 'fw' ),
		'desc'    => __( 'This is the default language of your website.', 'fw' ),
		'value'   => 'ro',
		'attr'=> array('class'=>'fw-trans-selectize fw-tt'),
		'choices' => fw_ext_translation_generate_language_choices()
	),
	'translate-to'     => array(
		'type'    => 'select-multiple',
		'label'   => __( 'Translate to', 'fw' ),
		'desc'    => __( 'Choose the languages you want your website translated to.', 'fw' ),
		'value'   => array( 'en', 'fr' ),
		'attr'=> array('class'=>'fw-selectize fw-trans-selectize'),
		'choices' => fw_ext_translation_generate_language_choices()
	),
/*	'convert'          => array(
		'type'     => 'runnable',
		'value'    => 'This script have no runs',
		'label'    => __( 'Convert data', 'fw' ),
		'desc'     => __( 'There are posts, pages, categories or tags without language set. Do you want to set them all to default language.', 'fw' ),
		'help'     => __( 'Help tip', 'fw' ),
		'content'  => __( 'Run this script' ),
		'callback' => array( 'translation', 'convert_data_to_default_language' )
	),*/
	'convert'          => array(
		'type'     => 'switch',
		'label'    => __( 'Convert data', 'fw' ),
		'desc'     => __( 'Set to default language the posts, pages categories or tags that don\'t have a language set ?', 'fw' ),
	)
), $collector );

$options = array(
	'general-tab' => array(
		'title'   => '',
		'type'    => 'box',
		'options' => $merged_options
	)
);
