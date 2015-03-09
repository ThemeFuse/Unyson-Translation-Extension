<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
/**
 * Helper Class that contains
 * language codes and names.
 */

/**
 * Class FW_Language
 */
class FW_Language {


	/**
	 * Languages codes.
	 *
	 * @access private
	 * @var array
	 */
	private $codes = array();

	/**
	 * Languages names.
	 *
	 * @access private
	 * @var array
	 */
	private $languages = array(
		'af'  => array( 'locale' => 'af', 'name' => 'Afrikaans', 'direction' => '', ),
		'ar'  => array( 'locale' => 'ar', 'name' => 'العربية', 'direction' => 'rtl', ),
		'az'  => array( 'locale' => 'az', 'name' => 'Azərbaycan', 'direction' => '', ),
		'be'  => array( 'locale' => 'bel', 'name' => 'Беларуская мова', 'direction' => '', ),
		'bg'  => array( 'locale' => 'bg_BG', 'name' => 'български', 'direction' => '', ),
		'bs'  => array( 'locale' => 'bs_BA', 'name' => 'Bosanski', 'direction' => '', ),
		'ca'  => array( 'locale' => 'ca', 'name' => 'Català', 'direction' => '', ),
		'cs'  => array( 'locale' => 'cs_CZ', 'name' => 'Čeština', 'direction' => '', ),
		'cy'  => array( 'locale' => 'cy', 'name' => 'Cymraeg', 'direction' => '', ),
		'da'  => array( 'locale' => 'da_DK', 'name' => 'Dansk', 'direction' => '', ),
		'de'  => array( 'locale' => 'de_DE', 'name' => 'Deutsch', 'direction' => '', ),
		'el'  => array( 'locale' => 'el', 'name' => 'Ελληνικά', 'direction' => '', ),
		'en'  => array( 'locale' => 'en_US', 'name' => 'English', 'direction' => '', ),
		'eo'  => array( 'locale' => 'eo', 'name' => 'Esperanto', 'direction' => '', ),
		'es'  => array( 'locale' => 'es_VE', 'name' => 'Español', 'direction' => '', ),
		'et'  => array( 'locale' => 'et', 'name' => 'Eesti', 'direction' => '', ),
		'eu'  => array( 'locale' => 'eu', 'name' => 'Euskara', 'direction' => '', ),
		'fa'  => array( 'locale' => 'fa_IR', 'name' => 'فارسی', 'direction' => 'rtl', ),
		'fi'  => array( 'locale' => 'fi', 'name' => 'Suomi', 'direction' => '', ),
		'fo'  => array( 'locale' => 'fo', 'name' => 'Føroyskt', 'direction' => '', ),
		'fr'  => array( 'locale' => 'fr_FR', 'name' => 'Français', 'direction' => '', ),
		'fy'  => array( 'locale' => 'fy', 'name' => 'Frysk', 'direction' => '', ),
		'gd'  => array( 'locale' => 'gd', 'name' => 'Gàidhlig', 'direction' => '', ),
		'gl'  => array( 'locale' => 'gl_ES', 'name' => 'Galego', 'direction' => '', ),
		'he'  => array( 'locale' => 'he_IL', 'name' => 'עברית', 'direction' => 'rtl', ),
		'hi'  => array( 'locale' => 'hi_IN', 'name' => 'हिन्दी', 'direction' => '', ),
		'hr'  => array( 'locale' => 'hr', 'name' => 'Hrvatski', 'direction' => '', ),
		'hu'  => array( 'locale' => 'hu_HU', 'name' => 'Magyar', 'direction' => '', ),
		'id'  => array( 'locale' => 'id_ID', 'name' => 'Bahasa Indonesia', 'direction' => '', ),
		'is'  => array( 'locale' => 'is_IS', 'name' => 'Íslenska', 'direction' => '', ),
		'it'  => array( 'locale' => 'it_IT', 'name' => 'Italiano', 'direction' => '', ),
		'ja'  => array( 'locale' => 'ja', 'name' => '日本語', 'direction' => '', ),
		'jv'  => array( 'locale' => 'jv_ID', 'name' => 'Basa Jawa', 'direction' => '', ),
		'ka'  => array( 'locale' => 'ka_GE', 'name' => 'ქართული', 'direction' => '', ),
		'kk'  => array( 'locale' => 'kk', 'name' => 'Қазақ тілі', 'direction' => '', ),
		'ko'  => array( 'locale' => 'ko_KR', 'name' => '한국어', 'direction' => '', ),
		'ku'  => array( 'locale' => 'ckb', 'name' => 'کوردی', 'direction' => 'rtl', ),
		'lo'  => array( 'locale' => 'lo', 'name' => 'ພາສາລາວ', 'direction' => '', ),
		'lt'  => array( 'locale' => 'lt_LT', 'name' => 'Lietuviškai', 'direction' => '', ),
		'lv'  => array( 'locale' => 'lv', 'name' => 'Latviešu valoda', 'direction' => '', ),
		'mk'  => array( 'locale' => 'mk_MK', 'name' => 'македонски јазик', 'direction' => '', ),
		'mn'  => array( 'locale' => 'mn', 'name' => 'Монгол хэл', 'direction' => '', ),
		'ms'  => array( 'locale' => 'ms_MY', 'name' => 'Bahasa Melayu', 'direction' => '', ),
		'my'  => array( 'locale' => 'my_MM', 'name' => 'ဗမာစာ', 'direction' => '', ),
		'nb'  => array( 'locale' => 'nb_NO', 'name' => 'Norsk Bokmål', 'direction' => '', ),
		'ne'  => array( 'locale' => 'ne_NP', 'name' => 'नेपाली', 'direction' => '', ),
		'nl'  => array( 'locale' => 'nl_NL', 'name' => 'Nederlands', 'direction' => '', ),
		'nn'  => array( 'locale' => 'nn_NO', 'name' => 'Norsk Nynorsk', 'direction' => '', ),
		'pl'  => array( 'locale' => 'pl_PL', 'name' => 'Polski', 'direction' => '', ),
		'pt'  => array( 'locale' => 'pt_PT', 'name' => 'Português', 'direction' => '', ),
		'ro'  => array( 'locale' => 'ro_RO', 'name' => 'Română', 'direction' => '', ),
		'ru'  => array( 'locale' => 'ru_RU', 'name' => 'Русский', 'direction' => '', ),
		'si'  => array( 'locale' => 'si_LK', 'name' => 'සිංහල', 'direction' => '', ),
		'sk'  => array( 'locale' => 'sk_SK', 'name' => 'Slovenčina', 'direction' => '', ),
		'sl'  => array( 'locale' => 'sl_SI', 'name' => 'Slovenščina', 'direction' => '', ),
		'so'  => array( 'locale' => 'so_SO', 'name' => 'Af-Soomaali', 'direction' => '', ),
		'sq'  => array( 'locale' => 'sq', 'name' => 'Shqip', 'direction' => '', ),
		'sr'  => array( 'locale' => 'sr_RS', 'name' => 'Српски језик', 'direction' => '', ),
		'su'  => array( 'locale' => 'su_ID', 'name' => 'Basa Sunda', 'direction' => '', ),
		'sv'  => array( 'locale' => 'sv_SE', 'name' => 'Svenska', 'direction' => '', ),
		'ta'  => array( 'locale' => 'ta_LK', 'name' => 'தமிழ்', 'direction' => '', ),
		'th'  => array( 'locale' => 'th', 'name' => 'ไทย', 'direction' => '', ),
		'tr'  => array( 'locale' => 'tr_TR', 'name' => 'Türkçe', 'direction' => '', ),
		'ug'  => array( 'locale' => 'ug_CN', 'name' => 'Uyƣurqə', 'direction' => '', ),
		'uk'  => array( 'locale' => 'uk', 'name' => 'Українська', 'direction' => '', ),
		'ur'  => array( 'locale' => 'ur', 'name' => 'اردو', 'direction' => 'rtl', ),
		'uz'  => array( 'locale' => 'uz_UZ', 'name' => 'Oʻzbek', 'direction' => '', ),
		'vec' => array( 'locale' => 'vec', 'name' => 'Vèneto', 'direction' => '', ),
		'vi'  => array( 'locale' => 'vi', 'name' => 'Tiếng Việt', 'direction' => '', ),
		'zh'  => array( 'locale' => 'zh_TW', 'name' => '中文 (台灣)', 'direction' => '', ),
	);

