<div class="wrapper">
    <form data-target="registration_form" method="post" action="#">
        <input name="mm_registration_nonce" value="<?php echo wp_create_nonce('mm-registration-nonce') ?>"
            type="hidden">
        <input type="hidden" value="<?php echo home_url(add_query_arg(array(), $wp->request)); ?>"
            name="mm_registration_redirect_to">
        <input name="action" value="mm_registration_user" type="hidden">
        <div class="input">
            <input placeholder="Email" autocomplete="email" name="mm_registration_email" data-field="email" readonly type="text">
        </div>
        <div data-input="password" class="input">
            <div data-target="password_input" class="input-wrapper">
                <input name="mm_registration_password" autocomplete="current-password" id="create_account_password" data-field="password" placeholder=""
                    type="password">
		<label for="create_account_password">Create Password <span>*</span></label>
                <button type="button" class="toggle hide-button" aria-label="Toggle"></button>
            </div>
        </div>
        <div class="message">
            <span>At least 8 characters: a mix of letters and numbers.</span>
        </div>
        <div class="optional-wrapper">
            <div class="optional">
                <div data-input="first_name" class="input half">
                    <input name="mm_registration_first_name"  placeholder="First Name" type="text">
                </div>
                <div data-input="last_name" class="input half">
                    <input name="mm_registration_last_name"  placeholder="Last Name" type="text">
                </div>
                <div class="input">
                    <input name="mm_registration_company" placeholder="Company (optional)" type="text">
                </div>
                <span class="optional-message">Optional</span>
            </div>
        </div>
        <button type="submit" class="primary-button">Create account</button>
    </form>
</div>
