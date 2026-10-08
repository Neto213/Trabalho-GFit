document.addEventListener('DOMContentLoaded', () => {
    // Alternar visibilidade de senha (mostrar/ocultar)
    const toggleButtons = document.querySelectorAll('[data-toggle-password]');

    toggleButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-toggle-password');
            const targetInput = document.getElementById(targetId);

            if (!targetInput) return;

            const isPassword = targetInput.getAttribute('type') === 'password';
            targetInput.setAttribute('type', isPassword ? 'text' : 'password');

            button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            button.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Exibir senha');

            const icon = button.querySelector('i');
            if (icon) {
                if (isPassword) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            } else {
                button.textContent = isPassword ? 'Ocultar' : 'Mostrar';
            }
        });
    });

    // Dashboard Tabs — Persistência da aba ativa
    const tabContainer = document.getElementById('dashboardStudentTabs');

    if (tabContainer) {
        const STORAGE_KEY = 'gfit_student_active_tab';
        const tabButtons = tabContainer.querySelectorAll('[data-bs-toggle="tab"]');

        // Salvar aba ativa ao clicar
        tabButtons.forEach(btn => {
            btn.addEventListener('shown.bs.tab', (e) => {
                const tabId = e.target.getAttribute('data-bs-target');
                try {
                    localStorage.setItem(STORAGE_KEY, tabId);
                } catch (_) { /* storage indisponível */ }

                // Atualizar hash da URL sem scroll
                if (tabId) {
                    history.replaceState(null, '', tabId);
                }
            });
        });

        // Restaurar aba ativa (prioridade: hash da URL > localStorage)
        let targetTab = null;
        const hash = window.location.hash;

        if (hash && Array.from(tabButtons).some(btn => btn.getAttribute('data-bs-target') === hash)) {
            targetTab = hash;
        } else {
            try {
                targetTab = localStorage.getItem(STORAGE_KEY);
            } catch (_) { /* storage indisponível */ }
        }

        if (targetTab) {
            const tabTrigger = Array.from(tabButtons).find(btn => btn.getAttribute('data-bs-target') === targetTab);
            if (tabTrigger && window.bootstrap) {
                const bsTab = new bootstrap.Tab(tabTrigger);
                bsTab.show();
            }
        }
    }
    const menu = document.querySelector('[data-menu-toggle]');
    const sidebar = document.getElementById('app-navigation');
    const backdrop = document.querySelector('[data-close-menu]');
    function closeMenu() {
        sidebar?.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
        menu?.setAttribute('aria-expanded', 'false');
    }
    menu?.addEventListener('click', () => {
        const opened = sidebar.classList.toggle('is-open');
        backdrop.classList.toggle('is-open', opened);
        menu.setAttribute('aria-expanded', String(opened));
        if(opened) sidebar.querySelector('a')?.focus();
    });
    backdrop?.addEventListener('click', closeMenu);
    document.querySelector('[data-menu-close]')?.addEventListener('click', () => {closeMenu();menu?.focus();});
    document.addEventListener('keydown', event => {
        if(event.key === 'Escape' && sidebar?.classList.contains('is-open')) {
            closeMenu(); menu?.focus();
        }
    });
    // Mantém os botões disponíveis ao voltar pelo histórico do navegador.
    window.addEventListener('pageshow', () => document.querySelectorAll('[data-submitting]').forEach(btn => {
        btn.disabled = false; btn.removeAttribute('data-submitting');
    }));
    document.querySelectorAll('form[method="post" i]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
            if (event.defaultPrevented) return;
            const submit = event.submitter;
            if(submit && !submit.name) {submit.disabled=true;submit.setAttribute('data-submitting','true');}
        });
    });
});
