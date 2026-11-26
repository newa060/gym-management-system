/**
 * Gym Membership System Client-Side Scripts.
 * Handles action confirmations and basic client-side form validation.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Delete and Cancel Action Confirmation Dialogs
    const deleteButtons = document.querySelectorAll('.delete-btn, [data-confirm]');
    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            const confirmMessage = button.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!window.confirm(confirmMessage)) {
                event.preventDefault();
            }
        });
    });

    // 2. Client-Side Form Validation
    const validatedForms = document.querySelectorAll('.validated-form');
    validatedForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const requiredFields = form.querySelectorAll('[required]');
            let formIsValid = true;

            requiredFields.forEach(function (field) {
                if (!field.value.trim()) {
                    field.style.borderColor = '#b91c1c';
                    formIsValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!formIsValid) {
                event.preventDefault();
                alert('Please fill out all required fields marked with an asterisk (*).');
            }
        });
    });
});
