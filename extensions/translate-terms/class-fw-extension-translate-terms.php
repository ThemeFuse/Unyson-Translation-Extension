<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Terms sub extension make all work to
 * translate terms( categories, tags ) in different languages.
 */

/**
 * Class FW_Extension_Translate_Terms
 */
class FW_Extension_Translate_Terms extends FW_Extension {

	/**
	 * Called after all extensions instances was created.
	 *
	 * @internal
	 */
	protected function _init() {
		$this->add_admin_filters();
		$this->add_admin_actions();

		add_filter( 'pre_get_posts', array( $this, 'filter_date_page' ) );

		add_action( 'admin_enqueue_scripts', array( $this, '_admin_action_add_static' ), 20 );

		//add_filter( 'term_link', array( $this, 'term_link_filter' ), 10, 3 );

		add_filter( 'terms_clauses', array( $this, 'change_frontend_terms_query' ) );

	}

	/**
	 * Group admin filters.
	 */
	public function add_admin_filters() {
		add_filter( 'terms_clauses', array( $this, 'change_admin_terms_query' ) );
		add_filter( 'get_edit_term_link', array( $this, 'filter_edit_term_link' ), 10, 4 );
	}

	/**
	 * Group admin actions.
	 */
	public function add_admin_actions() {
		add_action( 'create_term', array( $this, 'create_term' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'wp_list_table_terms_content' ) );
		add_action( 'admin_head', array( $this, 'render_table_links' ) );
		add_action( 'fw_extension_settings_form_saved:' . $this->get_parent()->get_name(), array(
			$this,
			'generate_categories_in_enabled_languages'
		) );
		add_action( 'save_post', array( $this, 'save_default_post_categories' ) );

	}

	/**
	 * This function set post in default category.
	 *
	 * @param $post_ID
	 *
	 * @return mixed
	 */
	public function save_default_post_categories( $post_ID ) {

		if ( wp_is_post_autosave( $post_ID ) || wp_is_post_revision( $post_ID ) ) {
			return $post_ID;
		}

		$categories = wp_get_post_categories( $post_ID );

		if ( count( $categories ) === 1 && $categories[0] === (int) get_option( 'default_category' ) ) {
			$cats = (array) $this->get_default_post_category_by_lang( get_post_meta( $post_ID, 'translation_lang', true ) );
			wp_set_post_categories( $post_ID, $cats );
		}
	}

	/**
	 * Filter main query in date page by active language.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function filter_date_page( $query ) {
		if ( $query->is_date() and $query->is_main_query() ) {
			$query->set( 'meta_key', 'translation_lang' );
			$query->set( 'meta_value', $this->get_parent()->get_frontend_active_language() );
			$query->set( 'meta_compare', '=' );
		}

		return $query;
	}

	/**
	 * Enqueue admin js what render All Languages template.
	 */
	public function _admin_action_add_static() {
		if ( $this->is_public_tax_type() ) {
			wp_enqueue_script(
				$this->get_name() . '-scripts', $this->get_declared_URI( '/static/js/list-table.js' ),
				array( 'jquery' )
			);
		}

	}

	/**
	 * Render template for all Languages link.
	 */
	public function render_table_links() {
		if ( $this->is_public_tax_type() ) {
			echo '<script id="fw-list-table-link" type="text/template">';
			echo '<ul class="subsubsub">';
			echo '<li><a href="' . add_query_arg( array( 'fw_all_languages' => true ) ) . '">' . __( 'All Languages', 'fw' ) . '</a></li>';
			echo '</ul>';
			echo '</script>';
		}
	}

	/**
	 * Add filters, actions to change list table view and add tag/cat form.
	 */
	public function wp_list_table_terms_content() {
		$taxonomies = $this->get_post_types_taxonomies();

		foreach ( $taxonomies as $tax ) {
			add_action( $tax . '_add_form_fields', array( $this, 'add_form' ) );
			add_filter( 'manage_edit-' . $tax . '_columns', array( $this, 'generate_term_column' ) );
			add_filter( 'manage_' . $tax . '_custom_column', array( $this, 'generate_term_column_content' ), 10, 3 );
		}
	}

