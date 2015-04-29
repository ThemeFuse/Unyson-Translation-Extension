<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Posts sub extension make all work to
 * translate posts, pages in different languages.
 */

/**
 * Class FW_Extension_Translate_Posts
 */
class FW_Extension_Translate_Posts extends FW_Extension {
	/**
	 * Called after all extensions instances was created.
	 *
	 * @internal
	 */
	protected function _init() {

		if ( is_admin() ) {
			$this->add_admin_filters();
			$this->add_admin_actions();
		}
	}

	/**
	 * Get current post type.
	 *
	 * @return null|string
	 */
	public function get_current_post_type() {
		global $post, $typenow, $current_screen;

		//we have a post so we can just get the post type from that
		if ( $post && $post->post_type ) {
			return $post->post_type;
		} //check the global $typenow - set in admin.php
		elseif ( $typenow ) {
			return $typenow;
		} //check the global $current_screen object - set in sceen.php
		elseif ( $current_screen && $current_screen->post_type ) {
			return $current_screen->post_type;
		} //lastly check the post_type querystring
		elseif ( isset( $_REQUEST['post_type'] ) ) {
			return sanitize_key( $_REQUEST['post_type'] );
		}

		//we do not know the post type!
		return null;
	}

	/**
	 * Verify if post type is public.
	 *
	 * @return bool
	 */
	public function is_public_post_type() {

		$post_type = $this->get_current_post_type();

		return in_array( $post_type, $this->get_filtered_post_types() );
	}

	/**
	 * Get filtered post types.
	 *
	 * @return array
	 */
	public function get_filtered_post_types() {
		static $filtered_post_types = array();

		if ( empty( $filtered_post_types ) ) {
			$wp_post_types       = get_post_types( array( 'public' => true, '_builtin' => true ) );
			$custom_post_types   = get_post_types( array( 'public' => true, '_builtin' => false ) );
			$filtered_post_types = array_merge( $wp_post_types, $custom_post_types );
		}

		return $filtered_post_types;
	}

	/**
	 * Add all languages link.
	 */
	public function add_all_languages_link() {
		$current_screen = get_current_screen();
		if ( $this->is_public_post_type() ) {
			add_filter( 'views_' . $current_screen->id, array( $this, 'set_all_languages_link' ) );
		}
	}

	/**
	 * Add query arg fw_all_language to all links when is set fw_all_languages get parameter.
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function convert_wp_list_table_urls( $data ) {
		foreach ( $data as $key => &$partial ) {
			$dom_element = new DOMDocument();
			$dom_element->loadHTML( $partial );
			$obj = $dom_element->getElementsByTagName( 'a' )->item( 0 );
			$obj->setAttribute( 'href', add_query_arg( array( 'fw_all_languages' => true ), $obj->getAttribute( 'href' ) ) );
			$data[ $key ] = $dom_element->saveXML( $obj, LIBXML_NOEMPTYTAG );
		}

		return $data;
	}

	/**
	 * Set all languages link in array.
	 *
	 * @param $views
	 *
	 * @return mixed
	 */
	public function set_all_languages_link( $views ) {

		if ( ! is_null( FW_Request::GET( 'fw_all_languages' ) ) ) {
			$views = $this->convert_wp_list_table_urls( $views );
		}
		$views['all_languages'] = '<a href="' . add_query_arg( array( 'fw_all_languages' => true ) ) . '">All Languages</a>';

		return $views;
	}

	/**
	 * Filter backend main query by active language.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function filter_query_by_active_language( $query ) {
		global $pagenow;

		if ( $this->is_public_post_type() ) {
			if ( 'edit.php' === $pagenow && is_null( FW_Request::get( 'fw_all_languages' ) ) ) {
				$active_lang = FW_Request::GET( 'fw_translate_to', $this->get_parent()->get_admin_active_language() );
				$query->set( 'meta_key', 'translation_lang' );
				$query->set( 'meta_value', $active_lang );
				$query->set( 'meta_compare', '=' );
			} elseif ( 'edit.php' === $pagenow && ! is_null( FW_Request::get( 'fw_all_languages' ) ) ) {
				$query->set( 'meta_key', 'translation_lang' );
				$query->set( 'meta_compare', '=' );
			}
		}

		return $query;
	}

	/**
	 * Group admin filters.
	 */
	private function add_admin_filters() {
		add_filter( 'manage_posts_columns', array( $this, 'generate_translation_columns' ), 10, 2 );//+
		add_filter( 'manage_pages_columns', array( $this, 'generate_translation_columns' ), 10, 2 );//+
		add_filter( 'get_edit_post_link', array( $this, 'change_edit_link' ), 10, 2 );//+
		add_filter( 'parse_query', array( $this, 'filter_query_by_active_language' ) );//+
		add_filter( 'terms_clauses', array( $this, 'change_terms_query' ) );//+
		add_filter( 'posts_where', array( $this, 'filter_posts_where' ) );//+

		if ( is_null( FW_Request::GET( 'fw_all_languages' ) ) ) {
			add_filter( 'wp_count_posts', array( $this, 'count_post_by_language' ), 10, 3 );
		} else{
			add_filter( 'wp_count_posts', array( $this, 'count_post_by_all_languages' ), 10, 3 );
		}
	}

