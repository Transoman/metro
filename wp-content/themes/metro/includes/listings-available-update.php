<?php

/**
 * Listings Available Update Utility
 *
 * This class provides an admin interface to update the "Available" date for listings in bulk. The admin can replace
 * old "Available" dates with a new date for listings that have a date older than one week from the current date.
 */
class ListingsAvailableUpdate
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_option_page']);
        add_action('admin_enqueue_scripts', [$this, 'register_scripts']);
        add_action('wp_ajax_replace_available_dates', [$this, 'replace_available_dates']);
    }

	/**
	 * Registers the scripts and styles needed for the admin page.
	 */
    public function register_scripts()
    {
        wp_register_script('listings-available', get_theme_file_uri('assets/js/listings-available.js'), [], null, true);
        wp_enqueue_script('listings-available');
        wp_register_style('listings-available', get_theme_file_uri('assets/css/listings-available.scss'), [], null);
        wp_enqueue_style('listings-available');
    }

	/**
	 * Registers the option page under the "Settings" menu in the WordPress admin.
	 */
    public function register_option_page()
    {
        add_submenu_page(
            'options-general.php',
            'Listings Available Update',
            'Listings Available Update',
            'manage_options',
            'listings-available-update',
            [$this, 'available_listings_template']
        );
    }

	/**
	 * Renders the admin interface for updating available dates.
	 */
    public function available_listings_template()
    {
        $listing_to_update = $this->get_listings_to_update();
?>
        <div id="availability_updated" class="updated settings-error notice is-dismissible" style="display: none;">
            <p></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
        <h1>Listings Available Update</h1>
        <p>Enter the new "Available" date to be replaced on listings with dates previous
            to <?php echo date("m/d/Y", strtotime("+1 week")); ?> and click "Update".</p>
        <input id="new-available-date" type="date" />
        <br><br>
        <input id="replace-dates" class="button-primary" type="button" value="Update Available Dates" disabled="disabled" />
        <br><br>
        <div id="to-update-section">
            <table id="to-update-list">
                <thead>
                    <tr>
                        <td><input id="select-all" type="checkbox" /> Select All</td>
                        <td>Listing #</td>
                        <td>Available</td>
                        <td>Title</td>
                        <td>S.F.</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $ix = 1;
                    foreach ($listing_to_update as $row) :
                        if ($row["ID"]) : ?>
                            <tr class="<?php echo ($ix % 2 == 0) ? 'even' : 'odd' ?>">
                                <td><input id="<?php echo $row['meta_id']; ?>" type="checkbox" class="meta_ids" name="meta_ids" value="<?php echo $row['meta_id']; ?>" /></td>
                                <td><?php echo $row['ID']; ?></td>
                                <td><?php echo $row['available']; ?></td>
                                <td><?php echo $row['post_title']; ?> - <a href="<?php echo $row['guid']; ?>" target="_blank">view
                                        -></a></td>
                                <td><?php echo $row['meta_value']; ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php $ix += 1;
                    endforeach; ?>
                </tbody>
            </table>
        </div>
<?php
    }

	/**
	 * Retrieves the listings that need their available dates updated.
	 *
	 * @return array The listings that require updating.
	 */
    private function get_listings_to_update()
    {
        global $wpdb;
        if (isset($_REQUEST['order_by'])) {
            $order_by = str_replace('+', ' ', $_REQUEST['order_by']);
        } else {
            $order_by = 'm.meta_value DESC';
        }
        $results = $wpdb->get_results("SELECT m.meta_id, m.post_id, m.meta_value AS available, p.ID, p.post_title, p.guid,
        m2.meta_value FROM $wpdb->postmeta m
        LEFT JOIN (SELECT * FROM $wpdb->posts WHERE post_type='listings' ) AS p ON p.ID=m.post_id 
        LEFT JOIN (SELECT * FROM $wpdb->postmeta WHERE meta_key='square_feet' ) AS m2 ON m2.post_id=m.post_id 
        WHERE m.meta_key='available' 
        ORDER BY " . $order_by . ";", ARRAY_A);
        $max_date = date("m/d/Y", strtotime("+1 week"));
        $values_to_replace = array();
        foreach ($results as $value) {
            $val_date = str_replace('-', '/', $value['available']);
            if (strtotime($max_date) >= strtotime($val_date)) {
                $values_to_replace[] = $value;
            }
        }
        return $values_to_replace;
    }

	/**
	 * Handles the AJAX request to replace available dates for selected listings.
	 */
    public function replace_available_dates()
    {
        global $wpdb;
        $new_date = $_POST['date'];
        $new_date = date("m-d-Y", strtotime($new_date));
        $ids = $_POST['ids'];
        $update_query = "UPDATE $wpdb->postmeta SET meta_value = '" . $new_date . "' WHERE meta_id IN (" . $ids . ")";
        $result = $wpdb->query($update_query);
        echo $result;

        wp_die();
    }
}

new ListingsAvailableUpdate();

