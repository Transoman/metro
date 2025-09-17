<?php
$global_post = get_queried_object_id();
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$title = '';
$text = (get_field('text', $global_post)) ? get_field('text', $global_post) : get_field('short_description', 'category_' . $global_post);
$hero_background = get_field('background_hero', get_option('page_for_posts'));
$posts_background = get_field('background_posts', get_option('page_for_posts'));
$articles_background = get_field('background_articles', get_option('page_for_posts'));
$numberposts_articles = get_field('numberposts_article', get_option('page_for_posts'));
$articles_title = get_field('title_articles', get_option('page_for_posts'));
$youtube_title = get_field('title_youtube', get_option('page_for_posts'));
$youtube_background = get_field('background_youtube', get_option('page_for_posts'));
$youtube_range = get_field('range', get_option('page_for_posts'));
$youtube_max_results = get_field('youtube_range', get_option('page_for_posts'));
$youtube_channel_id = get_field('youtube_channel_id', 'option');
$youtube_api_key = get_field('youtube_api_key', 'option');
$youtube_button = get_field('button_youtube', get_option('page_for_posts'));
$youtube_videos = MetroManhattanHelpers::get_latest_videos($youtube_api_key, $youtube_max_results, $youtube_channel_id);
$category_list = get_categories(['orderby' => 'meta_value_num', 'order' => 'ASC', 'meta_key' => 'custom_order']);
$numberposts = get_field('numberposts', get_option('page_for_posts'));
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$args = [
    'posts_per_page' => $numberposts,
    'paged' => $paged,
    'order' => 'DESC',
    'orderby' => 'date'
];
if ($global_post == get_option('page_for_posts') && !empty(get_field('title', $global_post))) {
    $title = get_field('title', $global_post);
} elseif ($global_post == get_option('page_for_posts') && empty(get_field('title', $global_post))) {
    $title = get_the_title($global_post);
} elseif ($global_post != get_option('page_for_posts')) {
    $title = get_term_by("term_taxonomy_id", $global_post)->name;
}
if ($global_post != get_option('page_for_posts')) {
    $args['cat'] = $global_post;
}
$query = new WP_Query($args);
$posts = $query->posts;
$total_pages = $query->max_num_pages;

// Get featured articles, excluding posts already displayed in the main section
$main_post_ids = array_map(function($post) {
    return $post->ID;
}, $posts);

$articles_args = [
    'post_type' => 'post', 
    'numberposts' => $numberposts_articles
];

// Only add exclude parameter if there are posts to exclude
if (!empty($main_post_ids)) {
    $articles_args['exclude'] = $main_post_ids;
}

$articles = MetroManhattanHelpers::get_posts_by_mode('featured', $articles_args);
?>

