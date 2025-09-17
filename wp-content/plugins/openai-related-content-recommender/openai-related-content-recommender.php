<?php
  /**
   * Plugin Name:       OpenAI Related Content Recommender
   * Plugin URI:        https://metro-manhattan.com
   * Description:       Generates, balances, and displays related & high-quality post recommendations using OpenAI embeddings.
   * Version:           1.0.1
   * Author:            Nemanja Tanaskovic
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  // -----------------------------------------------------------------------------
  // CONFIGURATION
  // -----------------------------------------------------------------------------
  if ( ! defined( 'OPENAI_API_KEY' ) ) {
    define( 'OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY_HERE' );
  }

  // Hook into post actions to regenerate recommendations
  function orc_regenerate_recommendations($post_id, $post = null, $update = null) {
    // Only proceed for post type 'post'
    if (get_post_type($post_id) !== 'post') {
      return;
    }

    // Skip auto-saves and revisions
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
      return; 
    }

    // Generate recommendations
    orc_generate_all_related_articles();
    orc_generate_high_quality_articles();
  }

  // Hook into post publish, update and delete
  // add_action('publish_post', 'orc_regenerate_recommendations', 10, 3);
  // add_action('edit_post', 'orc_regenerate_recommendations', 10, 3); 
  // add_action('delete_post', 'orc_regenerate_recommendations', 10);
  
  // -----------------------------------------------------------------------------
  // 1) EMBEDDING + SIMILARITY
  // -----------------------------------------------------------------------------
  function orc_get_openai_embedding( $input ) {
    $endpoint     = 'https://api.openai.com/v1/embeddings';
    $instructions = "\n\n[Prioritize same-category tone & topics; evergreen = stay relevant if older; news = favor recency.]";
    $response     = wp_remote_post( $endpoint, [
      'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . OPENAI_API_KEY,
      ],
      'body'    => wp_json_encode([
        'model' => 'text-embedding-ada-002',
        'input' => $input . $instructions,
      ]),
      'timeout' => 15,
    ] );
    if ( is_wp_error( $response ) ) {
      return $response;
    }
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( isset( $data['data'][0]['embedding'] ) ) {
      return $data['data'][0]['embedding'];
    }
    return new WP_Error( 'no_embedding', 'No embedding found in response.' );
  }
  
  function orc_compute_cosine_similarity( array $a, array $b ) {
    $dot = $n1 = $n2 = 0;
    foreach ( $a as $i => $v ) {
      if ( isset( $b[ $i ] ) ) {
        $dot += $v * $b[ $i ];
        $n1  += $v * $v;
        $n2  += $b[ $i ] * $b[ $i ];
      }
    }
    if ( $n1 == 0 || $n2 == 0 ) {
      return 0;
    }
    return $dot / ( sqrt( $n1 ) * sqrt( $n2 ) );
  }
  
  // -----------------------------------------------------------------------------
  // 2) AUTO-EMBED ON SAVE
  // -----------------------------------------------------------------------------
  // function orc_compute_and_store_openai_embedding( $post_id, $post, $update ) {
  //   if ( wp_is_post_autosave( $post_id )
  //        || wp_is_post_revision( $post_id )
  //        || ! in_array( $post->post_type, [ 'post', 'listings', 'buildings' ], true )
  //   ) {
  //     return;
  //   }
  //   $existing = get_post_meta( $post_id, 'openai_embedding', true );
  //   if ( is_array( $existing ) && ! empty( $existing ) ) {
  //     return;
  //   }
  //   $text = $post->post_title . "\n" . wp_strip_all_tags( $post->post_content );
  //   $emb  = orc_get_openai_embedding( $text );
  //   if ( ! is_wp_error( $emb ) ) {
  //     update_post_meta( $post_id, 'openai_embedding', $emb );
  //   }
  // }
  // add_action( 'save_post', 'orc_compute_and_store_openai_embedding', 10, 3 );
  
  // -----------------------------------------------------------------------------
  // 3) CATEGORY BONUS + RECENCY PENALTY
  // -----------------------------------------------------------------------------
  function orc_get_category_bonus( $src, $cand ) {
    $preferred = [ 'finding-your-space','real-estate-guides','client-case-studies','office-workspace-design' ];
    $src_cats  = wp_get_post_terms( $src, 'category', [ 'fields'=>'slugs' ] );
    $cd_cats   = wp_get_post_terms( $cand, 'category', [ 'fields'=>'slugs' ] );
    $shared    = array_intersect( $src_cats, $cd_cats );
    if ( $shared ) {
      return array_intersect( $preferred, $shared ) ? 0.2 : 0.1;
    }
    return 0;
  }
  
  function orc_get_recency_penalty( $src, $cand ) {
    $src_time = get_post_time( 'U', false, $src );
    $cd_time  = get_post_time( 'U', false, $cand );
    $days_old = ( time() - $cd_time ) / DAY_IN_SECONDS;
    $penalty  = $days_old > 180
      ? 0.05 * ( ( $days_old - 180 ) / 30 )
      : 0;
    $news     = [ 'expert-insights','real-estate-news','real-estate-market-reports' ];
    $ever     = [ 'finding-your-space','real-estate-guides','client-case-studies','office-workspace-design' ];
    $cd_cats  = wp_get_post_terms( $cand, 'category', [ 'fields'=>'slugs' ] );
    if ( array_intersect( $cd_cats, $news ) ) {
      $penalty = $penalty * 2 + ( $cd_time < $src_time ? 0.1 : 0 );
    } elseif ( array_intersect( $cd_cats, $ever ) ) {
      $penalty *= 0.5;
    }
    return $penalty;
  }
  
  // -----------------------------------------------------------------------------
  // 4) FALLBACK RELATED (category-based)
  // -----------------------------------------------------------------------------
  function orc_get_fallback_related_posts( $post_id, $limit = 3 ) {
    $cats = wp_get_post_categories( $post_id );
    if ( empty( $cats ) ) {
      return [];
    }
    $q = new WP_Query([
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'posts_per_page' => $limit,
      'post__not_in'   => [ $post_id ],
      'category__in'   => $cats,
      'fields'         => 'ids',
    ]);
    $ids = $q->posts;
    wp_reset_postdata();
    return $ids;
  }
  
  // -----------------------------------------------------------------------------
  // 5) BALANCE RECOMMENDATIONS (1–10 appearances)
  // -----------------------------------------------------------------------------
  function orc_balance_recommendations( array $recs, array $scores, $min = 1, $max = 10 ) {
    // Collect all IDs to initialize counts
    $all_candidate_ids = [];
    foreach ( $recs as $inner ) {
      foreach ( $inner as $cid ) {
        $all_candidate_ids[] = $cid;
      }
    }
    $all_ids = array_unique( array_merge( array_keys( $scores ), $all_candidate_ids ) );
    // Initialize counts to zero for all IDs
    $counts = array_fill_keys( $all_ids, 0 );
    
    // Tally initial occurrences
    foreach ( $recs as $src => $list ) {
      foreach ( $list as $cand ) {
        $counts[ $cand ]++;
      }
    }
    
    // Enforce maximum appearances
    foreach ( $counts as $cand => $cnt ) {
      if ( $cnt <= $max ) {
        continue;
      }
      $excess = $cnt - $max;
      $placements = [];
      foreach ( $recs as $src => $list ) {
        $idx = array_search( $cand, $list, true );
        if ( $idx !== false ) {
          $placements[] = [
            'src'   => $src,
            'pos'   => $idx,
            'score' => $scores[ $src ][ $cand ],
          ];
        }
      }
      usort( $placements, fn( $a, $b ) => $a['score'] <=> $b['score'] );
      for ( $i = 0; $i < $excess; $i++ ) {
        $src = $placements[ $i ]['src'];
        $pos = $placements[ $i ]['pos'];
        foreach ( $scores[ $src ] as $alt => $sim ) {
          if ( ! in_array( $alt, $recs[ $src ], true ) && $counts[ $alt ] < $max ) {
            $recs[ $src ][ $pos ] = $alt;
            $counts[ $cand ]--;
            $counts[ $alt ]++;
            break;
          }
        }
      }
    }
    
    // Enforce minimum appearances
    foreach ( $counts as $cand => $cnt ) {
      if ( $cnt >= $min ) {
        continue;
      }
      foreach ( $recs as $src => & $list ) {
        if ( in_array( $cand, $list, true ) ) {
          continue;
        }
        $worst_score = INF;
        $worst_idx   = null;
        foreach ( $list as $idx => $cur ) {
          if ( $counts[ $cur ] > $min
               && $scores[ $src ][ $cur ] < $scores[ $src ][ $cand ]
               && $scores[ $src ][ $cur ] < $worst_score
          ) {
            $worst_score = $scores[ $src ][ $cur ];
            $worst_idx   = $idx;
          }
        }
        if ( $worst_idx !== null ) {
          $removed             = $list[ $worst_idx ];
          $list[ $worst_idx ] = $cand;
          $counts[ $removed ]--;
          $counts[ $cand ]++;
          break;
        }
      }
      unset( $list );
    }
    
    return $recs;
  }
  
  // -----------------------------------------------------------------------------
  // 6) GENERATION ROUTINES
  // -----------------------------------------------------------------------------
  function orc_generate_all_related_articles() {
    $posts = get_posts([ 'post_type'=>'post','numberposts'=>-1,'fields'=>'ids' ]);
    $embs = [];
    foreach ( $posts as $pid ) {
      $e = get_post_meta( $pid, 'openai_embedding', true );
      if ( is_array( $e ) ) {
        $embs[ $pid ] = $e;
      }
    }
    if ( count( $embs ) < 2 ) {
      return 0;
    }
    
    $scores = [];
    foreach ( $embs as $src => $src_emb ) {
      foreach ( $embs as $cand => $cand_emb ) {
        if ( $src === $cand ) {
          continue;
        }
        $sim = orc_compute_cosine_similarity( $src_emb, $cand_emb )
               + orc_get_category_bonus( $src, $cand )
               - orc_get_recency_penalty( $src, $cand );
        $scores[ $src ][ $cand ] = $sim;
      }
      arsort( $scores[ $src ] );
    }
    
    $initial = [];
    foreach ( $scores as $src => $map ) {
      $initial[ $src ] = array_slice( array_keys( $map ), 0, 3 );
    }
    
    $balanced = orc_balance_recommendations( $initial, $scores, 1, 10 );
    foreach ( $balanced as $src => $list ) {
      update_post_meta( $src, '_generated_recommended_articles', $list );
    }
    return count( $balanced );
  }
  
  function get_semantically_similar_high_quality_posts($post_id, $limit = 3) {
    // Get the source post's embedding
    $src_emb = get_post_meta($post_id, 'openai_embedding', true);
    if (!is_array($src_emb)) {
        return [];
    }

    // Get all posts with embeddings
    $posts = get_posts([
        'post_type' => 'post',
        'numberposts' => -1,
        'fields' => 'ids',
        'post__not_in' => [$post_id] // Exclude the source post
    ]);

    // Get already recommended articles for this post
    $already_recommended = get_post_meta($post_id, '_generated_recommended_articles', true);
    if (!is_array($already_recommended)) {
        $already_recommended = [];
    }

    // Get posts that appear as high quality recommendations more than 10 times
    global $wpdb;
    $overused_posts = [];
    $rows = $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_generated_high_quality_articles' AND meta_value != ''");
    $counts = [];
    foreach ($rows as $r) {
        $arr = maybe_unserialize($r->meta_value);
        if (is_array($arr)) {
            foreach ($arr as $id) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
                if ($counts[$id] >= 10) {
                    $overused_posts[] = $id;
                }
            }
        }
    }

    $scores = [];
    foreach ($posts as $cand_id) {
        // Skip if this article is already recommended or overused
        if (in_array($cand_id, $already_recommended) || in_array($cand_id, $overused_posts)) {
            continue;
        }

        $cand_emb = get_post_meta($cand_id, 'openai_embedding', true);
        if (!is_array($cand_emb)) {
            continue;
        }

        // Check if post is marked as high quality
        $is_high_quality = get_post_meta($cand_id, 'high_quality', true) == '1';
        
        $sim = orc_compute_cosine_similarity($src_emb, $cand_emb)
               + orc_get_category_bonus($post_id, $cand_id)
               - orc_get_recency_penalty($post_id, $cand_id);

        // Add significant bonus for high quality posts
        if ($is_high_quality) {
            $sim += 0.5; // Substantial bonus to prioritize high quality posts
        }
        
        $scores[$cand_id] = $sim;
    }

    arsort($scores);
    return array_slice(array_keys($scores), 0, $limit);
  }
  
  function orc_generate_high_quality_articles() {
    $posts = get_posts([ 'post_type'=>'post','numberposts'=>-1,'fields'=>'ids' ]);
    $count = 0;
    foreach ( $posts as $pid ) {
      $hq = get_semantically_similar_high_quality_posts( $pid, 3 );
      update_post_meta( $pid, '_generated_high_quality_articles', $hq );
      $count++;
    }
    return $count;
  }
  
  function orc_get_most_recommended_articles( $limit = 10 ) {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_generated_recommended_articles' AND meta_value != ''");
    $counts = [];
    foreach ( $rows as $r ) {
      $arr = maybe_unserialize( $r->meta_value );
      if ( is_array( $arr ) ) {
        foreach ( $arr as $id ) {
          $counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1;
          if ( $counts[ $id ] > 10 ) {
            $counts[ $id ] = 10;
          }
        }
      }
    }
    arsort( $counts );
    return array_slice( $counts, 0, $limit, true );
  }
  
  function orc_list_post_categories() {
    $out = [];
    $posts = get_posts([ 'post_type'=>'post','numberposts'=>-1,'fields'=>'ids' ]);
    foreach ( $posts as $pid ) {
      $out[ $pid ] = wp_get_post_terms( $pid, 'category', ['fields'=>'slugs'] );
    }
    return $out;
  }

  function orc_get_related_articles() {
    $posts = get_posts(['post_type'=>'post', 'numberposts'=>-1]);
    $related = [];
    
    foreach($posts as $post) {
      $recommended = get_post_meta($post->ID, '_generated_recommended_articles', true);
      if(!empty($recommended)) {
        $related_posts = [];
        foreach($recommended as $rec_id) {
          $related_posts[] = [
            'title' => get_the_title($rec_id),
            'url' => get_permalink($rec_id)
          ];
        }
        $related[] = [
          'post' => [
            'title' => $post->post_title,
            'url' => get_permalink($post->ID)
          ],
          'related' => $related_posts
        ];
      }
    }
    return $related;
  }

  function orc_get_high_quality_articles() {
    $posts = get_posts(['post_type'=>'post', 'numberposts'=>-1]);
    $high_quality = [];
    
    foreach($posts as $post) {
      $hq_articles = get_post_meta($post->ID, '_generated_high_quality_articles', true);
      if(!empty($hq_articles)) {
        $hq_posts = [];
        foreach($hq_articles as $hq_id) {
          $hq_posts[] = [
            'title' => get_the_title($hq_id),
            'url' => get_permalink($hq_id)
          ];
        }
        $high_quality[] = [
          'post' => [
            'title' => $post->post_title,
            'url' => get_permalink($post->ID)
          ],
          'high_quality' => $hq_posts
        ];
      }
    }
    return $high_quality;
  }
  
  // -----------------------------------------------------------------------------
  // 7) ADMIN UI + MENU
  // -----------------------------------------------------------------------------
  add_action( 'admin_menu', function() {
    add_submenu_page(
      'tools.php',
      'OpenAI Recommender',
      'OpenAI Recommender',
      'manage_options',
      'orc-recommender',
      'orc_render_admin_page'
    );
  } );
  
  function orc_render_admin_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'No permission.' );
  }
  if ( isset( $_POST['orc_do_recs'] ) && check_admin_referer( 'orc_recs_action', 'orc_recs_nonce' ) ) {
    $done = orc_generate_all_related_articles();
    echo "<div class='notice notice-success'><p>Recomputed & balanced for $done posts.</p></div>";
  }
  if ( isset( $_POST['orc_do_hq'] ) && check_admin_referer( 'orc_hq_action', 'orc_hq_nonce' ) ) {
    $done = orc_generate_high_quality_articles();
    echo "<div class='notice notice-success'><p>Generated high-quality for $done posts.</p></div>";
  }
  if ( isset( $_POST['orc_do_most'] ) && check_admin_referer( 'orc_most_action', 'orc_most_nonce' ) ) {
    $limit = absint( $_POST['roc_most_limit'] ?? 10 );
    $most  = orc_get_most_recommended_articles( 304 );
  }
  if ( isset( $_POST['orc_do_cats'] ) && check_admin_referer( 'orc_cats_action', 'orc_cats_nonce' ) ) {
    $cats_out = orc_list_post_categories();
  }
?>
<div class="wrap">
    <h1>OpenAI Recommender</h1>
    <h2 class="nav-tab-wrapper">
        <a href="#tab-recs" class="nav-tab nav-tab-active">Recommended Articles</a>
        <a href="#tab-hq" class="nav-tab">High Quality</a>
        <!-- <a href="#tab-most" class="nav-tab">Most Recommended</a> -->
        <!-- <a href="#tab-cats" class="nav-tab">Categories</a> -->
    </h2>

    <div id="tab-recs" style="display:block; padding:1em 0;">
        <form method="post">
          <?php wp_nonce_field( 'orc_recs_action', 'orc_recs_nonce' ); ?>
            <input type="submit" name="orc_do_recs" class="button button-primary" value="Recompute All Related" />
        </form>

        <?php 
        $related_articles = orc_get_related_articles();
        if(!empty($related_articles)):
        ?>
        <div style="margin-top: 20px;">
          <table class="widefat striped">
              <thead>
                  <tr>
                      <th>Article</th>
                      <th>Related Articles</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach($related_articles as $item): ?>
                      <tr>
                          <td>
                              <a href="<?php echo esc_url($item['post']['url']); ?>" target="_blank">
                                  <?php echo esc_html($item['post']['title']); ?>
                              </a>
                          </td>
                          <td>
                              <ul style="margin: 0; padding-left: 20px;">
                                  <?php foreach($item['related'] as $related): ?>
                                      <li>
                                          <a href="<?php echo esc_url($related['url']); ?>" target="_blank">
                                              <?php echo esc_html($related['title']); ?>
                                          </a>
                                      </li>
                                  <?php endforeach; ?>
                              </ul>
                          </td>
                      </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
        </div>
        <?php endif; ?>
    </div>

    <div id="tab-hq" style="display:none; padding:1em 0;">
        <form method="post">
          <?php wp_nonce_field( 'orc_hq_action', 'orc_hq_nonce' ); ?>
            <input type="submit" name="orc_do_hq" class="button button-primary" value="Generate High-Quality" />
        </form>

        <?php 
        $high_quality_articles = orc_get_high_quality_articles();
        if(!empty($high_quality_articles)):
        ?>
        <div style="margin-top: 20px;">
          <table class="widefat striped">
              <thead>
                  <tr>
                      <th>Article</th>
                      <th>High Quality Articles</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach($high_quality_articles as $item): ?>
                      <tr>
                          <td>
                              <a href="<?php echo esc_url($item['post']['url']); ?>" target="_blank">
                                  <?php echo esc_html($item['post']['title']); ?>
                              </a>
                          </td>
                          <td>
                              <ul style="margin: 0; padding-left: 20px;">
                                  <?php foreach($item['high_quality'] as $hq): ?>
                                      <li>
                                          <a href="<?php echo esc_url($hq['url']); ?>" target="_blank">
                                              <?php echo esc_html($hq['title']); ?>
                                          </a>
                                      </li>
                                  <?php endforeach; ?>
                              </ul>
                          </td>
                      </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
        </div>
        <?php endif; ?>
    </div>

    <div id="tab-most" style="display:none; padding:1em 0;">
        <form method="post">
          <?php wp_nonce_field( 'orc_most_action', 'orc_most_nonce' ); ?>
            <label for="orc_most_limit">Show top </label>
            <input type="number" id="orc_most_limit" name="orc_most_limit" value="<?php echo esc_attr( $limit ?? 10 ); ?>" min="1" />
            <input type="submit" name="orc_do_most" class="button button-primary" value="Fetch Most Recommended" />
        </form>
      <?php if ( ! empty( $most ) ): ?>
          <table class="widefat striped">
              <thead><tr><th>Post</th><th>Count</th></tr></thead>
              <tbody>
              <?php foreach ( $most as $pid => $cnt ): ?>
                  <tr>
                      <td><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $pid ) ); ?></a></td>
                      <td><?php echo esc_html( $cnt ); ?></td>
                  </tr>
              <?php endforeach; ?>
              </tbody>
          </table>
      <?php endif; ?>
    </div>

    <div id="tab-cats" style="display:none; padding:1em 0;">
        <form method="post">
          <?php wp_nonce_field( 'orc_cats_action', 'orc_cats_nonce' ); ?>
            <input type="submit" name="orc_do_cats" class="button button-primary" value="List Categories" />
        </form>
      <?php if ( ! empty( $cats_out ) ): ?>
          <table class="widefat striped">
              <thead><tr><th>Post</th><th>Slugs</th></tr></thead>
              <tbody>
              <?php foreach ( $cats_out as $pid => $slugs ): ?>
                  <tr>
                      <td><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $pid ) ); ?></a></td>
                      <td><?php echo esc_html( implode( ', ', $slugs ) ?: '—' ); ?></td>
                  </tr>
              <?php endforeach; ?>
              </tbody>
          </table>
      <?php endif; ?>
    </div>

    <script>
      (function(){
        const tabs = document.querySelectorAll('.nav-tab');
        tabs.forEach(tab => {
          tab.addEventListener('click', e => {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            tab.classList.add('nav-tab-active');
            ['#tab-recs','#tab-hq','#tab-most','#tab-cats'].forEach(sel => {
              document.querySelector(sel).style.display = (sel===tab.getAttribute('href')) ? 'block' : 'none';
            });
          });
        });
      })();
    </script>
</div>
<?php
}
?>
