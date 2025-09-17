<?php
get_header('search');
get_template_part('templates/parts/notification', 'template');

$hero_image = get_field('hero_image');
$hero_image_caption = (!empty($hero_image)) ? wp_get_attachment_caption($hero_image['id']) : null;
$featured_listings = MetroManhattanHelpers::get_generated_featured_listings( get_the_ID() );
$featured_listings_button = get_field('global_featured_listings_button', 'option');
$generated_high_quality_articles = get_post_meta(get_the_ID(), '_generated_high_quality_articles', true);

if (!empty($generated_high_quality_articles)) {
    $more_from_metro_manhattan = maybe_unserialize($generated_high_quality_articles);
} else {
    $more_from_metro_manhattan = MetroManhattanHelpers::get_posts_by_mode('featured', ['post_type' => 'post', 'exclude' => get_the_ID(), 'numberposts' => 3]);
}
$featured_buildings = MetroManhattanHelpers::get_posts_by_mode('featured', ['post_type' => 'buildings']);
$post_date = get_the_date('d F, Y');
$post_author = get_post()->post_author;
$post_author = get_the_author_meta('display_name', $post_author);
$generated_recommended_articles = get_post_meta(get_the_ID(), '_generated_recommended_articles', true);

if (!empty($generated_recommended_articles)) {
    $recommended_articles = maybe_unserialize($generated_recommended_articles);
} else {
    $recommended_articles = get_posts([
        'numberposts' => 3,
        'orderby' => 'rand',
        'category' => current(get_the_category())->term_id,
        'exclude' => get_the_ID()
    ]);
    $recommended_articles = array_map(function ($recommended_article) {
        return $recommended_article->ID;
    }, $recommended_articles);
}

$images = [];

$document = new DOMDocument();
@$document->loadHTML(get_the_content());
$document_images = $document->getElementsByTagName('img');
foreach ($document_images as $document_image) {
    $images[] = $document_image->getAttribute('src');
}

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => get_the_title(),
    'image' => $images,
    'datePublished' => get_the_date('c'),
    'dateModified' => get_the_modified_date('c'),
    'author' => [
        [
            '@type' => 'Person',
            'name' => $post_author,
        ]
    ]
];