<style>
    .pagination-blog-posts {
        display: flex;
        justify-content: center;
        align-items: center;
        justify-content: space-between;
    }

    .pagination-blog-posts .pages-wrapper {
        flex: 0 0 auto;
        margin: 0 16px;
    }

    .pagination-blog-posts .page-numbers {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 54px;
        border-radius: 50%;
        text-decoration: none;
        font-weight: 700;
        color: var(--mm-blue-color);
        position: relative;
        z-index: 1;
        line-height: 0;
        transition: all 0.3s ease;
    }

    .pagination-blog-posts .page-numbers:not(.current):not(.dots):not(.prev):not(.next):hover::before {
        content: "";
        display: block;
        width: 100%;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
        background: var(--mm-black-color);
        opacity: .04;
        border-radius: 50%;
    }

    .pagination-blog-posts .page-numbers.current {
        font-weight: 500;
        color: var(--mm-black-color);
    }

    .pagination-blog-posts .page-numbers.current::before {
        content: "";
        display: block;
        width: 100%;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
        background: var(--mm-black-color);
        opacity: .04;
        border-radius: 50%;
    }

    .pagination-blog-posts .page-numbers.prev,
    .pagination-blog-posts .page-numbers.next {
        flex-direction: row-reverse;
        padding-left: 30px;
        display: flex;
        align-items: center;
        height: 54px;
        border: 1px solid var(--mm-blue-color);
        padding: 16px 26px;
        border-radius: var(--mm-button-radius);
        font-family: var(--mm-primary-font-family), sans-serif;
        color: var(--mm-blue-color);
        font-weight: 700;
        line-height: var(--mm-body-text-line-height);
        font-size: var(--mm-body-md-text-size);
        text-decoration: none;
        width: auto;
        transition: all 0.3s ease;
    }

    .pagination-blog-posts .nav-text {
        color: var(--mm-blue-color);
        font-weight: 700;
    }

    .pagination-blog-posts .page-numbers.prev:hover,
    .pagination-blog-posts .page-numbers.next:hover {
        background: linear-gradient(0deg, rgba(0, 0, 0, .04), rgba(0, 0, 0, .04)), var(--mm-blue-grey-color);
    }

    .pagination-blog-posts .nav-arrow {
        display: none;
    }

    .page-numbers.is-disabled,
    .page-numbers.is-disabled svg path {
        pointer-events: none !important;
        cursor: default !important;
        border: 1px solid var(--mm-placeholder-grey-2) !important;
        stroke: var(--mm-placeholder-grey-2) !important;
    }

    .next.page-numbers svg,
    .prev.page-numbers svg {
        display: none;
    }

    @media (max-width: 768px) {
        .pagination-blog-posts {
            display: inline-flex;
            width: 100%;
            justify-content: space-between;
        }
        .pagination-blog-posts .nav-text {
            display: none;
        }
        .pagination-blog-posts .nav-arrow {
            color: var(--mm-blue-color);
            font-weight: 700;
            display: inline;
        }
        .pagination-blog-posts .page-numbers {
            width: 40px;
            height: 40px;
        }
        .next.page-numbers,
        .prev.page-numbers {
            height: 40px !important;
            width: 40px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
        }
        .next.page-numbers svg,
        .prev.page-numbers svg {
            display: inline-block;
            width: 11.5px;
            height: 23px;
        }
    }
    @media (min-width: 769px) {
        .pagination-blog-posts {
            width: auto;
        }
    }
</style>

