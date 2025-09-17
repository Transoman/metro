<?php
  
  /**
   * Base Block Template.
   *
   * @param array $block The block settings and attributes.
   * @param string $content The block inner HTML (empty).
   * @param bool $is_preview True during backend preview render.
   * @param int $post_id The post ID the block is rendering content against.
   *          This is either the post ID currently being displayed inside a query loop,
   *          or the post ID of the post hosting this block.
   * @param array $context The context provided to the block by the post or its parent block.
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  // Support custom "anchor" values.
  $anchor = '';
  if ( ! empty( $block['anchor'] ) ) {
    $anchor = 'id="' . esc_attr( $block['anchor'] ) . '" ';
  }
  
  // Create class attribute allowing for custom "className" and "align" values.
  $class_name = 'featured-spaces';
  if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
  }
  
  if ( ! empty( $block['align'] ) ) {
    $class_name .= ' align' . $block['align'];
  }
  
  $fields = get_fields();
  
  $title       = get_field( 'title' );
  $tabs        = get_field( 'tabs' );
  $numberposts = get_field( 'numberposts' );
  $button      = get_field( 'button' );
  $helpers     = new MetroManhattanHelpers();
  $listings    = get_posts( [
    'post_type'   => 'listings',
    'numberposts' => $numberposts,
    'orderby'     => 'date',
    'order'       => 'ASC',
    'tax_query'   => [
      [
        'taxonomy' => 'listing-category',
        'field'    => 'slug',
        'terms'    => 'featured'
      ]
    ]
  ] );
?>
<section <?php echo $anchor; ?> data-target="featured_spaces" class="<?php echo esc_attr( $class_name ); ?>">
    <div class="container">
        <div class="content">
          <?php if ( $title ): ?>
              <h2><?php echo $title ?></h2>
          <?php endif; ?>
          <?php if ( $tabs ): ?>
              <form data-target="featured_spaces_form" action="#">
                  <input type="hidden" name="numberposts" value="<?php echo $numberposts ?>">
                  <ul class="tabs">
                    <?php foreach ( $tabs as $tab ):
                      $term = get_term_by( 'term_taxonomy_id', $tab );
                      $condition = strpos( strtolower( $term->name ), 'manhattan' );
                      $term_page = get_field( 'page_id', 'location_' . $term->term_id );
                      $name = $term->name;
                      if ( $condition !== false ) {
                        $name = str_replace( 'manhattan', '', strtolower( $name ) );
                      }
                      ?>
                        <li><a aria-label="<?php echo esc_attr( $name ) ?>" data-slug="<?php echo $term->slug ?>"
                               href="<?php echo get_permalink( $term_page ) ?>"><?php echo $name ?></a></li>
                    <?php endforeach; ?>
                  </ul>
              </form>
          <?php endif; ?>
          <?php if ( $listings ): ?>
              <div data-target="featured_spaces_slider" class="swiper blocks">
                  <div data-target="featured_spaces_wrapper" class="swiper-wrapper">
                    <?php foreach ( $listings as $listing ):
                      $listing_id = $listing->ID;
                      ?>
                        <div class="swiper-slide block">
                            <!--<div class="swiper-zoom-container">-->
                              <?php get_template_part( 'templates/parts/listing', 'card', [
                                'id'                  => $listing_id,
                                'heading'             => 'h3',
                                'map_card'            => false,
                                'favourites_template' => false
                              ] ) ?>
                            <!--</div>-->
                        </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="swiper-pagination swiper-wrapper" style="display:block;"></div>
              </div>
          <?php endif; ?>
          <?php
            if ( $button ):
              $link_url = $button['url'];
              $link_title = $button['title'];
              $link_target = $button['target'] ? $button['target'] : '_self';
              ?>
                <a aria-label="<?php echo esc_attr( $link_title ) ?>" href="<?php echo esc_url( $link_url ); ?>"
                   target="<?php echo esc_attr( $link_target ); ?>" class="show-properties">
                    <span><?php echo esc_html( $link_title ); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>