?>
<main class="single">
    <?php
    get_template_part('templates/parts/notification', 'template');
    ?>
    <article class="single-post">
        <header class="single-post-header">
            <div class="container">
                <div class="content">
                    <?php echo MetroManhattanHelpers::breadcrumbs() ?>
                    <h1><?php echo get_the_title() ?></h1>
                    <div class="post-data">
                        <?php if ($post_date) : ?>
                            <span class="date"><?php echo $post_date ?></span> /
                        <?php endif; ?>
                        <?php if ($post_author) : ?>
                            <span class="author"><?php echo $post_author ?></span>
                        <?php endif; ?>
                    </div>
                    <ul class="share-links">
                        <li class="facebook">
                            <a aria-label="Share post via facebook" target="_blank" rel="noopener noreferrer nofollow" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo get_permalink(get_the_ID()) ?>">
                                <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.5 3.50005C17.5 3.36745 17.4473 3.24027 17.3536 3.1465C17.2598 3.05273 17.1326 3.00005 17 3.00005H14.5C13.2411 2.93734 12.0086 3.37544 11.0717 4.21863C10.1348 5.06182 9.56978 6.24155 9.5 7.50005V10.2001H7C6.86739 10.2001 6.74021 10.2527 6.64645 10.3465C6.55268 10.4403 6.5 10.5674 6.5 10.7001V13.3001C6.5 13.4327 6.55268 13.5598 6.64645 13.6536C6.74021 13.7474 6.86739 13.8001 7 13.8001H9.5V20.5001C9.5 20.6327 9.55268 20.7598 9.64645 20.8536C9.74021 20.9474 9.86739 21.0001 10 21.0001H13C13.1326 21.0001 13.2598 20.9474 13.3536 20.8536C13.4473 20.7598 13.5 20.6327 13.5 20.5001V13.8001H16.12C16.2312 13.8017 16.3397 13.7661 16.4285 13.6991C16.5172 13.6321 16.5811 13.5374 16.61 13.4301L17.33 10.8301C17.3499 10.7562 17.3526 10.6787 17.3378 10.6036C17.3231 10.5286 17.2913 10.4579 17.2449 10.397C17.1985 10.3362 17.1388 10.2868 17.0704 10.2526C17.0019 10.2185 16.9265 10.2005 16.85 10.2001H13.5V7.50005C13.5249 7.25253 13.6411 7.02317 13.826 6.85675C14.0109 6.69033 14.2512 6.59881 14.5 6.60005H17C17.1326 6.60005 17.2598 6.54737 17.3536 6.45361C17.4473 6.35984 17.5 6.23266 17.5 6.10005V3.50005Z" fill="#AFAFAF" />
                                </svg>
                            </a>
                        </li>
                        <li class="twitter">
                            <a aria-label="Share post via twitter" target="_blank" rel="noopener noreferrer nofollow" href="https://twitter.com/share?url=<?php echo get_permalink(get_the_ID()) ?>">
                                <svg width="21" height="17" viewBox="0 0 21 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20.4608 1.93617C19.7121 2.26583 18.9189 2.48343 18.1068 2.58198C18.9618 2.06804 19.602 1.26207 19.9092 0.312908C19.1167 0.775389 18.2384 1.11204 17.3035 1.29953C16.6866 0.639919 15.8693 0.20243 14.9783 0.0550032C14.0873 -0.0924239 13.1726 0.0584605 12.3761 0.484226C11.5797 0.909992 10.9461 1.58681 10.5738 2.40959C10.2015 3.23237 10.1112 4.15506 10.3171 5.03438C6.9089 4.87355 3.88819 3.23695 1.86661 0.762889C1.49895 1.38771 1.30719 2.10036 1.31163 2.8253C1.31163 4.25024 2.0366 5.50269 3.13489 6.23849C2.48397 6.21777 1.84742 6.04178 1.2783 5.72518V5.77601C1.27793 6.72297 1.60518 7.64089 2.20452 8.37404C2.80386 9.10719 3.63838 9.61042 4.5665 9.79835C3.9651 9.95949 3.33523 9.98369 2.72324 9.86918C2.98677 10.6841 3.49806 11.3964 4.18581 11.9069C4.87355 12.4173 5.70344 12.7003 6.55975 12.7166C5.10938 13.8547 3.3186 14.4725 1.47496 14.4707C1.14997 14.4707 0.82582 14.4515 0.5 14.4148C2.37968 15.6184 4.56526 16.2572 6.79724 16.2556C14.3419 16.2556 18.4626 10.0092 18.4626 4.60106C18.4626 4.4269 18.4626 4.25108 18.4501 4.07608C19.255 3.49681 19.9494 2.77764 20.5 1.95284L20.4608 1.93617Z" fill="#AFAFAF" />
                                </svg>
                            </a>
                        </li>
                        <li class="linkedin">
                            <a aria-label="Share post via linke" target="_blank" rel="noopener noreferrer nofollow" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo get_permalink(get_the_ID()) ?>">
                                <svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15.8353 15.339H13.1698V11.1623C13.1698 10.1663 13.1495 8.8845 11.7808 8.8845C10.391 8.8845 10.1788 9.96825 10.1788 11.0887V15.339H7.51325V6.75H10.0738V7.92075H10.1082C10.466 7.24575 11.336 6.53325 12.6357 6.53325C15.3365 6.53325 15.836 8.31075 15.836 10.6245V15.339H15.8353ZM4.50275 5.57475C4.29941 5.57485 4.09804 5.53485 3.91018 5.45703C3.72232 5.37921 3.55164 5.26511 3.40793 5.12126C3.26421 4.97741 3.15028 4.80662 3.07265 4.61868C2.99501 4.43075 2.9552 4.22934 2.9555 4.026C2.95565 3.71983 3.04658 3.42059 3.2168 3.1661C3.38702 2.91162 3.62888 2.71333 3.9118 2.5963C4.19472 2.47927 4.50598 2.44877 4.80624 2.50864C5.10649 2.56852 5.38224 2.71608 5.59863 2.93268C5.81502 3.14928 5.96232 3.42517 6.0219 3.72549C6.08149 4.0258 6.05068 4.33703 5.93338 4.61984C5.81608 4.90264 5.61755 5.14431 5.3629 5.31428C5.10825 5.48425 4.80892 5.5749 4.50275 5.57475ZM5.83925 15.339H3.16625V6.75H5.83925V15.339ZM17.1688 0H1.82825C1.094 0 0.5 0.5805 0.5 1.29675V16.7033C0.5 17.4202 1.094 18 1.82825 18H17.1665C17.9 18 18.5 17.4202 18.5 16.7033V1.29675C18.5 0.5805 17.9 0 17.1665 0H17.1688Z" fill="#AFAFAF" />
                                </svg>
                            </a>
                        </li>
                        <li class="mail">
                            <a aria-label="Share post via email" href="#" class="share-link--email">
                                <svg width="19" height="14" viewBox="0 0 19 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0.5 0V2.25L9.5 6.75L18.5 2.25V0H0.5ZM0.5 4.5V13.5H18.5V4.5L9.5 9L0.5 4.5Z" fill="#AFAFAF" />
                                </svg>
                            </a>
                        </li>
                        <div id="emailShareModal" class="email-share-modal" style="display: none;">
                            <div class="email-share-content">
                                <button id="shareGmail" class="email-share-btn">Share via Gmail</button>
                                <button id="shareOutlook" class="email-share-btn">Share via Outlook.com</button>
                                <hr>
                                <button id="copyLink" class="email-share-btn">Copy link</button>
                                <span id="copySuccess" style="display:none;color:var(--mm-navy-color);font-size:14px;">Link copied!</span>
                            </div>
                        </div>
                        <style>
                            .email-share-modal {
                                position: fixed;
                                top: 0;
                                left: 0;
                                width: 100vw;
                                height: 100vh;
                                background: rgba(0,0,0,0.3);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                z-index: 1000;
                            }
                            .email-share-content {
                                background: #fff;
                                padding: 24px 32px;
                                border-radius: 8px;
                                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                display: flex;
                                flex-direction: column;
                                gap: 16px;
                                min-width: 250px;
                                align-items: center;
                            }
                            .email-share-btn {
                                color: #0961AF;
                                border: none;
                                border-radius: 4px;
                                padding: 10px 24px;
                                font-size: 16px;
                                cursor: pointer;
                                width: 100%;
                                transition: background 0.2s;
                            }
                            .email-share-content hr {
                                border: none;
                                border-top: 1px solid buttonface;
                                margin: 16px 0;
                                width: 100%;
                            }
                        </style>
                        <script>
                            document.addEventListener('DOMContentLoaded', function(){
                                const emailLink = document.querySelector('.share-link--email');
                                const modal = document.getElementById('emailShareModal');
                                const gmailBtn = document.getElementById('shareGmail');
                                const outlookBtn = document.getElementById('shareOutlook');
                                const copyBtn = document.getElementById('copyLink');
                                const copySuccess = document.getElementById('copySuccess');
                                const articleTitle = document.title;
                                const articleUrl = window.location.href;

                                emailLink.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    modal.style.display = 'flex';
                                });

                                gmailBtn.addEventListener('click', function() {
                                    const gmailUrl = `https://mail.google.com/mail/?view=cm&fs=1&to=&su=${encodeURIComponent(articleTitle)}&body=${encodeURIComponent(articleUrl)}`;
                                    window.open(gmailUrl, '_blank');
                                    modal.style.display = 'none';
                                });

                                outlookBtn.addEventListener('click', function() {
                                    const outlookUrl = `https://outlook.live.com/mail/0/deeplink/compose?subject=${encodeURIComponent(articleTitle)}&body=${encodeURIComponent(articleUrl)}`;
                                    window.open(outlookUrl, '_blank');
                                    modal.style.display = 'none';
                                });

                                copyBtn.addEventListener('click', function() {
                                    navigator.clipboard.writeText(articleUrl).then(function() {
                                        copySuccess.style.display = 'block';
                                        setTimeout(function() {
                                            copySuccess.style.display = 'none';
                                        }, 1500);
                                    });
                                });
                                
                                modal.addEventListener('click', function(e) {
                                    if (e.target === modal) {
                                        modal.style.display = 'none';
                                    }
                                });
                            });
                        </script>
                    </ul>
                </div>
            </div>
        </header>
        <?php if ($hero_image) : ?>
            <div class="hero-image">
                <div class="container">
                    <div class="content">
                        <figure>
                            <?php echo wp_get_attachment_image($hero_image['id'], 'full', '', ['loading' => 'eager']) ?>
                            <?php if ($hero_image_caption) : ?>
                                <figcaption>
                                    <p><?php echo $hero_image_caption ?></p>
                                </figcaption>
                            <?php endif; ?>
                        </figure>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <section class="main-post">
            <div class="container">
                <div class="content">
                    <div class="post-content">
                        <?php the_content(); ?>
                                                    <style>
                                .author-box {
                                    background-color: #EDEDF1;
                                    border: 1px solid #EDEDF1;
                                    border-radius: 7px;
                                    display: flex;
                                    padding: 15px;
                                    flex-wrap: wrap;
                                }

                                .author-image {
                                    width: 96px;
                                    height: 96px;
                                    border-radius: 50%;
                                    overflow: hidden;
                                    margin-right: 15px;
                                    flex-shrink: 0;
                                }

                                .author-image img {
                                    width: 100%;
                                    height: 100%;
                                    object-fit: cover;
                                }

                                .author-info {
                                    flex: 1;
                                }

                                .author-info span {
                                    display: block;
                                    margin-bottom: 5px;
                                }

                                .about-author {
                                    color: #6E7484;
                                    font-weight: 700;
                                    font-size: 15px;
                                    line-height: 21px;
                                }

                                .author-name {
                                    line-height: 30px;
                                    font-size: 22px;
                                    font-weight: 700;
                                    color: #000;
                                }

                                .author-position {
                                    color: #4A4D51;
                                    line-height: 28px;
                                    font-size: 20px;
                                    font-weight: 700;
                                }

                                .author-bio {
                                    color: #4A4D51;
                                    line-height: 21px;
                                    font-size: 15px;
                                    font-weight: 400;
                                }

                                /* Responsive Styles */
                                @media (max-width: 600px) {
                                    .author-box {
                                        flex-direction: column;
                                        align-items: center;
                                        text-align: center;
                                    }

                                    .author-image {
                                        margin-right: 0;
                                        margin-bottom: 15px;
                                    }
                                }
                                
                                .author-img img, .author-img {
                                    margin-top: 0 !important;
                                }
                            </style>
                        <?php
                            $post_author_id   = get_post()->post_author;
                            $post_author_name = get_the_author_meta( 'display_name', $post_author_id );
                            
                            $author_image    = get_field( 'author_image', 'user_' . $post_author_id );
                            $author_position = get_field( 'author_position', 'user_' . $post_author_id );
                            $author_bio      = get_field( 'author_bio', 'user_' . $post_author_id );
                          ?>
                            <div class="author-box">
                                <div class="author-image">
                                  <?php if ( $author_image ): ?>
                                    <?php echo wp_get_attachment_image( $author_image['ID'], 'thumbnail', false, [
                                      'class' => 'author-img', 'style' => 'margin-top: 0;'
                                    ] ); ?>
                                  <?php else: ?>
                                      <picture class="author-img">
                                          <img width="150" height="150" src="https://www.metro-manhattan.com/wp-content/uploads/2024/11/alan-about-the-author.png" style="margin-top:0;" alt="" decoding="async">
                                      </picture>
                                  <?php endif; ?>
                                </div>
                                <div class="author-info">
                                    <span class="about-author">ABOUT THE AUTHOR</span>
                                    <span class="author-name"><?php echo esc_html( $post_author_name ); ?></span>
                                  <?php if ( $author_position ): ?>
                                      <span class="author-position"><?php echo esc_html( $author_position ); ?></span>
                                  <?php endif; ?>
                                  <?php if ( $author_bio ): ?>
                                      <span class="author-bio"><?php echo wp_kses_post( $author_bio ); ?></span>
                                  <?php endif; ?>
                                </div>
                            </div>
                    </div>
                    <aside class="sidebar">
                        <h3>Recommended Articles</h3>
                        <?php if ($recommended_articles) : ?>
                            <div class="posts">
                                <?php foreach ($recommended_articles as $article) :
                                    get_template_part('templates/parts/blog', 'post', ['id' => $article, 'text' => false, 'heading' => 'h4', 'author' => true, 'date' => true, 'type_of_articles' => 'recommended']);
                                endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </section>
        <?php if ($featured_listings) : ?>
            <section data-target="featured_listings" class="featured-listings mm-section">
                <div class="container">
                    <div class="content">
                        <div class="heading">
                            <h2>Featured Listings</h2>
                            <div class="heading-part">
                                <?php if (sizeof($featured_listings) > 4) : ?>
                                    <div class="controllers">
                                        <button aria-label="Previous slide" data-target="swiper_left" class="btn swiper-button-disabled" disabled="">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                                <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z" fill="#0961AF"></path>
                                            </svg>
                                        </button>
                                        <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                                <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z" fill="#0961AF"></path>
                                            </svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($featured_listings_button)) :
                                    $link_url = $featured_listings_button['url'];
                                    $link_target = ($featured_listings_button['target']) ? $featured_listings_button['target'] : '_self';
                                    $link_title = $featured_listings_button['title']; ?>
                                    <a href="<?php echo esc_url($link_url) ?>" title="<?php echo esc_attr($link_title) ?>" target="<?php echo esc_attr($link_target) ?>"><?php echo esc_html($link_title) ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($featured_listings) : ?>
                            <div class="swiper">
                                <div class="swiper-wrapper">
                                    <?php foreach ($featured_listings as $listing) : ?>
                                        <div class="swiper-slide">
                                            <?php get_template_part('templates/parts/listing', 'card', ['id' => $listing, 'heading' => 'h3', 'class' => 'horizontal', 'favourites_template' => false]); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
        <?php if (!empty($featured_buildings)) : ?>
            <section data-target="featured_buildings" class="featured-buildings mm-section">
                <div class="container">
                    <div class="content">
                        <div class="heading">
                            <h2>Featured Buildings</h2>
                            <?php if (sizeof($featured_buildings) > 3) : ?>
                                <div class="heading-part">
                                    <div class="controllers">
                                        <button aria-label="Previous slide" data-target="swiper_left" class="btn swiper-button-disabled" disabled="">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                                <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z" fill="#0961AF"></path>
                                            </svg>
                                        </button>
                                        <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                                <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z" fill="#0961AF"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($featured_buildings as $building) : ?>
                                    <div class="swiper-slide">
                                        <?php get_template_part('templates/parts/listing', 'card', ['id' => $building, 'heading' => 'h3', 'building' => true, 'favourites_template' => false]) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
        <?php if ($more_from_metro_manhattan) : ?>
            <section class="more-posts">
                <div class="container">
                    <div class="content">
                        <h2>More from Metro Manhattan</h2>
                        <?php if ($more_from_metro_manhattan) : ?>
                            <div class="posts">
                                <?php foreach ($more_from_metro_manhattan as $post) :
                                    get_template_part('templates/parts/blog', 'post', ['id' => $post, 'author' => true, 'date' => true, 'type_of_articles' => 'more-from-metro']);
                                endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    </article>
</main>

<?php get_footer();
