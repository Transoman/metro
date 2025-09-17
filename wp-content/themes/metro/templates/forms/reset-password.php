<div class="wrapper">
    <form data-target="reset_password_form" method="post" action="#">
        <input name="mm_reset_password_nonce" value="<?php echo wp_create_nonce('mm-reset-password-nonce') ?>"
            type="hidden">
        <input name="action" value="mm_reset_password_user" type="hidden">
        <input type="hidden" name="mm_reset_password_template" value="search">
        <p>Enter your email and we’ll send you a link to reset your password</p>
        <div data-input="email" class="input">
            <input name="mm_reset_password_email" data-field="email" placeholder="Email Address*" type="text">
        </div>
        <button class="primary-button" type="submit">Send Email</button>
    </form>
</div>