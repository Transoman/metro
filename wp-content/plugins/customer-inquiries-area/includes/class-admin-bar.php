<?php
  // includes/class-admin-bar.php
  
  /**
   * Add items to the WordPress admin bar (for users with manage_options).
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  add_action( 'admin_bar_menu', 'cia_admin_bar_menu', 2000 );
  
  function cia_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }
    
    $wp_admin_bar->add_menu( [
      'id'    => 'cia',
      'title' => __( 'Customer Inquiries Area' ),
      'href'  => '#',
    ] );
    
    $wp_admin_bar->add_menu( [
      'parent' => 'cia',
      'title'  => __( 'Search Stats' ),
      'id'     => 'cia-stats',
      'href'   => admin_url( 'admin.php?page=search-stats' ),
    ] );
    
    $wp_admin_bar->add_menu( [
      'parent' => 'cia',
      'title'  => __( 'Account creation' ),
      'id'     => 'cia-account',
      'href'   => admin_url( 'users.php?orderby=user_registered&order=desc' ),
    ] );
    
    $wp_admin_bar->add_menu( [
      'parent' => 'cia',
      'title'  => __( 'Form completions' ),
      'id'     => 'cia-fc',
      'href'   => admin_url( 'admin.php?page=vxcf_leads&tab=entries_stats' ),
    ] );
    
    $wp_admin_bar->add_menu( [
      'parent' => 'cia',
      'title'  => __( 'Uncompleted Forms' ),
      'id'     => 'cia-uncompleted-forms',
      'href'   => admin_url( 'admin.php?page=cf7-partial-submissions' ),
    ] );
  }
