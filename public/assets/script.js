const toggles = document.querySelectorAll('[data-password]');
for (const toggle of toggles) {
    toggle.addEventListener('click', function () {
        const field = document.getElementById(toggle.getAttribute('data-password'));
        const start = field.selectionStart;
        const end = field.selectionEnd;
        const visible = field.type === 'password';
        const label = visible ? 'Hide password' : 'Show password';

        field.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.querySelector('img').src = visible ? '/assets/eye_off.svg' : '/assets/eye.svg';
        field.focus({ preventScroll: true });

        if (start !== null && end !== null) {
            field.setSelectionRange(start, end);
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

function refreshPlaces() {
    const message = document.getElementById('live_message');
    const refresh = document.getElementById('refresh_places');
    if (refresh) {
        refresh.disabled = true;
    }
    return fetch('/availability.php')
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Availability could not be loaded.');
            }
            return response.json();
        })
        .then(function (data) {
            for (const activity of data.activities) {
                for (const count of document.querySelectorAll(
                    '[data-count="' + activity.id + '"]',
                )) {
                    count.textContent = activity.remaining + ' places left';
                }
                for (const state of document.querySelectorAll(
                    '[data-state="' + activity.id + '"]',
                )) {
                    state.textContent = activity.status;
                }
                const form = document.getElementById('registration_form');
                if (
                    form &&
                    Number(form.querySelector('[name="activity_id"]').value) === activity.id
                ) {
                    const button = document.getElementById('registration_button');
                    const action = document.getElementById('registration_action').value;
                    const closed = activity.status !== 'Open' && activity.status !== 'Full';
                    button.disabled = closed || (action === 'register' && activity.remaining === 0);
                    button.textContent = closed
                        ? 'Registration closed'
                        : action === 'cancel'
                          ? 'Cancel my registration'
                          : activity.remaining === 0
                            ? 'Activity full'
                            : 'Reserve my place →';
                }
            }
            if (message) {
                message.textContent = 'Availability is up to date.';
            }
        })
        .catch(function () {
            if (message) {
                message.textContent = 'Could not refresh places. Please try again.';
            }
        })
        .finally(function () {
            if (refresh) {
                refresh.disabled = false;
            }
        });
}
const refreshButton = document.getElementById('refresh_places');
if (refreshButton) {
    refreshButton.addEventListener('click', refreshPlaces);
}
const registrationForm = document.getElementById('registration_form');
if (registrationForm) {
    registrationForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const button = document.getElementById('registration_button');
        const message = document.getElementById('registration_message');
        const action = document.getElementById('registration_action');
        button.disabled = true;
        message.textContent = 'Updating your registration…';
        fetch('/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                token: registrationForm.querySelector('[name="token"]').value,
                activity_id: registrationForm.querySelector('[name="activity_id"]').value,
                action: action.value,
            }),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error(
                            data.error ||
                                'Your request could not be completed. Refresh and try again.',
                        );
                    }
                    return data;
                });
            })
            .then(function (data) {
                message.textContent = data.message;
                action.value = data.registered ? 'cancel' : 'register';
            })
            .catch(function (error) {
                message.textContent =
                    error.message ||
                    'Connection lost. Refresh the page to check your registration.';
            })
            .finally(function () {
                button.disabled = false;
                refreshPlaces();
            });
    });
}
if (document.querySelector('[data-count]')) {
    window.addEventListener('focus', refreshPlaces);
}
