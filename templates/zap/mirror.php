<?php
/**
 * Add Zap Mirror archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/zap/mirror.php.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<title><?php wp_title(''); ?></title>
	<link rel="stylesheet" href="<?php echo plugins_url( '/assets/dtree/dtree.css', dirname( __DIR__ ) ); ?>" type="text/css" />
	<script type="text/javascript" src="<?php echo plugins_url( '/assets/dtree/dtree.js', dirname( __DIR__ ) ); ?>"></script>
</head>
<body <?php body_class(); ?>>
	<script type="text/javascript">
		d = new dTree('d');
		for (var key in d.icon) {
			d.icon[key] = '<?php echo plugins_url( '/assets/dtree/', dirname( __DIR__ ) ); ?>' + d.icon[key];
		}
		d.add(0, -1, '<?php echo get_bloginfo( 'name' ); ?>', '<?php echo wc_get_page_permalink( 'zap' ); ?>');
		<?php echo $nodes; ?>
		document.write(d);
	</script>
</body>
</html>