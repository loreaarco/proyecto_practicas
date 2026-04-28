// OSCISA Solutions — app.js

// ── Drag & drop dropzone ─────────────────────────────────
(function () {
    const dz    = document.getElementById('dropzone');
    const input = document.getElementById('inputFile');
    if (!dz || !input) return;

    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
        e.preventDefault();
        dz.classList.add('dragover');
    }));

    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
        e.preventDefault();
        dz.classList.remove('dragover');
    }));

    dz.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            input.files = dt.files;
            input.dispatchEvent(new Event('change'));
        }
    });
})();

// ── Auto-dismiss flash alerts after 5s ──────────────────
document.querySelectorAll('.alert-dismissible').forEach(el => {
    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
        if (bsAlert) bsAlert.close();
    }, 5000);
});

// ── Active nav link ──────────────────────────────────────
(function () {
    const path  = window.location.pathname;
    const links = document.querySelectorAll('.navbar .nav-link');
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href && path.startsWith(href) && href !== '/') {
            link.classList.add('active');
        }
    });
})();
