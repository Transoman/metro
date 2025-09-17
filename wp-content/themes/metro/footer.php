<?php
$listings = get_field('footer_locations_list', 'option');
$social_links = get_field('footer_social_links', 'option');
$footer_text = get_field('footer_text', 'option');
$footer_address = get_field('global_footer_address', 'option');
$footer_address_links = get_field('global_address_links', 'option');
$footer_neighborhood_menu = wp_nav_menu(['theme_location' => 'footer_neighborhoods', 'container' => false, 'echo' => false, 'walker' => new ProfiDev_Walker_Nav_Menu()]);
$footer_listing_type_menu = wp_nav_menu(['theme_location' => 'footer_types', 'container' => false, 'echo' => false, 'walker' => new ProfiDev_Walker_Nav_Menu()]);
$footer_menu = wp_nav_menu(['theme_location' => 'footer_menu', 'container' => false, 'echo' => false, 'walker' => new ProfiDev_Walker_Nav_Menu()]);
$footer_small_menu = wp_nav_menu(['theme_location' => 'footer_small_menu', 'container' => false, 'echo' => false, 'walker' => new ProfiDev_Walker_Nav_Menu()]);
$simple_contant_form = get_field('shortcode_simple_contact_form', 'option');
global $wp;
?>
<div data-target="a11y" type="button" class="a11y">
    <button data-target="toggle" aria-label="Accessibility Button" class="hide-button" type="button"></button>
    <div class="inner">
        <p>Accessibility Tools</p>
        <ul>
            <li>
                <button class="hide-button" type="button" data-target="increase_size">
                    <span></span> Increase Text
                </button>
            </li>
            <li>
                <button class="hide-button" type="button" data-target="decrease_size">
                    <span></span> Decrease Text
                </button>
            </li>
            <li>
                <button class="hide-button" type="button" data-target="grayscale">
                    <span></span> Grayscale
                </button>
            </li>
            <li>
                <button class="hide-button" type="button" data-target="high_contrast">
                    <span></span> High Contrast
                </button>
            </li>
            <li>
                <button class="hide-button" type="button" data-target="negative_contrast">
                    <span></span> Negative Contrast
                </button>
            </li>
            <li>
                <button class="hide-button" type="button" data-target="light_background">
                    <span></span> Light Background
                </button>
            </li>
            <li>
                <button class="hide-button" type="button" data-target="links_underline">
                    <span></span> Links underline
                </button>
            </li>
            <li>
                <button class="hide-button" type="button" data-target="readable_font">
                    <span></span> Readable font
                </button>
            </li>
            <li>
                <button class="hide-button" type="button" data-target="reset">
                    <span></span> Reset
                </button>
            </li>
        </ul>
    </div>
</div>
<div data-target="overlay" class="mm-overlay">
    <?php if (!empty($simple_contant_form)): ?>
        <div data-target="simple_contact_form" class="popup simple-form">
            <div class="button">
                <button class="hide-button" aria-label="Close popup" data-target="close_popup" type="button">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M20.0001 1.32074L18.6793 0L10.0001 8.6792L1.32086 0L0 1.32074L8.67926 10L0 18.6793L1.32086 20L10.0001 11.3208L18.6793 20L20.0001 18.6793L11.3209 10L20.0001 1.32074Z"
                            fill="var(--mm-navy-color)" />
                    </svg>
                </button>
            </div>
            <div class="content">
                <div class="wrapper">
                    <div class="form">
                        <?php echo do_shortcode($simple_contant_form); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<footer class="front-footer">
    <div class="footer-part">
        <div class="container">
            <div class="content">
                <div class="menus">
                    <?php if ($footer_listing_type_menu): ?>
                        <nav>
                            <?php echo $footer_listing_type_menu ?>
                        </nav>
                    <?php endif; ?>
                    <?php if ($footer_neighborhood_menu): ?>
                        <nav>
                            <?php echo $footer_neighborhood_menu ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-part single-menu">
        <div class="container">
            <div class="content">
                <div class="menus">
                    <nav>
                        <?php echo $footer_menu ?>
                    </nav>
                    <div class="address">
                        <p class="title">Address & Contact</p>
                        <?php if ($footer_address): ?>
                            <p>
                                <?php echo $footer_address ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($footer_address_links): ?>
                            <?php foreach ($footer_address_links as $link): ?>
                                <a aria-label="<?php echo esc_attr($link['text']) ?>"
                                    class="<?php echo ($link['underline']) ? 'underline' : '' ?>"
                                    href="<?php echo esc_url($link['url']) ?>">
                                    <?php echo esc_html($link['text']) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-part last-part">
        <div class="container">
            <div class="content">
                <?php if ($social_links): ?>
                    <ul class="social-links">
                        <?php foreach ($social_links as $link):
                            $icon = $link['icon'];
                            $url = $link['link'];
                            ?>
                            <li>
                                <a aria-label="social media" href="<?php echo esc_url( $url ) ?>" target="_blank">
                                  <?php
                                    if ( str_contains( $url, 'x.com' ) ) {
                                      ?>
                                        <img width="20" height="17" style="width:20px;height:17px;"
                                             src="https://www.metro-manhattan.com/wp-content/uploads/2024/10/x-icon.svg"
                                             class="attachment-full size-full" alt="Twitter icon" loading="lazy"
                                             decoding="async">
                                      <?php
                                    } else {
                                      echo wp_get_attachment_image( $icon['id'], 'full', '', [ 'loading' => 'lazy' ] );
                                    }
                                  ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($footer_text): ?>
                    <p>
                        <?php echo esc_html($footer_text) ?>
                    </p>
                <?php endif; ?>
                <nav>
                    <?php echo $footer_small_menu ?>
                </nav>
            </div>
        </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        function resetSalesIQSession(email, name) {
          if (typeof $zoho !== 'undefined' && $zoho.salesiq) {
            $zoho.salesiq.visitor.email(email);
            $zoho.salesiq.visitor.name(name);
            console.log("SalesIQ visitor email set to:", email);
            console.log("SalesIQ visitor name set to:", name);
            sessionStorage.setItem("salesiq_data_set", "true");
          }
        }
        
        async function fetchUserData() {
          try {
            const response = await fetch('<?php echo admin_url( "admin-ajax.php" ); ?>?action=get_logged_in_user_data', {
              method: 'GET',
              credentials: 'same-origin',
            });
            const data = await response.json();
            if (data.success) {
              return data.data;
            } else {
              console.warn("User not logged in or data unavailable");
              return null;
            }
          } catch (error) {
            console.error("Error fetching user data:", error);
            return null;
          }
        }
        
        function waitForZoho(callback) {
          let attempts = 0;
          const interval = setInterval(() => {
            if (typeof $zoho !== 'undefined' && $zoho.salesiq) {
              clearInterval(interval);
              callback();
            } else if (attempts > 10) {
              clearInterval(interval);
              console.warn("Zoho SalesIQ is not available.");
            }
            attempts++;
          }, 600);
        }
        
        waitForZoho(async function () {
          if (sessionStorage.getItem("salesiq_data_set") === "true") {
            console.log("SalesIQ data already set. Skipping...");
            return;
          }
          
          const userData = await fetchUserData();
          if (userData) {
            resetSalesIQSession(userData.email, userData.name);
          }
        });
      });
    </script>
