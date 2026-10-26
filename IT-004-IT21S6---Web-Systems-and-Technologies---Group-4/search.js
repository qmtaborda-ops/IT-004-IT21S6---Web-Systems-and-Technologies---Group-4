// Book search suggestions, shared by the admin and user pages
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('bookSearch');
    if (!input) return;

    const list = document.createElement('ul');
    list.className = 'search-suggest';
    list.hidden = true;
    input.parentElement.appendChild(list);

    let matches = [];

    function render() {
        const q = input.value.trim().toLowerCase();
        list.textContent = '';
        matches = [];
        list.hidden = !q;
        if (!q) return;

        document.querySelectorAll('.tab-pane').forEach(pane => {
            const tab = document.querySelector(`[data-bs-target="#${pane.id}"]`);
            pane.querySelectorAll('tbody tr').forEach(row => {
                const [title, author, , , call] = [...row.cells].map(c => c.textContent.trim());
                if ([title, author, call].join(' ').toLowerCase().includes(q)) {
                    matches.push({ row, pane, tab, title, author, call });
                }
            });
        });
        matches = matches.slice(0, 8);

        if (!matches.length) list.innerHTML = '<li class="search-suggest-empty">No matching books found</li>';

        matches.forEach(m => {
            const li = document.createElement('li');
            li.innerHTML = '<strong></strong><small></small>';
            li.children[0].textContent = m.title;
            li.children[1].textContent = `${m.author} · Call No. ${m.call} · ${m.tab.textContent.trim()}`;
            li.onclick = () => goTo(m);
            list.appendChild(li);
        });
    }

    function goTo({ row, pane, tab, title }) {
        list.hidden = true;
        input.value = title;

        const show = () => {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('search-hit');
            setTimeout(() => row.classList.remove('search-hit'), 3000);
        };

        if (pane.classList.contains('active')) return show();
        tab.addEventListener('shown.bs.tab', show, { once: true });
        bootstrap.Tab.getOrCreateInstance(tab).show();
    }

    input.addEventListener('input', render);
    input.addEventListener('keydown', e => { if (e.key === 'Enter' && matches[0]) goTo(matches[0]); });
    document.addEventListener('click', e => { if (!input.parentElement.contains(e.target)) list.hidden = true; });
});