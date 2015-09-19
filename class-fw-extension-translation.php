<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
/**
 * Translation extension is a main extension that
 * manage sub extensions in order to implement translation
 * mechanism in WordPress.
 */

/**
 * Class FW_Extension_Translation
 */
class FW_Extension_Translation extends FW_Extension {

	/**
	 * Languages list.
	 *
	 * @access public
	 * @var
	 */
	public $languages_list;
	public $home = '';
	public $lang_tag = 'fw_lang';

	/**
	 * Called after all extensions instances was created
	 * @internal
	 */
	protected function _init() {
		$this->languages_list = new FW_Language();

		$this->home = get_home_url();

		add_action( 'shutdown', array( $this, 'enable_flush_rules' ) );
		add_action( 'fw_extensions_after_activation', array( $this, 'set_flush_rules_option' ) );
		add_action( 'fw_extensions_before_deactivation', array( $this, 'disable_flush_rules' ) );

		if ( is_admin() ) {
			$this->add_admin_actions();
			$this->add_admin_filters();
		}

		add_action( 'widgets_init', array( $this, 'register_language_switcher_widget' ) );

		// rewrite rules add language parameter.
		add_filter( 'rewrite_rules_array', array( $this, 'change_rewrite_rules' ) ); // needed for post type archives


		add_action( 'fw_ext_translation_render_language_switcher', array( $this, 'frontend_language_switcher' ) );
		add_action( 'init', array( $this, 'set_admin_active_language' ), 0 );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ), 0 );
		add_action( 'parse_query', array( $this, 'set_active_cookie' ), 20 );
		add_action( 'parse_query', array( $this, 'parse_query' ), 10, 1 );

		add_filter( 'option_page_on_front', array( $this, 'translate_home_page' ) );
		add_filter( 'option_page_for_posts', array( $this, 'translate_home_page' ) );
		add_filter( 'pre_get_posts', array( $this, 'filter_homepage_query' ) );
		add_filter( 'home_url', array( $this, 'filter_home_url' ), 10, 3 );

	}

	/**
	 * Register language switcher widget.
	 */
	public function register_language_switcher_widget() {
		register_widget( 'FW_Widget_Language_Switcher' );
	}

	/**
	 * Filter home url.
	 *
	 * @param $url
	 * @param $path
	 * @param $schema
	 *
	 * @return mixed|string
	 */
	public function filter_home_url( $url, $path, $schema ) {

		$active_lang = $this->get_frontend_active_language();


		if (
			is_admin() ||
			! did_action( 'template_redirect' ) ||
			did_action( 'login_init' ) ||
			rtrim( $url, '/' ) == rtrim( $this->home, '/' )
		) {
			return $url;
		}

		global $wp_rewrite;
		if ( $wp_rewrite->using_permalinks() ) {
			if ( $path && is_string( $path ) && '/' != $path ) {
				$url = str_replace( $path, '', $url );
				$url = untrailingslashit( $url ) . user_trailingslashit( '/' . $this->lang_tag . '/' . $active_lang . '/' . ltrim( $path, '/' ) );
			} else {
				$url = untrailingslashit( $url ) . user_trailingslashit( '/' . $this->lang_tag . '/' . $active_lang );
			}
		} else {
			$url = add_query_arg( array( $this->lang_tag => $active_lang ), $url );
		}

		return $url;
	}

	public function set_flush_rules_option( $extensions ) {
		if ( in_array( $this->get_name(), array_keys( $extensions ) ) ) {
			fw_set_db_ext_settings_option( $this->get_name(), 'flush_rules_enabled', false );
		}
	}

	/**
	 * Enable flush_rules.
	 */
	public function enable_flush_rules() {
		$flush_rules = (bool) fw_get_db_ext_settings_option( $this->get_name(), 'flush_rules_enabled' );
		if ( $flush_rules === false ) {
			flush_rewrite_rules();
			fw_set_db_ext_settings_option( $this->get_name(), 'flush_rules_enabled', true );

		}
	}

	/**
	 * Disable flush_rules.
	 *
	 * @param $extensions
	 */
	public function disable_flush_rules( $extensions ) {
		if ( in_array( $this->get_name(), array_keys( $extensions ) ) ) {
			remove_filter( 'rewrite_rules_array', array( $this, 'change_rewrite_rules' ) );
			flush_rewrite_rules();
		}
	}

	/**
	 * Add lang query var.
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	function add_custom_query_var( $vars ) {
		$vars[] = $this->lang_tag;

		return $vars;
	}

	/**
	 * Function that resolve WordPress bug with frontpage,
	 * see this link https://core.trac.wordpress.org/ticket/23867
	 *
	 * @param $query
	 */
	public function parse_query( $query ) {
		$qv =& $query->query_vars;

		if ( $query->is_home && 'page' == get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
			$_query = wp_parse_args( $query->query );
			// pagename can be set and empty depending on matched rewrite rules. Ignore an empty pagename.
			if ( isset( $_query['pagename'] ) && '' == $_query['pagename'] ) {
				unset( $_query['pagename'] );
			}

			// this is where will break from core wordpress
			$ignore = array( 'preview', 'page', 'paged', 'cpage' );

			$ignore[] = $this->lang_tag;

			if ( empty( $_query ) || ! array_diff( array_keys( $_query ), $ignore ) ) {
				$query->is_page = true;
				$query->is_home = false;

				$qv['page_id'] = get_option( 'page_on_front' );
				// Correct <!--nextpage--> for page_on_front
				if ( ! empty( $qv['paged'] ) ) {
					$qv['page'] = $qv['paged'];
					unset( $qv['paged'] );
				}
			}
		}

		// reset the is_singular flag after our updated code above
		$query->is_singular = $query->is_single || $query->is_page || $query->is_attachment;
	}

	/**
	 * Function that change rewrite rules for correct working with languages.
	 *
	 * @param $rules
	 *
	 * @return array
	 */
	public function change_rewrite_rules( $rules ) {
		$collector = array();

		global $wp_rewrite;
		$enabled_language_string = implode( '|', array_keys( $this->get_enabled_languages() ) );

		foreach ( $rules as $key => $rule ) {
			$collector[ $this->lang_tag . '/(' . $enabled_language_string . ')/' . str_replace( $wp_rewrite->root, '', $key ) ] = str_replace(
				array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
				array(
					'[9]',
					'[8]',
					'[7]',
					'[6]',
					'[5]',
					'[4]',
					'[3]',
					'[2]',
					'?' . $this->lang_tag . '=$matches[1]&'
				),
				$rule
			);
		}

		$slug                      = $wp_rewrite->root . $this->lang_tag . '/(' . $enabled_language_string . ')/';
		$collector[ $slug . '?$' ] = $wp_rewrite->index . '?' . $this->lang_tag . '=$matches[1]';

		return $collector + $rules;
	}

	/**
	 * @internal
	 */
	public function _admin_action_enqueue_static() {
		wp_enqueue_style(
			'fw-extension-' . $this->get_name() . '-css',
			$this->get_declared_URI( '/static/css/style.css' ),
			array( 'fw-selectize' ),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			$this->get_name() . '-js',
			$this->get_declared_URI( '/static/js/general.js' ),
			array( 'jquery', 'fw-selectize', 'fw' ),
			fw()->manifest->get_version(),
			true
		);

	}

	/**
	 * Set active language and set it to cookie.
	 */
	public function set_active_cookie() {
		if ( ! headers_sent() && ! is_admin() ) {
			$active_lang = get_query_var( $this->lang_tag,
				isset( $_COOKIE['fw_active_lang'] ) ?
					$_COOKIE['fw_active_lang'] :
					$this->get_default_language_code()
			);

			//set to default language if the string is other then enabled languages.
			$active_lang = $this->language_is_enabled( $active_lang ) ? $active_lang : $this->get_default_language_code();

			setcookie( 'fw_active_lang',
				$active_lang,
				time() + 31536000 /* 1 year */,
				COOKIEPATH
			);

			if ( isset( $_COOKIE['fw_active_lang'] ) ) {
				$_COOKIE['fw_active_lang'] = $active_lang;
			}
		}
	}

	/**
	 * filter homepage main query when is not set page_on_front or page_for_posts.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function filter_homepage_query( $query ) {

		if ( //to avoid error msg
			did_action( 'wp' ) &&
			//when page_for_posts and page_on_front not set
			( $query->is_front_page() && $query->is_main_query() ) ||
			//when page_for_posts is set page_on_front not set
			( $query->is_home() && $query->is_main_query() )
		) {
			$query->set( 'meta_key', 'translation_lang' );
			$query->set( 'meta_value', $this->get_frontend_active_language() );
			$query->set( 'meta_compare', '=' );
		}

		return $query;
	}

	/**
	 * Filter frontend homepage by active language.
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function translate_home_page( $id ) {
		//TODO MUST OPTIMIZE AND CACHE ID BECAUSE THIS FILTER IS RUN MANY TIMES
		if ( 'page' == get_option( 'show_on_front' ) && $id > 0 ) {
			$translation_id = get_post_meta( $id, 'translation_id', true );
			$data           = $this->get_child( 'translate-posts' )->query_translation( $translation_id );
			$translation    = $this->get_child( 'translate-posts' )->translation_exists( $data, $this->get_frontend_active_language() );

			if ( ! empty( $translation['post_id'] ) ) {
				$id = $translation['post_id'];
			}
		}

		return $id;
	}

	/**
	 * Group admin actions.
	 */
	private function add_admin_actions() {
		add_action( 'admin_enqueue_scripts', array( $this, '_admin_action_enqueue_static' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_language_switcher' ) );
		add_action( 'fw_extension_settings_form_saved:' . $this->get_name(), array(
			$this,
			'set_default_admin_active_language'
		) );
		add_action( 'fw_extension_settings_form_saved:' . $this->get_name(), array(
			$this,
			'convert_data_to_default_language'
		) );
	}

	/**
	 * Group admin filters.
	 */
	private function add_admin_filters() {
		add_filter( 'get_pages', array( $this, 'filter_dropdown_pages_by_language' ) );
	}

	/**
	 * Filter dropdown select with pages in options-reading.php and customize.php.
	 *
	 * @param $pages
	 *
	 * @return mixed
	 */
	public function filter_dropdown_pages_by_language( $pages ) {
		global $pagenow;

		if ( 'options-reading.php' === $pagenow or 'customize.php' === $pagenow ) {
			foreach ( $pages as $key => $page ) {
				$lang = get_post_meta( $page->ID, 'translation_lang', true );
				if ( $lang !== $this->get_admin_active_language() ) {
					unset( $pages[ $key ] );
				}
			}
		}

		return $pages;
	}

	/**
	 * Verify if language is enabled.
	 *
	 * @param $language
	 *
	 * @return bool
	 */
	public function language_is_enabled( $language ) {
		return array_key_exists( $language, $this->get_enabled_languages() );
	}

	/**
	 * Set admin active language
	 */
	public function set_admin_active_language() {
		if (
			! defined( 'DOING_AJAX' ) &&
			! is_null( $active_lang = FW_Request::GET( 'fw_translate_to' ) ) &&
			$this->language_is_enabled( $active_lang ) &&
			current_user_can( 'edit_user', $user_id = get_current_user_id() )
		) {
			update_user_meta( $user_id, 'fw_active_lang', $active_lang );
		}
	}

	public function set_default_admin_active_language() {
		if ( current_user_can( 'edit_user', $user_id = get_current_user_id() ) ) {
			update_user_meta( $user_id, 'fw_active_lang', $this->get_default_language_code() );

		}
	}

	/**
	 * Get admin active language.
	 *
	 * @return mixed|null
	 */
	public function get_admin_active_language() {
		$active_lang = get_user_meta( get_current_user_id(), 'fw_active_lang', true );

		return empty( $active_lang ) ? $this->get_default_language_code() : $active_lang;
	}

	/**
	 * Get frontend active language.
	 *
	 * @return array|bool|mixed|null|string
	 */
	public function get_frontend_active_language() {
		$active_lang = FW_Request::COOKIE( 'fw_active_lang' );

		return isset( $active_lang ) ? FW_Request::COOKIE( 'fw_active_lang' ) : $this->get_default_language_code();
	}

	/**
	 * Get enabled languages.
	 *
	 * @return array
	 */
	public function get_enabled_languages() {
		return array_merge(
			$this->get_translated_languages(),
			array( $this->get_default_language_code() => $this->get_default_language() )
		);
	}

	/**
	 * Get enabled languages without $lang parameter.
	 *
	 * @param $lang
	 *
	 * @return array
	 */
	public function get_enabled_languages_without( $lang ) {
		return array_diff_key( $this->get_enabled_languages(), array( $lang => $lang ) );
	}

	/**
	 * Generate default backend switch urls.
	 *
	 * @return array
	 */
	private function generate_default_backend_switch_urls() {
		$languages = $this->get_enabled_languages_without( $this->get_admin_active_language() );
		$collector = array();
		foreach ( $languages as $code => $language ) {
			$collector[ $code ] = array(
				'lang_name' => $language['name'],
				'url'       => esc_url( add_query_arg( array( 'fw_translate_to' => $code ) ) )
			);
		}

		return $collector;
	}

	/**
	 * Generate admin bar language switcher
	 *
	 * @param $wp_admin_bar
	 */
	function admin_language_switcher( $wp_admin_bar ) {
		global $pagenow;
		global $post;

		$parent_id         = 'fw_language_switcher';
		$active_lang       = $this->get_admin_active_language();
		$enabled_languages = $this->get_enabled_languages();

		switch ( $pagenow ) {
			case 'post.php' :
			case 'post-new.php' :
				$post_id = $post->ID;

				$admin_switch_urls = $this->get_child( 'translate-posts' )->generate_backend_switch_urls( $post_id );

				break;
			case 'edit-tags.php' :

				$term_id = FW_Request::GET( 'fw_translate_id', FW_Request::GET( 'tag_ID' ) );

				if ( ! is_null( $term_id ) ) {
					$admin_switch_urls = $this->get_child( 'translate-terms' )->generate_backend_switch_urls( $term_id );
				} else {
					$admin_switch_urls = $this->generate_default_backend_switch_urls();
				}
				break;

			case 'nav-menus.php':

				global $nav_menu_selected_id;

				$menu_id = FW_Request::GET( 'menu', $nav_menu_selected_id );

				$admin_switch_urls = $this->get_child( 'translate-menus' )->generate_backend_switch_urls( $menu_id );
				break;

			default :
				$admin_switch_urls = $this->generate_default_backend_switch_urls();
		}

		$args = array(
			'id'     => $parent_id,
			// id of the existing child node (New > Post)
			'title'  => '<span style="cursor:pointer" ><img src="' . fw_ext_translation_get_flag( $active_lang ) . '"> ' . $enabled_languages[ $active_lang ]['name'] . '</span>',
			// alter the title of existing node
			'parent' => false,
			// set parent to false to make it a top level (parent) node,
		);
		$wp_admin_bar->add_node( $args );


		foreach ( $admin_switch_urls as $key => $language ) {

			$args = array(
				'id'     => 'fw-lang-' . $key,
				// id of the existing child node (New > Post)
				'title'  => '<img src="' . fw_ext_translation_get_flag( $key ) . '"> ' . $language['lang_name'],
				// alter the title of existing node
				'parent' => $parent_id,
				// set parent to false to make it a top level (parent) node
				'href'   => $language['url'],
			);

			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Generate frontend language switcher
	 */
	public function frontend_language_switcher() {

		if ( is_single() ) {
			global $post;
			$frontend_urls = $this->get_child( 'translate-posts' )->generate_frontend_switch_urls( $post->ID );

			$this->render_frontend_switch_urls( $frontend_urls );
		}

		if ( is_front_page() && ! is_home() ) {

			$post_id       = 'page' == get_option( 'show_on_front' ) ? get_option( 'page_on_front' ) : get_option( 'page_for_posts' );
			$frontend_urls = $this->get_child( 'translate-posts' )->generate_frontend_switch_urls( $post_id );

			$this->render_frontend_switch_urls( $frontend_urls );
		}

		if ( is_page() and ! is_front_page() ) {

			global $post;

			$frontend_urls = $this->get_child( 'translate-posts' )->generate_frontend_switch_urls( $post->ID );

			$this->render_frontend_switch_urls( $frontend_urls );
		}

		if ( is_archive() && ! is_date() ) {

			$frontend_urls = $this->get_child( 'translate-terms' )->generate_frontend_switch_urls();

			$this->render_frontend_switch_urls( $frontend_urls );
		}

		if ( is_search() || is_date() || is_home() ) {

			$frontend_urls = $this->generate_default_frontend_switch_urls();

			$this->render_frontend_switch_urls( $frontend_urls );
		}

	}

	/**
	 * Render frontend switch urls.
	 *
	 * @param $frontend_urls
	 */
	public function render_frontend_switch_urls( $frontend_urls ) {

		$str = '<ul>';
		foreach ( $frontend_urls as $lang_code => $link ) {
			$str .= '<li><a href="' . $link . '"><img src="' . fw_ext_translation_get_flag( $lang_code ) . '">&nbsp;&nbsp;' . fw_ext_translation_get_language_name( $lang_code ) . '</a></li>';
		}
		$str .= '</ul>';

		echo apply_filters( 'fw_ext_translation_change_render_language_switcher', $str, $frontend_urls );
	}

	/**
	 * Generate default frontend swith urls.
	 *
	 * @return array
	 */
	private function generate_default_frontend_switch_urls() {

		$languages     = $this->get_enabled_languages();
		$frontend_urls = array();
		global $wp_rewrite;
		$current_url = ( fw_current_url() === preg_replace( '/(\/fw_lang\/)(\w+)/ix', '', get_home_url() ) ) ?
			get_home_url() :
			fw_current_url();


		foreach ( $languages as $lang_code => $language ) {
			if ( $wp_rewrite->using_permalinks() ) {
				if ( preg_match( '/(\/fw_lang\/)(\w+)/ix', $current_url ) ) {
					$permalink = preg_replace( '/(\/fw_lang\/)(\w+)/ix', '${1}' . $lang_code, $current_url );
				} else {
					$permalink = $url = untrailingslashit( $current_url ) . user_trailingslashit( '/' . $this->lang_tag . '/' . $lang_code );
				}

				$frontend_urls[ $lang_code ] = $permalink;
			} else {
				$permalink                   = esc_url( remove_query_arg( 'fw_lang', $current_url ) );
				$frontend_urls[ $lang_code ] = esc_url( add_query_arg( array( 'fw_lang' => $lang_code ), $permalink ) );
			}
		}

		return $frontend_urls;
	}

	/**
	 * Get translated languages codes.
	 *
	 * @return array
	 */
	public function get_translated_languages_codes() {
		return (array) fw_get_db_ext_settings_option( $this->get_name(), 'translate-to' );
	}

	public function get_enabled_custom_post_types() {
		return (array) fw_get_db_ext_settings_option( $this->get_name(), 'post_types' );
	}

	/**
	 * Get translated languages.
	 *
	 * @return array
	 */
	public function get_translated_languages() {
		return array_intersect_key( $this->languages_list->get_languages(), array_flip( $this->get_translated_languages_codes() ) );
	}

	/**
	 * Get default language code.
	 *
	 * @return mixed|null
	 */
	public function get_default_language_code() {
		return fw_get_db_ext_settings_option( $this->get_name(), 'default-language', fw_ext_translation_get_default_language() );
	}

	/**
	 * Get default language.
	 *
	 * @return mixed
	 */
	public function get_default_language() {
		return $this->languages_list->get_language( $this->get_default_language_code() );
	}

	/**
	 * Convert data to default language
	 */
	public function convert_data_to_default_language() {

		$convert = fw_get_db_ext_settings_option( $this->get_name(), 'convert' );

		if ( $convert ) {
			$this->get_child( 'translate-menus' )->convert_to_default_language();
			$this->get_child( 'translate-terms' )->convert_to_default_language();
			$this->get_child( 'translate-posts' )->convert_to_default_language();
			$this->get_child( 'translate-widgets' )->convert_to_default_language();
		}
	}
}