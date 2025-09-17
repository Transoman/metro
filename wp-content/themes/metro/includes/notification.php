<?php

/**
 * MMNotification class
 *
 * This class handles the management of notifications, including setting and clearing them.
 */
class MMNotification {
	public static $show = false;
	public static $params = [];

	public function __construct() {
		// Register AJAX actions for clearing notifications
		add_action( 'wp_ajax_nopriv_clear_notification', [ $this, 'clear_notification' ] );
		add_action( 'wp_ajax_clear_notification', [ $this, 'clear_notification' ] );
	}

	/**
	 * Sets the notification display status and parameters.
	 *
	 * @param bool $show Whether to show the notification.
	 * @param array $params Additional parameters for the notification.
	 */
	public static function set_notification( $show, $params = [] ) {
		self::$show   = $show;
		self::$params = $params;
	}

	/**
	 * Clears the notification stored in the session.
	 */
	public function clear_notification() {
		if ( array_key_exists( 'mm_notification', $_SESSION ) ) {
			unset( $_SESSION['mm_notification'] );
			wp_send_json_success( 'Notification cleared successfully.' );
		} else {
			wp_send_json_error( 'No notification found.' );
		}
	}

}

new MMNotification();
