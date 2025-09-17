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
$class_name = 'hero-home';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$fields = get_fields();

$search_page = get_field('choose_search_page', 'option');
$search_page = get_permalink($search_page);
$helper = new MetroManhattanHelpers();
$type_taxonomy = $helper->get_hierarchically_taxonomy('listing-type');
$location_taxonomy = $helper->get_hierarchically_taxonomy('location', 70);
$global_filters = get_field('choose_filters', 'option');
?>
<section <?php echo $anchor; ?> data-target="search_form_section" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
	    <?php if ($fields['image']) : ?>
                <div class="image">
                    <picture>
                        <source srcset="https://www.metro-manhattan.com/wp-content/uploads/2023/07/office_interior-300x169.webp 300w, https://www.metro-manhattan.com/wp-content/uploads/2023/07/office_interior-768x432.webp 768w, https://www.metro-manhattan.com/wp-content/uploads/2023/07/office_interior.webp 1000w" sizes="(max-width: 600px) 300px, (max-width: 1200px) 768px, 1000px" type="image/webp">
                        <source srcset="https://www.metro-manhattan.com/wp-content/uploads/2023/07/office_interior-300x169.jpg 300w, https://www.metro-manhattan.com/wp-content/uploads/2023/07/office_interior-768x432.jpg 768w, https://www.metro-manhattan.com/wp-content/uploads/2023/07/office_interior.jpg 1000w" sizes="(max-width: 600px) 300px, (max-width: 1200px) 768px, 1000px" type="image/jpeg">
                        <img fetchpriority="high" src="https://www.metro-manhattan.com/wp-content/uploads/2023/07/office_interior.webp" alt="Office interior" width="1000" height="563">
                    </picture>
                </div>
            <?php endif; ?>
            <div class="text">
                <?php if ($fields['title']) : ?>
                    <h1><?php echo $fields['title'] ?></h1>
                <?php endif; ?>
                <?php if ($fields['text']) : ?>
                    <p><?php echo $fields['text'] ?></p>
                <?php endif; ?>
                <?php if ($fields['show_google_rating']) : ?>
                    <div class="google-reviews">
                        <div class="rating">
                            <span>4.9</span>
                            <div data-google-rating="5.0" class="stars">
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                            </div>
                        </div>
                        <p>81 reviews on <strong>Google</strong></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($fields['show_filter']) : ?>
            <?php if (is_array($global_filters)) : ?>
                <div class="form-wrapper">
                    <div class="form">
                        <form method="post" action="<?php echo $search_page ?>">
                            <input type="hidden" name="is_search_form_submit" value="true">
                            <div class="heading">
                                <p>Search by</p>
                                <button aria-label="Clear all" data-target="clear_all" type="button" class="simple-button">Clear all</button>
                            </div>
                            <div class="content">
                                <?php foreach ($global_filters as $filter) : ?>
                                    <?php if ($filter['acf_fc_layout'] == 'listing_types') :
                                        $types = $filter['listing_type']; ?>
                                        <div data-name="types" data-target="form_field" class="form-field">
                                            <div class="placeholder">
                                                <span>All Types</span>
                                                <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                                </svg>
                                            </div>
                                            <div class="parent-wrapper">
                                                <div data-target="wrapper" class="wrapper">
                                                    <div class="mobile-header">
                                                        <div class="header">
                                                            <button aria-label="Back" type="button" data-target="back_to_menu">
                                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M12 4.95812H2.56031L6.57268 0.945755L5.62692 0L0 5.62692L5.62692 11.2538L6.57268 10.3081L2.56023 6.29564H12V4.95812Z" fill="var(--mm-navy-color" />
                                                                </svg>
                                                                <span>Back</span>
                                                            </button>
                                                            <button aria-label="Clear all" data-target="clear_field" class="simple-button" type="button">Clear
                                                                all
                                                            </button>
                                                        </div>
                                                        <div class="tab">
                                                            <span>Types</span>
                                                        </div>
                                                    </div>
                                                    <div class="wrapper-list">
                                                        <div data-target="select_all" class="parent checkbox">
                                                            <input type="checkbox" name="filter[uses][0]" value="All Uses" id="all-uses">
                                                            <label for="all-uses">Select all</label>
                                                        </div>
                                                        <?php foreach ($types as $type) :
                                                            $term = get_term_by('term_taxonomy_id', $type['choose_type']);
                                                            $name = (!empty($type['text'])) ? $type['text'] : $term->name;
                                                        ?>
                                                            <div data-target="checkbox" class="checkbox">
                                                                <input type="checkbox" name="filter[uses][<?php echo $term->term_id ?>]" value="<?php echo $term->name ?>" id="use[<?php echo $term->term_id ?>]">
                                                                <label for="use[<?php echo $term->term_id ?>]"><?php echo $name ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="controllers">
                                                        <button aria-label="Cancel" data-target="cancel_button" class="simple-button" type="button">
                                                            Cancel
                                                        </button>
                                                        <button aria-label="Apply" data-target="apply_button" type="button">Apply</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif ($filter['acf_fc_layout'] == 'location') : ?>
                                        <div data-name="NYC" data-target="form_field" class="form-field">
                                            <div class="placeholder">
                                                <span>All NYC</span>
                                                <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                                </svg>
                                            </div>
                                            <div class="parent-wrapper">
                                                <div data-target="wrapper" class="wrapper">
                                                    <div class="mobile-header">
                                                        <div class="header">
                                                            <button aria-label="Back" type="button" data-target="back_to_menu">
                                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M12 4.95812H2.56031L6.57268 0.945755L5.62692 0L0 5.62692L5.62692 11.2538L6.57268 10.3081L2.56023 6.29564H12V4.95812Z" fill="var(--mm-navy-color" />
                                                                </svg>
                                                                <span>Back</span>
                                                            </button>
                                                            <button aria-label="Clear all" data-target="clear_field" class="simple-button" type="button">Clear
                                                                All
                                                            </button>
                                                        </div>
                                                        <div class="tab">
                                                            <span>NYC</span>
                                                        </div>
                                                    </div>
                                                    <div class="wrapper-list">
                                                        <div data-target="select_all" class="parent checkbox">
                                                            <input type="checkbox" value="-1" id="all-locations">
                                                            <label for="all-locations">Select all</label>
                                                        </div>
                                                        <?php foreach ($location_taxonomy as $location_term) :
                                                            if (sizeof($location_term->children) > 0) : ?>
                                                                <div data-target="group_checkbox" class="group-checkbox">
                                                                    <div data-target="group_parent_checkbox" class="group-parent checkbox">
                                                                        <input type="checkbox" name="filter[locations][<?php echo $location_term->term_id ?>]" value="<?php echo $location_term->name ?>" id="locations[<?php echo $location_term->term_id ?>]">
                                                                        <label for="locations[<?php echo $location_term->term_id ?>]"><?php echo $location_term->name ?></label>
                                                                        <button aria-label="Open accordion" data-target="accordion_button" class="parent-button" type="button">
                                                                            <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                                                            </svg>
                                                                            <span>
                                                                                &nbsp;
                                                                            </span>
                                                                        </button>
                                                                    </div>
                                                                    <div data-target="accordion_content" class="checkboxes-accordion">
                                                                        <?php foreach ($location_term->children as $location_child) : ?>
                                                                            <div data-target="checkbox" class="group-child checkbox">
                                                                                <input type="checkbox" name="filter[locations][<?php echo $location_child->term_id ?>]" value="<?php echo $location_child->name ?>" id="locations[<?php echo $location_child->term_id ?>]">
                                                                                <label for="locations[<?php echo $location_child->term_id ?>]"><?php echo $location_child->name ?></label>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            <?php else : ?>
                                                                <div data-target="checkbox" class="checkbox">
                                                                    <input type="checkbox" name="filter[locations][<?php echo $location_term->term_id ?>]" value="<?php echo $location_term->name ?>" id="locations[<?php echo $location_term->term_id ?>]">
                                                                    <label for="locations[<?php echo $location_term->term_id ?>]"><?php echo $location_term->name ?></label>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="controllers">
                                                        <button data-target="cancel_button" class="simple-button" type="button">Cancel
                                                        </button>
                                                        <button aria-label="Apply" data-target="apply_button" type="button">Apply</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif ($filter['acf_fc_layout'] == 'sizes') : ?>
                                        <div data-name="sizes" data-target="form_field" data-range="true" class="form-field">
                                            <div class="placeholder">
                                                <span>All Sizes</span>
                                                <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                                </svg>
                                            </div>
                                            <?php if ($filter['enter_sizes'] && !is_admin()) : ?>
                                                <div class="parent-wrapper">
                                                    <div data-target="wrapper" class="wrapper">
                                                        <div class="mobile-header">
                                                            <div class="header">
                                                                <button aria-label="Back" type="button" data-target="back_to_menu">
                                                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M12 4.95812H2.56031L6.57268 0.945755L5.62692 0L0 5.62692L5.62692 11.2538L6.57268 10.3081L2.56023 6.29564H12V4.95812Z" fill="var(--mm-navy-color" />
                                                                    </svg>
                                                                    <span>Back</span>
                                                                </button>
                                                                <button aria-label="Clear all" data-target="clear_field" class="simple-button" type="button">Clear
                                                                    all
                                                                </button>
                                                            </div>
                                                            <div class="tab">
                                                                <span>Sizes</span>
                                                            </div>
                                                        </div>
                                                        <div class="wrapper-list">
                                                            <div data-target="select_all" class="parent checkbox">
                                                                <input type="checkbox" value="-1" id="all-sizes">
                                                                <label for="all-sizes">Select all</label>
                                                            </div>
                                                            <?php
                                                            $i = 1;
                                                            foreach ($filter['enter_sizes'] as $size) : ?>
                                                                <div data-target="checkbox" class="checkbox">
                                                                    <input type="checkbox" name="filter[sizes][<?php echo esc_attr($i) ?>]" value="<?php echo esc_attr($size['type']) ?><?php echo esc_attr($size['value']) ?>" id="size[<?php echo esc_attr($i) ?>]">
                                                                    <label for="size[<?php echo esc_attr($i) ?>]"><?php echo esc_html($size['text']) ?></label>
                                                                </div>
                                                            <?php $i++;
                                                            endforeach; ?>
                                                        </div>
                                                        <div class="controllers">
                                                            <button aria-label="Cancel" data-target="cancel_button" class="simple-button" type="button">
                                                                Cancel
                                                            </button>
                                                            <button aria-label="Apply" data-target="apply_button" type="button">Apply
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else : ?>
                                        <div data-name="Max Rent/Month" data-target="form_field" data-single="true" class="form-field">
                                            <div class="placeholder">
                                                <span>Max Rent/Month</span>
                                                <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                                </svg>
                                            </div>
                                            <?php if ($filter['enter_prices'] && !is_admin()) : ?>
                                                <div class="parent-wrapper">
                                                    <div data-target="wrapper" class="wrapper">
                                                        <div class="mobile-header">
                                                            <div class="header">
                                                                <button aria-label="Back" type="button" data-target="back_to_menu">
                                                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M12 4.95812H2.56031L6.57268 0.945755L5.62692 0L0 5.62692L5.62692 11.2538L6.57268 10.3081L2.56023 6.29564H12V4.95812Z" fill="var(--mm-navy-color" />
                                                                    </svg>
                                                                    <span>Back</span>
                                                                </button>
                                                                <button aria-label="Clear all" data-target="clear_field" class="simple-button" type="button">Clear
                                                                    all
                                                                </button>
                                                            </div>
                                                            <div class="tab">
                                                                <span>Max Rent/Month</span>
                                                            </div>
                                                        </div>
                                                        <div class="wrapper-list">
                                                            <div data-target="select_all" class="parent checkbox">
                                                                <input type="checkbox" value="-1" id="all-prices">
                                                                <label for="all-prices">Select all</label>
                                                            </div>
                                                            <?php
                                                            $i = 1;
                                                            foreach ($filter['enter_prices'] as $price) : ?>
                                                                <div data-target="checkbox" class="checkbox">
                                                                    <input type="checkbox" name="filter[prices][<?php echo esc_attr($i) ?>]" value="<?php echo esc_attr($price['type']) ?><?php echo esc_attr($price['value']) ?>" id="price[<?php echo esc_attr($i) ?>]">
                                                                    <label for="price[<?php echo esc_attr($i) ?>]"><?php echo esc_html($price['text']) ?></label>
                                                                </div>
                                                            <?php $i++;
                                                            endforeach; ?>
                                                        </div>
                                                        <div class="controllers">
                                                            <button aria-label="Cancel" data-target="cancel_button" class="simple-button" type="button">
                                                                Cancel
                                                            </button>
                                                            <button aria-label="Apply" data-target="apply_button" type="button">Apply
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <div class="button">
                                    <button aria-label="Get results" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="26" viewBox="0 0 24 26" fill="none">
                                            <path d="M9.39125 0.546875C4.21302 0.546875 0 4.91888 0 10.2925C0 15.6661 4.21302 20.0381 9.39125 20.0381C11.5456 20.0381 13.5267 19.2733 15.113 18.0025L21.9384 25.0854C22.41 25.5749 23.1746 25.5749 23.6463 25.0854C24.1179 24.596 24.1179 23.8026 23.6463 23.3131L16.8209 16.2302C18.0455 14.584 18.7825 12.5282 18.7825 10.2925C18.7825 4.91888 14.5695 0.546875 9.39125 0.546875ZM9.39125 2.71257C13.4185 2.71257 16.6956 6.11326 16.6956 10.2925C16.6956 14.4718 13.4185 17.8724 9.39125 17.8724C5.36397 17.8724 2.08694 14.4718 2.08694 10.2925C2.08694 6.11326 5.36397 2.71257 9.39125 2.71257Z" fill="var(--mm-navy-color)" />
                                        </svg>
                                        <span>get results</span>
                                    </button>
                                </div>
                            </div>
                            <div class="controllers">
                                <button aria-label="Cancel" data-target="cancel_form" type="button" class="simple-button">Cancel</button>
                                <button aria-label="Get listings" type="submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="26" viewBox="0 0 24 26" fill="none">
                                        <path d="M9.39125 0.546875C4.21302 0.546875 0 4.91888 0 10.2925C0 15.6661 4.21302 20.0381 9.39125 20.0381C11.5456 20.0381 13.5267 19.2733 15.113 18.0025L21.9384 25.0854C22.41 25.5749 23.1746 25.5749 23.6463 25.0854C24.1179 24.596 24.1179 23.8026 23.6463 23.3131L16.8209 16.2302C18.0455 14.584 18.7825 12.5282 18.7825 10.2925C18.7825 4.91888 14.5695 0.546875 9.39125 0.546875ZM9.39125 2.71257C13.4185 2.71257 16.6956 6.11326 16.6956 10.2925C16.6956 14.4718 13.4185 17.8724 9.39125 17.8724C5.36397 17.8724 2.08694 14.4718 2.08694 10.2925C2.08694 6.11326 5.36397 2.71257 9.39125 2.71257Z" fill="var(--mm-navy-color)" />
                                    </svg>
                                    <span>Get Listings</span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <button aria-label="Search listings" data-target="search_listings" class="search-listings" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="26" viewBox="0 0 24 26" fill="none">
                            <path d="M9.39125 0.546875C4.21302 0.546875 0 4.91888 0 10.2925C0 15.6661 4.21302 20.0381 9.39125 20.0381C11.5456 20.0381 13.5267 19.2733 15.113 18.0025L21.9384 25.0854C22.41 25.5749 23.1746 25.5749 23.6463 25.0854C24.1179 24.596 24.1179 23.8026 23.6463 23.3131L16.8209 16.2302C18.0455 14.584 18.7825 12.5282 18.7825 10.2925C18.7825 4.91888 14.5695 0.546875 9.39125 0.546875ZM9.39125 2.71257C13.4185 2.71257 16.6956 6.11326 16.6956 10.2925C16.6956 14.4718 13.4185 17.8724 9.39125 17.8724C5.36397 17.8724 2.08694 14.4718 2.08694 10.2925C2.08694 6.11326 5.36397 2.71257 9.39125 2.71257Z" fill="var(--mm-navy-color)" />
                        </svg>
                        <span>Search Listings</span>
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
