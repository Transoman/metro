<div class="wrapper">
    <form data-target="authorization_form" action="#">
        <input name="mm_authorization_nonce" value="<?php echo wp_create_nonce('mm-authorization-nonce') ?>"
            type="hidden">
        <input type="hidden" name="mm_authorization_redirect_to"
            value="<?php echo home_url(add_query_arg(array(), $wp->request)); ?>">
        <input name="action" value="mm_authorization_user" type="hidden">
        <div data-input="email" class="input">
            <input placeholder="Email" data-field="email" autocomplete="email" name="mm_authorization_email" readonly type="text">
        </div>
        <div data-input="password" class="input">
            <div data-target="password_input" class="input-wrapper">
                <input name="mm_authorization_password" autocomplete="current-password" data-field="password" placeholder="Enter Password"
                    type="password">
                <button type="button" class="toggle hide-button" aria-label="Toggle"></button>
            </div>
        </div>
        <div class="message">
            <span>At least 8 characters: a mix of letters and numbers.</span>
        </div>
        <button type="submit" class="primary-button">Sign in</button>
    </form>
    <div class="bottom">
        <button class="hide-button" data-target="forgot_pass" type="button">Forgot your
            password?</button>
    </div>
</div>
