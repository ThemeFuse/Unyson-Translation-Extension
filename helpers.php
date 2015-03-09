<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
/**
 * Returns all custom posts types.
 */
function fw_ext_translation_get_custom_post_types() {
	return wp_list_pluck(get_post_types( array( '_builtin' => false, 'public' => true), 'objects' ), 'label');
}

/**
 * Returns all custom taxonomies.
 */
function fw_ext_translation_get_custom_taxonomies() {
	return wp_list_pluck(get_taxonomies( array( '_builtin' => false,'public' => true ), 'objects' ), 'label');
}

/**
 * Returns all enabled post types.
 */
function fw_ext_translation_get_enabled_post_types() {
	return array_merge(get_post_types(array('public'=>true, '_builtin')), fw_ext_translation_get_custom_post_types());
}

/**
 * Return the uri to flag image.
 * @param $code
 *
 * @return string
 */
function fw_ext_translation_get_flag($code){
	$code = fw_ext('translation')->languages_list->get_locale($code);
	return fw_ext('translation')->get_declared_URI('/static/flags/'.$code.'.png');
}

/**
 * Return the language name , ex: English, Română.
 * @param $code
 *
 * @return mixed
 */
function fw_ext_translation_get_language_name($code){
	$data = fw_ext('translation')->languages_list->get_language($code);
	return $data['name'];
}

function fw_ext_translation_generate_language_choices(){
	$languages = fw_ext( 'translation' )->languages_list->get_languages_names();

	foreach($languages as $code=>&$title){
		$languages[$code]= array(
			'text'=>$title,
			'attr'=>array(
				'data-data' =>json_encode(array('src'=>fw_ext_translation_get_flag($code)))
			)
		);
	}

	return $languages;
}

/**
 * Get language from locale.
 *
 * @param $locale
 *
 * @return mixed
 */
function fw_ext_translation_get_language_from_locale($locale){
	return fw_ext('translation')->languages_list->get_language_from_locale($locale);
}

/**
 * Get default language.
 * @return mixed
 */
function fw_ext_translation_get_default_language(){
	return fw_ext_translation_get_language_from_locale(get_locale());
}

/**
 * Get translate to languages.
 * @return array
 */
function fw_ext_translation_get_translate_to_languages(){
	return array_diff(array('de','en','fr'), (array) get_locale());
}

