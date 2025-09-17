<?php

/**
 * Base Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 *          This is either the post ID currently being displayed inside a query loop,
 *          or the post ID of the post hosting this block.
 * @param   array $context The context provided to the block by the post or its parent block.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Support custom "anchor" values.
$anchor = '';
if (!empty($block['anchor'])) {
    $anchor = 'id="' . esc_attr($block['anchor']) . '" ';
}

// Create class attribute allowing for custom "className" and "align" values.
$class_name = 'youtube-section mm-section';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$background = get_field('background');
$title = get_field('title');
$range = get_field('range');
$videos = get_field('videos');
$youtube_api_key = get_field('youtube_api_key', 'option');
$is_home_page = get_field('is_home_page');
$is_small_border = get_field('smaller_borders');
$helpers = new MetroManhattanHelpers();

$class_name = ($is_small_border) ? $class_name . ' smaller-border' : $class_name;
$class_name = ($is_home_page) ? $class_name . ' not-home' : $class_name;
$class_name .= ' ' . $background;

$schema = [
    "@context" => "https://schema.org",
    "@type" => "ItemList",
    "itemListElement" => [],
];
?>
<section <?php echo $anchor; ?> data-target="swiper" data-swiper-slides="<?php echo $range ?>" data-swiper-tablet-slides="2" data-swiper-space-between="30" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="heading">
                <?php if ($title) : ?>
                    <h2><?php echo $title ?></h2>
                <?php endif; ?>
                <?php if (sizeof($videos) > $range) : ?>
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
            </div>
            <?php if (have_rows('videos')) : ?>
                <div class="blocks swiper">
                    <div class="swiper-wrapper">
                        <?php while (have_rows('videos')) : the_row();
                            $video_id = get_sub_field('video_id');
                            if ($video_id) :
                                $video_data = $helpers->get_youtube_video($youtube_api_key, $video_id);
                                $full_data = $helpers->get_youtube_video($youtube_api_key, $video_id, true);
                                $schema_data = [
                                    "@context" => "http://schema.org",
                                    "@type" => "VideoObject",
                                    "contentUrl" => "https://www.youtube.com/watch?v=" . $video_id
                                ];
                                if (!empty($full_data->snippet->title)) {
                                    $schema_data["name"] = $full_data->snippet->title;
                                }
                                if (!empty($full_data->snippet->thumbnails->default->url)) {
                                    $schema_data['thumbnailUrl'] = $full_data->snippet->thumbnails->default->url;
                                }
                                if (!empty($full_data->snippet->publishedAt)) {
                                    $schema_data['uploadDate'] = $full_data->snippet->publishedAt;
                                }
                                if (!empty($full_data->contentDetails->duration)) {
                                    $schema_data['duration'] = $full_data->contentDetails->duration;
                                }
                                if (!empty($full_data->snippet->description)) {
                                    $schema_data['description'] = $full_data->snippet->description;
                                }
                                if (!empty($full_data->snippet->tags)) {
                                    $schema_data['keywords'] = $full_data->snippet->tags;
                                }
                                $schema['itemListElement'][] = $schema_data;

                        ?>
                                <div data-target="youtube_video" data-video-insert="false" data-video-id="<?php echo $video_id ?>" class="swiper-slide block">
                                    <div class="image">
                                        <?php if ($video_data->thumbnail) : ?>
                                            <img loading="lazy" width="<?php echo $video_data->thumbnail->width ?>" height="<?php echo $video_data->thumbnail->height ?>" src="<?php echo $video_data->thumbnail->url ?>" alt="<?php echo $video_data->title ?>">
                                        <?php else : ?>
                                            <img width="360" height="240" src="<?php echo get_template_directory_uri() . '/assets/images/video-placeholder.png' ?>" alt="<?php echo $video_data->title ?>">
                                        <?php endif; ?>
                                        <button type="button" aria-label="play <?php echo $video_data->title ?>" class="video-icon">
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
                                        <?php if ($video_data->title) : ?>
                                            <h3 class="title">
                                                <?php echo $video_data->title ?>
                                            </h3>
                                        <?php endif; ?>
                                        <div class="meta-data">
                                            <span><?php echo $video_data->views ?> views</span>
                                            <span><?php echo $video_data->date ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php if (!empty($schema)) : ?>
    <script type="application/ld+json">
        <?php echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
    </script>
<?php endif; ?>