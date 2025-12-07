document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('authForm');
    const authTitle = document.getElementById('authTitle');
    const submitButton = document.getElementById('submitButton');
    const toggleModeButton = document.getElementById('toggleModeButton');
    const authMessage = document.getElementById('authMessage');
    const backButton = document.getElementById('backButton');

    let mode = 'login';

    toggleModeButton.addEventListener('click', () => {
        if (mode === 'login') {
            mode = 'register';
            authTitle.textContent = 'Register';
            submitButton.textContent = 'Register';
            toggleModeButton.textContent = 'Already have an account? Login';
        } else {
            mode = 'login';
            authTitle.textContent = 'Login';
            submitButton.textContent = 'Login';
            toggleModeButton.textContent = 'Need an account? Register';
        }
        authMessage.textContent = '';
    });

    backButton.addEventListener('click', () => {
        window.location.href = 'index.html';
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        authMessage.textContent = '';

        const formData = new FormData(form);
        const username = formData.get('username');
        const password = formData.get('password');

        if (!username || !password) {
            authMessage.textContent = 'Fill in all fields.';
            return;
        }

        try {
            authMessage.textContent = 'Sending...';

           let response;
            if (mode === 'login') {
                response = await apiLogin(username, password);
            } else {
                response = await apiRegister(username, password);
            }
            
            let user = response.user ?? null;
            if (!user) {
                try {
                    user = await apiMe(); // /api/me вернёт данные текущего пользователя
                } catch (e) {
                    console.warn('Failed to fetch current user via /api/me', e);
                }
            }
            
            saveAuth(response.token, user);
            authMessage.textContent = 'Success! Redirecting...';
            window.location.href = 'index.html';


        } catch (err) {
            authMessage.textContent = 'Error: ' + err.message;
        }
    });
});
