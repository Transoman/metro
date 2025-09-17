<?php
// Hook to wp_login to save last login time to user meta
add_action( 'wp_login', 'update_last_login_timestamp', 10, 2 );

function update_last_login_timestamp( $user_login, $user ) {
	// Save the current timestamp in the user meta table
	update_user_meta( $user->ID, 'wfls_last_login', time() );
}