	/**
	 * Filter terms in frontend and backend by active language.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function change_admin_terms_query( $query ) {
		global $pagenow;
		global $wpdb;

		//filter backend
		if ( $this->is_public_tax_type() ) {

			if ( 'edit-tags.php' === $pagenow and is_null( FW_Request::get( 'fw_all_languages' ) ) ) {
				$active_lang = FW_Request::GET( 'fw_translate_to', $this->get_parent()->get_admin_active_language() );
				$query['join'] .= " INNER JOIN $wpdb->fw_termmeta AS fw_tm
								ON t.term_id = fw_tm.fw_term_id AND
								fw_tm.meta_key = 'translation_lang' AND
								fw_tm.meta_value = '" . $active_lang . "'";
			}

			if ( 'edit-tags.php' === $pagenow and ! is_null( FW_Request::get( 'fw_all_languages' ) ) ) {
				$query['join'] .= " INNER JOIN $wpdb->fw_termmeta AS fw_tm
								ON t.term_id = fw_tm.fw_term_id AND
								fw_tm.meta_key = 'translation_lang'";
				$query['where'] .= " AND fw_tm.meta_value IN ( '" . implode( "','", array_keys( $this->get_parent()->get_enabled_languages() ) ) . "' )";
			}
		}

		return $query;
	}

	/**
	 * Filter frontend terms by active language.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function change_frontend_terms_query( $query ) {

		//TODO IF WILL BE CONFLICTS WITH QUIERIES MUST SEE WHAT TO DO
		if ( ! is_admin() ) {
			global $wpdb;
			$active_lang = $this->get_parent()->get_frontend_active_language();
			$query['join'] .= " INNER JOIN $wpdb->fw_termmeta AS fw_tm
								ON t.term_id = fw_tm.fw_term_id AND
								fw_tm.meta_key = 'translation_lang' AND
								fw_tm.meta_value = '" . $active_lang . "'";
		}

		return $query;
	}

	/**
	 * Verify if the taxonomy is public.
	 * @return bool
	 */
	public function is_public_tax_type() {

		$tax_type = FW_Request::GET( 'taxonomy', FW_Request::POST( 'taxonomy' ) );

		return in_array( $tax_type, $this->get_filtered_tax_types() );
	}

	/**
	 * Get filtered tax types.
	 *
	 * @return array
	 */
	public function get_filtered_tax_types() {
		static $filtered_tax_types = array();
		if ( empty( $filtered_tax_types ) ) {
			$wp_tax_types       = get_taxonomies( array( 'public' => true, '_builtin' => true ) );
			$custom_tax_types   = get_taxonomies( array( 'public' => true, '_builtin' => false ) );
			$filtered_tax_types = array_merge( $wp_tax_types, $custom_tax_types );
		}

		return $filtered_tax_types;
	}

	/**
	 * Filter edit_term link in backend append translate_to language endpoint.
	 *
	 * @param $location
	 * @param $term_id
	 * @param $taxonomy
	 * @param $object_type
	 *
	 * @return string
	 */
	function filter_edit_term_link( $location, $term_id, $taxonomy, $object_type ) {
		return $this->is_public_tax_type() ? add_query_arg( array( 'fw_translate_to' => $this->get_language_from_term( $term_id ) ), $location ) :
			$location;
	}

	/**
	 * Extract language from term.
	 *
	 * @param $term_id
	 *
	 * @return mixed
	 */
	private function get_language_from_term( $term_id ) {
		$lang = fw_get_term_meta( $term_id, 'translation_lang', true );

		return empty( $lang ) ? $this->get_parent()->get_admin_active_language() : $lang;
	}

	/**
	 * Render options that will save active language in terms.
	 *
	 * @param $taxonomy
	 */
	public function add_form( $taxonomy ) {
		$render_options = array();

		if ( $this->is_public_tax_type() ) {

			if ( ! is_null( FW_Request::GET( 'fw_translate_id' ) ) ) {
				$render_options['fw_translate_id'] = array(
					'type'  => 'hidden',
					'value' => FW_Request::GET( 'fw_translate_id' )
				);
			}

			if ( ! is_null( FW_Request::GET( 'fw_second_translate_to' ) ) ) {
				$render_options['fw_second_translate_to'] = array(
					'type'  => 'hidden',
					'value' => FW_Request::GET( 'fw_second_translate_to' )
				);
			}

			if ( ! is_null( FW_Request::GET( 'fw_all_languages' ) ) ) {
				$render_options['fw_all_languages'] = array(
					'type'  => 'hidden',
					'value' => FW_Request::GET( 'fw_all_languages' )
				);
			}

			$translation_lang = FW_Request::GET( 'fw_translate_to', $this->get_parent()->get_admin_active_language() );

			$render_options['fw_translate_to'] = array(
				'type'  => 'hidden',
				'value' => $translation_lang
			);

			echo fw()->backend->render_options( $render_options );
		}
	}

