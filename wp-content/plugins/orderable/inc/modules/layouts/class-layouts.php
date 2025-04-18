<?php
/**
 * Module: Layouts.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Layouts module class.
 */
class Orderable_Layouts {
	/**
	 * Layout settings meta key.
	 *
	 * @var string
	 */
	public static $layout_settings_key = '_orderable_layout_settings';

	/**
	 * Init.
	 */
	public static function run() {
		self::load_functions();
		self::load_classes();

		add_action( 'init', array( __CLASS__, 'add_shortcodes' ) );
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_filter( 'manage_orderable_layouts_posts_columns', array( __CLASS__, 'admin_columns' ), 1 );
		add_action( 'manage_orderable_layouts_posts_custom_column', array( __CLASS__, 'admin_columns_content' ), 10, 2 );
		add_action( 'load-post.php', array( __CLASS__, 'init_metabox' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'init_metabox' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_filter( 'orderable_is_settings_page', array( __CLASS__, 'is_settings_page' ) );
		add_action( 'wp_ajax_orderable_preview', array( __CLASS__, 'render_layout_preview_ajax' ) );
		add_filter( 'orderable_admin_notices', array( __CLASS__, 'admin_notice' ) );
	}

	/**
	 * Define settings page.
	 *
	 * @param bool $bool
	 *
	 * @return bool
	 */
	public static function is_settings_page( $bool = false ) {
		global $current_screen;

		if ( is_null( $current_screen ) || 'orderable_layouts' !== $current_screen->id ) {
			return $bool;
		}

		return true;
	}

	/**
	 * Load functions file for this module.
	 */
	public static function load_functions() {
		require_once ORDERABLE_MODULES_PATH . 'layouts/functions-layouts.php';
	}

	/**
	 * Load classes for this module.
	 */
	public static function load_classes() {
		$classes = array(
			'layouts-blocks' => 'Orderable_Layouts_Blocks',
		);

		foreach ( $classes as $file_name => $class_name ) {
			require_once ORDERABLE_MODULES_PATH . 'layouts/class-' . $file_name . '.php';

			$class_name::run();
		}
	}

	/**
	 * Add layout shrotcodes.
	 */
	public static function add_shortcodes() {
		add_shortcode( 'orderable', array( __CLASS__, 'orderable_shortcode' ) );
	}

	/**
	 * Register post type.
	 */
	public static function register_post_type() {
		$labels = Orderable_Helpers::prepare_post_type_labels(
			array(
				'plural'   => __( 'Product Layouts', 'orderable' ),
				'singular' => __( 'Product Layout', 'orderable' ),
			)
		);

		$labels['all_items'] = __( 'Product Layouts', 'orderable' );

		$args = array(
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'orderable',
			'show_in_rest'        => true,
			'menu_position'       => 20,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
		);

		register_post_type( 'orderable_layouts', $args );
	}

	/**
	 * Post type columns.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public static function admin_columns( $columns = array() ) {
		return array(
			'title'        => $columns['title'],
			'shortcode'    => __( 'Shortcode', 'orderable' ),
			'php_function' => __( 'PHP Function', 'orderable' ),
			'date'         => $columns['date'],
		);
	}

	/**
	 * Output post type column content.
	 *
	 * @param string $column_name
	 * @param int    $post_ID
	 */
	public static function admin_columns_content( $column_name, $post_ID ) {
		if ( 'shortcode' === $column_name ) {
			echo '<code>[orderable id="' . (int) $post_ID . '"]</code>';
		} elseif ( 'php_function' === $column_name ) {
			echo '<code>&lt;?php orderable(' . (int) $post_ID . '); ?&gt;</code>';
		}
	}

	/**
	 * Initialize meta boxes.
	 */
	public static function init_metabox() {
		add_meta_box(
			'orderable-layout-settings-metabox',
			__( 'Layout Settings', 'orderable' ),
			array( __CLASS__, 'render_layout_settings_metabox' ),
			'orderable_layouts',
			'advanced',
			'default'
		);

		add_meta_box(
			'orderable-layout-preview-metabox',
			__( 'Layout Preview', 'orderable' ),
			array( __CLASS__, 'render_layout_preview_metabox' ),
			'orderable_layouts',
			'advanced',
			'default'
		);
	}

	/**
	 * Save layout meta.
	 *
	 * @param $post_id
	 */
	public static function save_meta( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		/**
		 * Filter the layout settings before saving.
		 *
		 * @since 1.6.0
		 * @hook orderable_layout_settings_save_data
		 * @param  array $layout_settings The product layout settings.
		 * @return array New value
		 */
		$layout_settings = apply_filters(
			'orderable_layout_settings_save_data',
			array(
				'categories'       => (array) filter_input( INPUT_POST, 'orderable_categories', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
				'layout'           => empty( $_POST['orderable_layout'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['orderable_layout'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
				'images'           => empty( $_POST['orderable_images'] ) ? false : 'yes' === sanitize_text_field( wp_unslash( $_POST['orderable_images'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
				'card_click'       => empty( $_POST['orderable_card_click'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['orderable_card_click'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
				'quantity_roller'  => 'yes' === sanitize_text_field( filter_input( INPUT_POST, 'orderable_quantity_roller' ) ),
				'sort'             => empty( $_POST['orderable_sort'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['orderable_sort'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
				'sort_on_frontend' => empty( $_POST['orderable_sort_on_frontend'] ) ? false : 'yes' === sanitize_text_field( wp_unslash( $_POST['orderable_sort_on_frontend'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
			)
		);

		update_post_meta( $post_id, self::$layout_settings_key, $layout_settings );
	}

	/**
	 * Render Layout Settings Metabox.
	 *
	 * @param WP_Post $post WP_Post object.
	 *
	 * @return void
	 */
	public static function render_layout_settings_metabox( $post ) {
		$layout_settings = self::get_layout_settings( $post );

		include Orderable_Helpers::get_template_path( 'admin/layout-settings-metabox.php', 'layouts' );
	}

	/**
	 * Get max orders field.
	 *
	 * @param string $field_name      The field name. Example: `sort`.
	 * @param array  $layout_settings The layout settings.
	 *
	 * @return void|string
	 */
	public static function get_layout_field( $field_name, $layout_settings ) {
		if ( empty( $field_name ) ) {
			return '';
		}

		$allowed_html = array(
			'a' => array(
				'class'  => array(),
				'href'   => array(),
				'target' => array(),
			),
		);

		ob_start();
		?>
		<?php echo wp_kses( Orderable_Helpers::get_pro_button( $field_name ), $allowed_html ); ?>
		<?php

		/**
		 * Filter the layout field.
		 *
		 * The dynamic portion of the hook name, `$field_name`, refers to
		 * the field name e.g. `sort`, `max-orders`, etc.
		 *
		 * @since 1.10.0
		 * @hook filter_hook
		 * @param  string $html            The html markup.
		 * @param  array  $layout_settings The layout settings.
		 * @return string New value
		 */
		$html = apply_filters( "orderable_layout_{$field_name}_field", ob_get_clean(), $layout_settings );

		echo wp_kses_post( $html );
	}

	/**
	 * Render Layout Preview Metabox.
	 *
	 * @param WP_Post $post WP_Post object.
	 *
	 * @return void
	 */
	public static function render_layout_preview_metabox( $post ) {
		?>
		<div class="orderable-layout-preview-notice">
			<p><?php _e( 'This preview is for demo purposes and is not interactive.', 'orderable' ); ?></p>
		</div>
		<div class="orderable-main-wrap">
			<?php
			echo do_shortcode( sprintf( '[orderable id="%d"]', $post->ID ) );
			?>
		</div>
		<?php
	}

	/**
	 * Render layout from ajax data.
	 */
	public static function render_layout_preview_ajax() {
		$data = (array) filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		$return = array(
			'shortcode' => self::orderable_shortcode( $data ),
		);

		wp_send_json_success( $return );
	}

	/**
	 * Main orderable shortcode.
	 *
	 * @param array  $args
	 * @param string $content
	 * @param string $name
	 *
	 * @return string|void
	 */
	public static function orderable_shortcode( $args, $content = '', $name = '' ) {
		$defaults = self::get_layout_defaults();

		$args                     = wp_parse_args( $args, $defaults );
		$args['sort_on_frontend'] = (bool) json_decode( strtolower( $args['sort_on_frontend'] ) );
		$args['images']           = (bool) json_decode( strtolower( $args['images'] ) );

		if ( ! is_null( $args['id'] ) ) {
			$args = self::get_layout_settings( $args['id'] );
		}

		if ( ! empty( $args['categories'] ) ) {
			$args['categories'] = ( is_string( $args['categories'] ) && false !== strpos( $args['categories'], ',' ) ) ? array_filter( explode( ',', $args['categories'] ) ) : $args['categories'];
			$args['categories'] = is_array( $args['categories'] ) ? $args['categories'] : array( $args['categories'] );
			$args['categories'] = array_map( 'absint', $args['categories'] );
			$args['categories'] = self::get_unique_categories( $args['categories'], $args );
		}

		$args = apply_filters( 'orderable_shortcode_args', $args, $content, $name );

		$products = Orderable_Products::get_products_by_category( $args );

		if ( empty( $products ) ) {
			return;
		}

		ob_start();

		include Orderable_Helpers::get_template_path( 'main.php', 'layouts' );

		return ob_get_clean();
	}

	/**
	 * Get Unique Categories.
	 *
	 * Remove child categories if parent category exists already.
	 *
	 * @param array $categories Categories.
	 * @param array $args       Layout Settings.
	 *
	 * @return array
	 */
	public static function get_unique_categories( $categories, $args ) {
		/**
		 * Exclude Sections for Unique Categories.
		 *
		 * @param array $exclude_sections The Excluded sections.
		 * @param array $categories       The Unfiltered Categories.
		 * @param array $args             Layout Settings.
		 *
		 * @return array
		 */
		$exclude_sections = apply_filters( 'orderable_exclude_sections_for_unique_categories', array( 'side_tabs', 'top_tabs' ), $categories, $args );

		if ( isset( $args['sections'] ) && in_array( $args['sections'], $exclude_sections, true ) ) {
			$unique_categories = $categories;
		} else {
			$unique_categories = array();

			foreach ( $categories as $category_id ) {
				$ancestors = get_ancestors( $category_id, 'product_cat' );

				if ( count( array_intersect( $ancestors, $categories ) ) > 0 ) {
					continue;
				}

				$unique_categories[] = $category_id;
			}
		}

		/**
		 * Get Unique Categories.
		 *
		 * @param array $unique_categories The Filtered Categories.
		 * @param array $categories        The Unfiltered Categories.
		 * @param array $args              Layout Settings.
		 *
		 * @return array
		 */
		return apply_filters( 'orderable_get_unique_categories', $unique_categories, $categories, $args );
	}

	/**
	 * Enqueue admin assets.
	 */
	public static function admin_assets( $hook ) {
		if ( ! self::is_settings_page() && $hook !== 'post-new.php' && $hook !== 'post.php' ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		add_thickbox();

		// Styles.
		wp_enqueue_style( 'orderable-layouts', ORDERABLE_URL . 'inc/modules/layouts/assets/admin/css/layouts' . $suffix . '.css', array(), ORDERABLE_VERSION );

		// Scripts.
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script( 'orderable-layouts', ORDERABLE_URL . 'inc/modules/layouts/assets/admin/js/main' . $suffix . '.js', array( 'jquery', 'thickbox' ), ORDERABLE_VERSION, true );
	}

	/**
	 * Get layout settings.
	 *
	 * @param int|WP_Post $layout
	 *
	 * @return array|bool
	 */
	public static function get_layout_settings( $layout, $string = false ) {
		if ( ! empty( $layout ) && is_numeric( $layout ) ) {
			$layout = get_post( $layout );
		}

		$layout_settings = self::get_layout_defaults();

		if ( ! empty( $layout ) && is_a( $layout, 'WP_Post' ) ) {
			$layout_settings['id'] = $layout->ID;
			$saved_layout_settings = get_post_meta( $layout->ID, self::$layout_settings_key, true );
			$layout_settings       = wp_parse_args( $saved_layout_settings, $layout_settings );
		}

		if ( $string ) {
			$layout_settings = self::convert_layout_settings_to_string( $layout_settings );
		}

		return apply_filters( 'orderable_layout_settings', $layout_settings, $layout, $string );
	}

	/**
	 * Get layout defaults.
	 *
	 * @param null $layout_id
	 *
	 * @return mixed|void
	 */
	public static function get_layout_defaults( $layout_id = null ) {
		return apply_filters(
			'orderable_layout_defaults',
			array(
				'id'               => $layout_id,
				'categories'       => array(),
				'layout'           => 'grid',
				'images'           => true,
				'card_click'       => '',
				'quantity_roller'  => false,
				'sort'             => 'menu_order',
				'sort_on_frontend' => false,
			),
			$layout_id
		);
	}

	/**
	 * Convert layout settings to string.
	 *
	 * @param array $layout_settings
	 *
	 * @return string
	 */
	public static function convert_layout_settings_to_string( $layout_settings ) {
		$layout_settings_array = array();

		if ( ! empty( $layout_settings ) ) {
			foreach ( $layout_settings as $attribute => $value ) {
				if ( empty( $value ) && false !== $value ) {
					continue;
				}

				if ( is_bool( $value ) ) {
					$value = $value ? 'true' : 'false';
				} elseif ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}

				$layout_settings_array[] = sprintf( '%s="%s"', $attribute, $value );
			}
		}

		return apply_filters( 'orderable_layout_settings_string', implode( ' ', $layout_settings_array ) );
	}

	/**
	 * Get categories list.
	 *
	 * @param array $categories
	 * @param int   $parent
	 * @param int   $level
	 *
	 * @return array
	 */
	public static function get_categories( $categories = array(), $parent = 0, $level = 0 ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => $parent,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $categories;
		}

		foreach ( $terms as $term ) {
			$categories[ $term->term_id ] = trim( sprintf( '%s %s', str_repeat( '—', $level ), $term->name ) );

			$categories = self::get_categories( $categories, $term->term_id, $level + 1 );
		}

		return $categories;
	}

	/**
	 * Add admin notice.
	 *
	 * @param array $notices
	 *
	 * @return array
	 */
	public static function admin_notice( $notices = array() ) {
		global $pagenow, $typenow;

		if ( 'orderable_layouts' !== $typenow || 'edit.php' !== $pagenow ) {
			return $notices;
		}

		$notices[] = array(
			'name'        => 'layout_builder',
			'title'       => __( 'What are Product Layouts?', 'orderable' ),
			'description' => __( 'This is where you can create product layouts and customize their settings. Save your layouts here and reuse them later using the block editor, shortcode (great for page builders), or PHP snippet.', 'orderable' ),
		);

		return $notices;
	}

	/**
	 * Get product card classes.
	 *
	 * @param array $args
	 *
	 * @return mixed|void
	 */
	public static function get_product_card_classes( $args = array() ) {
		$class = array(
			'orderable-product',
		);

		if ( empty( $args['images'] ) ) {
			$class[] = 'orderable-product--no-image';
		}

		if ( ! empty( $args['card_click'] ) ) {
			$class[] = 'orderable-product--' . $args['card_click'];
		}

		return apply_filters( 'orderable_get_product_card_classes', $class, $args );
	}
}
