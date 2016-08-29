<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}


/**
 * Class FW_Extension_Translate_Menus
 */
class FW_Extension_Translate_Menus extends FW_Extension {
	/**
	 * Called after all extensions instances was created
	 * @internal
	 */
	protected function _init() {

		if ( is_admin() ) {
			$this->add_admin_actions();
			$this->add_admin_filters();
		}

		$this->add_frontend_filters();
	}

	/**
	 * Enqueue static in admin.
	 */
	public function _admin_action_add_static() {
		wp_enqueue_script(
			$this->get_name() . '-scripts', $this->get_uri( '/static/js/nav-menu-switcher.js' ),
			array( 'jquery' )
		);

		wp_enqueue_style(
			'fw-extension-' . $this->get_name() . '-css',
			$this->get_uri( '/static/css/style.css' ),
			false,
			fw()->manifest->get_version()
		);
	}

	/**
	 * Add frontend filters.
	 */
	public function add_frontend_filters() {
		add_filter( 'wp_nav_menu_args', array( $this, 'frontend_filter_menu_by_language' ) );
	}

	/**
	 * Filter menu by frontend language.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function frontend_filter_menu_by_language( $args ) {
		$mods = get_theme_mod( 'nav_menu_locations' );

		if ( ! empty( $mods ) ) {
			$term_id        = empty( $args['menu'] ) ? $mods[ $args['theme_location'] ] : $args['menu']->term_id;
			$translation_id = fw_get_term_meta( $term_id, 'translation_id', true );
			$active_menu    = $this->query_translation_by_language( $translation_id, $this->get_parent()->get_frontend_active_language() );

			if ( ! empty( $active_menu ) ) {
				$args['menu'] = $active_menu['term_id'];
			}
		}

		return $args;
	}

	/**
	 * Add admin filters.
	 */
	public function add_admin_filters() {
		add_filter( 'terms_clauses', array( $this, 'change_terms_query' ) );
		add_filter( 'parse_query', array( $this, 'filter_query_by_active_language' ) );
	}

	/**
	 * Filter query by active language in admin.
	 *
	 * @param WP_Query $query
	 *
	 * @return mixed
	 */
	public function filter_query_by_active_language( $query ) {
		global $pagenow;

		if ( 'nav-menus.php' === $pagenow and $query->query['post_type'] != 'nav_menu_item' ) {

			$active_lang = FW_Request::GET( 'fw_translate_to', $this->get_parent()->get_admin_active_language() );
			$query->set( 'meta_key', 'translation_lang' );
			$query->set( 'meta_value', $active_lang );
			$query->set( 'meta_compare', '=' );
		}

		return $query;
	}

	/**
	 * Add admin actions.
	 */
	public function add_admin_actions() {
		add_action( 'wp_loaded', array( $this, 'redirect_to_translated_term' ) );
		add_action( 'admin_enqueue_scripts', array( $this, '_admin_action_add_static' ), 20 );
		add_action( 'create_term', array( $this, 'create_term' ), 10, 3 );
		add_action( 'admin_head', array( $this, 'embed_nav_menu_switcher_template' ) );

	}

	/**
	 * Redirect to translated terms.
	 */
	public function redirect_to_translated_term() {
		global $pagenow;
		if ( 'nav-menus.php' === $pagenow and is_null( FW_Request::GET( 'menu' ) ) and is_null( FW_Request::GET( 'action' ) ) ) {
			$last_edited_menu   = get_user_meta( get_current_user_id(), 'nav_menu_recently_edited', true );
			$translation_id     = fw_get_term_meta( $last_edited_menu, 'translation_id', true );
			$translation_exists = $this->query_translation_by_language( $translation_id, $this->get_parent()->get_admin_active_language() );
			$redirect_menu_id   = empty( $translation_exists ) ? 0 : $translation_exists['term_id'];

			wp_redirect( esc_url( add_query_arg( array(
				'action' => 'edit',
				'menu'   => $redirect_menu_id
			), admin_url( $pagenow ) ) ) );
		}
	}

	/**
	 * Filter terms by active language in backend.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function change_terms_query( $query ) {
		global $pagenow;

		if ( 'nav-menus.php' === $pagenow ) {
			global $wpdb;

			$active_lang = FW_Request::GET( 'fw_translate_to', $this->get_parent()->get_admin_active_language() );
			$query['join'] .= " INNER JOIN $wpdb->termmeta AS fw_tm
								ON t.term_id = fw_tm.fw_term_id AND
								fw_tm.meta_key = 'translation_lang' AND
								fw_tm.meta_value = '" . $active_lang . "'";
		}

		return $query;
	}

	/**
	 * Embed language switcher template.
	 */
	public function embed_nav_menu_switcher_template() {
		global $nav_menu_selected_id;

		$menu_id = FW_Request::GET( 'menu', $nav_menu_selected_id );
		$urls    = $this->generate_backend_switch_urls( $menu_id );

		if ( $menu_id > 0 ) {
			echo '<script id="fw-nav-menu-switcher" type="text/template">';
			echo '<div class="fw-nav-menu-switcher-wrapper">';
			foreach ( $urls as $code => $url ) {
				$class = $urls[ $code ]['type'] === 'edit' ? 'dashicons dashicons-edit' : 'dashicons dashicons-plus';
				echo '<a class="button" href="' . $url['url'] . '">
				<span class="' . $class . '"></span>' . $url['lang_name'] . '</a>';
			}
			echo "</div>";
			echo '</script>';
		}
	}

