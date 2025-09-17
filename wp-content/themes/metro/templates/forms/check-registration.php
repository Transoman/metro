<div class="wrapper">
    <form action="#" data-target="login_register_form">
        <input type="hidden" name="action" value="is_email_registered">
        <div class="input">
            <input placeholder="Email" autocomplete="email" data-field="email" name="email" type="text">
            <div class="message"></div>
        </div>
        <button type="submit" class="primary-button">Continue</button>
    </form>
    <div class="social-buttons">
        <?php
        if (class_exists('NextendSocialLogin', false)) {
            echo NextendSocialLogin::renderButtonsWithContainer();
        }
        ?>
    </div>
    <div class="bottom">
        <button data-target="forgot_pass" class="hide-button" type="button">Forgot your
            password?</button>
    </div>
</div>