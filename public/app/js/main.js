document.addEventListener('DOMContentLoaded', () => {
    const loginButton = document.getElementById('loginButton');
    const logoutButton = document.getElementById('logoutButton');
    const userInfo = document.getElementById('userInfo');
    const createPollButton = document.getElementById('createPollButton');
    const pollList = document.getElementById('pollList');

    function setupAuthUi() {
        const currentUser = getCurrentUser();

        if (currentUser) {
            loginButton.style.display = 'none';
            logoutButton.style.display = 'inline-block';
            userInfo.style.display = 'inline-block';
            userInfo.textContent = currentUser.username;
            createPollButton.style.display = 'inline-block';
        } else {
            loginButton.style.display = 'inline-block';
            logoutButton.style.display = 'none';
            userInfo.style.display = 'none';
            createPollButton.style.display = 'none';
        }
    }

    function getPollStatus(poll) {
        if (typeof poll.is_active === 'boolean') {
            return {
                text: poll.is_active ? 'active' : 'closed',
                isActive: poll.is_active
            };
        }
        return {
            text: 'unknown',
            isActive: false
        };
    }

    loginButton.addEventListener('click', () => {
        window.location.href = 'auth.html';
    });

    logoutButton.addEventListener('click', () => {
        clearAuth();
        window.location.reload();
    });

    createPollButton.addEventListener('click', () => {
        window.location.href = 'create-poll.html';
    });

    setupAuthUi();
    loadPolls();

    async function loadPolls() {
        pollList.innerHTML = '<p>Loading polls...</p>';
        try {
            const polls = await apiGetPolls();
            if (!polls || polls.length === 0) {
                pollList.innerHTML = '<p>Еще нет опросов.</p>';
                return;
            }

            pollList.innerHTML = '';

            polls.forEach(poll => {
                const status = getPollStatus(poll);

                const card = document.createElement('article');
                card.className = 'poll-card';
                card.innerHTML = `
                    <h2>${poll.title ?? '(no title)'}</h2>
                    <p>${poll.description ?? ''}</p>
                    <p class="poll-meta">
                        Статус: <strong>${status.text}</strong><br>
                        Создан: ${poll.created_at ?? ''}
                    </p>
                    <button class="btn btn-primary" data-id="${poll.id}">
                        Открыть опрос
                    </button>
                `;
                pollList.appendChild(card);
            });

            pollList.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-id]');
                if (!btn) return;
                const pollId = btn.getAttribute('data-id');
                window.location.href = `poll.html?id=${encodeURIComponent(pollId)}`;
            });

        } catch (err) {
            console.error(err);
            pollList.innerHTML = `<p>Error loading polls: ${err.message}</p>`;
        }
    }
});
