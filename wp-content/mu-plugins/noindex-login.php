<?php
// wp-content/mu-plugins/noindex-login.php

if (
    false !== stripos( $_SERVER['REQUEST_URI'], '/wp-login.php' )
    && isset( $_GET['loginSocial'] )
) {
    // Tell every bot: “Don’t index or follow this URL”
    header( 'X-Robots-Tag: noindex, nofollow', true );
    // And point all these variants back to the clean login page
    header( 'Link: <https://www.metro-manhattan.com/wp-login.php>; rel="canonical"', false );
}
