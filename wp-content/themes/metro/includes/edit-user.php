<?php
  
  /**
   * Class MMEditUser
   *
   * Handles user profile editing and deletion through shortcodes and AJAX.
   */
  class MMEditUser {
    /**
     * MMEditUser constructor.
     *
     * Initializes the shortcode and AJAX actions for editing and deleting user accounts.
     */
    public function __construct() {
      add_shortcode( 'mm_edit_user_form', [ $this, 'edit_user_form' ] );
      add_action( 'wp_ajax_mm_edit_user', [ $this, 'edit_user' ] );
      add_action( 'wp_ajax_mm_delete_user', [ $this, 'delete_user' ] );
    }
    
    /**
     * Displays the user editing form.
     *
     * @return string HTML content of the form.
     */
    public static function edit_user_form() {
      if ( is_user_logged_in() ) {
        $output = self::edit_user_fields();
      } else {
        $output = __( 'Editing user is not available' );
      }
      
      return $output;
    }
    
    /**
     * Generates the HTML form fields for editing the user profile.
     *
     * @return string The form HTML.
     */
    public static function edit_user_fields() {
      ob_start();
      $current_user = wp_get_current_user();
      ?>
        <form method="post" data-target="edit_user_form" action="#">
            <input name="action" value="mm_edit_user" type="hidden">
            <input name="mm_edit_user_nonce" value="<?php echo wp_create_nonce( 'mm-edit-user-nonce' ) ?>"
                   type="hidden">
            <fieldset>
                <div class="title">
                    <p>Personal Details</p>
                </div>
                <div class="fields">
                    <div class="field">
                        <label for="first_name">First Name</label>
                        <input name="first_name" id="first_name"
                               value="<?php echo get_user_meta( $current_user->ID, 'first_name', true ); ?>"
                               placeholder="e.g. John"
                               type="text">
                    </div>
                    <div class="field">
                        <label for="last_name">Last Name</label>
                        <input name="last_name" id="last_name"
                               value="<?php echo get_user_meta( $current_user->ID, 'last_name', true ); ?>"
                               placeholder="e.g. Doe"
                               type="text">
                    </div>
                    <div class="field">
                        <label for="phone_number">Phone Number</label>
                        <input name="phone_number"
                               value="<?php echo get_user_meta( $current_user->ID, 'phone_number', true ); ?>"
                               id="phone_number" placeholder="e.g. +1 (212) 123-1234" type="text">
                    </div>
                    <div class="field">
                        <label for="company">Company</label>
                        <input name="company" id="company"
                               value="<?php echo get_user_meta( $current_user->ID, 'company', true ); ?>"
                               placeholder="e.g. MetroManhattan LLC"
                               type="text">
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <div class="title">
                    <p>Email Address</p>
                </div>
                <div class="fields">
                    <div class="field">
                        <input name="email" id="email" value="<?php echo $current_user->data->user_email ?>"
                               placeholder="lukeskywalker@gmail.com" type="text">
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <div class="title">
                    <p>Change Password</p>
                </div>
                <div class="fields">
                    <div class="field password">
                        <label for="old_password">Old Password</label>
                        <div data-target="pass_input" class="input">
                            <input name="old_password" id="old_password" type="password">
                            <button type="button">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2317_16690)">
                                        <path
                                                d="M17.94 17.94C16.2306 19.243 14.1491 19.9649 12 20C5 20 1 12 1 12C2.24389 9.68192 3.96914 7.65663 6.06 6.06003M9.9 4.24002C10.5883 4.0789 11.2931 3.99836 12 4.00003C19 4.00003 23 12 23 12C22.393 13.1356 21.6691 14.2048 20.84 15.19M14.12 14.12C13.8454 14.4148 13.5141 14.6512 13.1462 14.8151C12.7782 14.9791 12.3809 15.0673 11.9781 15.0744C11.5753 15.0815 11.1752 15.0074 10.8016 14.8565C10.4281 14.7056 10.0887 14.4811 9.80385 14.1962C9.51897 13.9113 9.29439 13.572 9.14351 13.1984C8.99262 12.8249 8.91853 12.4247 8.92563 12.0219C8.93274 11.6191 9.02091 11.2219 9.18488 10.8539C9.34884 10.4859 9.58525 10.1547 9.88 9.88003"
                                                stroke="#0961AF" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round"/>
                                        <path d="M1 1L23 23" stroke="#0961AF" stroke-width="2" stroke-linecap="round"
                                              stroke-linejoin="round"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2317_16690">
                                            <rect width="24" height="24" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="field password">
                        <label for="new_password">New Password</label>
                        <div data-target="pass_input" class="input">
                            <input name="new_password" id="new_password" type="password">
                            <button type="button">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2317_16690)">
                                        <path
                                                d="M17.94 17.94C16.2306 19.243 14.1491 19.9649 12 20C5 20 1 12 1 12C2.24389 9.68192 3.96914 7.65663 6.06 6.06003M9.9 4.24002C10.5883 4.0789 11.2931 3.99836 12 4.00003C19 4.00003 23 12 23 12C22.393 13.1356 21.6691 14.2048 20.84 15.19M14.12 14.12C13.8454 14.4148 13.5141 14.6512 13.1462 14.8151C12.7782 14.9791 12.3809 15.0673 11.9781 15.0744C11.5753 15.0815 11.1752 15.0074 10.8016 14.8565C10.4281 14.7056 10.0887 14.4811 9.80385 14.1962C9.51897 13.9113 9.29439 13.572 9.14351 13.1984C8.99262 12.8249 8.91853 12.4247 8.92563 12.0219C8.93274 11.6191 9.02091 11.2219 9.18488 10.8539C9.34884 10.4859 9.58525 10.1547 9.88 9.88003"
                                                stroke="#0961AF" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round"/>
                                        <path d="M1 1L23 23" stroke="#0961AF" stroke-width="2" stroke-linecap="round"
                                              stroke-linejoin="round"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2317_16690">
                                            <rect width="24" height="24" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </fieldset>
          <?php if ( ! current_user_can( 'administrator' ) ): ?>
              <fieldset>
                  <div class="title">
                      <p>Delete Account</p>
                  </div>
                  <div class="fields">
                      <button data-target="delete_account" class="delete-button secondary-button" type="button">Delete
                          Account
                      </button>
                      <div data-target="confirmation" class="confirmation hide">
                          <p>Are you sure?</p>
                          <div class="buttons">
                              <button class="default secondary-button" data-target="cancel" type="button">No</button>
                              <button type="button" class="submit primary-button"
                                      data-user-id="<?php echo $current_user->ID ?>"
                                      data-target="confirm">Yes
                              </button>
                          </div>
                      </div>
                  </div>
              </fieldset>
          <?php endif; ?>
            <div class="buttons">
                <button data-target="cancel" class="default secondary-button" type="button"
                        onclick="window.location.href='<?php echo home_url(); ?>'">Cancel
                </button>
                <button type="submit" class="submit primary-button">Save Changes</button>
            </div>
            <div data-target="message" class="message">
                <span></span>
            </div>
        </form>
      <?php
      return ob_get_clean();
    }
    
    /**
     * Handles the AJAX request for editing the user profile.
     *
     * Verifies the nonce, validates the input, and updates the user profile if valid.
     */
    public function edit_user() {
      if ( $_POST['mm_edit_user_nonce'] && wp_verify_nonce( $_POST['mm_edit_user_nonce'], 'mm-edit-user-nonce' ) ) {
        $current_user = wp_get_current_user();
        // Retrieve and sanitize user inputs
        $user_email        = $_POST['email'];
        $user_first_name   = $_POST['first_name'];
        $user_last_name    = $_POST['last_name'];
        $user_phone_number = $_POST['phone_number'];
        $user_old_password = $_POST['old_password'];
        $user_new_password = $_POST['new_password'];
        $user_company      = $_POST['company'];
        
        // Validation and error handling
        $response            = [];
        $response['invalid'] = [];
        
        // Phone number validation
        if ( ! self::is_phone_valid( $user_phone_number ) && $user_phone_number != '' ) {
          self::errors()->add( 'phone_invalid', __( 'Invalid phone number' ) );
          array_push( $response['invalid'], [ 'field' => 'phone_number', 'id' => 'phone_invalid' ] );
        }
        
        // Only validate passwords if old password is provided (i.e., the user wants to change it)
        if ( ! empty( $user_old_password ) || ! empty( $user_new_password ) ) {
          // Old password validation
          if ( ! self::is_old_password_valid( $user_old_password, $current_user ) ) {
            self::errors()->add( 'old_password_invalid', __( 'Enter correct old password' ) );
            array_push( $response['invalid'], [ 'field' => 'old_password', 'id' => 'old_password_invalid' ] );
          }
          
          // New password validation
          if ( ! self::is_password_valid( $user_new_password ) ) {
            self::errors()->add( 'new_password_invalid', __( 'The password doesn’t look right. Please use at least 8 characters: a mix of letters and numbers.' ) );
            array_push( $response['invalid'], [ 'field' => 'new_password', 'id' => 'new_password_invalid' ] );
          }
        }
        
        // Email validation
        if ( empty( $user_email ) ) {
          self::errors()->add( 'email_empty', __( 'Email is required' ) );
          array_push( $response['invalid'], [ 'field' => 'email', 'id' => 'email_empty' ] );
        }
        if ( email_exists( $user_email ) && $user_email !== $current_user->user_email ) {
          self::errors()->add( 'email_used', __( 'Email already registered' ) );
          array_push( $response['invalid'], [ 'field' => 'email', 'id' => 'email_used' ] );
        }
        if ( ! is_email( $user_email ) && $user_email !== '' ) {
          self::errors()->add( 'email_invalid', __( 'Invalid email' ) );
          array_push( $response['invalid'], [ 'field' => 'email', 'id' => 'email_invalid' ] );
        }
        
        // Error handling
        $errors = self::errors()->get_error_messages();
        
        if ( empty( $errors ) ) {
          // Update user profile
          $args = [
            'ID'           => $current_user->ID,
            'first_name'   => $user_first_name,
            'last_name'    => $user_last_name,
            'phone_number' => $user_phone_number
          ];
          
          // Update email
          if ( ! empty( $user_email ) ) {
            $args['user_email'] = $user_email;
          }
          
          // Update password if provided
          if ( ! empty( $user_new_password ) ) {
            $args['user_pass'] = $user_new_password;
          }
          
          // Update company
          if ( ! empty( $user_company ) ) {
            update_user_meta( $current_user->ID, 'company', $user_company );
          }
          
          wp_update_user( $args );
          $response['success'] = true;
        } else {
          $response['invalid']  = array_values( $response['invalid'] );
          $response['messages'] = self::errors_message();
        }
        
        echo json_encode( $response );
        
        wp_die();
      }
    }
    
    /**
     * Handles the AJAX request for soft-deleting a user account.
     */
    public function delete_user() {
      $user_id  = $_POST['user_id'];
      $response = [];
      
      $user = get_userdata( $user_id );
      
      if ( $user ) {
        // Generate the new email with "-DELETED" suffix
        $deleted_email = $user->user_email . '-DELETED';
        
        // Ensure email uniqueness
        while ( email_exists( $deleted_email ) ) {
          $deleted_email .= rand( 10, 99 );
        }
        
        $args = [
          'ID'           => $user_id,
          'user_email'   => $deleted_email,
        ];
        
        // Update the user email and display name
        $update_status = wp_update_user( $args );
        
        if ( is_wp_error( $update_status ) ) {
          error_log( 'Failed to soft-delete user: ' . $user->user_email );
          $response['message'] = 'Something went wrong';
        } else {
          // Remove all user roles to prevent login
          wp_update_user( [ 'ID' => $user_id, 'role' => '' ] );
          
          error_log( 'User soft-deleted successfully: ' . $user->user_email );
          $response['redirect_to'] = get_home_url();
        }
      } else {
        error_log( 'Attempted to delete a non-existent user. User ID: ' . $user_id );
        $response['message'] = 'User does not exist.';
      }
      
      echo json_encode( $response );
      wp_die();
    }
    
    
    /**
     * Retrieves the WP_Error object for storing validation errors.
     *
     * @return WP_Error The WP_Error instance.
     */
    private static function errors() {
      static $wp_error;
      
      return $wp_error ?? ( $wp_error = new WP_Error( null, null, null ) );
    }
    
    /**
     * Compiles and returns the error messages.
     *
     * @return array The error messages.
     */
    private static function errors_message() {
      if ( $codes = self::errors()->get_error_codes() ) {
        $output = [];
        foreach ( $codes as $code ) {
          $message  = self::errors()->get_error_message( $code );
          $output[] = [ 'id' => $code, 'html' => '<span class="error">' . $message . '</span>' ];
        }
        
        return $output;
      }
    }
    
    /**
     * Validates a phone number.
     *
     * @param string $phone_number The phone number to validate.
     *
     * @return bool Whether the phone number is valid.
     */
    private static function is_phone_valid( $phone_number ) {
      if ( preg_match( '/^(\+\d{1,2}\s?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}$/', $phone_number ) ) {
        $count        = 1;
        $phone_number = str_replace( [ '+' ], '', $phone_number, $count );
        
        $phone_number = str_replace( [ ' ', '.', '-', '(', ')' ], '', $phone_number );
        
        return self::is_digits_only( $phone_number );
      }
    }
    
    
    /**
     * Checks if a string contains only digits.
     *
     * @param string $s The string to check.
     * @param int $minDigits The minimum number of digits.
     * @param int $maxDigits The maximum number of digits.
     *
     * @return bool Whether the string contains only digits.
     */
    private static function is_digits_only( string $s, int $minDigits = 9, int $maxDigits = 14 ) {
      return preg_match( '/^[0-9]{' . $minDigits . ',' . $maxDigits . '}\z/', $s );
    }
    
    /**
     * Normalizes a phone number by removing non-digit characters.
     *
     * @param string $phone_number The phone number to normalize.
     *
     * @return string The normalized phone number.
     */
    private static function normalize_phone_number( $phone_number ) {
      $phone_number = str_replace( [ ' ', '.', '-', '(', ')' ], '', $phone_number );
      
      return $phone_number;
    }
    
    /**
     * Validates the old password.
     *
     * @param string $password The old password.
     * @param WP_User $user The current user object.
     *
     * @return bool Whether the password is valid.
     */
    private static function is_old_password_valid( $password, $user ) {
      return wp_check_password( $password, $user->data->user_pass, $user->ID );
    }
    
    /**
     * Validates a new password based on complexity requirements.
     *
     * @param string $password The new password.
     *
     * @return bool Whether the password meets the complexity requirements.
     */
    private static function is_password_valid( $password ): bool {
      $condition = preg_match( '/^(?=.*[a-zA-Z])(?=.*[0-9]).{8,}$/', $password );
      
      return ( strlen( $password ) > 8 && $condition );
    }
  }
  
  $instance = new MMEditUser();
