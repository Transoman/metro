<?php
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Custom WP-CLI command to add 'timestamp_register' meta for users.
	 */
	class UserTimestampCommand {

		/**
		 * Adds 'timestamp_register' to all users who don't have it yet.
		 *
		 * ## EXAMPLES
		 *
		 *     wp user add-timestamp-meta
		 *
		 * @when after_wp_load
		 */
		public function add_timestamp_meta() {
			global $wpdb;

			// Set your table names
			$users_table    = $wpdb->prefix . 'users';
			$usermeta_table = $wpdb->prefix . 'usermeta';

			// Get all users who don't have the 'timestamp_register' meta_key
			$users_without_timestamp = $wpdb->get_results( "
                SELECT u.ID, u.user_registered
                FROM $users_table u
                LEFT JOIN $usermeta_table um
                ON u.ID = um.user_id AND um.meta_key = 'timestamp_register'
                WHERE um.user_id IS NULL
            " );

			if ( ! empty( $users_without_timestamp ) ) {
				foreach ( $users_without_timestamp as $user ) {
					// Convert 'user_registered' to a Unix timestamp
					$timestamp_register = strtotime( $user->user_registered );

					// Insert the 'timestamp_register' meta key for the user
					$inserted = add_user_meta( $user->ID, 'timestamp_register', $timestamp_register, true );

					if ( $inserted ) {
						WP_CLI::success( "Added 'timestamp_register' for user ID {$user->ID}." );
					} else {
						WP_CLI::error( "Failed to add 'timestamp_register' for user ID {$user->ID}." );
					}
				}
			} else {
				WP_CLI::success( "All users already have 'timestamp_register'." );
			}
		}
	}

	// Register the CLI command
	WP_CLI::add_command( 'user', 'UserTimestampCommand' );
}
