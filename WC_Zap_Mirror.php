<?php
/*
Plugin Name: Woo Zap Mirror
Plugin URI:  https://wordpress.org/plugins/woo-zap-mirror/
Description: Creates a mirror site for Zap.
Version:     1.2.2
Author:      Ido Friedlander
Author URI:  https://profiles.wordpress.org/idofri/
Text Domain: woo-zap-mirror
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Zap Mirror site for WooCommerce
 */
class WC_Zap_Mirror {
	
	public static $info;
	public static $slug;
	
	/**
	 * Constructor: setups filters and actions
	 */
	public function __construct() {
		
		/**
		 * Localization
		 */
		$this->localization();
		
		/**
		 * Filters
		 */
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		
		/**
		 * Actions
		 */
		add_action( 'init', array( $this, 'init_slug' ) );
		add_action( 'admin_init',  array( $this, 'plugin_info' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 99 );
		add_action( 'admin_init', array( $this, 'register_zap_settings' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'mirror' ), 9999 );
	}
	
	/**
	 * Plugin info
	 */
	public function plugin_info() {
		self::$info = get_plugin_data( __FILE__ );
	}
	
	/**
	 * Load text-domain
	 */
	public function localization() {
		load_plugin_textdomain( 'woo-zap-mirror' );
	}
	
	/**
	 * Load mirror slug
	 *
	 * @return string mirror site slug
	 */
	public function init_slug() {
		self::$slug = get_option( 'wc_zap_mirror_url', WC_Zap_Mirror::get_unique_slug() );
		return self::$slug;
	}
	
	/**
	 * home_url() + Multilang compatibility
	 *
	 * @since 1.1.8
	 *
	 * @param string $input
	 *
	 * @return string modified home url
	 */
	public function home_url( $slug = '', $params = array() ) {
		
		global $wp_rewrite;
		
		$url = '';
		
		/**
		 * Polylang
		 *
		 * @since 1.1.8
		 *
		 */
		if ( function_exists( 'pll_home_url' ) ) {
			
			$language = ( pll_current_language() ) ? pll_current_language() : pll_default_language();
			
			if ( empty( $wp_rewrite->permalink_structure ) ) {
				$url = home_url( '/?lang=' . $language . '&pagename=' . $slug );
			} else {
				$url = home_url( '/' . $language . '/' . $slug );
			}
			
			$url = trailingslashit( $url );
			
			if ( $params ) {
				$url = add_query_arg( $params, $url );
			}
			
		/**
		 * WPML
		 *
		 * @since 1.1.8
		 *
		 */
		} elseif ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			
			global $sitepress;
			
			$settings = $sitepress->get_settings();
			$language = ( ICL_LANGUAGE_CODE === 'all' ) ? $sitepress->get_default_language() : ICL_LANGUAGE_CODE;
			
			if ( empty( $wp_rewrite->permalink_structure ) || $settings['language_negotiation_type'] == 3 ) {
				$url = add_query_arg( 'pagename', $slug, $sitepress->language_url( $language ) );
				
				if ( empty( $slug ) ) {
					$url .= '=';
				}
				
			} else {
				$url = trailingslashit( $sitepress->language_url( $language ) );
				
				if ( !empty( $slug ) ) {
					$url .= $slug;
					$url = trailingslashit( $url );
				}
			}
			
			if ( !empty( $params ) ) {
				$url = add_query_arg( $params, $url );
			}
			
		} else {
			
			if ( empty( $wp_rewrite->permalink_structure ) ) {
				$url = home_url( '/?pagename=' . $slug );
			} else {
				$url = home_url( $wp_rewrite->front . $slug );
			}
			
			if ( $params ) {
				$url = add_query_arg( $params, $url );
			}
		}
		
		return $url;
	}
	