	/**
	 * Save active language in term metadata.
	 *
	 * @param $term_id
	 * @param $tt_id
	 * @param $taxonomy
	 */
	function create_term( $term_id, $tt_id, $taxonomy ) {
		if ( ! $this->is_public_tax_type() && FW_Request::REQUEST( 'action' ) !== 'add-tag' ) {
			return;
		}

		$translation_id     = FW_Request::POST( 'fw_options/fw_translate_id', $term_id );
		$translation_lang   = FW_Request::POST( 'fw_options/fw_translate_to', $this->get_parent()->get_admin_active_language() );
		$translations       = $this->query_translation( $translation_id );
		$translation_exists = $this->translation_exists( $translations, $translation_lang );

		if ( ! empty( $translation_exists ) ) {
			$response    = array(
				'what'   => 'taxonomy',
				'action' => 'add-tag',
				'id'     => new WP_Error( 'translation_exists', __( 'The term translation does already exists.ACTION +++ ' . FW_Request::REQUEST( 'action' ) ) ),
			);
			$xmlResponse = new WP_Ajax_Response( $response );
			$xmlResponse->send();
		}

		fw_update_term_meta( $term_id, 'translation_id', $translation_id );
		fw_update_term_meta( $term_id, 'translation_lang', $translation_lang );

		if ( ! is_null( FW_Request::POST( 'fw_options/fw_second_translate_to' ) ) ) {
			fw_update_term_meta( $translation_id, 'translation_id', $translation_id );
			fw_update_term_meta( $translation_id, 'translation_lang', FW_Request::POST( 'fw_options/fw_second_translate_to' ) );
		}
	}

