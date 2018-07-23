<?php
/*
Plugin Name: Woo Zap Mirror
Plugin URI:  https://wordpress.org/plugins/woo-zap-mirror/
Description: Creates a mirror site for Zap.
Version:     1.3.7
Author:      Ido Friedlander
Author URI:  https://profiles.wordpress.org/idofri/
Text Domain: woo-zap-mirror
WC requires at least: 3.0.0
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** @class WC_Zap_Mirror */
class WC_Zap_Mirror {
	
	/**
	 * Woo Zap Mirror version.
	 * @var string
	 */
	public $version = '1.3.7';
	
	/** The single instance of the class. */
	protected static $_instance = null;
	
	/**
	 * Notices (array)
	 * @var array
	 */
	public $notices = array();

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woo-zap-mirror' ), '1.3.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woo-zap-mirror' ), '1.3.0' );
	}

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_init', 								array( $this, 'check_environment' ) );
		add_action( 'admin_notices', 							array( $this, 'admin_notices' ) );
		add_action( 'plugins_loaded', 							array( $this, 'load_plugin_textdomain' ) );
		add_action( 'wc_zap_mirror_head',						array( $this, 'wpseo_head' ) );
		add_action( 'admin_enqueue_scripts',					array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'woocommerce_settings_tabs_zap_mirror', 	array( $this, 'show_settings_tab' ) );
		add_action( 'woocommerce_update_options_zap_mirror', 	array( $this, 'update_settings_tab' ) );
		add_action( 'woocommerce_admin_field_checklist',		array( $this, 'show_checklist_field' ) );
		add_action( 'woocommerce_product_data_tabs', 			array( $this, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', 			array( $this, 'add_product_data_panel' ) );
		add_action( 'woocommerce_process_product_meta', 		array( $this, 'process_product_meta' ) );
		add_filter( 'woocommerce_settings_tabs_array', 			array( $this, 'add_settings_tab' ), 25 );
		add_filter( 'query_vars', 								array( $this, 'add_custom_query_var' ) );
		add_filter( 'template_include', 						array( $this, 'template_loader' ), 0 );
	}

	/**
	 * Checks the environment for compatibility problems.
	 *
	 * @return void
	 */
	public function check_environment() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$class = 'notice notice-error is-dismissible';
			$message = __( 'This plugin requires <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> to be active.', 'woo-zap-mirror' );
			$this->add_admin_notice( $class, $message );
			// Deactivate the plugin
			deactivate_plugins( __FILE__ );
			return;
		}
		
		if ( ! class_exists( 'SimpleXMLElement' ) ) {
			$class = 'notice notice-error is-dismissible';
			$message = __( 'The SimpleXMLElement extension could not be found. Please ask your host to install this extension.', 'woo-zap-mirror' );
			$this->add_admin_notice( $class, $message );
		}

		$php_version = phpversion();
		$required_php_version = '5.3';
		if ( version_compare( $required_php_version, $php_version, '>' ) ) {
			$class = 'notice notice-warning is-dismissible';
			$message = sprintf( __( 'Your server is running PHP version %1$s but some features requires at least %2$s.', 'woo-zap-mirror' ), $php_version, $required_php_version );
			$this->add_admin_notice( $class, $message );
		}
	}
	
	/**
	 * Yoast SEO.
	 */
	public function wpseo_head() {
		do_action( 'wpseo_head' );
	}
	
	/**
	 * Localization.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woo-zap-mirror' );
	}
	
	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}
	
	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Add admin notices.
	 *
	 * @param string $class
	 * @param string $message
	 * @return void
	 */
	public function add_admin_notice( $class, $message ) {
		$this->notices[] = array(
			'class'   => $class,
			'message' => $message
		);
	}

	/**
	 * Notices function.
	 */
	public function admin_notices() {
		foreach ( (array) $this->notices as $notice ) {
			echo '<div class="' . esc_attr( $notice['class'] ) . '"><p><b>' . __( 'Woo Zap Mirror', 'woo-zap-mirror' ) . ': </b>';
			echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
			echo '</p></div>';
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'woo-zap-mirror', plugins_url( '/assets/css/style.css', __FILE__ ), array(), $this->version );
	}

	/**
	 * Add query vars.
	 *
	 * @access public
	 * @param array $vars
	 * @return array
	 */
	public function add_custom_query_var( $vars ) {
		$vars[] = 'product_cat';
		return $vars;
	}

	/**
	 * Load mirror site.
	 *
	 * @param mixed $template
	 * @return string
	 */
	public function template_loader( $template ) {
		if ( is_page( wc_get_page_id( 'zap' ) ) ) {
			// Tell WP Super Cache & W3 Total Cache to not cache requests
			define( 'DONOTCACHEPAGE', true );
			
			$this->mirror_site();
			exit;
		}
		return $template;
	}

	/**
	 * Add custom product tab.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_product_data_tab( $tabs ) {
		$tabs['zap'] = array(
			'label' => __( 'Zap Settings', 'woo-zap-mirror' ),
			'target' => 'zap_product_data',
			'class' => ''
		);
	    return $tabs;
	}
	
	/**
	 * Contents of the Zap settings product tab.
	 */
	public function add_product_data_panel() {
		global $post;

		?><div id="zap_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				do_action( 'wc_zap_mirror_before_options' );
				
				woocommerce_wp_checkbox( array(
					'id' 				=> '_wc_zap_disable',
					'label' 			=> __( 'Hide Product', 'woo-zap-mirror' )
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_product_name',
					'label' 			=> __( 'Product Name', 'woo-zap-mirror' ),
					'type' 				=> 'text',
					'description' 		=> __( 'Maximum 40 characters', 'woo-zap-mirror' )
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_product_model',
					'label' 			=> __( 'Product Model', 'woo-zap-mirror' ),
					'type' 				=> 'text',
					'description' 		=> __( 'Optional', 'woo-zap-mirror' )
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_product_catalog_number',
					'label' 			=> __( 'Product Catalog Number', 'woo-zap-mirror' ),
					'type' 				=> 'text'
				) );
				
				$productCode = get_post_meta( $post->ID, '_wc_zap_productcode', true );
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_productcode',
					'label' 			=> __( 'Product Code', 'woo-zap-mirror' ),
					'type' 				=> 'text',
					'value'				=> $productCode ? $productCode : $post->ID
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_product_currency',
					'value'				=> 'ILS',
					'label' 			=> __( 'Currency', 'woo-zap-mirror' ),
					'type' 				=> 'text',
					'custom_attributes' => array(
						'disabled' => 'disabled'
					)
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_product_price',
					'label' 			=> __( 'Price', 'woo-zap-mirror' ),
					'type' 				=> 'number',
					'description' 		=> __( 'Digits only', 'woo-zap-mirror' ),
					'custom_attributes'	=> array(
						'step' 	=> 'any',
						'min' 	=> '0'
					)
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_shipment',
					'label' 			=> __( 'Shipping Costs', 'woo-zap-mirror' ),
					'type' 				=> 'number',
					'description' 		=> __( 'Digits only', 'woo-zap-mirror' ),
					'custom_attributes'	=> array(
						'step' 	=> 'any',
						'min' 	=> '0'
					)
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_delivery',
					'label' 			=> __( 'Delivery Time', 'woo-zap-mirror' ),
					'type' 				=> 'number',
					'description' 		=> __( 'Digits only', 'woo-zap-mirror' ),
					'custom_attributes'	=> array(
						'step' 	=> 'any',
						'min' 	=> '0'
					)
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_manufacturer',
					'label' 			=> __( 'Manufacturer', 'woo-zap-mirror' ),
					'type' 				=> 'text',
					'description' 		=> __( 'Should also appear in product\'s name', 'woo-zap-mirror' )
				) );
				
				woocommerce_wp_text_input( array(
					'id' 				=> '_wc_zap_warranty',
					'label' 			=> __( 'Warranty', 'woo-zap-mirror' ),
					'type' 				=> 'text'
				) );
				
				woocommerce_wp_textarea_input( array(
					'id' 				=> '_wc_zap_product_description',
					'rows'				=> 5,
					'style' 			=> 'height: auto;',
					'label' 			=> __( 'Product Description', 'woo-zap-mirror' ),
					'description' 		=> __( 'Maximum 255 characters', 'woo-zap-mirror' ),
					'custom_attributes'	=> array(
						'maxlength' => '255'
					)
				) );
				
				do_action( 'wc_zap_mirror_after_options' );
				?>
				<p class="form-field">
					<label for="_wc_zap_product_image"><?php _e( 'Image', 'woo-zap-mirror' ); ?></label>
					<img src="<?php echo has_post_thumbnail() ? get_the_post_thumbnail_url( $post->ID, 'shop_thumbnail' ) : wc_placeholder_img_src(); ?>" id="_wc_zap_product_image" />
				</p>
			</div>
		</div><?php
	}

	/**
	 * Save settings.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function process_product_meta( $post_id ) {
		if ( ! empty( $_POST['_wc_zap_disable'] ) ) {
			update_post_meta( $post_id, '_wc_zap_disable', 'yes' );
		} else {
			update_post_meta( $post_id, '_wc_zap_disable', '' );
		}

		if ( isset( $_POST['_wc_zap_product_name'] ) ) {
			update_post_meta( $post_id, '_wc_zap_product_name', wc_clean( mb_substr( $_POST['_wc_zap_product_name'], 0, 40, "utf-8" ) ) );
		}

		if ( isset( $_POST['_wc_zap_product_model'] ) ) {
			update_post_meta( $post_id, '_wc_zap_product_model', wc_clean( $_POST['_wc_zap_product_model'] ) );
		}

		if ( isset( $_POST['_wc_zap_product_description'] ) ) {
			update_post_meta( $post_id, '_wc_zap_product_description', wc_clean( mb_substr( $_POST['_wc_zap_product_description'], 0, 255, "utf-8" ) ) );
		}

		if ( isset( $_POST['_wc_zap_product_catalog_number'] ) ) {
			update_post_meta( $post_id, '_wc_zap_product_catalog_number', wc_clean( $_POST['_wc_zap_product_catalog_number'] ) );
		}
		
		if ( isset( $_POST['_wc_zap_productcode'] ) ) {
			update_post_meta( $post_id, '_wc_zap_productcode', wc_clean( $_POST['_wc_zap_productcode'] ) );
		}

		if ( isset( $_POST['_wc_zap_product_price'] ) ) {
			update_post_meta( $post_id, '_wc_zap_product_price', wc_clean( $_POST['_wc_zap_product_price'] ) );
		}

		if ( isset( $_POST['_wc_zap_shipment'] ) ) {
			update_post_meta( $post_id, '_wc_zap_shipment', wc_clean( $_POST['_wc_zap_shipment'] ) );
		}

		if ( isset( $_POST['_wc_zap_delivery'] ) ) {
			update_post_meta( $post_id, '_wc_zap_delivery', wc_clean( $_POST['_wc_zap_delivery'] ) );
		}

		if ( isset( $_POST['_wc_zap_manufacturer'] ) ) {
			update_post_meta( $post_id, '_wc_zap_manufacturer', wc_clean( $_POST['_wc_zap_manufacturer'] ) );
		}

		if ( isset( $_POST['_wc_zap_warranty'] ) ) {
			update_post_meta( $post_id, '_wc_zap_warranty', wc_clean( $_POST['_wc_zap_warranty'] ) );
		}
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs
	 * @return array
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[ 'zap_mirror' ] = __( 'Zap Mirror Site', 'woo-zap-mirror' );
		return $settings_tabs;
	}

	/**
	 * Uses the WooCommerce admin fields API to output settings via the woocommerce_admin_fields() function.
	 */
	public function show_settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Uses the WooCommerce options API to save settings via the woocommerce_update_options() function.
	 */
	public function update_settings_tab() {
		// Allow empty array value
		add_filter( 'woocommerce_admin_settings_sanitize_option_wc_zap_mirror_excluded_term_ids', function( $value ) {
			if ( is_null( $value ) ) {
				$value = array();
			}
			return $value;
		} );
		woocommerce_update_options( $this->get_settings() );
	}

	/**
	 * Get all the settings for this plugin for woocommerce_admin_fields() function.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = array(
			array(
				'title' 	=> __( 'Zap Settings', 'woo-zap-mirror' ),
				'type' 		=> 'title',
				'id' 		=> 'wc_zap_mirror_options'
			),
			array(
				'title'		=> __( 'Mirror Site Page', 'woo-zap-mirror' ),
				'id'		=> 'woocommerce_zap_page_id',
				'type' 		=> 'single_select_page',
				'default' 	=> '',
				'class' 	=> 'wc-enhanced-select-nostd',
				'css' 		=> 'min-width: 300px;',
				'desc_tip' 	=> __( 'This sets the zap mirror page of your shop.', 'woo-zap-mirror' )
			),
			array(
				'title' 	=> __( 'Hide Categories', 'woo-zap-mirror' ),
				'type' 		=> 'checklist',
				'id' 		=> 'wc_zap_mirror_excluded_term_ids',
				'desc_tip' 	=> __( 'Check the categories you wish to hide on the mirror site', 'woo-zap-mirror' )
			),
			array(
				'type' 		=> 'sectionend',
				'id' 		=> 'wc_zap_mirror_options'
			)
		);
		return apply_filters( 'wc_zap_mirror_settings', $settings );
	}

	/**
	 * Used to print the settings field of the custom-type checklist field.
	 *
	 * @param array $value
	 */
	public function show_checklist_field( $value ) {
		$field_description = WC_Admin_Settings::get_field_description( $value );
		extract( $field_description );

		?><tr valign="top" class="single_select_page">
			<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?> <?php echo $tooltip_html; ?></th>
			<td class="forminp categorydiv">
				<div id="product_cat-all" class="tabs-panel">
					<ul id="product_catchecklist" data-wp-lists="list:product_cat" class="categorychecklist form-no-clear">
						<?php echo str_replace(
							'tax_input[product_cat]', 
							$value['id'], 
							wp_terms_checklist(
								wc_get_page_id( 'zap' ), 
								array(
									'selected_cats'	=> WC_Admin_Settings::get_option( $value['id'] ),
									'taxonomy' 		=> 'product_cat',
									'echo' 			=> false
								)
							)
						); ?>
					</ul>
				</div>
				<?php echo $description; ?>
			</td>
		</tr><?php
	}
	
	/**
	 * Get products by term id.
	 *
	 * @param int $term_id
	 * @return WP_Query
	 */
	public function get_products( $term_id ) {
		$args = array(
			'post_type' 		=> 'product',
			'posts_per_page' 	=> -1,
			'tax_query' 		=> array(
				array(
					'taxonomy' 			=> 'product_cat',
					'field' 			=> 'term_id',
					'terms' 			=> $term_id,
					'include_children'	=> false,
					'operator' 			=> 'IN'
				)
			),
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     	=> '_wc_zap_disable',
					'value'		=> 'yes',
					'compare' 	=> '!='
				),
				array(
					'key' 		=> '_wc_zap_disable',
					'compare' 	=> 'NOT EXISTS'
				)
			)
		);
		
		/**
		 * Filters the arguments passed to each WP_Query instance.
		 */
		$args = apply_filters( 'wc_zap_mirror_wp_query', $args, $term_id );
		
		/**
		 * Filters the arguments passed to a specific WP_Query instance.
		 */
		$args = apply_filters( "wc_zap_mirror_wp_query_{$term_id}", $args );
		
		return new WP_Query( $args );
	}
	
	/**
	 * Get product categories.
	 *
	 * @param array $exclude_ids
	 * @return array
	 */
	public function get_terms( $exclude_ids = array() ) {
		$args = array(
			'taxonomy' 		=> 'product_cat',
			'hide_empty' 	=> 0,
			'exclude_tree' 	=> $exclude_ids
		);
		
		/**
		 * Filters the arguments passed to the get_terms() function.
		 */
		$args = apply_filters( 'wc_zap_mirror_get_terms', $args, $exclude_ids );
		
		return get_terms( $args );
	}
	
	/**
	 * Mirror Site handler.
	 */
	public function mirror_site() {
		$exclude_ids = apply_filters( 'wc_zap_mirror_excluded_term_ids', WC_Admin_Settings::get_option( 'wc_zap_mirror_excluded_term_ids', array() ) );
		
		// XML
		if ( $term_id = get_query_var( 'product_cat' ) ) {
			// Bail out prior to query initiation if term is excluded.
			if ( in_array( $term_id, $exclude_ids ) ) {
				wp_die( __( 'No categories found.', 'woo-zap-mirror' ) );
			}
			
			$products = $this->get_products( $term_id );
			if ( $products->have_posts() ) {
				$this->create_xml( $products );
			} else {
				wp_die( __( 'No available products.', 'woo-zap-mirror' ) );
			}
			
			return;
			
		// HTML Template
		} elseif ( $terms = $this->get_terms( $exclude_ids ) ) {
			ob_start();
			foreach ( $terms as $term ) {
				$id		= $term->term_id;
				$pid	= $term->parent;
				$name	= esc_attr( $term->name );
				$url	= add_query_arg( 'product_cat', $term->term_id, wc_get_page_permalink( 'zap' ) );
				echo "d.add({$id}, {$pid}, '{$name}', '{$url}');\n";
				// Indentation
				if ( $term !== end( $terms ) ) {
					echo "\t\t";
				}
			}
			$nodes = ob_get_clean();
			
			wc_get_template( 'zap/mirror.php', array( 'nodes' => $nodes ), null, $this->plugin_path() . '/templates/' );
			return;
		}
		
		wp_die( __( 'No categories found.', 'woo-zap-mirror' ) );
	}

	/**
	 * Output XML.
	 *
	 * @param WP_Query $products
	 */
	public function create_xml( $products ) {
		header( "Content-Type: application/xml; charset=utf-8" );

		$xml = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><STORE></STORE>' );
		$xml->addAttribute( 'URL', home_url() );
		$xml->addAttribute( 'DATE', date_i18n( 'd/m/Y' ) );
		$xml->addAttribute( 'TIME', date_i18n( 'H:i:s' ) );
		$parent = $xml->addChild( 'PRODUCTS' );

		foreach ( $products->posts as $post ) {
			$product = wc_get_product( $post->ID );
			$productCode = $product->get_meta( '_wc_zap_productcode' );
			$node = $parent->addchild( 'PRODUCT' );
			$node->addAttribute( 'NUM', $product->get_id() );
			$node->PRODUCT_URL 		= wp_get_shortlink( $post->ID );
			$node->PRODUCT_NAME 	= $product->get_meta( '_wc_zap_product_name' );
			$node->MODEL 			= $product->get_meta( '_wc_zap_product_model' );
			$node->DETAILS 			= $product->get_meta( '_wc_zap_product_description' );
			$node->CATALOG_NUMBER	= $product->get_meta( '_wc_zap_product_catalog_number' );
			$node->PRODUCTCODE		= $productCode ? $productCode : $product->get_id();
			$node->CURRENCY 		= 'ILS';
			$node->PRICE 			= $product->get_meta( '_wc_zap_product_price' );
			$node->SHIPMENT_COST 	= $product->get_meta( '_wc_zap_shipment' );
			$node->DELIVERY_TIME 	= $product->get_meta( '_wc_zap_delivery' );
			$node->MANUFACTURER 	= $product->get_meta( '_wc_zap_manufacturer' );
			$node->WARRANTY 		= $product->get_meta( '_wc_zap_warranty' );
			$node->IMAGE 			= $product->get_image_id() ? wp_get_attachment_url( $product->get_image_id() ) : wc_placeholder_img_src();
			$node->TAX 				= 1;
			
			/**
			 * Fires after each node properties have been set.
			 */
			do_action_ref_array( 'wc_zap_mirror_xml_node', array( &$node, $product, $post ) );
			
			/**
			 * Fires after specific node properties have been set.
			 */
			do_action_ref_array( "wc_zap_mirror_xml_node_{$post->ID}", array( &$node, $product, $post ) );
		}
		
		/**
		 * Fires after XML is complete.
		 */
		do_action_ref_array( 'wc_zap_mirror_xml', array( &$xml, $products ) );

		echo $xml->asXML();
	}

}

/**
 * @return WC_Zap_Mirror
 */
function WC_Zap_Mirror() {
	return WC_Zap_Mirror::instance();
}

// Global for backwards compatibility.
$GLOBALS['wc_zap_mirror'] = WC_Zap_Mirror();
