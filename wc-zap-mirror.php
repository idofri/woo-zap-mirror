<?php
/**
 * Plugin Name: Woo Zap Mirror
 * Plugin URI: https://wordpress.org/plugins/woo-zap-mirror/
 * Description: Creates a mirror site for Zap.
 * Version: 1.4
 * Author: Ido Friedlander
 * Author URI: https://profiles.wordpress.org/idofri
 * Text Domain: woo-zap-mirror
 *
 * WC requires at least: 3.0
 * WC tested up to: 3.6
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** @class WC_Zap_Mirror */
class WC_Zap_Mirror {

    protected static $instance = null;

    protected $notices = [];

    public $version = '1.4';

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_init',                            [ $this, 'checkEnvironment' ] );
        add_action( 'admin_notices',                         [ $this, 'renderAdminNotices' ] );
        add_action( 'plugins_loaded',                        [ $this, 'loadPluginTextdomain' ] );
        add_action( 'wc_zap_mirror_head',                    [ $this, 'yoastSeoHead' ] );
        add_action( 'admin_enqueue_scripts',                 [ $this, 'enqueueAdminScripts' ] );
        add_action( 'woocommerce_settings_tabs_zap_mirror',  [ $this, 'renderSettingsTab' ] );
        add_action( 'woocommerce_update_options_zap_mirror', [ $this, 'updateSettingsTab' ] );
        add_action( 'woocommerce_admin_field_checklist',     [ $this, 'renderChecklistField' ] );
        add_action( 'woocommerce_product_data_tabs',         [ $this, 'addProductDataTab' ] );
        add_action( 'woocommerce_product_data_panels',       [ $this, 'renderProductDataPanel' ] );
        add_action( 'woocommerce_process_product_meta',      [ $this, 'updateProductMeta' ] );
        add_filter( 'woocommerce_settings_tabs_array',       [ $this, 'addSettingsTab' ], 25 );
        add_filter( 'query_vars',                            [ $this, 'addCustomQueryVar' ] );
        add_filter( 'template_include',                      [ $this, 'renderMirrorPage' ], 0 );
    }

    public function checkEnvironment() {
        if ( is_admin() && current_user_can( 'activate_plugins' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $class = 'notice notice-error is-dismissible';
            $message = __( 'This plugin requires <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> to be active.', 'woo-zap-mirror' );
            $this->addAdminNotice( $class, $message );
            // Deactivate the plugin
            deactivate_plugins( __FILE__ );
            return;
        }

        if ( ! class_exists( 'SimpleXMLElement' ) ) {
            $class = 'notice notice-error is-dismissible';
            $message = __( 'The SimpleXMLElement extension could not be found. Please ask your host to install this extension.', 'woo-zap-mirror' );
            $this->addAdminNotice( $class, $message );
        }

        $php_version = phpversion();
        $required_php_version = '5.6';
        if ( version_compare( $required_php_version, $php_version, '>' ) ) {
            $class = 'notice notice-warning is-dismissible';
            $message = sprintf( __( 'Your server is running PHP version %1$s but some features requires at least %2$s.', 'woo-zap-mirror' ), $php_version, $required_php_version );
            $this->addAdminNotice( $class, $message );
        }
    }

    public function yoastSeoHead() {
        do_action( 'wpseo_head' );
    }

    public function loadPluginTextdomain() {
        load_plugin_textdomain( 'woo-zap-mirror' );
    }

    public function getPluginUrl() {
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }

    public function getPluginPath() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    public function addAdminNotice( $class, $message ) {
        $this->notices[] = [
            'class'   => $class,
            'message' => $message
        ];
    }

    public function renderAdminNotices() {
        foreach ( $this->notices as $notice ) {
            echo '<div class="' . esc_attr( $notice['class'] ) . '"><p><b>' . __( 'Woo Zap Mirror', 'woo-zap-mirror' ) . ': </b>';
            echo wp_kses( $notice['message'], [ 'a' => [ 'href' => [] ] ] );
            echo '</p></div>';
        }
    }

    public function enqueueAdminScripts() {
        wp_enqueue_style( 'woo-zap-mirror', plugins_url( '/assets/css/style.css', __FILE__ ), [], $this->version );
    }

    public function addCustomQueryVar( $vars ) {
        $vars[] = 'product_cat';
        return $vars;
    }

    public function addProductDataTab( $tabs ) {
        $tabs['zap'] = [
            'label'  => __( 'Zap Settings', 'woo-zap-mirror' ),
            'target' => 'zap_product_data',
            'class'  => ''
        ];

        return $tabs;
    }

    public function renderProductDataPanel() {
        global $post;

        ?><div id="zap_product_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <?php
                do_action( 'wc_zap_mirror_before_options' );

                woocommerce_wp_checkbox( [
                    'id'    => '_wc_zap_disable',
                    'label' => __( 'Hide Product', 'woo-zap-mirror' )
                ] );

                woocommerce_wp_text_input( [
                    'id'          => '_wc_zap_product_name',
                    'label'       => __( 'Product Name', 'woo-zap-mirror' ),
                    'type'        => 'text',
                    'description' => __( 'Maximum 40 characters', 'woo-zap-mirror' )
                 ] );

                woocommerce_wp_text_input( [
                    'id'          => '_wc_zap_product_model',
                    'label'       => __( 'Product Model', 'woo-zap-mirror' ),
                    'type'        => 'text',
                    'description' => __( 'Optional', 'woo-zap-mirror' )
                 ] );

                woocommerce_wp_text_input( [
                    'id'    => '_wc_zap_product_catalog_number',
                    'label' => __( 'Product Catalog Number', 'woo-zap-mirror' ),
                    'type'  => 'text'
                ] );

                $productCode = get_post_meta( $post->ID, '_wc_zap_productcode', true );
                woocommerce_wp_text_input( [
                    'id'    => '_wc_zap_productcode',
                    'label' => __( 'Product Code', 'woo-zap-mirror' ),
                    'type'  => 'text',
                    'value' => $productCode ? $productCode : $post->ID
                ] );

                woocommerce_wp_text_input( [
                    'id'                => '_wc_zap_product_currency',
                    'value'             => 'ILS',
                    'label'             => __( 'Currency', 'woo-zap-mirror' ),
                    'type'              => 'text',
                    'custom_attributes' => [
                        'disabled' => 'disabled'
                    ]
                ] );

                woocommerce_wp_text_input( [
                    'id'                => '_wc_zap_product_price',
                    'label'             => __( 'Price', 'woo-zap-mirror' ),
                    'type'              => 'number',
                    'description'       => __( 'Digits only', 'woo-zap-mirror' ),
                    'custom_attributes' => [
                        'step' => 'any',
                        'min'  => '0'
                    ]
                 ] );

                woocommerce_wp_text_input( [
                    'id'                => '_wc_zap_shipment',
                    'label'             => __( 'Shipping Costs', 'woo-zap-mirror' ),
                    'type'              => 'number',
                    'description'       => __( 'Digits only', 'woo-zap-mirror' ),
                    'custom_attributes' => [
                        'step' => 'any',
                        'min'  => '0'
                    ]
                ] );

                woocommerce_wp_text_input( [
                    'id'                => '_wc_zap_delivery',
                    'label'             => __( 'Delivery Time', 'woo-zap-mirror' ),
                    'type'              => 'number',
                    'description'       => __( 'Digits only', 'woo-zap-mirror' ),
                    'custom_attributes' => [
                        'step' => 'any',
                        'min'  => '0'
                    ]
                ] );

                woocommerce_wp_text_input( [
                    'id'          => '_wc_zap_manufacturer',
                    'label'       => __( 'Manufacturer', 'woo-zap-mirror' ),
                    'type'        => 'text',
                    'description' => __( 'Should also appear in product\'s name', 'woo-zap-mirror' )
                ] );

                woocommerce_wp_text_input( [
                    'id'    => '_wc_zap_warranty',
                    'label' => __( 'Warranty', 'woo-zap-mirror' ),
                    'type'  => 'text'
                ] );

                woocommerce_wp_textarea_input( [
                    'id'                => '_wc_zap_product_description',
                    'rows'              => 5,
                    'style'             => 'height: auto;',
                    'label'             => __( 'Product Description', 'woo-zap-mirror' ),
                    'description'       => __( 'Maximum 255 characters', 'woo-zap-mirror' ),
                    'custom_attributes' => [
                        'maxlength' => '255'
                    ]
                ] );

                do_action( 'wc_zap_mirror_after_options' );

                ?><p class="form-field">
                    <label for="_wc_zap_product_image"><?php _e( 'Image', 'woo-zap-mirror' ); ?></label>
                    <img src="<?php echo has_post_thumbnail() ? get_the_post_thumbnail_url( $post->ID, 'shop_thumbnail' ) : wc_placeholder_img_src(); ?>" id="_wc_zap_product_image" />
                </p>
            </div>
        </div><?php
    }

    public function updateProductMeta( $post_id ) {
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

    public function addSettingsTab( $settings_tabs ) {
        $settings_tabs[ 'zap_mirror' ] = __( 'Zap Mirror Site', 'woo-zap-mirror' );
        return $settings_tabs;
    }

    public function renderSettingsTab() {
        woocommerce_admin_fields( $this->getSettings() );
    }

    public function updateSettingsTab() {
        if ( ! isset( $_POST['wc_zap_mirror_excluded_term_ids'] ) ) {
            $_POST['wc_zap_mirror_excluded_term_ids'] = [];
        }
        woocommerce_update_options( $this->getSettings() );
    }

    public function getSettings() {
        $settings = [
            [
                'title' => __( 'Zap Settings', 'woo-zap-mirror' ),
                'id'    => 'wc_zap_mirror_options',
                'type'  => 'title'
            ],
            [
                'title'    => __( 'Mirror Site Page', 'woo-zap-mirror' ),
                'id'       => 'woocommerce_zap_page_id',
                'type'     => 'single_select_page',
                'default'  => '',
                'class'    => 'wc-enhanced-select-nostd',
                'css'      => 'min-width: 300px;',
                'desc_tip' => __( 'This sets the zap mirror page of your shop.', 'woo-zap-mirror' )
            ],
            [
                'title'    => __( 'Hide Categories', 'woo-zap-mirror' ),
                'id'       => 'wc_zap_mirror_excluded_term_ids',
                'type'     => 'checklist',
                'default'  => 0,
                'css'      => 'max-width: 400px; padding: 10px 15px; box-sizing: border-box;',
                'desc_tip' => __( 'Check the categories you wish to hide on the mirror site', 'woo-zap-mirror' )
            ],
            [
                'type' => 'sectionend',
                'id'   => 'wc_zap_mirror_options'
            ]
        ];

        return apply_filters( 'wc_zap_mirror_settings', $settings );
    }

    public function renderChecklistField( $value ) {
        $field_description = WC_Admin_Settings::get_field_description( $value );
        extract( $field_description );

        ?><tr valign="top" class="">
            <th scope="row" class="titledesc">
                <label><?php echo esc_html( $value['title'] ) ?> <?php echo $tooltip_html; ?></label>
            </th>
            <td class="forminp categorydiv">
                <div id="product_cat-all" class="tabs-panel" style="<?php echo $value['css']; ?>">
                    <ul id="product_catchecklist" data-wp-lists="list:product_cat" class="categorychecklist form-no-clear">
                        <?php echo str_replace(
                            'tax_input[product_cat]',
                            $value['id'],
                            wp_terms_checklist(
                                wc_get_page_id( 'zap' ),
                                [
                                    'selected_cats' => WC_Admin_Settings::get_option( $value['id'] ),
                                    'taxonomy'      => 'product_cat',
                                    'echo'          => false
                                ]
                            )
                        ); ?>
                    </ul>
                </div>
                <?php echo $description; ?>
            </td>
        </tr><?php
    }

    public function getProductsByTerm( $term_id ) {
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => [ [
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => $term_id,
                'include_children' => false,
                'operator'         => 'IN'
            ] ],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key'     => '_wc_zap_disable',
                    'value'   => 'yes',
                    'compare' => '!='
                ],
                [
                    'key'     => '_wc_zap_disable',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];

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

    public function getProductTerms( $exclude_tree = [] ) {
        $args = [
            'taxonomy'     => 'product_cat',
            'hide_empty'   => 0,
            'exclude_tree' => $exclude_tree
        ];

        /**
         * Filters the arguments passed to the get_terms() function.
         */
        $args = apply_filters( 'wc_zap_mirror_get_terms', $args, $exclude_tree );

        return get_terms( $args );
    }

    public function renderMirrorPage( $template ) {
        if ( ! is_page( wc_get_page_id( 'zap' ) ) ) {
            return $template;
        }

        // Tell WP Super Cache & W3 Total Cache to not cache requests
        define( 'DONOTCACHEPAGE', true );

        $exclude_tree = $this->getExcludedTerms();
        $term_id = get_query_var( 'product_cat' );

        if ( $term_id && ! in_array( $term_id, $exclude_tree ) ) {
            $this->renderXmlTemplate( $term_id, $exclude_tree );
        } elseif ( $terms = $this->getProductTerms( $exclude_tree ) ) {
            $this->renderHtmlTemplate( $terms );
        } else {
            wp_die( __( 'No categories found.', 'woo-zap-mirror' ) );
        }

        return;
    }

    public function renderHtmlTemplate( $terms ) {
        $pageUrl = wc_get_page_permalink( 'zap' );

        ob_start();
        foreach ( $terms as $term ) {
            $id   = $term->term_id;
            $pid  = $term->parent;
            $name = esc_attr( $term->name );
            $url  = add_query_arg( 'product_cat', $term->term_id, $pageUrl );
            echo "d.add({$id}, {$pid}, '{$name}', '{$url}');\n";

            // Indentation
            if ( $term !== end( $terms ) ) {
                echo "\t\t";
            }
        }
        $nodes = ob_get_clean();

        wc_get_template( 'zap/mirror.php', [ 'nodes' => $nodes ], null, $this->getPluginPath() . '/templates/' );
    }

    public function renderXmlTemplate( $term_id ) {
        $products = $this->getProductsByTerm( $term_id );
        if ( ! $products->have_posts() ) {
            wp_die( __( 'No available products.', 'woo-zap-mirror' ) );
        }

        // Xml headers
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
            $node->PRODUCT_URL    = wp_get_shortlink( $post->ID );
            $node->PRODUCT_NAME   = $product->get_meta( '_wc_zap_product_name' );
            $node->MODEL          = $product->get_meta( '_wc_zap_product_model' );
            $node->DETAILS        = $product->get_meta( '_wc_zap_product_description' );
            $node->CATALOG_NUMBER = $product->get_meta( '_wc_zap_product_catalog_number' );
            $node->PRODUCTCODE    = $productCode ? $productCode : $product->get_id();
            $node->CURRENCY       = 'ILS';
            $node->PRICE          = $product->get_meta( '_wc_zap_product_price' );
            $node->SHIPMENT_COST  = $product->get_meta( '_wc_zap_shipment' );
            $node->DELIVERY_TIME  = $product->get_meta( '_wc_zap_delivery' );
            $node->MANUFACTURER   = $product->get_meta( '_wc_zap_manufacturer' );
            $node->WARRANTY       = $product->get_meta( '_wc_zap_warranty' );
            $node->IMAGE          = $product->get_image_id() ? wp_get_attachment_url( $product->get_image_id() ) : wc_placeholder_img_src();
            $node->TAX            = 1;

            /**
             * Fires after each node properties have been set.
             */
            do_action_ref_array( 'wc_zap_mirror_xml_node', [ &$node, $product, $post ] );

            /**
             * Fires after specific node properties have been set.
             */
            do_action_ref_array( "wc_zap_mirror_xml_node_{$post->ID}", [ &$node, $product, $post ] );
        }

        /**
         * Fires after XML is complete.
         */
        do_action_ref_array( 'wc_zap_mirror_xml', [ &$xml, $products ] );

        echo $xml->asXML();
    }

    public function getExcludedTerms() {
        $term_ids = WC_Admin_Settings::get_option( 'wc_zap_mirror_excluded_term_ids', [] );
        return apply_filters( 'wc_zap_mirror_excluded_term_ids', $term_ids );
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