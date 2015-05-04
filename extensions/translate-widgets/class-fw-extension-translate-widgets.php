<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
/**
 * Widget sub extension make all work to
 * translate widgets in different languages.
 */

/**
 * Class FW_Extension_Translate_Widgets.
 */
class FW_Extension_Translate_Widgets extends FW_Extension {

	/**
	 * Array of backend active widgets.
	 *
	 * @access private
	 * @var array
	 */
	private $backend_active_widgets = array();

	/**
	 * Array of frontend active widgets.
	 * @access private
	 * @var array
	 */
	private $frontend_active_widgets = array();

	/**
	 * Called after all extensions instances was created.
	 *
	 * @internal
	 */
	protected function _init() {
		//TODO group filters, actions in admin and frontend

		add_action( 'parse_query', array( $this, 'action_init_frontend_active_widgets' ), 40 );

		add_action( 'init', array( $this, 'action_init_backend_active_widgets' ), 2 );

		add_action( 'widgets_init', array( $this, 'action_register_calendar_widget' ) );

		add_filter( 'widget_update_callback', array( $this, 'filter_save_widget_language' ), 20 );

		add_filter( 'dynamic_sidebar_params', array( $this, 'filter_backend_widgets' ) );

		add_filter( 'widget_pages_args', array( $this, 'filter_pages_widget' ) );

		add_filter( 'pre_get_posts', array( $this, 'filter_search_widget' ) );

		add_filter( 'widget_display_callback', array( $this, 'filter_frontend_widgets' ), 10, 2 );

		add_filter( 'widget_posts_args', array( $this, 'filter_recent_posts_widget' ) );

		add_filter( 'terms_clauses', array( $this, 'filter_backend_menu_widget' ), 10, 3 );

	}

	/**
	 * Save widget active language in terms meta.
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	public function filter_save_widget_language( $instance ) {
		$instance['fw_lang'] = $this->get_parent()->get_admin_active_language();

		return $instance;
	}

	/**
	 * Init frontend active widgets filtered by language.
	 */
	public function action_init_frontend_active_widgets() {
		if ( ! is_admin() ) {
			$this->frontend_active_widgets = $this->filter_widgets_by_language( 'frontend' );
		}
	}

	/**
	 * Init backend active widgets filtered by language.
	 */
	public function action_init_backend_active_widgets() {
		if ( is_admin() ) {
			$this->backend_active_widgets = $this->filter_widgets_by_language( 'backend' );
		}
	}

	/**
	 * Filter backend widgets by active language.
	 *
	 * @param $params
	 *
	 * @return mixed
	 */
	public function  filter_backend_widgets( $params ) {
		if ( ! in_array( $params[0]['widget_id'], $this->backend_active_widgets ) ) {
			$params[0]['_hide'] = 1;
		}

		return $params;
	}

	/**
	 * Filter pages widgets by active language.
	 *
	 * @return array
	 */
	public function filter_pages_widget( $args ) {
		$args['meta_value'] = $this->get_parent()->get_frontend_active_language();
		$args['meta_key']   = 'translation_lang';

		return $args;
	}

	/**
	 * Filter search widget by active language.
	 *
	 * @param $query
	 *
	 * @return WP_Query
	 */
	public function filter_search_widget( $query ) {
		if ( $query->is_search() && $query->is_main_query() ) {
			$query->set( 'meta_key', 'translation_lang' );
			$query->set( 'meta_value', $this->get_parent()->get_frontend_active_language() );
			$query->set( 'meta_compare', '=' );
		}

		return $query;
	}

	/**
	 * Filter frontend widgets by active language.
	 *
	 * @param $widget_instance
	 * @param $current_obj
	 *
	 * @return bool
	 */
	public function filter_frontend_widgets( $widget_instance, $current_obj ) {
		return in_array( $current_obj->id, $this->frontend_active_widgets ) ? $widget_instance : false;
	}

	/**
	 * Register FW_Calendar widget.
	 */
	public function action_register_calendar_widget() {
		unregister_widget( 'WP_Widget_Calendar' );
		register_widget( 'FW_Widget_Calendar' );
	}

	/**
	 * Filter recent posts widget by active language.
	 *
	 * @param $args
	 */
	public function filter_recent_posts_widget( $args ) {
		$args['meta_value'] = $this->get_parent()->get_frontend_active_language();
		$args['meta_key']   = 'translation_lang';

		return $args;
	}

	/**
	 * Filter backend menu select by active language.
	 *
	 * @param $pieces
	 */
	public function filter_backend_menu_widget( $pieces ) {
		global $pagenow;
		global $wpdb;

		if ( 'widgets.php' === $pagenow ) {
			$active_lang = $this->get_parent()->get_admin_active_language();
			$pieces['join'] .= " INNER JOIN $wpdb->fw_termmeta AS fw_tm
								ON t.term_id = fw_tm.fw_term_id AND
								fw_tm.meta_key = 'translation_lang' AND
								fw_tm.meta_value = '" . $active_lang . "'";
		}

		return $pieces;
	}


	/**
	 * Filter registered widgets by active language.
	 *
	 * @param $mode
	 *
	 * @return array
	 */
	public function filter_widgets_by_language( $mode ) {
		//TODO refactor function maybe replace $mode with is_admin()
		global $wp_registered_widgets;
		$collector = array();

		foreach ( $wp_registered_widgets as $key => $control ) {
			$settings = $control['callback'][0]->get_settings();
			if ( ! empty( $settings ) ) {
				$id       = (int) preg_replace( '/[^0-9]/', '', $key );
				$language = ( $mode === 'backend' ) ?
					$this->get_parent()->get_admin_active_language() :
					$this->get_parent()->get_frontend_active_language();

				if (
				( isset( $settings[ $id ]['fw_lang'] ) &&
				  $settings[ $id ]['fw_lang'] == $language )
				) {
					$collector[] = $key;
				}
			}
		}

		return $collector;
	}

	/**
	 * Convert widgets to default language.
	 */
	public function convert_to_default_language() {
		$sidebars = wp_get_sidebars_widgets();

		foreach ( $sidebars as $widgets ) {
			foreach ( $widgets as $widget ) {
				$parts                          = explode( '-', strrev( $widget ), 2 );
				$option_name                    = 'widget_' . strrev( $parts[1] );
				$option                         = get_option( $option_name );
				$option[ $parts[0] ]['fw_lang'] = $this->get_parent()->get_default_language_code();

				update_option( $option_name, $option );
			}
		}
	}
}