</footer>

<?php
$post_type = get_post_type();
if ( $post_type == 'listings' ) :
	?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('button.print-button').forEach(function (button) {
                button.addEventListener('click', function (e) {
                    const listingId = this.dataset.id;

                    const data = new FormData();
                    data.append('action', 'generate_print_template');
                    data.append('listing_id', listingId);

                    fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                        method: 'POST',
                        body: data,
                    })
                        .then(response => response.text())
                        .then(html => {
                            const baseUrl = window.location.origin;
                            const currentUrl = window.location.href;
                            const cssUrl = baseUrl + '/wp-content/themes/metro/dist/98645f988f0a31f0af75b7d5c4cfa390.css';
                            const qrcodeScriptUrl = baseUrl + '/wp-content/themes/metro/assets/js/qrcode.min.js';

                            const head = document.head;
                            const link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = cssUrl;
                            link.media = 'all';
                            link.fetchpriority = 'high';
                            head.appendChild(link);

                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');

                            const newHeader = doc.querySelector('header');
                            const oldHeader = document.querySelector('header');
                            if (oldHeader && newHeader) {
                                oldHeader.replaceWith(newHeader);
                            } else if (newHeader) {
                                document.body.insertBefore(newHeader, document.body.firstChild);
                            }

                            const oldFooter = document.querySelector('footer');
                            if (oldFooter) {
                                oldFooter.remove();
                            }

                            const newMain = doc.querySelector('main');
                            const oldMain = document.querySelector('main');
                            if (oldMain && newMain) {
                                oldMain.replaceWith(newMain);
                            } else if (newMain) {
                                document.body.appendChild(newMain);
                            }

                            document.body.classList.remove('front');
                            document.body.classList.add('no-index');

                            const a11yElement = document.querySelector('div[data-target="a11y"]');
                            if (a11yElement) {
                                a11yElement.remove();
                            }

                            const overlayElement = document.querySelector('div[data-target="overlay"]');
                            if (overlayElement) {
                                overlayElement.remove();
                            }

                            const script = document.createElement('script');
                            script.src = qrcodeScriptUrl;
                            script.onload = () => {
                                generateQRCode(currentUrl);
                            };
                            document.head.appendChild(script);
                        })
                        .catch(error => console.error('Error:', error));
                });
            });
        });

        function generateQRCode(url) {
            var qrcode = new QRCode(document.getElementById("metro-qrcode"), {
                text: url,
                width: 100,
                height: 100,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            qrcode.makeCode(url);
        }
    </script>
<?php endif; ?>

<?php wp_footer() ?>
<?php
// Check if origin is nemanjatanaskovic.com - we store uptime script there.
if ( ! isset( $_SERVER['HTTP_REFERER'] ) || strpos( $_SERVER['HTTP_REFERER'], 'nemanjatanaskovic.com' ) === false ) :
	?>
    <!-- Google Analytics load on scroll -->
    <script>
        function analyticsOnScroll() {
            var head = document.getElementsByTagName('head')[0]
            var script = document.createElement('script')
            script.type = 'text/javascript';
            script.src = 'https://www.googletagmanager.com/gtag/js?id=G-PWLRFG0LC6'
            head.appendChild(script);
            document.removeEventListener('scroll', analyticsOnScroll);
        }

        document.addEventListener('scroll', analyticsOnScroll);
    </script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());
        gtag('config', 'G-PWLRFG0LC6');
    </script>
<?php
endif;
?>

<!--<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>-->
<!--<script>-->
<!--    var swiper = new Swiper(".swiper", {-->
<!--        zoom: true,-->
<!--        spaceBetween: 20,-->
<!--        slidesPerView: 1.11,-->
<!--    });-->
<!--</script>-->
</body>

</html>