	/**
	 * Filter posts active only in enabled languages.
	 *
	 * @param $where
	 *
	 * @return string
	 */
	public function filter_posts_where( $where ) {
		global $pagenow, $wpdb;
		if ( 'edit.php' === $pagenow &&
		     ! is_null( FW_Request::get( 'fw_all_languages' ) ) &&
		     $this->is_public_post_type()
		) {
			$where .= " AND $wpdb->postmeta.meta_value IN ( '" . implode( "','", array_keys( $this->get_parent()->get_enabled_languages() ) ) . "' )";
		}

		return $where;
	}

	/**
	 * Group admin actions.
	 */
	private function add_admin_actions() {
		add_action( 'manage_posts_custom_column', array( $this, 'generate_translation_table_data' ), 10, 2 );//+
		add_action( 'manage_pages_custom_column', array( $this, 'generate_translation_table_data' ), 10, 2 );//+
		//TODO must use maybe in next iteration
		//add_action('fw_post_options', array($this, '_action_admin_add_post_options'), 10, 2);
		add_action( 'save_post', array( $this, 'save_translations_meta' ) );
		add_action( 'current_screen', array( $this, 'add_all_languages_link' ) );
	}

	/**
	 * Filter terms(categories, tags) from posts in active language
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function change_terms_query( $query ) {
		global $pagenow;
		if ( $this->is_public_post_type() && ( 'post-new.php' === $pagenow ||
		                                       ( ! is_null( FW_Request::GET( 'action' ) ) && FW_Request::GET( 'action' ) === 'ajax-tag-search' ) ||
		                                       'post.php' === $pagenow )
		) {

			global $wpdb;

			$active_lang = FW_Request::GET( 'fw_translate_to', $this->get_parent()->get_admin_active_language() );
			$query['join'] .= " INNER JOIN $wpdb->fw_termmeta AS fw_tm
								ON t.term_id = fw_tm.fw_term_id AND
								fw_tm.meta_key = 'translation_lang' AND
								fw_tm.meta_value = '" . $active_lang . "'";
		}

		return $query;
	}

	/**
	 * Add active language endpoint to edit post links in backend.
	 *
	 * @param $post_link
	 * @param $post_id
	 *
	 * @return string
	 */
	public function change_edit_link( $post_link, $post_id ) {
		return ( $this->is_public_post_type() ) ?
			add_query_arg( array( 'fw_translate_to' => $this->get_language_from_post( $post_id ) ), $post_link ) :
			$post_link;
	}

	/**
	 * Get saved post language.
	 *
	 * @param $post_id
	 *
	 * @return mixed
	 */
	private function get_language_from_post( $post_id ) {
		return get_post_meta( $post_id, 'translation_lang', true );
	}

