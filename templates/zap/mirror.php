<?php
/**
 * Add Zap Mirror archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/zap/mirror.php.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

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

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<title><?php wp_title(''); ?></title>
	<link rel="stylesheet" href="<?php echo WC_Zap_Mirror()->plugin_url(); ?>/assets/dtree/dtree.css" type="text/css" />
	<script type="text/javascript" src="<?php echo WC_Zap_Mirror()->plugin_url(); ?>/assets/dtree/dtree.js"></script>
	<?php do_action( 'wc_zap_mirror_head' ); ?>
</head>
<body <?php body_class(); ?>>
	<?php do_action( 'wc_zap_mirror_body' ); ?>
	<script type="text/javascript">
		d = new dTree('d');
		for (var key in d.icon) {
			d.icon[key] = '<?php echo WC_Zap_Mirror()->plugin_url(); ?>/assets/dtree/' + d.icon[key];
		}
		d.add(0, -1, '<?php echo get_bloginfo( 'name' ); ?>', '<?php echo wc_get_page_permalink( 'zap' ); ?>');
		<?php echo $nodes; ?>
		document.write(d);
	</script>
</body>
</html>