	/**
	 * Generate backend switch urls.
	 *
	 * @param $term_id
	 *
	 * @return array
	 */
	public function generate_backend_switch_urls( $term_id ) {
		$urls = array();

		$meta_translation_id = fw_get_term_meta( $term_id, 'translation_id', true );
		$translate_id        = empty( $meta_translation_id ) ? $term_id : $meta_translation_id;
		$translate_lang      = (bool) fw_get_term_meta( $term_id, 'translation_lang', true ) ?
			fw_get_term_meta( $term_id, 'translation_lang', true ) :
			$this->get_parent()->get_admin_active_language();

		$translated_terms      = $this->query_translation( $translate_id );
		$translation_languages = $this->get_parent()->get_enabled_languages_without( $translate_lang );


		foreach ( $translation_languages as $code => $language ) {

			$translation_exists = $this->translation_exists( $translated_terms, $code );

			if ( empty( $translation_exists ) ) {

				$args = array(
					'action'          => 'edit',
					'menu'            => 0,
					'fw_translate_to' => $code
				);

				if ( $translate_id > 0 ) {
					$args['fw_translate_id'] = $translate_id;
				}

				$urls[ $code ] = array(
					'lang_name' => $language['name'],
					'url'       => esc_url( add_query_arg( $args, admin_url( 'nav-menus.php' ) ) ),
					'type'      => 'add'
				);
			} else {
				$urls[ $code ] = array(
					'lang_name' => $language['name'],
					'url'       => esc_url( add_query_arg( array(
						'action'          => 'edit',
						'menu'            => $translation_exists['term_id'],
						'fw_translate_to' => $code
					), admin_url( 'nav-menus.php' ) ) ),
					'type'      => 'edit'
				);
			}
		}

		return $urls;
	}

	/**
	 * Group term meta by translation id and translation lang.
	 *
	 * @param $translation_id
	 * @param $lang
	 *
	 * @return array
	 */
	private function query_translation_by_language( $translation_id, $lang ) {
		global $wpdb;

		$sql = "SELECT
			t1.fw_term_id as term_id,
			t1.meta_value as translation_id,
			t2.meta_value as translation_language
			FROM  $wpdb->termmeta as t1
				JOIN  $wpdb->termmeta as t2 ON
				t1.fw_term_id = t2.fw_term_id AND
				t2.meta_key='translation_lang'
			WHERE t1.meta_key='translation_id'
			AND t1.meta_value=%d and t2.meta_value=%s";

		return (array) $wpdb->get_row( $wpdb->prepare( $sql, $translation_id, $lang ), ARRAY_A );
	}

	/**
	 * Group term meta by translation id.
	 *
	 * @param $translation_id
	 *
	 * @return mixed
	 */
	private function query_translation( $translation_id ) {
		global $wpdb;

		$sql = "SELECT
			t1.fw_term_id as term_id,
			t1.meta_value as translation_id,
			t2.meta_value as translation_language
			FROM  $wpdb->termmeta as t1
				JOIN  $wpdb->termmeta as t2 ON
				t1.fw_term_id = t2.fw_term_id AND
				t2.meta_key='translation_lang'
			WHERE t1.meta_key='translation_id'
			AND t1.meta_value=%d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $translation_id ), ARRAY_A );
	}


	/**
	 * Verify if translation exists.
	 *
	 * @param $data
	 * @param $lang
	 *
	 * @return array
	 */
	private function translation_exists( $data, $lang ) {
		foreach ( $data as $translation ) {
			if ( $translation['translation_language'] === $lang ) {
				return $translation;
			}
		}

		return array();
	}

	/**
	 * Update term meta, set term meta active lang.
	 *
	 * @param $term_id
	 * @param $tt_id
	 * @param $taxonomy
	 */
	function create_term( $term_id, $tt_id, $taxonomy ) {
		global $pagenow;

		if ( 'nav-menus.php' === $pagenow ) {
			$language       = FW_Request::GET( 'fw_translate_to', $this->get_parent()->get_admin_active_language() );
			$translation_id = FW_Request::GET( 'fw_translate_id', $term_id );

			fw_update_term_meta( $term_id, 'translation_id', $translation_id );
			fw_update_term_meta( $term_id, 'translation_lang', $language );
		}
	}

	function convert_to_default_language() {

		$menus = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			fw_update_term_meta( $menu->term_id, 'translation_id', $menu->term_id );
			fw_update_term_meta( $menu->term_id, 'translation_lang', $this->get_parent()->get_default_language_code() );
		}
	}
}
