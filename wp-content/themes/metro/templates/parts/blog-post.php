<?php
  $post_id          = $args['id'];
  $author           = isset( $args['author'] ) ? $args['author'] : true;
  $date             = isset( $args['date'] ) ? $args['date'] : true;
  $type_of_articles = isset( $args['type_of_articles'] ) ? $args['type_of_articles'] : '';
  $show_content     = ( array_key_exists( 'text', $args ) ) ? $args['text'] : '';
  $category         = ( ! empty( $args['category'] ) ) ? $args['category'] : get_the_category( $post_id )[0]->name;
  $content          = ( get_the_excerpt( $post_id ) ) ? get_the_excerpt( $post_id ) : wp_strip_all_tags( get_the_content( $post_id ) );
  $author_name      = get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
  $publish_date     = date( 'd M \'y', strtotime( get_the_date( 'Y-m-d', $post_id ) ) );
?>
<div class="block">
    <a aria-label="News post" href="<?php echo get_permalink( $post_id ) ?>" class="link">
        <div class="inner">
            <div class="image">
              <?php
                $image_args = [ 'loading' => 'lazy' ];
                if ( array_key_exists( 'loading', $args ) && ! empty( $args['loading'] ) ) {
                  $image_args['loading'] = $args['loading'];
                }
                echo wp_get_attachment_image( get_post_thumbnail_id( $post_id ), 'full', '', $image_args ) ?>
            </div>
            <div class="text">
              <?php if ( ! empty( $category ) ) : ?>
                  <span><?php echo $category ?></span>
              <?php endif; ?>
              <?php if ( isset( $args['heading'] ) && $args['heading'] == 'h4' ) : ?>
                <?php if ( $type_of_articles === 'recommended' ) { ?>
                      <h4 class="title" style="font-size:16px;"><?php echo get_the_title( $post_id ); ?></h4>
                <?php } else { ?>
                      <h4 class="title"><?php echo get_the_title( $post_id ); ?></h4>
                <?php } ?>
              <?php else : ?>
                  <h3 class="title"><?php echo get_the_title( $post_id ); ?></h3>
              <?php endif; ?>
              <?php if ( $show_content !== false ) :
                $sentence = preg_replace( '/(.*?[?!.](?=\s|$)).*/', '\\1', $content );
                ?>
                  <p><?php echo $sentence ?></p>
              <?php endif; ?>
              <?php if ( $author && $date ) : ?>
                <?php
                if ( $type_of_articles == 'more-from-metro' || $type_of_articles == 'more-new-section' ) {
                  ?>
                    <div class="meta" style="margin-top: auto; padding-top: 10px; display: flex; gap: 5px;">
                        <span class="date"
                              style="font-weight: 400;font-size: 14px;color: #6E7484;"><?php echo $publish_date; ?></span>
                        <span style="font-weight: 400; font-size: 10px; color: #6E7484;margin-top:2px;">•</span>
                        <span class="author"
                              style="font-weight: 400;font-size: 14px;color: #6E7484;">By <?php echo $author_name; ?></span>
                    </div>
                  <?php
                } else if ( $type_of_articles == 'recommended' ) {
                  ?>
                    <div class="meta" style="margin-top: auto; padding-top: 10px;">
                        <span class="date"
                              style="font-weight: 400;font-size: 12px;color: #6E7484;"><?php echo $publish_date; ?></span>
                        <span class="author"
                              style="font-weight: 400;font-size: 12px;color: #6E7484;">• By <?php echo $author_name; ?></span>
                    </div>
                  <?php
                } else if ( $type_of_articles == 'real-estate-insights' ) {
                  ?>
                    <div class="meta" style="margin-top: auto; padding-top: 10px; display: flex; gap: 5px;">
                        <span class="date"
                              style="font-weight: 400;font-size: 14px;color: #6E7484;"><?php echo $publish_date; ?></span>
                        <span style="font-weight: 400; font-size: 10px; color: #6E7484;margin-bottom:2px;">•</span>
                        <span class="author"
                              style="font-weight: 400;font-size: 14px;color: #6E7484;">By <?php echo $author_name; ?></span>
                    </div>
                  <?php
                } else if ( $type_of_articles == 'popular' ) {
                  ?>
                    <div class="meta" style="margin-top: auto; padding-top: 10px; display: flex; gap: 5px;">
                        <span class="date"
                              style="font-weight: 400;font-size: 12px;color: #6E7484;"><?php echo $publish_date; ?></span>
                        <span style="font-weight: 400; font-size: 10px; color: #6E7484;margin-top:1px;">•</span>
                        <span class="author"
                              style="font-weight: 400;font-size: 12px;color: #6E7484;">By <?php echo $author_name; ?></span>
                    </div>
                  <?php
                } else {
                  ?>
                    <div class="meta" style="margin-top: auto; padding-top: 10px; display: flex; gap: 5px;">
                        <span class="date"
                              style="font-weight: 400;font-size: 14px;color: #6E7484;"><?php echo $publish_date; ?></span>
                        <span style="font-weight: 400; font-size: 10px; color: #6E7484;margin-top:2px;">•</span>
                        <span class="author"
                              style="font-weight: 400;font-size: 14px;color: #6E7484;">By <?php echo $author_name; ?></span>
                    </div>
                <?php } ?>
              <?php endif; ?>
            </div>
        </div>
    </a>
</div>