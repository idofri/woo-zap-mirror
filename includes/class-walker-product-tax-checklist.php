<?php
/*
 * Exit if file accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Walker_Product_Tax_Checklist extends Walker {
	
	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id');
	
	public $tags;

    function __construct( $tags ) {
        $this->tags = $tags;
    }
	
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		
		$taxonomy = $args['taxonomy'];

		$name = 'wc_zap_hide_tax[' . $taxonomy . ']';
		
		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'>" .
			'<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' .
			checked( in_array( $category->term_id, $this->tags ), true, false ) .
			disabled( empty( $args['disabled'] ), false, false ) . ' /> ' .
			esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
	}
	
	public function end_el( &$output, $category, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
}