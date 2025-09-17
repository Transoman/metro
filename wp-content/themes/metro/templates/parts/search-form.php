<?php
$filters = get_field('choose_filters', 'option');

if ($filters) : ?>
    <div class="form-wrapper">
        <div class="form">
            <form method="post" action="<?php echo $search_page ?>">
                <div class="heading">
                    <p>Search by</p>
                    <button aria-label="Clear all" data-target="clear_all" type="button" class="simple-button">Clear all</button>
                </div>
                <div class="content">
                    <?php while (have_rows('choose_filters', 'option')) : the_row(); ?>
                        <?php if (get_row_layout() == 'location') : ?>
                            <div data-name="uses" data-target="form_field" class="form-field">
                                <div class="placeholder">
                                    <span>All Uses</span>
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
                                                <span>Uses</span>
                                            </div>
                                        </div>
                                        <div class="wrapper-list">
                                            <div data-target="select_all" class="parent checkbox">
                                                <input type="checkbox" value="-1" id="all-uses">
                                                <label for="all-uses">Select all</label>
                                            </div>
                                            <?php foreach ($type_taxonomy as $type_term) : ?>
                                                <div data-target="checkbox" class="checkbox">
                                                    <input type="checkbox" name="filter[uses][<?php echo $type_term->term_id ?>]" value="<?php echo $type_term->name ?>" id="use[<?php echo $type_term->term_id ?>]">
                                                    <label for="use[<?php echo $type_term->term_id ?>]"><?php echo $type_term->name ?></label>
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
                        <?php elseif (get_row_layout() == 'listing_types') : ?>
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
                                            <?php foreach ($location_taxonomy as $location_term) : ?>
                                                <div data-target="checkbox" class="checkbox">
                                                    <input type="checkbox" name="filter[locations][<?php echo $location_term->term_id ?>]" value="<?php echo $location_term->name ?>" id="locations[<?php echo $location_term->term_id ?>]">
                                                    <label for="locations[<?php echo $location_term->term_id ?>]"><?php echo $location_term->name ?></label>
                                                </div>
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
                        <?php elseif (get_row_layout() == 'sizes') : ?>
                            <div data-name="sizes" data-target="form_field" data-range="true" class="form-field">
                                <div class="placeholder">
                                    <span>All Sizes</span>
                                    <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                    </svg>
                                </div>
                                <?php if (have_rows('enter_sizes')) : ?>
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
                                                while (have_rows('enter_sizes')) : the_row();
                                                    $text = get_sub_field('text');
                                                    $value = get_sub_field('value');
                                                    $type = get_sub_field('type');
                                                ?>
                                                    <div data-target="checkbox" class="checkbox">
                                                        <input type="checkbox" name="filter[sizes][<?php echo $i ?>]" value="<?php echo $type ?><?php echo $value ?>" id="size[<?php echo $i ?>]">
                                                        <label for="size[<?php echo $i ?>]"><?php echo $text ?></label>
                                                    </div>
                                                <?php
                                                    $i++;
                                                endwhile; ?>
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
                        <?php elseif (get_row_layout() == 'prices') : ?>
                            <div data-name="Max Rent/Month" data-target="form_field" data-single="true" class="form-field">
                                <div class="placeholder">
                                    <span>Max Rent/Month</span>
                                    <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                    </svg>
                                </div>
                                <?php if (have_rows('enter_prices')) : ?>
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
                                                while (have_rows('enter_prices')) : the_row();
                                                    $text = get_sub_field('text');
                                                    $value = get_sub_field('value');
                                                    $type = get_sub_field('type');
                                                ?>
                                                    <div data-target="checkbox" class="checkbox">
                                                        <input type="checkbox" name="filter[prices][<?php echo $i ?>]" value="<?php echo $type ?><?php echo $value ?>" id="price[<?php echo $i ?>]">
                                                        <label for="price[<?php echo $i ?>]"><?php echo $text ?></label>
                                                    </div>
                                                <?php
                                                    $i++;
                                                endwhile; ?>
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
                    <?php endwhile; ?>
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