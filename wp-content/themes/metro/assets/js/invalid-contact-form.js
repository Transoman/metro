document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.wpcf7 form');
    console.log(form);

    if (form) {
        console.log('in');
        form.addEventListener('submit', function (event) {
            let allFilled = true;
            const requiredFields = form.querySelectorAll('input[aria-required="true"], textarea[aria-required="true"]');

            requiredFields.forEach(function (field) {
                if (!field.value.trim()) {
                    allFilled = false;
                    field.classList.add('invalid-field');
                } else {
                    field.classList.remove('invalid-field');
                }
            });

            if (!allFilled) {
                console.log('Please fill all the required fields.');
                event.preventDefault(); // Stop the form submission
                // alert('Please fill all the required fields.');
            }
        });
    }
});