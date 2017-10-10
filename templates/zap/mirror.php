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
	<title><?php wp_title(''); ?></title>
	<link rel="stylesheet" href="<?php echo plugins_url( '/assets/dtree/dtree.css', dirname( __DIR__ ) ); ?>" type="text/css" />
	<script type="text/javascript" src="<?php echo plugins_url( '/assets/dtree/dtree.js', dirname( __DIR__ ) ); ?>"></script>
</head>
<body>
	<script type="text/javascript">
		d = new dTree("d");
		d.icon.root 		= "<?php echo plugins_url( '/assets/dtree/img/globe.gif', dirname( __DIR__ ) ); ?>";
		d.icon.folder 		= "<?php echo plugins_url( '/assets/dtree/img/folder.gif', dirname( __DIR__ ) ); ?>";
		d.icon.folderOpen 	= "<?php echo plugins_url( '/assets/dtree/img/folderopen.gif', dirname( __DIR__ ) ); ?>";
		d.icon.node 		= "<?php echo plugins_url( '/assets/dtree/img/folder.gif', dirname( __DIR__ ) ); ?>";
		d.icon.empty		= "<?php echo plugins_url( '/assets/dtree/img/empty.gif', dirname( __DIR__ ) ); ?>";
		d.icon.line 		= "<?php echo plugins_url( '/assets/dtree/img/line.gif', dirname( __DIR__ ) ); ?>";
		d.icon.join 		= "<?php echo plugins_url( '/assets/dtree/img/join.gif', dirname( __DIR__ ) ); ?>";
		d.icon.joinBottom 	= "<?php echo plugins_url( '/assets/dtree/img/joinbottom.gif', dirname( __DIR__ ) ); ?>";
		d.icon.plus 		= "<?php echo plugins_url( '/assets/dtree/img/plus.gif', dirname( __DIR__ ) ); ?>";
		d.icon.plusBottom 	= "<?php echo plugins_url( '/assets/dtree/img/plusbottom.gif', dirname( __DIR__ ) ); ?>";
		d.icon.minus 		= "<?php echo plugins_url( '/assets/dtree/img/minus.gif', dirname( __DIR__ ) ); ?>";
		d.icon.minusBottom 	= "<?php echo plugins_url( '/assets/dtree/img/minusbottom.gif', dirname( __DIR__ ) ); ?>";
		d.icon.nlPlus 		= "<?php echo plugins_url( '/assets/dtree/img/nolines_plus.gif', dirname( __DIR__ ) ); ?>";
		d.icon.nlMinus		= "<?php echo plugins_url( '/assets/dtree/img/nolines_minus.gif', dirname( __DIR__ ) ); ?>";
		d.add(0, -1, "<?php echo get_bloginfo( 'name' ); ?>", "<?php echo wc_get_page_permalink( 'zap' ); ?>");
		<?php echo $nodes; ?>
		document.write(d);
	</script>
</body>
</html>