	/**
	 * Register query vars
	 *
	 * @param array $vars array of current query vars
	 *
	 * @return array modified vars array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'product_cat';
		return $vars;
	}
	
	/**
	 * Check if mirror site is accessed
	 *
	 * @return bool check if mirror-site request
	 */
	private function is_mirror() {
		
		global $wp, $wp_query, $wp_rewrite;
		
		if ( !$wp_rewrite->permalink_structure && isset( $wp_query->query[ 'pagename' ] ) &&  $wp_query->query[ 'pagename' ] === self::$slug ) {
			return true;
		}
		
		if ( trailingslashit( $this->home_url( self::$slug ) ) === trailingslashit( home_url( $wp->request ) ) && $wp_query->is_404 ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Mirror site redirection handle
	 */
	public function mirror() {
		
		global $wp_query;
		
		if ( $this->is_mirror() ) {
			
			$wp_query->is_404 = false;
			$wp_query->is_archive = true;
			header("HTTP/1.1 200 OK");
			
			$exclude = get_option( 'wc_zap_hide_tax', array( 'product_cat' => array() ) );
			
			if ( false !== get_query_var( 'product_cat', false ) ) {
				
				$product_cat = absint( get_query_var( 'product_cat' ) );
				
				if ( $product_cat && !in_array( $product_cat, $exclude[ 'product_cat' ] ) ) {
					
					$products = $this->get_products_by_term( $product_cat );
					
					if ( $products->have_posts() ) {
						
						header("Content-Type: application/xml; charset=utf-8");
				
						$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><STORE></STORE>');
						$xml->addAttribute( 'URL', get_site_url() );
						$xml->addAttribute( 'DATE', date_i18n( 'd/m/Y' ) );
						$xml->addAttribute( 'TIME', date_i18n( 'H:i:s' ) );
						$xml->addChild( 'PRODUCTS' );
						
						foreach ( $products->posts as $post ) {
							
							$product = new WC_Product( $post->ID );
							$node = $xml->addchild( 'PRODUCT' );
							$node->addAttribute( 'NUM', $product->id );
							
							$node->PRODUCT_URL 		= get_permalink( $product->id );
							$node->PRODUCT_NAME 	= $product->__get( 'wc_zap_product_name' );
							$node->MODEL 			= $product->__get( 'wc_zap_product_model' );
							$node->DETAILS 			= $product->__get( 'wc_zap_product_description' );
							$node->CATALOG_NUMBER	= $product->__get( 'wc_zap_product_catalog_number' );
							$node->CURRENCY 		= 'ILS';
							$node->PRICE 			= $product->__get( 'wc_zap_product_price' );
							$node->SHIPMENT_COST 	= $product->__get( 'wc_zap_shipment' );
							$node->DELIVERY_TIME 	= $product->__get( 'wc_zap_delivery' );
							$node->MANUFACTURER 	= $product->__get( 'wc_zap_manufacturer' );
							$node->WARRANTY 		= $product->__get( 'wc_zap_warranty' );
							$node->IMAGE 			= $product->get_image_id() ? wp_get_attachment_url( $product->get_image_id() ) : wc_placeholder_img_src();
							$node->TAX 				= 1;
						}
						
						exit( $xml->asXML() );
						
					} else {
						
						wp_die( __( 'No available products.', 'woo-zap-mirror' ) );
						
					}
				}
				
			} else {
				
				if ( $terms = $this->get_tax_hierarchical( 'product_cat' ) ) {
					
					ob_start();
					?>
					<!DOCTYPE html>
					<html <?php language_attributes(); ?>>
					<head>
						<title><?php _e( 'Zap Mirror Site', 'woo-zap-mirror' ); ?></title>
						<link rel="stylesheet" href="<?php echo plugins_url('/assets/dtree/dtree.css', __FILE__ ); ?>" type="text/css" />
						<script type="text/javascript" src="<?php echo plugins_url('/assets/dtree/dtree.js', __FILE__ ); ?>"></script>
					</head>
					<body>
						<script type="text/javascript">
							d = new dTree("d");
							
							d.icon.root = "<?php echo plugins_url('/assets/dtree/img/globe.gif', __FILE__ ); ?>";
							d.icon.folder = "<?php echo plugins_url('/assets/dtree/img/folder.gif', __FILE__ ); ?>";
							d.icon.folderOpen = "<?php echo plugins_url('/assets/dtree/img/folderopen.gif', __FILE__ ); ?>";
							d.icon.node = "<?php echo plugins_url('/assets/dtree/img/folder.gif', __FILE__ ); ?>";
							d.icon.empty = "<?php echo plugins_url('/assets/dtree/img/empty.gif', __FILE__ ); ?>";
							d.icon.line = "<?php echo plugins_url('/assets/dtree/img/line.gif', __FILE__ ); ?>";
							d.icon.join = "<?php echo plugins_url('/assets/dtree/img/join.gif', __FILE__ ); ?>";
							d.icon.joinBottom = "<?php echo plugins_url('/assets/dtree/img/joinbottom.gif', __FILE__ ); ?>";
							d.icon.plus = "<?php echo plugins_url('/assets/dtree/img/plus.gif', __FILE__ ); ?>";
							d.icon.plusBottom = "<?php echo plugins_url('/assets/dtree/img/plusbottom.gif', __FILE__ ); ?>";
							d.icon.minus = "<?php echo plugins_url('/assets/dtree/img/minus.gif', __FILE__ ); ?>";
							d.icon.minusBottom = "<?php echo plugins_url('/assets/dtree/img/minusbottom.gif', __FILE__ ); ?>";
							d.icon.nlPlus = "<?php echo plugins_url('/assets/dtree/img/nolines_plus.gif', __FILE__ ); ?>";
							d.icon.nlMinus = "<?php echo plugins_url('/assets/dtree/img/nolines_minus.gif', __FILE__ ); ?>";
							
							d.add(0, -1, "<?php echo get_bloginfo( 'name' ); ?>", "<?php echo $this->home_url( self::$slug ); ?>");
				
							<?php $this->print_nodes( $terms, $exclude['product_cat'] ); ?>
						
							document.write(d);
						</script>
					</body>
					</html>
					<?php
					
					exit( $this->html_minify( ob_get_clean() ) );
					
				} else {
					
					wp_die( __( 'No categories found.', 'woo-zap-mirror' ) );
					
				}
			}
		}
	}
	
	/**
	 * HTML minification
	 *
	 * @param string $buffer current buffer contents
	 *
	 * @return string minified html code
	 */
	public function html_minify( $buffer ) {
		
		$search = array(
			'/\>[^\S ]+/s',
			'/[^\S ]+\</s',
			'/(\s)+/s'
		);

		$replace = array(
			'>',
			'<',
			'\\1'
		);

		$buffer = preg_replace($search, $replace, $buffer);
		$buffer = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/', '', $buffer);
		$buffer = preg_replace('/\s+/', ' ', $buffer);
		
		return $buffer;
	}
	
	/**
	 * Get products
	 *
	 * @param int $term_id term id
	 *
	 * @return object products by term wp_query
	 */
	private function get_products_by_term( $term_id ) {
		
		$args = array( 'post_type' 		=> 'product', 
					   'posts_per_page' => -1,
					   'tax_query' 		=> array(
							array( 'taxonomy' 			=> 'product_cat',
								   'field' 				=> 'term_id',
								   'terms' 				=> $term_id,
								   'include_children'	=> false,
								   'operator' 			=> 'IN'
							), 
						),
						'meta_query' => array(
							'relation' => 'OR',
							array(
								'key'     	=> '_wc_zap_disable',
								'value'		=> 1,
								'compare' 	=> '!=',
							),
							array(
								'key' 		=> '_wc_zap_disable',
								'value' 	=> 1,
								'compare' 	=> 'NOT EXISTS'
							),
						),
					);
		
		return new WP_Query( $args );
	}
	
	/**
	 * Print tree nodes
	 *
	 * @param array $terms ids of terms to print
	 * @param array $exclude ids of terms to exclude
	 *
	 */
	public function print_nodes( $terms, $exclude ) {
		
		array_walk_recursive( $terms, function ( &$item, $key ) use ( $exclude ) {
			
			$instance = new WC_Zap_Mirror();
			
			if ( !in_array( $key, $exclude ) ) {
				echo 'd.add(' . $key . ', ' . $item->parent . ', "' . $item->name . '", "' . $instance->home_url( $instance::$slug, array( 'product_cat' => $key ) ) . '");' . PHP_EOL;
			}
			
			if ( !empty( $item->nodes ) ) {
				$instance->print_nodes( $item->nodes, $exclude );
			}
		});
	}
	
	/**
	 * Get terms hierarchically
	 *
	 * @param array|string $taxonomy the taxonomies
	 * @param int $parent parent term id
	 *
	 * @return array all terms sorted hierarchically
	 */
	private function get_tax_hierarchical( $taxonomy, $parent = 0 ) {
		
		$taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;
		$terms = get_terms( $taxonomy, array( 'parent' => $parent, 'hide_empty' => 0 ) );
		$nodes = array();
		
		foreach ( $terms as $term ) {
			$term->nodes = $this->get_tax_hierarchical( $taxonomy, $term->term_id );
			$nodes[ $term->term_id ] = $term;
		}
		
		return $nodes;
	}
	
	/**
	 * Save box metadata
	 *
	 * @param int $post_id the post id
	 * @param object $post the post object
	 *
	 */
	public function save( $post_id, $post ) {
		
		if ( !isset( $_POST['woo_zap_mirror_nonce'] ) || !wp_verify_nonce( $_POST['woo_zap_mirror_nonce'], 'woo_zap_update_settings' ) ) return;
		
		$disabled = ( isset( $_POST['_wc_zap_disable'] ) ) ? 1 : 0;
		update_post_meta( $post_id, '_wc_zap_disable', $disabled );
		
		if ( isset( $_POST['_wc_zap_product_name'] ) ) {
			update_post_meta( $post_id, '_wc_zap_product_name', wc_clean( mb_substr( $_POST['_wc_zap_product_name'], 0, 30, "utf-8" ) ) );
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
	
	/*
	 * Print metabox
	 *
	 * @param object $post the post object
	 *
	 */
	public function product_metabox( $post ) {
		
		$tags = array();
		$product = new WC_Product( $post->ID );
		
		if ( $attributes = $product->get_attributes() ) {
			
			foreach ( $attributes as $attribute ) {
				
				if ( $attribute['is_taxonomy'] ) {
					
					foreach ( $this->get_tax_hierarchical( $attribute['name'] ) as $term ) {
						$tags[] = $term->name;
					}
				
				} else {
					
					if ( is_array( $values = explode( '|', $attribute['value'] ) ) ) {
						
						foreach ( $values as $value ) {
							$tags[] = trim( $value );
						}
						
					} else {
						$tags[] = $values;
					}
				}
			}
			
			sort( $tags );
		}
		?>
		<p id="wc-zap-checkbox">
			<label for="wc-zap-disable">
				<input id="wc-zap-disable" name="_wc_zap_disable" type="checkbox" value="1" <?php checked('1', get_post_meta( $post->ID, '_wc_zap_disable', true ) ); ?>>
				<?php _e( 'Hide Product', 'woo-zap-mirror' ); ?>
			</label>
		</p>
		<div id="wc-zap-options" class="<?php if ( get_post_meta( $post->ID, '_wc_zap_disable', true ) ) { ?>disabled<?php } ?>">
			<div class="wc-zap-row-hidden">
				<?php wp_nonce_field( 'woo_zap_update_settings', 'woo_zap_mirror_nonce' ); ?>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Product Name', 'woo-zap-mirror' ); ?></strong>
					<p class="howto">(<?php _e( 'Maximum 30 characters', 'woo-zap-mirror' ); ?>)</p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-product-name"><?php _e( 'Product Name', 'woo-zap-mirror' ); ?></label>
					<input type="text" id="wc-zap-product-name" name="_wc_zap_product_name" class="wc_zap_input" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_product_name', true ) ); ?>" placeholder="<?php echo esc_attr( $post->post_title ); ?>" maxlength="30" />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Product Model', 'woo-zap-mirror' ); ?></strong>
					<p class="howto">(<?php _e( 'Optional', 'woo-zap-mirror' ); ?>)</p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-product-model"><?php _e( 'Product Model', 'woo-zap-mirror' ); ?></label>
					<input type="text" id="wc-zap-product-model" name="_wc_zap_product_model" class="wc_zap_input" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_product_model', true ) ); ?>"  />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Product Catalog Number', 'woo-zap-mirror' ); ?></strong>
					<p class="howto"></p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-product-catalog-number"><?php _e( 'Product Catalog Number', 'woo-zap-mirror' ); ?></label>
					<input type="text" id="wc-zap-product-catalog-number" name="_wc_zap_product_catalog_number" class="wc_zap_input" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_product_catalog_number', true ) ); ?>" placeholder="<?php echo esc_attr( $product->get_sku() ); ?>" />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Currency', 'woo-zap-mirror' ); ?></strong>
					<p class="howto"></p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-product-currency"><?php _e( 'Currency', 'woo-zap-mirror' ); ?></label>
					<input type="text" id="wc-zap-product-currency" class="wc_zap_input" value="ILS" disabled />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Price', 'woo-zap-mirror' ); ?></strong>
					<p class="howto">(<?php _e( 'Digits only', 'woo-zap-mirror' ); ?>)</p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-product-price"><?php _e( 'Price', 'woo-zap-mirror' ); ?></label>
					<input type="number" id="wc-zap-product-price" name="_wc_zap_product_price" class="wc_zap_input" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_product_price', true ) ); ?>" min="0" step="any" placeholder="<?php echo esc_attr( absint( $product->get_price() ) ); ?>" />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Shipping Costs', 'woo-zap-mirror' ); ?></strong>
					<p class="howto">(<?php _e( 'Digits only', 'woo-zap-mirror' ); ?>)</p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-shipment"><?php _e( 'Shipping Costs', 'woo-zap-mirror' ); ?></label>
					<input type="number" id="wc-zap-shipment" name="_wc_zap_shipment" class="wc_zap_input" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_shipment', true ) ); ?>" min="0" step="any" />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Delivery Time', 'woo-zap-mirror' ); ?></strong>
					<p class="howto">(<?php _e( 'Digits only', 'woo-zap-mirror' ); ?>)</p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-delivery"><?php _e( 'Delivery Time', 'woo-zap-mirror' ); ?></label>
					<input type="number" id="wc-zap-delivery" name="_wc_zap_delivery" class="wc_zap_input" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_delivery', true ) ); ?>" min="0" />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Manufacturer', 'woo-zap-mirror' ); ?></strong>
					<p class="howto">(<?php _e( 'Should also appear in product\'s name', 'woo-zap-mirror' ); ?>)</p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-manufacturer"><?php _e( 'Manufacturer', 'woo-zap-mirror' ); ?></label>
					<input type="text" id="wc-zap-manufacturer" name="_wc_zap_manufacturer" class="wc_zap_input" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_manufacturer', true ) ); ?>" />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Warranty', 'woo-zap-mirror' ); ?></strong>
					<p class="howto"></p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-warranty"><?php _e( 'Warranty', 'woo-zap-mirror' ); ?></label>
					<input type="text" id="wc-zap-warranty" name="_wc_zap_warranty" class="wc_zap_input" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_warranty', true ) ); ?>" />
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label wc-zap-valign-top">
					<strong><?php _e( 'Product Description', 'woo-zap-mirror' ); ?></strong>
					<p class="howto">(<?php _e( 'Maximum 255 characters', 'woo-zap-mirror' ); ?>)</p>
				</div><!--
				--><div class="wc-zap-row-input">
					<label class="screen-reader-text" for="wc-zap-product-description"><?php _e( 'Product Description', 'woo-zap-mirror' ); ?></label>
					<textarea id="wc-zap-product-description" name="_wc_zap_product_description" class="wc_zap_input" placeholder="<?php echo esc_attr( $post->post_excerpt ); ?>" rows="3" maxlength="255"><?php echo esc_attr( get_post_meta( $post->ID, '_wc_zap_product_description', true ) ); ?></textarea>
				</div>
			</div>
			<div class="wc-zap-row">
				<div class="wc-zap-row-label">
					<strong><?php _e( 'Image', 'woo-zap-mirror' ); ?></strong>
					<p class="howto"></p>
				</div><!--
				--><div class="wc-zap-row-input">
					<?php $product_image_src = ( $product->get_image_id() ) ? wp_get_attachment_url( $product->get_image_id(), 'thumbnail' ) : wc_placeholder_img_src(); ?>
					<label class="screen-reader-text" for="wc-zap-product-image"><?php _e( 'Image', 'woo-zap-mirror' ); ?></label>
					<img src="<?php echo $product_image_src; ?>" id="wc-zap-product-image" class="wc_zap_input" />
				</div>
			</div>
		</div>
		<script>
		var tagsAttributes = ["<?php echo implode( '","', $tags ); ?>"];
		var SelectionLimit = "<?php _e( 'Only single value allowed', 'woo-zap-mirror' ); ?>";
		</script>
		<?php
	}
	
	/**
	 * Add settings page
	 */
	public function admin_menu() {
		add_submenu_page( 'woocommerce', __( 'Zap Settings', 'woo-zap-mirror' ), __( 'Zap Settings', 'woo-zap-mirror' ), 'manage_options', 'zap-settings', array( $this, 'wc_zap_submenu_page_callback' ) );
	}
	
	/**
	 * Setup options
	 */
	public static function set_up_options() {
		add_option( 'wc_zap_mirror_url', WC_Zap_Mirror::get_unique_slug() );
		add_option( 'wc_zap_hide_tax', array( 'product_cat' => array() ) );
	}
	
	/**
	 * Mirror slug validation
	 *
	 * @param string $input the requested slug
	 *
	 * @return string the validated slug
	 */
	public function validate_mirror_slug( $input ) {
		return $this->get_unique_slug( $input );
	}
	
	/**
	 * Get unique slug
	 *
	 * @param string $input input slug
	 *
	 * @return string unique slug
	 */
	public static function get_unique_slug( $input = '' ) {
		
		$input = sanitize_title( $input );
		
		if ( empty( $input ) ) $input = 'zap';
		
		$slug = wp_unique_post_slug( $input, 0, 'publish', 'post', 0 );
		$slug = wp_unique_post_slug( $slug, 0, 'publish', 'page', 0 );
		
		return $slug;
	}
	
	/**
	 * Hidden terms validation
	 *
	 * @param array|null $input the terms
	 *
	 * @return array the hidden terms
	 */
	public function validate_hidden_terms( $input ) {
		
		if ( empty( $input ) ) {
			$input = array( 'product_cat' => array() );
		}
		
		return $input;
	}
	
	/**
	 * Register page settings
	 */
	public function register_zap_settings() {
		register_setting( 'zap-settings-general', 'wc_zap_mirror_url', array( $this, 'validate_mirror_slug' ) );
		register_setting( 'zap-settings-hidden', 'wc_zap_hide_tax', array( $this, 'validate_hidden_terms' ) );
	}
	
	/**
	 * Print settings page
	 */
	public function wc_zap_submenu_page_callback() {
		
		include( plugin_dir_path( __FILE__ ) . '/includes/class-walker-product-tax-checklist.php' );
		
		if ( !$tags = get_option( 'wc_zap_hide_tax' ) ) {
			$tags['product_cat'] = array();
		}
		
		$tabs = array( 'general' => __( 'General Settings', 'woo-zap-mirror' ), 'hidden' => __( 'Hide Categories', 'woo-zap-mirror' ) );
		
		?>
		<div id="wc-zap-settings" class="wrap">
			<h1 class="hndle ui-sortable-handle"><span><?php _e( 'Zap Settings', 'woo-zap-mirror' ); ?></span></h1>
			<form method="post" action="<?php echo admin_url('options.php'); ?>">
				<?php
				 echo '<h2 class="nav-tab-wrapper">';
				
				 foreach( $tabs as $tab => $name ){
					$current = ( isset ( $_GET['tab'] ) ) ? $_GET['tab'] : 'general';
					$class = ( $tab == $current ) ? ' nav-tab-active' : '';
					echo "<a class='nav-tab$class' href='" . admin_url('admin.php?page=zap-settings') . "&tab=$tab'>$name</a>";
				}
				
				echo '</h2>';
				
				if ( isset( $_REQUEST['settings-updated'] ) ) {
				?>
				<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
					<p><strong><?php _e( 'Settings saved.', 'woo-zap-mirror' ); ?></strong></p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'woo-zap-mirror' ); ?></span>
					</button>
				</div>
				<?php
				}
				
				switch ( $current ) {
					
					case 'general':
						
						$permalink = $this->home_url();
						$slug = esc_attr( get_option( 'wc_zap_mirror_url',  $this->get_unique_slug() ) );
						?>
						<br/>
						<div class="postbox">
							<div class="inside">
								<?php
								settings_fields( 'zap-settings-general' );
								do_settings_sections( 'zap-settings-general' );
								?>
								<div class="categorydiv">
									<div class="tabs-panel">
										<div class="wc-zap-setting">
											<div class="wc-zap-row-label">
												<strong><?php _e( 'Mirror URL', 'woo-zap-mirror' ); ?></strong>
											</div><!--
											--><div class="wc-zap-row-input">
												<label class="screen-reader-text" for="wc-zap-mirror-url"><?php _e( 'Mirror URL', 'woo-zap-mirror' ); ?></label>
												<label id="wc-zap-placeholder" for="wc-zap-mirror-url"><?php echo $permalink; ?></label>
												<input type="text" id="wc-zap-mirror-url" name="wc_zap_mirror_url" class="wc_zap_input" value="<?php echo $slug; ?>" />
											</div>
											<a class="preview button" href="<?php echo $permalink . $slug; ?>" target="_blank"><?php _e( 'Preview', 'woo-zap-mirror' ); ?></a>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						break;
					
					case 'hidden':
						?>
						<div class="manage-menus">
							<span class="add-edit-menu-action"><?php _e( 'Check the categories you wish to hide on the mirror site', 'woo-zap-mirror' ); ?></span>
						</div>
						<br/>
						<div class="postbox">
							<div class="inside">
								<?php
								settings_fields( 'zap-settings-hidden' );
								do_settings_sections( 'zap-settings-hidden' );
								
								$args = array(
									'taxonomy' 		=> 'product_cat',
									'walker'   		=> new Walker_Product_Tax_Checklist( $tags['product_cat'] ),
									'title_li' 		=> '',
									'hide_empty'	=> 0
								);
								?>
								<div class="categorydiv">
									<div class="tabs-panel">
										<ul class="categorychecklist">
											<?php wp_list_categories( $args ); ?>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<?php
						break;
				}
				?>
				<p class="wc_top">
					<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php _e( 'Save Options', 'woo-zap-mirror' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Load admin scripts and styles
	 *
	 * @param string $hook current page
	 *
	 */
	public function admin_scripts( $hook ) {
		
		global $post_type;
		
		if( 'product' === $post_type || strpos( $hook, '_page_zap-settings' ) !== false ) {
			wp_enqueue_script( 'woo-zap-mirror', plugins_url('/assets/settings.js', __FILE__ ), array( 'jquery' ), self::$info['Version'] );
			wp_enqueue_style( 'woo-zap-mirror', plugins_url('/assets/style.css', __FILE__ ), array(), self::$info['Version'] );
		}
	}
	
	/**
	 * Register product settings metabox
	 */
	public function add_meta_boxes() {
		add_meta_box( 'woo-zap-mirror', __( 'Zap Settings', 'woo-zap-mirror' ), array( $this, 'product_metabox' ), 'product', 'advanced', 'low' );
	}
}

/**
 * Instantiate
 */
if ( class_exists( 'SimpleXMLElement' ) && in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	register_activation_hook( __FILE__, array( 'WC_Zap_Mirror', 'set_up_options' ) );
	
	new WC_Zap_Mirror();
}