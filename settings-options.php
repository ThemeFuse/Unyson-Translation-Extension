<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
$options = array(
	'general-tab' => array(
		'title'   => '',
		'type'    => 'box',
		'options' => array(
			'default-language' => array(
				'type'    => 'select',
				'label'   => __( 'Default Language', 'fw' ),
				'desc'    => __( 'This is the default language of your website.', 'fw' ),
				'value'   => fw_ext_translation_get_default_language(),
				'attr'=> array('class'=>'fw-trans-selectize fw-tt'),
				'choices' => fw_ext_translation_generate_language_choices()
			),
			'translate-to'     => array(
				'type'    => 'select-multiple',
				'label'   => __( 'Translate to', 'fw' ),
				'desc'    => __( 'Choose the languages you want your website translated to.', 'fw' ),
				'value'   => fw_ext_translation_get_translate_to_languages(),
				'attr'=> array('class'=>'fw-selectize fw-trans-selectize'),
				'choices' => fw_ext_translation_generate_language_choices()
			),
			'convert'          => array(
				'type'     => 'switch',
				'label'    => __( 'Convert data', 'fw' ),
				'desc'     => __( 'Set to default language the posts, pages categories or tags that don\'t have a language set ?', 'fw' ),
			),
			'sidebar' => array(
				'type' => 'sidebar-picker'
			)
		)
	)
);