	/**
	 * Group term meta by translation id.
	 *
	 * @param $translation_id
	 *
	 * @return array
	 */
	private function query_translation( $translation_id ) {
		global $wpdb;

		$sql = "SELECT
			t1.fw_term_id as term_id,
			t1.meta_value as translation_id,
			t2.meta_value as translation_language
			FROM  $wpdb->fw_termmeta as t1
				JOIN  $wpdb->fw_termmeta as t2 ON
				t1.fw_term_id = t2.fw_term_id AND
				t2.meta_key='translation_lang'
			WHERE t1.meta_key='translation_id'
			AND t1.meta_value=%d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $translation_id ), ARRAY_A );
	}

	/**
	 *  Generate th column in terms list tables.
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	function generate_term_column( $columns ) {

		if ( $this->is_public_tax_type() ) {

			$languages = is_null( FW_Request::GET( 'fw_all_languages', FW_Request::POST( 'fw_options/fw_all_languages' ) ) ) ?
				$this->get_parent()->get_enabled_languages_without( $this->get_parent()->get_admin_active_language() ) :
				$this->get_parent()->get_enabled_languages();
			$collector = array();

			foreach ( $languages as $code => $lang ) {
				$collector[ 'fw_lang_' . $code ] = '<img src="' . fw_ext_translation_get_flag( $code ) . '" >';
			}

			$columns = array_merge( $columns, $collector );
		}

		return $columns;
	}

	/**
	 * Generate td cells with add , edit links in active languages.
	 *
	 * @param $content
	 * @param $column
	 * @param $term_id
	 *
	 * @return mixed
	 */
	function generate_term_column_content( $content, $column, $term_id ) {

		if ( $this->is_public_tax_type() ) {

			$urls = $this->generate_backend_switch_urls( $term_id, true );

			if ( in_array( $column_code = str_replace( 'fw_lang_', '', $column ), array_keys( $urls ) ) ) {

				if ( $column_code === $this->get_language_from_term( $term_id ) ) {
					$class = 'dashicons dashicons-yes';
				} else {
					$class = ( $urls[ $column_code ]['type'] === 'edit' ? 'dashicons dashicons-edit' : 'dashicons dashicons-plus' );
				}

				echo '<a href="' .
				     esc_attr( $urls[ $column_code ]['url'] ) .
				     '" class="' . $class . '" >' .
				     '</a>';
			}
		}

		return $content;
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
	 * Generate backend switch urls.
	 *
	 * @param $term_id
	 *
	 * @return array
	 */
	public function generate_backend_switch_urls( $term_id, $all_languages = false ) {
		$urls = array();

		$meta_translation_id = fw_get_term_meta( $term_id, 'translation_id', true );
		$translate_id        = ( $meta_translation_id === '' ) ? $term_id : $meta_translation_id;
		$translate_lang      = ( fw_get_term_meta( $term_id, 'translation_lang', true ) === '' ) ?
			$this->get_parent()->get_admin_active_language() :
			fw_get_term_meta( $term_id, 'translation_lang', true );

		$taxonomy              = FW_Request::GET( 'taxonomy', FW_Request::POST( 'taxonomy' ) );
		$translated_terms      = $this->query_translation( $translate_id );
		$translation_languages = ( $all_languages === true ) ? $this->get_parent()->get_enabled_languages() :
			$this->get_parent()->get_enabled_languages_without( $translate_lang );


		foreach ( $translation_languages as $code => $language ) {

			$translation_exists = $this->translation_exists( $translated_terms, $code );

			if ( empty( $translation_exists ) ) {

				if ( $meta_translation_id > 0 ) {
					$urls[ $code ] = array(
						'lang_name' => $language['name'],
						'url'       => add_query_arg(
							array(
								'taxonomy'        => $taxonomy,
								'fw_translate_to' => $code,
								'fw_translate_id' => $translate_id
							), admin_url( 'edit-tags.php' ) ),
						'type'      => 'add'
					);
				} else {
					$urls[ $code ] = array(
						'lang_name' => $language['name'],
						'url'       => add_query_arg(
							array(
								'taxonomy'               => $taxonomy,
								'fw_translate_to'        => $code,
								'fw_translate_id'        => $translate_id,
								'fw_second_translate_to' => $this->get_parent()->get_admin_active_language()
							), admin_url( 'edit-tags.php' ) ),
						'type'      => 'add'
					);
				}
			} else {
				$urls[ $code ] = array(
					'lang_name' => $language['name'],
					'url'       => get_edit_term_link( $translation_exists['term_id'], $taxonomy ),
					'type'      => 'edit'
				);
			}
		}

		return $urls;
	}

	/**
	 * Get post types taxonomies.
	 *
	 * @return array
	 */
	public function get_post_types_taxonomies() {
		$collector = array();

		$wp_post_types       = get_post_types( array( 'public' => true, '_builtin' => true ) );
		$custom_post_types   = get_post_types( array( 'public' => true, '_builtin' => false ) );
		$filtered_post_types = array_merge( $wp_post_types, $custom_post_types );


		foreach ( $filtered_post_types as $key => $post_type ) {

			$have_categories = $this->get_taxonomies( $key );
			$have_tags       = $this->get_taxonomies( $key, false );

			if ( ! empty( $have_categories ) ) {
				$collector = array_merge( $collector, $have_categories );
			}

			if ( ! empty( $have_tags ) ) {
				$collector = array_merge( $collector, $have_tags );
			}
		}

		return $collector;
	}

	/**
	 * Get taxonomies.
	 *
	 * @param $post_type
	 * @param bool $hierarchy
	 *
	 * @return array
	 */
	private function get_taxonomies( $post_type, $hierarchy = true ) {
		return get_taxonomies( array(
			'object_type'  => array( $post_type ),
			'hierarchical' => $hierarchy,
			'show_ui'      => true
		) );
	}

	/**
	 * Convert terms ( tags, categories ) to default language.
	 */
	public function convert_terms_to_default_language() {

		$taxonomies = $this->get_filtered_tax_types();
		$terms      = $this->get_untranslated_terms( $taxonomies );

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				fw_update_term_meta( $term->term_id, 'translation_id', $term->term_id );
				fw_update_term_meta( $term->term_id, 'translation_lang', $this->get_parent()->get_default_language_code() );
			}
		}
	}

	/**
	 * Generate uncategorized cats in enabled languages.
	 */
	public function generate_categories_in_enabled_languages() {

		$default_cat_id   = get_option( 'default_category' );
		$translation_lang = fw_get_term_meta( $default_cat_id, 'translation_lang', true );


		if ( $translation_lang == false ) {
			fw_update_term_meta( $default_cat_id, 'translation_id', $default_cat_id );
			fw_update_term_meta( $default_cat_id, 'translation_lang', $this->get_parent()->get_default_language_code() );
		}

		$languages = ( $translation_lang == false ) ?
			$this->get_parent()->get_enabled_languages_without( $this->get_parent()->get_default_language_code() ) :
			$this->get_parent()->get_enabled_languages();

		foreach ( $languages as $code => $lang ) {
			$term_slug = 'uncategorized-' . $code;
			if ( ! get_term_by( 'slug', $term_slug, 'category' ) && $code != $translation_lang ) {
				$data = wp_insert_term( $term_slug, 'category', array(
					'slug' => $term_slug
				) );

				if ( ! is_wp_error( $data ) ) {
					$term_id = $data['term_id'];

					fw_update_term_meta( $term_id, 'translation_id', $default_cat_id );
					fw_update_term_meta( $term_id, 'translation_lang', $code );
				}
			}
		}
	}

	/**
	 * Get default post category by $code language.
	 *
	 * @param $code
	 *
	 * @return mixed|void
	 */
	public function get_default_post_category_by_lang( $code ) {
		$default_cat_id = get_option( 'default_category' );
		$data           = $this->query_translation( $default_cat_id );
		$result         = $this->translation_exists( $data, $code );

		return empty( $result ) ? $default_cat_id : $result['term_id'];
	}

	/**
	 * Get untranslated terms.
	 *
	 * @param $taxonomies
	 * @param array $args
	 *
	 * @return array|WP_Error
	 */
	public function get_untranslated_terms( $taxonomies, $args = array() ) {

		$args = wp_parse_args( $args );

		add_filter( 'terms_clauses', array( $this, 'terms_clauses_filter' ) );

		return get_terms( $taxonomies, $args );
	}

	/**
	 * Add where clauses that will select untranslated terms.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function terms_clauses_filter( $query ) {

		global $wpdb;

		$query['where'] .= " AND NOT EXISTS (SELECT * FROM $wpdb->fw_termmeta as fw_tm WHERE
										t.term_id = fw_tm.fw_term_id AND
								fw_tm.meta_key = 'translation_lang') ";
		remove_filter( current_filter(), __FUNCTION__ );

		return $query;
	}

	############################ FRONT END PART ###########################

	/**
	 * Generate frontend switch urls.
	 *
	 * @return array
	 */
	function generate_frontend_switch_urls() {
		global $wp_query;

		$urls             = array();
		$languages        = $this->get_parent()->get_enabled_languages();
		$taxonomy         = $wp_query->queried_object->taxonomy;
		$term_id          = $wp_query->queried_object->term_id;
		$translation_id   = fw_get_term_meta( $term_id, 'translation_id', true );
		$translated_terms = $this->query_translation( $translation_id );

		foreach ( $languages as $code => $name ) {
			$translation_data = $this->translation_exists( $translated_terms, $code );

			if ( ! empty( $translation_data ) ) {
				global $wp_rewrite;

				$term_link = get_term_link( (int) $translation_data['term_id'], $taxonomy );

				if ( $wp_rewrite->using_permalinks() ) {
					$term_link     = preg_replace( '/(\/fw_lang\/)(\w+)/ix', '${1}' . $code, $term_link );
					$urls[ $code ] = $term_link;
				} else {
					remove_query_arg( 'fw_lang', $term_link );
					$urls[ $code ] = add_query_arg( array( 'fw_lang' => $code ), $term_link );
				}
			} else {
				//translation did not exists for this post
				$urls[ $code ] = preg_replace( '/(\/fw_lang\/)(\w+)/ix', '${1}' . $code, get_home_url( '/' ) );
			}
		}

		return $urls;
	}

	/**
	 * Filter term links in frontend to add active language endpoint.
	 *
	 * @param $termlink
	 * @param $term
	 * @param $taxonomy
	 *
	 * @return string
	 */
	public function term_link_filter( $termlink, $term, $taxonomy ) {
		if ( ! is_admin() ) {
			global $wp_rewrite;

			$lang = $this->get_parent()->get_frontend_active_language();

			if ( $wp_rewrite->using_permalinks() ) {
				$termlink = $termlink . 'fw_lang/' . $lang . '/';
			} else {
				$termlink = add_query_arg( array( 'fw_lang' => $lang ), $termlink );
			}
		}

		return $termlink;
	}
}