<main class="home">
    <?php
    get_template_part('templates/parts/notification', 'template');
    ?>
    <section class="page-hero <?php echo $hero_background ?>">
        <div class="container">
            <div class="content">
                <?php echo MetroManhattanHelpers::breadcrumbs() ?>
                <h1><?php echo $title ?></h1>
                <?php if ($text) : ?>
                    <p><?php echo $text ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section class="blog-posts <?php echo $posts_background ?>">
        <div class="container">
            <div class="content">
                <div class="posts-wrapper">
                    <?php if (!empty($posts)) : ?>
                        <div data-target="blog_posts_wrapper" class="posts">
                            <?php
                            $idx = 0;
                            foreach ($posts as $post) :
                                $args = ['id' => $post->ID, 'loading' => ($idx < 2) ? 'eager' : 'lazy'];
                                if ($global_post !== (int) get_option('page_for_posts')) {
                                    $args['category'] = get_term_by("term_taxonomy_id", $global_post)->name;
                                }
                                $args['author'] = true;
                                $args['date']   = true;
                                
                                get_template_part('templates/parts/blog', 'post', $args);
                                $idx++;
                            endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-wrapper">
                      <?php if ( ( is_home() || is_category() ) && $total_pages > 1 ) : ?>
                          <div class="pagination-blog-posts">
                            <?php
                              $prev_classes     = 'prev page-numbers';
                              $prev_href        = ( $paged > 1 ) ? esc_url( get_pagenum_link( $paged - 1 ) ) : esc_url( get_pagenum_link( $paged ) );
                              $prev_aria_hidden = '';
                              if ( ! ( $paged > 1 ) ) {
                                $prev_classes     .= ' is-disabled';
                                $prev_aria_hidden = ' aria-hidden="true"';
                              }
                              echo '<a class="' . $prev_classes . '" href="' . $prev_href . '"' . $prev_aria_hidden . '><svg width="15" height="25" viewBox="0 0 15 25" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M13.5 1L2 12.5L13.5 24" stroke="var(--mm-blue-color)" stroke-width="2" stroke-linecap="round"></path> </svg><span class="nav-text">Previous</span></a>';
                              
                              echo '<div class="pages-wrapper">';
                              
                              if ( $paged <= 2 ) {
                                echo ( $paged == 1 ) ? '<span class="page-numbers current">1</span>' : '<a class="page-numbers" href="' . esc_url( get_pagenum_link( 1 ) ) . '">1</a>';
                                
                                if ( $total_pages >= 2 ) {
                                  echo ( $paged == 2 ) ? '<span class="page-numbers current">2</span>' : '<a class="page-numbers" href="' . esc_url( get_pagenum_link( 2 ) ) . '">2</a>';
                                }
                                
                                if ( $total_pages >= 3 ) {
                                  echo '<a class="page-numbers" href="' . esc_url( get_pagenum_link( 3 ) ) . '">3</a>';
                                }
                                
                                if ( $total_pages > 3 ) {
                                  if ( $total_pages > 4 ) {
                                    echo '<span class="page-numbers dots">&hellip;</span>';
                                  }
                                  echo '<a class="page-numbers" href="' . esc_url( get_pagenum_link( $total_pages ) ) . '">' . $total_pages . '</a>';
                                }
                              } else {
                                echo '<a class="page-numbers" href="' . esc_url( get_pagenum_link( $paged - 1 ) ) . '">' . ( $paged - 1 ) . '</a>';
                                echo '<span class="page-numbers current">' . $paged . '</span>';
                                
                                if ( $paged + 1 <= $total_pages ) {
                                  echo '<a class="page-numbers" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . '">' . ( $paged + 1 ) . '</a>';
                                }
                                
                                if ( $paged + 1 < $total_pages - 1 ) {
                                  echo '<span class="page-numbers dots">&hellip;</span>';
                                }
                                if ( ( $paged + 1 ) < $total_pages ) {
                                  echo '<a class="page-numbers" href="' . esc_url( get_pagenum_link( $total_pages ) ) . '">' . $total_pages . '</a>';
                                }
                              }
                              echo '</div>';
                              
                              $next_classes     = 'next page-numbers';
                              $next_href        = ( $paged < $total_pages ) ? esc_url( get_pagenum_link( $paged + 1 ) ) : esc_url( get_pagenum_link( $paged ) );
                              $next_aria_hidden = '';
                              if ( ! ( $paged < $total_pages ) ) {
                                $next_classes     .= ' is-disabled';
                                $next_aria_hidden = ' aria-hidden="true"';
                              }
                              echo '<a class="' . $next_classes . '" href="' . $next_href . '"' . $next_aria_hidden . '><span class="nav-text">Next</span><svg width="14" height="25" viewBox="0 0 14 25" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M1 1L12.5 12.5L1 24" stroke="var(--mm-blue-color)" stroke-width="2" stroke-linecap="round"></path> </svg></a>';
                            ?>
                          </div>
                      <?php endif; ?>
                    </div>
                </div>
                <aside class="sidebar">
                    <div data-target="blog_posts_search" class="form">
                        <form method="post" action="#">
                            <input name="category" value="<?php echo ($global_post != get_option('page_for_posts')) ? $global_post : '' ?>" type="hidden">
                            <input name="action" value="blog_posts_search" type="hidden">
                            <input placeholder="Search" name="search_post" type="text">
                            <button aria-label="Search" type="submit">
                                <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.39125 0.563477C4.21302 0.563477 0 4.7765 0 9.95473C0 15.133 4.21302 19.346 9.39125 19.346C11.5456 19.346 13.5267 18.609 15.113 17.3844L21.9384 24.2097C22.41 24.6814 23.1746 24.6814 23.6463 24.2097C24.1179 23.7381 24.1179 22.9735 23.6463 22.5019L16.8209 15.6765C18.0455 14.0902 18.7825 12.1091 18.7825 9.95473C18.7825 4.7765 14.5695 0.563477 9.39125 0.563477ZM9.39125 2.65042C13.4185 2.65042 16.6956 5.92745 16.6956 9.95473C16.6956 13.982 13.4185 17.259 9.39125 17.259C5.36397 17.259 2.08694 13.982 2.08694 9.95473C2.08694 5.92745 5.36397 2.65042 9.39125 2.65042Z" fill="#666666" />
                                </svg>
                            </button>
                            <div class="result">
                            </div>
                        </form>
                    </div>
                    <div class="categories">
                        <h3>Blog Categories</h3>
                        <ul class="list">
                            <li>
                                <a aria-label="View all" class="<?php echo ($actual_link == get_permalink(get_option('page_for_posts'))) ? 'active' : '' ?>" href="<?php echo get_permalink(get_option('page_for_posts')) ?>"><span>View All</span></a>
                            </li>
                            <?php foreach ($category_list as $item) :
                                $title = (get_field('menu_name', 'category_' . $item->term_id)) ? get_field('menu_name', 'category_' . $item->term_id) : $item->name; ?>
                                <li>
                                    <a aria-label="<?php echo $title ?>" class="<?php echo ($actual_link == get_term_link($item->term_id)) ? 'active' : '' ?>" href="<?php echo get_term_link($item->term_id) ?>"><span><?php echo $title ?></span></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div data-target="blog_posts_selector" class="mobile-selector">
                            <div class="placeholder">
                                <span>All Categories</span>
                                <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 1.5L7 7.5L13 1.5" stroke="var(--mm-navy-color)" stroke-width="2" />
                                </svg>
                            </div>
                            <ul class="list">
                                <li>
                                    <a aria-label="All Categories" class="<?php echo ($actual_link == get_permalink(get_option('page_for_posts'))) ? 'active' : '' ?>" href="<?php echo get_permalink(get_option('page_for_posts')) ?>"><span>All Categories</span></a>
                                </li>
                                <?php foreach ($category_list as $item) :
                                    $title = (get_field('menu_name', 'category_' . $item->term_id)) ? get_field('menu_name', 'category_' . $item->term_id) : $item->name; ?>
                                    <li>
                                        <a aria-label="<?php echo $title ?>" class="<?php echo ($actual_link == get_term_link($item->term_id)) ? 'active' : '' ?>" href="<?php echo get_term_link($item->term_id) ?>"><span><?php echo $title ?></span></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
    <?php if ($articles) : ?>
        <section class="popular-articles <?php echo $articles_background ?>">
            <div class="container">
                <div class="content">
                    <?php if ($articles_title) : ?>
                        <h2><?php echo $articles_title ?></h2>
                    <?php endif; ?>
                    <div class="posts">
                        <?php foreach ($articles as $article) :
                            get_template_part('templates/parts/blog', 'post', ['id' => $article, 'text' => false, 'author' => true, 'date' => true, 'type_of_articles' => 'popular']);
                        endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <section data-target="latest_videos" data-swiper-slides="<?php echo $youtube_range ?>" data-swiper-tablet-slides="2" data-swiper-space-between="30" data-swiper-mobile-disabled="true" class="latest-videos mm-section <?php echo $youtube_background ?>">
        <div class="container">
            <div class="content">
                <div class="heading">
                    <?php if ($youtube_title) : ?>
                        <h2><?php echo $youtube_title ?></h2>
                    <?php endif; ?>
                    <div class="heading-part">
                        <?php if (sizeof($youtube_videos) > $youtube_range) : ?>
                            <div class="controllers">
                                <button aria-label="Previous slide" data-target="swiper_left" class="btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                        <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z" fill="#0961AF" />
                                    </svg>
                                </button>
                                <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                        <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z" fill="#0961AF" />
                                    </svg>
                                </button>
                            </div>
                        <?php endif; ?>
                        <?php
                        if ($youtube_button) :
                            $link_url = $youtube_button['url'];
                            $link_title = $youtube_button['title'];
                            $link_target = $youtube_button['target'] ? $youtube_button['target'] : '_self';
                        ?>
                            <a aria-label="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (sizeof($youtube_videos) > 0) : ?>
                    <div class="blocks swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($youtube_videos as $youtube_video) : ?>
                                <div data-target="youtube_video" data-video-insert="false" data-video-id="<?php echo $youtube_video->id ?>" class="swiper-slide block">
                                    <div class="image">
                                        <?php if ($youtube_video->thumbnail) : ?>
                                            <img loading="lazy" width="<?php echo $youtube_video->thumbnail->width ?>" height="<?php echo $youtube_video->thumbnail->height ?>" src="<?php echo $youtube_video->thumbnail->url ?>" alt="<?php echo $youtube_video->title ?>">
                                        <?php else : ?>
                                            <img width="360" height="240" src="<?php echo get_template_directory_uri() . '/assets/images/video-placeholder.png' ?>" alt="<?php echo $youtube_video->title ?>">
                                        <?php endif; ?>
                                        <button type="button" aria-label="play <?php echo $youtube_video->title ?>" class="video-icon">
                                            <svg width="114" height="80" viewBox="0 0 114 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M111.065 12.2263C109.717 7.44288 105.987 3.69136 101.183 2.3652C92.3356 0.000113072 56.7706 0.000113644 56.7706 0.000113644C56.7706 0.000113644 21.2729 -0.0566209 12.3338 2.3652C7.554 3.6949 3.80602 7.44288 2.47278 12.2263C0.795582 21.293 -0.0376961 30.4697 0.0013084 39.689C-0.0164209 48.8479 0.813311 58.0105 2.47278 67.0205C3.80602 71.8039 7.554 75.5554 12.3338 76.9028C21.1808 79.2679 56.7706 79.2679 56.7706 79.2679C56.7706 79.2679 92.247 79.2679 101.183 76.9028C105.987 75.5554 109.717 71.8074 111.065 67.0205C112.692 58.0105 113.487 48.8479 113.43 39.689C113.487 30.4697 112.71 21.293 111.065 12.2263ZM45.4096 56.663V22.6405L75.0141 39.689L45.4096 56.663Z" fill="var(--mm-white-color)" />
                                            </svg>
                                        </button>
                                        <div class="loader hide">
                                            <img loading="lazy" src="<?php echo get_template_directory_uri() ?>/assets/images/loader-video.png" alt="loader">
                                        </div>
                                    </div>
                                    <div class="video hide">
                                        <div class="element-to-replace"></div>
                                    </div>
                                    <div class="text">
                                        <span class="tag">
                                            <svg width="20" height="17" viewBox="0 0 20 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18.5417 3.33325L14.3333 4.96979V0.833252H0V16.1666H14.3333V11.6967L18.5417 13.3333H20V3.33325H18.5417Z" fill="var(--mm-placeholder-grey-color)" />
                                            </svg>
                                            videos
                                        </span>
                                        <?php if ($youtube_video->title) : ?>
                                            <h3 class="title">
                                                <?php echo $youtube_video->title ?>
                                            </h3>
                                        <?php endif; ?>
                                        <div class="meta-data">
                                            <span><?php echo $youtube_video->views ?> views</span>
                                            <span><?php echo $youtube_video->date ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>