<?php
$class_name  = 'front';
$search_page = get_field( 'choose_search_page', 'option' );
$class_name  = ( is_page( $search_page ) ) ? $class_name . ' search-page' : $class_name;

$spam_keywords    = get_field( 'spam_keywords', 'option' );
$spam_keywords_js = [];
if ( $spam_keywords ) {
    foreach ( $spam_keywords as $keyword_entry ) {
      if ( isset( $keyword_entry['keyword'] ) ) {
        $spam_keywords_js[] = $keyword_entry['keyword'];
      }
    }
}
?>
<!doctype html>
<html lang="<?php echo get_bloginfo( 'language' ) ?>">

<head>
    <meta charset="<?php echo get_bloginfo( 'charset' ) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	<?php wp_head(); ?>
	<?php if ( ! isset( $_SERVER['HTTP_REFERER'] ) || strpos( $_SERVER['HTTP_REFERER'], 'nemanjatanaskovic.com' ) === false ) : ?>
		<?php if ( profidev_env( "SITE_ENV", "production" ) === "production" && str_contains( $_SERVER['HTTP_HOST'], 'metro-manhattan.com' ) ) : ?>
          <meta name="facebook-domain-verification" content="i0z7eh1zu8ilsrf1ztib89gkjmakq4"/>
          <!-- Google Tag Manager -->
          <script>(function (w, d, s, l, i) {
                  w[l] = w[l] || [];
                  w[l].push({
                      'gtm.start':
                          new Date().getTime(), event: 'gtm.js'
                  });
                  var f = d.getElementsByTagName(s)[0],
                      j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
                  j.async = true;
                  j.src =
                      'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                  f.parentNode.insertBefore(j, f);
              })(window, document, 'script', 'dataLayer', 'GTM-NDXBHH');</script>
          <!-- End Google Tag Manager -->
		<?php endif; ?>
	<?php endif; ?>
	<script>
      const spamKeywords = <?php echo json_encode( $spam_keywords_js ); ?>;
      
      document.addEventListener('wpcf7submit', function (event) {
        const inputs = event.detail.inputs;
        const containsSpam = inputs.some(input => {
          return spamKeywords.some(keyword => input.value.toLowerCase().includes(keyword.toLowerCase()));
        });
        
        if (containsSpam) {
          window.dataLayer = window.dataLayer || [];
          window.dataLayer.push({
            skipTracking: true,
            event: 'skipTrackingTrigger'
          });
          console.log("GA4/GTM tracking skipped due to spam keyword.");
        } else {
          console.log("GA4/GTM tracking allowed.");
        }
      });
    </script>
    <style>
        .single-post .post-content picture.alignleft {
            float: left;
            margin: 20px 20px 20px auto;
        }
        
        .single-post .post-content picture img:not(.youtube-thumbnail,[alt=loader]) {
            margin-top: 0;
            margin-bottom: 0;
        }
    </style>
</head>
<body <?php body_class( $class_name ); ?>>
<?php if ( ! isset( $_SERVER['HTTP_REFERER'] ) || strpos( $_SERVER['HTTP_REFERER'], 'nemanjatanaskovic.com' ) === false ) : ?>
	<?php if ( profidev_env( "SITE_ENV", "production" ) === "production" && str_contains( $_SERVER['HTTP_HOST'], 'metro-manhattan.com' ) ) : ?>
        <!-- Google Tag Manager (noscript) -->
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NDXBHH"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
        <!-- End Google Tag Manager (noscript) -->
	<?php endif; ?>