	/**
	 * Group post meta by translation id.
	 *
	 * @param $translation_id
	 *
	 * @return mixed
	 */
	public function query_translation( $translation_id ) {
		global $wpdb;

		$sql = "SELECT
			t1.post_id as post_id,
			t1.meta_value as translation_id,
			t2.meta_value as translation_language
			FROM  $wpdb->postmeta as t1
				JOIN  $wpdb->postmeta as t2 ON
				t1.post_id = t2.post_id AND
				t2.meta_key='translation_lang'
				JOIN $wpdb->posts as p ON
				t2.post_id = p.ID
				AND p.post_status = 'publish'
			WHERE t1.meta_key='translation_id'
			AND t1.meta_value <> ''
			AND t1.meta_value=%d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $translation_id ), ARRAY_A );
	}

	/**
	 * Save active language in post meta.
	 *
	 * @param $post_id
	 */
	public function save_translations_meta( $post_id ) {
		global $pagenow;
		if ( $this->is_public_post_type() && 'post-new.php' === $pagenow ) {
			$is_translable = isset( $_GET['fw_translate_id'] ) && isset( $_GET['fw_translate_to'] );

			$translation_id   = $is_translable ? $_GET['fw_translate_id'] : $post_id;
			$translation_lang = $is_translable ? $_GET['fw_translate_to'] : $this->get_parent()->get_admin_active_language();

			update_post_meta( $post_id, 'translation_id', $translation_id );
			update_post_meta( $post_id, 'translation_lang', $translation_lang );

			if ( isset( $_GET['fw_update_post'] ) and $_GET['fw_update_post'] == true ) {
				update_post_meta( $translation_id, 'translation_id', $translation_id );
				update_post_meta( $translation_id, 'translation_lang', $this->get_parent()->get_admin_active_language() );
			}
		}
	}

	/**
	 * Render translation metabox.
	 * @internal
	 */
	public function _action_admin_add_post_options( $options, $post_type ) {
		$options[] = array(
			'general' => array(
				'context'  => 'side',
				'title'    => __( 'Translations', 'fw' ),
				'type'     => 'box',
				'priority' => 'high',
				'options'  => array(
					'translation'  => array(
						'label'   => __( 'Language of this post', 'fw' ),
						'type'    => 'select',
						'choices' => $this->get_parent()->get_translated_languages()
					),
					'copy-content' => array(
						'type'  => 'html-full',
						'label' => false,
						'html'  => '<a class="preview button">Copy content from ' . $this->get_parent()->get_default_language() . '</a>'
					)
				)
			)
		);

		return $options;
	}

	/**
	 * Generate translation columns.
	 *
	 * @param $columns
	 * @param $post_type
	 *
	 * @return array
	 */
	public function generate_translation_columns( $columns, $post_type ) {

		if ( ! $this->is_public_post_type() ) {
			return $columns;
		}

		$languages = is_null( FW_Request::GET( 'fw_all_languages' ) ) ?
			$this->get_parent()->get_enabled_languages_without( $this->get_parent()->get_admin_active_language() ) :
			$this->get_parent()->get_enabled_languages();
		$collector = array();
		foreach ( $languages as $code => $lang ) {
			$collector[ 'fw_lang_' . $code ] = '<img src="' . fw_ext_translation_get_flag( $code ) . '" >';
		}

		$col_copy = $columns;

		return array_merge(
			array_splice( $columns, 0, 2 ),
			$collector,
			array_splice( $col_copy, 2 )
		);
	}

	/**
	 * Verify if translation exists.
	 *
	 * @param $data
	 * @param $lang
	 *
	 * @return array
	 */
	public function translation_exists( $data, $lang ) {
		foreach ( $data as $translation ) {
			if ( $translation['translation_language'] === $lang ) {
				return $translation;
			}
		}

		return array();
	}

	/**
	 * Count posts by language.
	 *
	 * @param $counts
	 * @param $type
	 * @param $perm
	 *
	 * @return array|bool|mixed|object
	 */
	public function count_post_by_language( $counts, $type, $perm ) {
		global $wpdb;

		$lang = $this->get_parent()->get_admin_active_language();

		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts}
		 JOIN {$wpdb->postmeta} as pm
		ON pm.meta_key = 'translation_lang'
		AND pm.meta_value = %s
		AND pm.post_id = {$wpdb->posts}.ID
		 WHERE post_type = %s";
		if ( 'readable' == $perm && is_user_logged_in() ) {
			$post_type_object = get_post_type_object( $type );
			if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
				$query .= $wpdb->prepare( " AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
					get_current_user_id()
				);
			}
		}

		$query .= ' GROUP BY post_status';
		$cache_key = 'fw_count_' . $type;
		$counts    = wp_cache_get( $cache_key );

		if ( false === $counts ) {
			$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $lang, $type ), ARRAY_A );

			$counts = array_fill_keys( get_post_stati(), 0 );

			foreach ( $results as $row ) {
				$counts[ $row['post_status'] ] = $row['num_posts'];
			}

			$counts = (object) $counts;
			wp_cache_set( $cache_key, $counts );
		}

		return $counts;
	}

	/**
	 * Count correct when is set all_language get parameter.
	 * @param $counts
	 * @param $type
	 * @param $perm
	 *
	 * @return array|bool|mixed|object
	 */
	public function count_post_by_all_languages( $counts, $type, $perm ) {
		global $wpdb;

		$lang = $this->get_parent()->get_admin_active_language();

		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts}
		 JOIN {$wpdb->postmeta} as pm
		ON pm.meta_key = 'translation_lang'
		AND pm.post_id = {$wpdb->posts}.ID
		 WHERE post_type = %s AND pm.meta_value IN ( '" . implode( "','", array_keys( $this->get_parent()->get_enabled_languages() ) ) . "' )";
		if ( 'readable' == $perm && is_user_logged_in() ) {
			$post_type_object = get_post_type_object( $type );
			if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
				$query .= $wpdb->prepare( " AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
					get_current_user_id()
				);
			}
		}

		$query .= ' GROUP BY post_status';
		$cache_key = 'fw_count_' . $type;
		$counts    = wp_cache_get( $cache_key );

		if ( false === $counts ) {
			$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );

			$counts = array_fill_keys( get_post_stati(), 0 );

			foreach ( $results as $row ) {
				$counts[ $row['post_status'] ] = $row['num_posts'];
			}

			$counts = (object) $counts;
			wp_cache_set( $cache_key, $counts );
		}

		return $counts;
	}

	/**
	 * Generate add/edit languages links in list table cells.
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function generate_translation_table_data( $column, $post_id ) {

		$urls = $this->generate_backend_switch_urls( $post_id, true );

		if ( $this->is_public_post_type() && in_array( $column_code = str_replace( 'fw_lang_', '', $column ), array_keys( $urls ) ) ) {
			if ( $column_code === $this->get_language_from_post( $post_id ) ) {
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

	/**
	 * Generate backend switch urls.
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public function generate_backend_switch_urls( $post_id, $all_languages = false ) {
		$url                   = array();
		$post_type             = get_post_type( $post_id );
		$meta_translate_id     = get_post_meta( $post_id, 'translation_id', true );
		$translate_id          = ( $meta_translate_id === '' ) ? $post_id : $meta_translate_id;
		$translate_lang        = ( get_post_meta( $post_id, 'translation_lang', true ) === '' ) ? $this->get_parent()->get_admin_active_language() : get_post_meta( $post_id, 'translation_lang', true );
		$translated_posts      = $this->query_translation( $translate_id );
		$translation_languages = ( $all_languages === true ) ? $this->get_parent()->get_enabled_languages() : $this->get_parent()->get_enabled_languages_without( $translate_lang );

		foreach ( $translation_languages as $key => $language ) {

			$collector = $this->translation_exists( $translated_posts, $key );
			if ( ! empty( $collector ) ) {
				$url[ $key ] = array(
					'lang_name' => $language['name'],
					'url'       => add_query_arg( array( 'fw_translate_to' => $key ), get_edit_post_link( $collector['post_id'] ) ),
					'type'      => 'edit'
				);
			} else {
				if ( $meta_translate_id > 0 ) {
					$post_new_uri = 'post-new.php?post_type=' . $post_type . '&fw_translate_id=' . $translate_id . '&fw_translate_to=' . $key;
					$url[ $key ]  = array(
						'lang_name' => $language['name'],
						'url'       => admin_url( $post_new_uri ),
						'type'      => 'add'
					);
				} else {
					if ( $this->get_parent()->get_admin_active_language() !== $key ) {
						$post_new_uri = 'post-new.php?post_type=' . $post_type . '&fw_translate_id=' . $post_id . '&fw_translate_to=' . $key . '&fw_update_post=true';
						$url[ $key ]  = array(
							'lang_name' => $language['name'],
							'url'       => admin_url( $post_new_uri ),
							'type'      => 'add'
						);
					}
				}
			}
		}

		return $url;
	}

	############################ FRONT END PART ###########################

	/**
	 * Generate frontend switch urls.
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public function generate_frontend_switch_urls( $post_id ) {
		$frontend_urls = array();

		$meta_translate_id = get_post_meta( $post_id, 'translation_id', true );
		$translate_id      = ( $meta_translate_id === '' ) ? $post_id : $meta_translate_id;
		$translated_posts  = $this->query_translation( $translate_id );
		$languages         = $this->get_parent()->get_enabled_languages();

		foreach ( $languages as $lang_code => $lang_name ) {
			$translation_data = $this->translation_exists( $translated_posts, $lang_code );

			if ( ! empty( $translation_data ) ) {
				global $wp_rewrite;

				$permalink = get_permalink( $translation_data['post_id'] );

				if ( $wp_rewrite->using_permalinks() ) {
					$permalink = preg_replace( '/(\/fw_lang\/)(\w+)/ix', '${1}' . $lang_code, $permalink );

					$frontend_urls[ $lang_code ] = $permalink;
				} else {
					$permalink                   = remove_query_arg( 'fw_lang', $permalink );
					$frontend_urls[ $lang_code ] = add_query_arg( array( 'fw_lang' => $lang_code ), $permalink );
				}

			} else {
				//translation did not exists for this post
				$frontend_urls[ $lang_code ] = preg_replace( '/(\/fw_lang\/)(\w+)/ix', '${1}' . $lang_code, get_home_url( '/' ) );
			}
		}

		return $frontend_urls;
	}

	/**
	 * Convert post to default language.
	 */
	public function convert_to_default_language() {

		$post_types   = $this->get_filtered_post_types();
		$query_object = new WP_Query();
		$posts        = $query_object->query( array(
			'post_type'      => array_keys( $post_types ),
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => - 1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'translation_id',
					'compare' => 'NOT EXISTS'
				)
			)
		) );

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				update_post_meta( $post->ID, 'translation_id', $post->ID );
				update_post_meta( $post->ID, 'translation_lang', $this->get_parent()->get_default_language_code() );
			}
		}
	}
}