	/**
	 * Set $languages array.
	 */
	function __construct() {
		$this->codes = array_keys( $this->languages );
	}

	/**
	 * Get codes.
	 *
	 * @return array
	 */
	public function get_codes() {
		return $this->codes;
	}

	/**
	 * Get Languages.
	 *
	 * @return array
	 */
	public function get_languages() {
		return $this->languages;
	}

	/**
	 * Get Languages.
	 *
	 * @return array
	 */
	public function get_languages_names() {
		return wp_list_pluck($this->languages, 'name');
	}

	/**
	 * Verify if code exists.
	 *
	 * @param $codes
	 *
	 * @return bool
	 */
	public function code_exists( $codes ) {
		return (bool) array_intersect( (array) $codes, $this->codes );
	}

	/**
	 * Get Language.
	 *
	 * @param $code
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_language( $code ) {
		if ( $this->code_exists( $code ) ) {
			return $this->languages[ $code ];
		} else {
			throw new Exception( 'Undefined language code in $languages array' );
		}
	}

	/**
	 * Get languages without $codes param.
	 *
	 * @param $codes
	 *
	 * @return array
	 */
	public function get_languages_without( $codes ) {
		return array_diff_key( $this->languages, (array) array_flip( $codes ) );
	}

	/**
	 * Get locale.
	 * @param $code
	 *
	 * @return mixed
	 */
	public function get_locale($code) {
		return $this->languages[$code]['locale'];
	}

	/**
	 * Get language from locale.
	 * @param $locale
	 *
	 * @return string
	 */
	public function get_language_from_locale($locale){
		$keys = array_keys(wp_list_pluck($this->languages, 'locale'), $locale);
		return empty($keys)? '' : $keys[0];
	}

}
