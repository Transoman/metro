document.addEventListener('load', function() {
    const phoneFields = document.querySelectorAll('.wpcf7 input[type="tel"]');
    if (phoneFields) {
        phoneFields.forEach(field => {
            field.value = user_phone_ajax_object.user_phone ? user_phone_ajax_object.user_phone : '';
        });
    }
});
