<?php
/*
Template Name: Login Page
*/

// Redirect if user is already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

$subtitle = get_field('subtitle_login_page', 'option');

get_header();
?>
<main>
    <?php get_template_part('templates/parts/notification', 'template'); ?>
    
    <div class="login-page-container">
        <div data-target="authorization_poup" class="authorization custom-scroll">
            <div class="container">
                <div class="wrapper global">
                    <div data-target="steps" class="steps footer">
                        <div data-step="first" class="step">
                            <div class="step-content">
                                <h3>Log In / Sign Up</h3>
                                <?php if ( $subtitle ) {
                                    echo "<p class=\"step-susbtitle\">$subtitle</p>";
                                } ?>
                                <?php echo get_template_part('templates/forms/check', 'registration') ?>
                            </div>
                        </div>
                        <div data-step="login" class="step hide">
                            <div class="step-content">
                                <button class="step-back hide-button" aria-label="Back" type="button"></button>
                                <h3>Log in</h3>
                                <?php echo get_template_part('templates/forms/login') ?>
                            </div>
                        </div>
                        <div data-step="register" class="step hide">
                            <div class="step-content">
                                <button class="step-back hide-button" aria-label="Back" type="button"></button>
                                <h3>Sign Up for a Free Account</h3>
                                <?php echo get_template_part('templates/forms/registration') ?>
                            </div>
                        </div>
                        <div data-step="reset" class="step hide">
                            <button class="step-back hide-button" aria-label="Back" type="button"></button>
                            <h3>Reset Password</h3>
                            <?php echo get_template_part('templates/forms/reset', 'password') ?>
                        </div>
                        <div data-step="send_reset" class="step hide">
                            <button class="step-back hide-button" aria-label="Back" type="button"></button>
                            <h3>Reset Password</h3>
                            <div class="wrapper">
                                <h4>Email sent</h4>
                                <p>Check your email and open the link we sent to continue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle back button functionality for login page
    const backButtons = document.querySelectorAll('.step-back');
    backButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const currentStep = this.closest('.step');
            const stepsContainer = this.closest('[data-target="steps"]');
            const firstStep = stepsContainer.querySelector('[data-step="first"]');
            
            // Hide current step
            currentStep.classList.add('hide');
            // Show first step
            firstStep.classList.remove('hide');
        });
    });
    
    // Handle forgot password button
    const forgotButtons = document.querySelectorAll('[data-target="forgot_pass"]');
    forgotButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const currentStep = this.closest('.step');
            const stepsContainer = this.closest('[data-target="steps"]');
            const resetStep = stepsContainer.querySelector('[data-step="reset"]');
            
            // Hide current step
            currentStep.classList.add('hide');
            // Show reset step
            resetStep.classList.remove('hide');
        });
    });
});
</script>

<?php get_footer(); ?> 