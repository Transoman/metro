<?php
$listing_id = isset($_POST['listing_id']) ? $_POST['listing_id'] : null;

if ($listing_id) {
	$listing_fields = get_fields($listing_id);
	$feature_terms = get_the_terms($listing_id, 'feature');
	$print_text = get_field('text_before_description', 'option');
	$broker_image = get_field('broker_image', 'option');
	$broker_name = get_field('broker_name', 'option');
	$broker_rank = get_field('broker_rank', 'option');
	$broker_contacts = get_field('broker_contacts', 'option');
	$listing_main_type = wp_get_object_terms($listing_id, 'listing-type', ['orderby' => 'parent', 'parent' => 0]);
}
?>

<main>
    <section class="print">
        <div class="logo-part">
            <div class="logo">
                <?php echo wp_get_attachment_image(get_theme_mod('custom_logo'), 'full') ?>
            </div>
	        <?php if ( isset( $listing_main_type ) && is_array( $listing_main_type ) && sizeof( $listing_main_type ) > 0 ) : ?>
                <div class="text">
                    <p><?php echo current($listing_main_type)->name ?> for rent</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="listing-header">
            <div class="title">
                <h1><?php echo get_the_title($listing_id) ?></h1>
            </div>
            <div class="details">
	            <?php if ( isset( $listing_fields['monthly_rent'] ) && ! $listing_fields['call_request'] ) : ?>
                    <p class="bold">$<?php echo number_format($listing_fields['monthly_rent']) ?>/month</p>
                <?php else : ?>
                    <p class="bold">Call for pricing</p>
                <?php endif; ?>
	            <?php if ( isset( $listing_fields['listing_id'] ) ) : ?>
                    <p class="bold">ID-<?php echo $listing_fields['listing_id'] ?> For Rent</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="listing-main">
            <div class="image">
                <?php echo wp_get_attachment_image(get_post_thumbnail_id($listing_id), 'full') ?>
            </div>
            <div class="details">
                <table>
	                <?php if ( isset( $listing_fields['square_feet'] ) ) : ?>
                        <tr>
                            <td class="bold">Size:</td>
                            <td><?php echo number_format($listing_fields['square_feet'], 0) ?> SF</td>
                        </tr>
                    <?php endif; ?>
	                <?php if ( isset( $listing_fields['rent_sf'] ) && ! $listing_fields['call_request'] ) : ?>
                        <tr>
                            <td class="bold">Rent/SF:</td>
                            <td>$<?php echo number_format($listing_fields['rent_sf']) ?></td>
                        </tr>
                    <?php else : ?>
                        <tr>
                            <td class="bold">Rent/SF:</td>
                            <td>Call for pricing</td>
                        </tr>
                    <?php endif; ?>
	                <?php if ( isset( $listing_fields['monthly_rent'] ) && ! $listing_fields['call_request'] ) : ?>
                        <tr>
                            <td class="bold">Monthly Rent:</td>
                            <td>$<?php echo number_format($listing_fields['monthly_rent']) ?></td>
                        </tr>
                    <?php else : ?>
                        <tr>
                            <td class="bold">Monthly Rent:</td>
                            <td>Call for pricing</td>
                        </tr>
                    <?php endif; ?>
	                <?php if ( isset( $listing_fields['lease_type'] ) ) : ?>
                        <tr>
                            <td class="bold">Lease Type:</td>
                            <td><?php echo $listing_fields['lease_type'] ?></td>
                        </tr>
                    <?php endif; ?>
	                <?php if ( isset( $listing_fields['available'] ) ) :
		                $time = strtotime( $listing_fields['available'] );
		                $date = date( 'm/d/Y', $time ); ?>
                        <tr>
                            <td class="bold">Available:</td>
                            <td><?php echo $date ?></td>
                        </tr>
                    <?php endif; ?>
	                <?php if ( isset( $listing_fields['suite_floor'] ) ) : ?>
                        <tr>
                            <td class="bold">Suite/Floor:</td>
                            <td><?php echo $listing_fields['suite_floor'] ?></td>
                        </tr>
                    <?php endif; ?>
	                <?php if ( isset( $listing_fields['address'] ) ) : ?>
                        <tr>
                            <td class="bold">Address</td>
                            <td><?php echo $listing_fields['address'] ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <div class="listing-description">
            <?php if ($print_text) : ?>
                <p class="underline"><?php echo $print_text ?></p>
            <?php endif; ?>
            <p><?php echo get_the_content($listing_id) ?></p>
        </div>
	    <?php if ( isset( $feature_terms ) && is_array( $feature_terms ) ) : ?>
            <div class="listing-tags">
                <ul>
                    <?php foreach ($feature_terms as $term) : ?>
                        <li class="bold"><?php echo $term->name ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="listing-broker">
            <div class="details">
                <?php if ($broker_image) : ?>
                    <div class="image">
                        <?php echo wp_get_attachment_image($broker_image['id'], 'full'); ?>
                    </div>
                <?php endif; ?>
                <div class="text">
                    <?php if ($broker_name) : ?>
                        <h2><?php echo $broker_name ?></h2>
                    <?php endif; ?>
                    <?php if ($broker_rank) : ?>
                        <p><?php echo $broker_rank ?></p>
                    <?php endif; ?>
                    <?php if ($broker_contacts) : ?>
                        <ul>
                            <?php foreach ($broker_contacts as $item) : ?>
                                <li class="<?php echo ($item['bold']) ? 'bold' : '' ?>"><?php echo $item['text'] ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div id="metro-qrcode">
                <img src="" alt="">
            </div>
        </div>
    </section>
</main>

<?php get_footer('no-index') ?>
