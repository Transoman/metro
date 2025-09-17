<?php

/**
 * ProfiDev Custom Walker Class for Navigation Menus
 *
 * This class extends the `Walker_Nav_Menu` class to create a custom output structure for navigation menus.
 * It adds custom classes to menu items based on their depth and allows for additional attributes.
 *
 * @package MetroManhattan
 */
class ProfiDev_Walker_Nav_Menu extends Walker_Nav_Menu {
	/**
	 * Starts the element output.
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param object $item The current menu item.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param array $args Additional arguments.
	 * @param int $id The ID of the current item.
	 */
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $wp_query;
		$indent = ( $depth > 0 ? str_repeat( "\t", $depth ) : '' );

		// Depth-based classes for menu items.
		$depth_classes     = array(
			( $depth == 0 ? 'main-menu-item' : 'sub-menu-item' ),
			( $depth >= 2 ? 'sub-sub-menu-item' : '' ),
			( $depth % 2 ? 'menu-item-odd' : 'menu-item-even' ),
			'menu-item-depth-' . $depth
		);
		$depth_class_names = esc_attr( implode( ' ', $depth_classes ) );

		// Additional classes from the menu item.
		$classes     = empty( $item->classes ) ? array() : (array) $item->classes;
		$class_names = esc_attr( implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) ) );

		// Add the list item element with appropriate classes.
		$output .= $indent . '<li id="nav-menu-item-' . $item->ID . '" class="' . $depth_class_names . ' ' . $class_names . '">';

		// Generate the attributes for the anchor tag.
		$attributes = ! empty( $item->attr_title ) ? ' title="' . esc_attr( $item->attr_title ) . '"' : '';
		$attributes .= ! empty( $item->target ) ? ' target="' . esc_attr( $item->target ) . '"' : '';
		$attributes .= ! empty( $item->xfn ) ? ' rel="' . esc_attr( $item->xfn ) . '"' : '';
		$attributes .= ! empty( $item->url ) ? ' href="' . esc_attr( $item->url ) . '"' : '';
		$attributes .= ' class="menu-link ' . ( $depth > 0 ? 'sub-menu-link' : 'main-menu-link' ) . '"';

		// Build the menu item output.
		$item_output = sprintf(
			'%1$s<a%2$s>%3$s%4$s%5$s</a>%6$s',
			$args->before,
			$attributes,
			$args->link_before,
			apply_filters( 'the_title', $item->title, $item->ID ),
			$args->link_after,
			$args->after
		);

		// Append the item output to the main output.
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}

