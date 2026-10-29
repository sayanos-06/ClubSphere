const toggles = document.querySelectorAll('[data-password]');
for (const toggle of toggles) {
    toggle.addEventListener('click', function () {
        const field = document.getElementById(toggle.getAttribute('data-password'));
        if (field.type === 'password') {
            field.type = 'text';
            toggle.textContent = 'Hide password';
        } else {
            field.type = 'password';
            toggle.textContent = 'Show password';
        }
    });
}
for (const form of document.querySelectorAll('.validated')) {
    form.addEventListener('submit', function (event) {
        const password = form.querySelector('[name="password"]');
        const confirm = form.querySelector('[name="confirm"]');
        const message = form.querySelector('.form_message');
        if (password && confirm && password.value !== confirm.value) {
            event.preventDefault();
            if (message) {
                message.hidden = false;
                message.textContent = 'The passwords do not match.';
            }
            confirm.focus();
        }
    });
}