<?php endif; ?>
<header class="front-header <?php echo ( is_front_page() ) ? 'front-page' : '' ?>">
    <div class="main-header">
        <div class="header-container">
            <div class="header-wrapper">
                <div class="header-part">
                    <div class="header-logo">
											<?php
											$header_logo = get_field( 'header_logo', 'option' );
											if ( $header_logo ) : ?>
                          <a rel="home" aria-current="page" href="<?php echo get_bloginfo( 'url' ); ?>/">
														<?php echo wp_get_attachment_image( $header_logo['id'], 'full', '', [ 'loading' => 'lazy' ] ) ?>
                          </a>
											<?php endif; ?>
                    </div>
                </div>
                <div class="header-part">
                    <div data-target="header-menu" class="header-menu">
                        <nav>
                            <button aria-label="Main menu" data-target="back-menu" class="back-button">
                                    <span class="icon">
                                        <svg width="20" height="12" viewBox="0 0 20 12" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20 4.95812H2.56031L6.57268 0.945755L5.62692 0L0 5.62692L5.62692 11.2538L6.57268 10.3081L2.56023 6.29564H20V4.95812Z"
                                                  fill="var(--mm-navy-color)"/>
                                        </svg>

                                    </span>
                                <span>Main menu</span>
                            </button>
													<?php
													wp_nav_menu( [
														'theme_location' => 'header',
														'container'      => false,
														'walker'         => new ProfiDev_Walker_Nav_Menu()
													] ); ?>
                        </nav>
                        <div class="header-login">
                            <?php if ( ! is_user_logged_in() ) : ?>
                               <a href="<?php echo home_url( '/login/' ); ?>" class="login-btn" aria-label="Sign up">
                                    <svg width="16" height="20" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.8054 13.6523L10.837 11.6801L11.9561 9.58283C12.217 9.09307 12.3539 8.54437 12.3543 7.98671V4.66667C12.3543 3.42899 11.8733 2.242 11.0172 1.36683C10.161 0.491665 8.99983 0 7.78906 0C6.57829 0 5.41711 0.491665 4.56097 1.36683C3.70483 2.242 3.22385 3.42899 3.22385 4.66667V7.98671C3.22425 8.54438 3.36113 9.09309 3.622 9.58288L4.74109 11.6801L1.77276 13.6523C1.31657 13.9542 0.941722 14.3682 0.682521 14.8565C0.42332 15.3447 0.288043 15.8917 0.289068 16.4472V20H15.2891V16.4472C15.2901 15.8917 15.1548 15.3447 14.8956 14.8565C14.6364 14.3682 14.2616 13.9542 13.8054 13.6523ZM13.9847 18.6667H1.59342V16.4472C1.59281 16.1139 1.67398 15.7857 1.82951 15.4927C1.98503 15.1998 2.20995 14.9514 2.48367 14.7703L6.46438 12.1253L4.76709 8.94442C4.61057 8.65054 4.52845 8.32132 4.5282 7.98671V4.66667C4.5282 3.78261 4.87175 2.93477 5.48328 2.30964C6.09481 1.68452 6.92423 1.33333 7.78906 1.33333C8.6539 1.33333 9.48331 1.68452 10.0948 2.30964C10.7064 2.93477 11.0499 3.78261 11.0499 4.66667V7.98671C11.0497 8.32132 10.9676 8.65054 10.811 8.94442L9.11379 12.1253L13.0946 14.7703C13.3683 14.9514 13.5932 15.1998 13.7487 15.4928C13.9042 15.7857 13.9853 16.1139 13.9847 16.4472V18.6667Z" fill="#023A6C" />
                                    </svg>
                                    <span>Log In / Sign Up</span>
                                </a>
                            <?php else : ?>
                              <button aria-label="My Account" type="button" class="authorized login-btn"
                                      data-target="authorized_button">
                                  <span>My Account</span>
                                  <svg width="14" height="13" viewBox="0 0 14 13" fill="none"
                                       xmlns="http://www.w3.org/2000/svg">
                                      <path d="M1 3.14062L7 9.14062L13 3.14062" stroke="var(--mm-blue-color)"
                                            stroke-width="2"/>
                                  </svg>
                              </button>
                              <div data-target="authorized_menu" class="authorized-menu">
																<?php wp_nav_menu( [
																	'theme_location' => 'header_authorized',
																	'container'      => false,
																	'walker'         => new ProfiDev_Walker_Nav_Menu()
																] ) ?>
                              </div>
													<?php endif; ?>
                        </div>
											<?php
											$phone_button  = get_field( 'phone_button', 'option' );
											if ( $phone_button ) :
												$link_url = $phone_button['url'];
												$link_title  = $phone_button['title'];
												$link_target = $phone_button['target'] ? $phone_button['target'] : '_self'; ?>
                          <div class="header-button">
                              <a aria-label="<?php echo esc_attr( $link_url ) ?>"
                                 href="<?php echo esc_url( $link_url ); ?>"
                                 target="<?php echo esc_attr( $link_target ); ?>">
                                        <span class="icon">
                                            <svg width="24" height="25" viewBox="0 0 24 25" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path d="M23.3138 3.1736L23.2741 3.14883L18.2623 0.679688L12.8528 7.89229L15.3416 11.2109C15.2671 12.4624 14.7364 13.6431 13.8499 14.5297C12.9633 15.4162 11.7826 15.9469 10.531 16.0213L7.21255 13.5326L0 18.9419L2.44859 23.9119L2.4692 23.9538L2.49403 23.9934C2.6247 24.204 2.80708 24.3776 3.02381 24.4977C3.24055 24.6178 3.48442 24.6805 3.73222 24.6797H5.02545C7.51722 24.6797 9.9846 24.1889 12.2867 23.2353C14.5888 22.2818 16.6805 20.8841 18.4425 19.1221C20.2044 17.3602 21.6021 15.2684 22.5557 12.9663C23.5092 10.6642 24 8.19685 24 5.70508V4.41179C24.0008 4.164 23.9381 3.92013 23.818 3.70339C23.6979 3.48666 23.5243 3.30427 23.3138 3.1736ZM22.1479 5.70508C22.1479 15.1465 14.4668 22.8276 5.02545 22.8276H3.97901L2.34071 19.5017L7.21289 15.8476L9.92547 17.8819H10.2341C12.0817 17.8799 13.8529 17.145 15.1593 15.8386C16.4658 14.5322 17.2006 12.761 17.2027 10.9134V10.6048L15.1683 7.89223L18.822 3.02034L22.1479 4.65893V5.70508Z"
                                                      fill="white"/>
                                            </svg>
                                        </span>
                                  <span><?php echo esc_html( $link_title ); ?></span>
                              </a>
                          </div>
											<?php endif; ?>
                        <div class="mobile-buttons">
                            <button aria-label="Search Listings" data-target="show_search_bar" type="button">
                                    <span class="icon">
                                        <svg width="20" height="22" viewBox="0 0 20 22" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path d="M7.82604 0.623047C3.51085 0.623047 0 4.26638 0 8.74441C0 13.2224 3.51085 16.8658 7.82604 16.8658C9.62134 16.8658 11.2722 16.2284 12.5942 15.1694L18.282 21.0719C18.675 21.4797 19.3122 21.4797 19.7052 21.0719C20.0983 20.664 20.0983 20.0028 19.7052 19.5949L14.0174 13.6925C15.0379 12.3207 15.6521 10.6075 15.6521 8.74441C15.6521 4.26638 12.1412 0.623047 7.82604 0.623047ZM7.82604 2.42779C11.1821 2.42779 13.913 5.2617 13.913 8.74441C13.913 12.2271 11.1821 15.061 7.82604 15.061C4.46997 15.061 1.73912 12.2271 1.73912 8.74441C1.73912 5.2617 4.46997 2.42779 7.82604 2.42779Z"
                                                  fill="#023A6C"/>
                                        </svg>
                                    </span>
                                <span>
                                        Search Listings
                                    </span>
                            </button>
													<?php if ( $phone_button ) : ?>
                              <a aria-label="Call us" href="<?php echo esc_url( $link_url ); ?>"
                                 target="<?php echo esc_attr( $link_target ); ?>">
                                        <span class="icon">
                                            <svg width="24" height="25" viewBox="0 0 24 25" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path d="M23.3138 3.1736L23.2741 3.14883L18.2623 0.679688L12.8528 7.89229L15.3416 11.2109C15.2671 12.4624 14.7364 13.6431 13.8499 14.5297C12.9633 15.4162 11.7826 15.9469 10.531 16.0213L7.21255 13.5326L0 18.9419L2.44859 23.9119L2.4692 23.9538L2.49403 23.9934C2.6247 24.204 2.80708 24.3776 3.02381 24.4977C3.24055 24.6178 3.48442 24.6805 3.73222 24.6797H5.02545C7.51722 24.6797 9.9846 24.1889 12.2867 23.2353C14.5888 22.2818 16.6805 20.8841 18.4425 19.1221C20.2044 17.3602 21.6021 15.2684 22.5557 12.9663C23.5092 10.6642 24 8.19685 24 5.70508V4.41179C24.0008 4.164 23.9381 3.92013 23.818 3.70339C23.6979 3.48666 23.5243 3.30427 23.3138 3.1736ZM22.1479 5.70508C22.1479 15.1465 14.4668 22.8276 5.02545 22.8276H3.97901L2.34071 19.5017L7.21289 15.8476L9.92547 17.8819H10.2341C12.0817 17.8799 13.8529 17.145 15.1593 15.8386C16.4658 14.5322 17.2006 12.761 17.2027 10.9134V10.6048L15.1683 7.89223L18.822 3.02034L22.1479 4.65893V5.70508Z"
                                                      fill="white"/>
                                            </svg>
                                        </span>
                                  <span>Call us</span>
                              </a>
													<?php endif; ?>
                        </div>
                    </div>
									<?php
									if ( $phone_button ) :
										$link_url = $phone_button['url'];
										$link_title = $phone_button['title'];
										$link_target = $phone_button['target'] ? $phone_button['target'] : '_self'; ?>
                      <div class="header-button mobile-button">
                          <a aria-label="<?php echo esc_attr( $link_url ) ?>" href="<?php echo esc_url( $link_url ); ?>"
                             target="<?php echo esc_attr( $link_target ); ?>">
                                    <span class="icon">
                                        <svg width="24" height="25" viewBox="0 0 24 25" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path d="M23.3138 3.1736L23.2741 3.14883L18.2623 0.679688L12.8528 7.89229L15.3416 11.2109C15.2671 12.4624 14.7364 13.6431 13.8499 14.5297C12.9633 15.4162 11.7826 15.9469 10.531 16.0213L7.21255 13.5326L0 18.9419L2.44859 23.9119L2.4692 23.9538L2.49403 23.9934C2.6247 24.204 2.80708 24.3776 3.02381 24.4977C3.24055 24.6178 3.48442 24.6805 3.73222 24.6797H5.02545C7.51722 24.6797 9.9846 24.1889 12.2867 23.2353C14.5888 22.2818 16.6805 20.8841 18.4425 19.1221C20.2044 17.3602 21.6021 15.2684 22.5557 12.9663C23.5092 10.6642 24 8.19685 24 5.70508V4.41179C24.0008 4.164 23.9381 3.92013 23.818 3.70339C23.6979 3.48666 23.5243 3.30427 23.3138 3.1736ZM22.1479 5.70508C22.1479 15.1465 14.4668 22.8276 5.02545 22.8276H3.97901L2.34071 19.5017L7.21289 15.8476L9.92547 17.8819H10.2341C12.0817 17.8799 13.8529 17.145 15.1593 15.8386C16.4658 14.5322 17.2006 12.761 17.2027 10.9134V10.6048L15.1683 7.89223L18.822 3.02034L22.1479 4.65893V5.70508Z"
                                                  fill="white"/>
                                        </svg>
                                    </span>
                          </a>
                      </div>
									<?php endif; ?>
                    <div data-target="burger" class="header-burger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
