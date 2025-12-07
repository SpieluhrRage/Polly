document.addEventListener('DOMContentLoaded', () => {
    const loginButton = document.getElementById('loginButton');
    const logoutButton = document.getElementById('logoutButton');
    const userInfo = document.getElementById('userInfo');

    const form = document.getElementById('createPollForm');
    const addOptionButton = document.getElementById('addOptionButton');
    const optionsContainer = document.getElementById('optionsContainer');
    const createMessage = document.getElementById('createMessage');
    const backButton = document.getElementById('backButton');

    function setupAuthUi() {
        const currentUser = getCurrentUser();
        if (currentUser) {
            loginButton.style.display = 'none';
            logoutButton.style.display = 'inline-block';
            userInfo.style.display = 'inline-block';
            userInfo.textContent = currentUser.username;
        } else {
            loginButton.style.display = 'inline-block';
            logoutButton.style.display = 'none';
            userInfo.style.display = 'none';

            window.location.href = 'auth.html';
        }
    }

    loginButton.addEventListener('click', () => {
        window.location.href = 'auth.html';
    });

    logoutButton.addEventListener('click', () => {
        clearAuth();
        window.location.reload();
    });

    backButton.addEventListener('click', () => {
        window.location.href = 'index.html';
    });

    setupAuthUi();

    function createOptionField(value = '') {
        const wrapper = document.createElement('div');
        wrapper.className = 'option-row';

        wrapper.innerHTML = `
            <input type="text" name="option" placeholder="Option text" value="${value}">
            <button type="button" class="btn btn-secondary btn-small remove-option-btn">
                ×
            </button>
        `;

        const removeBtn = wrapper.querySelector('.remove-option-btn');
        removeBtn.addEventListener('click', () => {
            // минимум 2 поля оставляем
            if (optionsContainer.children.length > 2) {
                wrapper.remove();
            }
        });

        return wrapper;
    }

    function ensureInitialOptions() {
        optionsContainer.innerHTML = '';
        optionsContainer.appendChild(createOptionField());
        optionsContainer.appendChild(createOptionField());
    }

    addOptionButton.addEventListener('click', () => {
        optionsContainer.appendChild(createOptionField());
    });

    ensureInitialOptions();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        createMessage.textContent = '';

        const formData = new FormData(form);
        const title = (formData.get('title') || '').toString().trim();
        const description = (formData.get('description') || '').toString().trim();

        if (!title) {
            createMessage.textContent = 'Title is required.';
            return;
        }

        const optionInputs = optionsContainer.querySelectorAll('input[name="option"]');
        const options = [];

        optionInputs.forEach(input => {
            const value = input.value.trim();
            if (value !== '') {
                options.push(value);
            }
        });

        if (options.length < 2) {
            createMessage.textContent = 'At least two options are required.';
            return;
        }

        try {
            createMessage.textContent = 'Creating poll...';

            const payload = {
                title,
                description: description === '' ? null : description,
                options
            };

            const result = await apiCreatePoll(payload);

            createMessage.textContent = 'Poll created successfully. Redirecting...';


            if (result && typeof result.id === 'number') {
                window.location.href = `poll.html?id=${encodeURIComponent(result.id)}`;
            } else {
                window.location.href = 'index.html';
            }

        } catch (err) {
            console.error(err);
            createMessage.textContent = 'Error: ' + err.message;
        }
    });
});
