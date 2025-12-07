document.addEventListener('DOMContentLoaded', () => {
    const loginButton = document.getElementById('loginButton');
    const logoutButton = document.getElementById('logoutButton');
    const userInfo = document.getElementById('userInfo');

    const pollHeader = document.getElementById('pollHeader');
    const pollResults = document.getElementById('pollResults');
    const voteSection = document.getElementById('voteSection');
    const manageSection = document.getElementById('manageSection');

    // --------- helpers ---------

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
        }
    }

    function getPollStatus(poll) {
        if (typeof poll.is_active === 'boolean') {
            return {
                text: poll.is_active ? 'active' : 'closed',
                isActive: poll.is_active
            };
        }
        // fallback
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

    setupAuthUi();

    const params = new URLSearchParams(window.location.search);
    const pollId = params.get('id');

    if (!pollId) {
        pollHeader.innerHTML = '<p>Poll id is missing</p>';
        return;
    }

    // --------- main load ---------

    async function loadPollAndResults() {
        try {
            // детали опроса
            const poll = await apiGetPoll(pollId);
            const status = getPollStatus(poll);

            pollHeader.innerHTML = `
                <h1>${poll.title ?? '(no title)'}</h1>
                <p>${poll.description ?? ''}</p>
                <p class="poll-meta">
                    Статус: <strong>${status.text}</strong><br>
                    Создан: ${poll.created_at ?? ''}
                </p>
           `;

            // результаты
            await loadResultsAndVoteForm(poll, status);
            renderManageSection(poll, status);

        } catch (err) {
            console.error(err);
            pollHeader.innerHTML = `<p>Error loading poll: ${err.message}</p>`;
            pollResults.innerHTML = '';
            voteSection.innerHTML = '';
            manageSection.innerHTML = '';
        }
    }

    async function loadResultsAndVoteForm(poll, status) {
        pollResults.innerHTML = '<p>Loading results...</p>';

        try {
            const data = await apiGetPollResults(pollId);
            const options = data.options ?? [];

            let html = '<h2>Результаты</h2>';
            if (!options.length) {
                html += '<p>No options.</p>';
            } else {
                html += '<ul class="option-list">';
                options.forEach(opt => {
                    const label =
                        opt.text ??
                        opt.label ??
                        opt.label_key ??
                        `Option #${opt.id}`;

                    const votes =
                        opt.votes ??
                        opt.votes_count ??
                        opt.total_votes ??
                        0;

                    html += `
                        <li>
                            <strong>${label}</strong>
                            — ${votes} votes
                        </li>
                    `;
                });
                html += '</ul>';
            }
            pollResults.innerHTML = html;

            renderVoteSection(poll, status, options);

        } catch (err) {
            console.error(err);
            pollResults.innerHTML = `<p>Error loading results: ${err.message}</p>`;
            voteSection.innerHTML = '';
        }
    }

    function renderVoteSection(poll, status, options) {
        voteSection.innerHTML = '';

        const currentUser = getCurrentUser();
        if (!currentUser) {
            voteSection.innerHTML = '<p>Войдите, чтобы проголосовать.</p>';
            return;
        }

        if (!status.isActive) {
            voteSection.innerHTML = '<p>Этот опрос закрыт.</p>';
            return;
        }

        if (!options.length) {
            voteSection.innerHTML = '<p>Нет опций для голосования.</p>';
            return;
        }

        const form = document.createElement('form');
        form.innerHTML = `
            <h2>Vote</h2>
            ${options.map(opt => `
                <label class="radio-option">
                    <input type="radio" name="option" value="${opt.id}">
                    <span>${opt.text ?? opt.label ?? opt.label_key ?? `Option #${opt.id}`}</span>
                </label>
            `).join('')}
            <button type="submit" class="btn btn-primary">
                Проголосовать
            </button>
            <p id="voteMessage" class="message"></p>
        `;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const optionId = formData.get('option');

            if (!optionId) {
                document.getElementById('voteMessage').textContent = 'Пожалуйста, выберете опцию.';
                return;
            }

            try {
                document.getElementById('voteMessage').textContent = 'Sending vote...';
                await apiCastVote(pollId, Number(optionId));
                document.getElementById('voteMessage').textContent = 'Vote accepted.';
                await loadPollAndResults();
            } catch (err) {
                document.getElementById('voteMessage').textContent = `Error: ${err.message}`;
            }
        });

        voteSection.appendChild(form);
    }

    function renderManageSection(poll, status) {
        manageSection.innerHTML = '';

        const currentUser = getCurrentUser();
        if (!currentUser) return;

        const ownerId =
            poll.created_by ??
            poll.author_id ??
            poll.user_id ??
            null;

        if (ownerId === null || ownerId !== currentUser.id) {
            return;
        }

        const wrapper = document.createElement('div');

        let buttonsHtml = '<h2>Manage poll</h2>';

        if (status.isActive) {
            buttonsHtml += `
                <button id="closePollBtn" class="btn btn-secondary">Закрыть опрос</button>
                <button id="editPollBtn" class="btn btn-secondary">Редактировать опрос</button>
            `;
        } else {
            buttonsHtml += `
                <button id="openPollBtn" class="btn btn-secondary">Открыть опрос</button>
                <button id="deletePollBtn" class="btn btn-danger">Удалить опрос</button>
            `;
        }

        buttonsHtml += `<div id="editFormContainer"></div>`;

        wrapper.innerHTML = buttonsHtml;
        manageSection.appendChild(wrapper);

        const closeBtn = document.getElementById('closePollBtn');
        const openBtn = document.getElementById('openPollBtn');
        const deleteBtn = document.getElementById('deletePollBtn');
        const editBtn = document.getElementById('editPollBtn');
        const editFormContainer = document.getElementById('editFormContainer');

        if (closeBtn) {
            closeBtn.addEventListener('click', async () => {
                try {
                    await apiClosePoll(poll.id);
                    await loadPollAndResults();
                } catch (err) {
                    alert('Error closing poll: ' + err.message);
                }
            });
        }

        if (openBtn) {
            openBtn.addEventListener('click', async () => {
                try {
                    await apiOpenPoll(poll.id);
                    await loadPollAndResults();
                } catch (err) {
                    alert('Error opening poll: ' + err.message);
                }
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', async () => {
                if (!confirm('Delete this poll?')) return;
                try {
                    await apiDeletePoll(poll.id);
                    window.location.href = 'index.html';
                } catch (err) {
                    alert('Error deleting poll: ' + err.message);
                }
            });
        }

        if (editBtn) {
            editBtn.addEventListener('click', () => {
                // клик по Edit: показать/спрятать форму редактирования
                if (editFormContainer.innerHTML.trim() !== '') {
                    editFormContainer.innerHTML = '';
                    return;
                }

                editFormContainer.innerHTML = `
                    <form id="editPollForm" class="edit-form">
                        <label>
                            Title
                            <input type="text" name="title" value="${poll.title ?? ''}" required>
                        </label>
                        <label>
                            Description
                            <textarea name="description" rows="3">${poll.description ?? ''}</textarea>
                        </label>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <p id="editMessage" class="message"></p>
                    </form>
                `;

                const form = document.getElementById('editPollForm');
                const msg = document.getElementById('editMessage');

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const title = formData.get('title')?.toString().trim() ?? '';
                    const description = formData.get('description')?.toString().trim() ?? '';

                    if (!title) {
                        msg.textContent = 'Title is required.';
                        return;
                    }

                    try {
                        msg.textContent = 'Saving...';
                        await apiEditPoll(poll.id, { title, description });
                        msg.textContent = 'Saved.';
                        await loadPollAndResults();
                    } catch (err) {
                        msg.textContent = 'Error: ' + err.message;
                    }
                });
            });
        }
    }

    // initial load
    loadPollAndResults();
});
