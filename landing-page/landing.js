const API_URL = 'http://localhost/IT-004-IT21S6---Web-Systems-and-Technologies---Group-4/database/';
const MAX_PREVIEW_BOOKS_PER_GENRE = 5;

async function loadCatalogPreview() {
    const tableBodies = Array.from(document.querySelectorAll('#genre-tabs-content tbody[data-genre-id]'));

    try {
        const response = await fetch(API_URL + 'get_books.php');
        if (!response.ok) {
            throw new Error(`Unable to load catalog (HTTP ${response.status}).`);
        }

        const books = await response.json();
        if (!Array.isArray(books)) {
            throw new Error('The catalog response is not a list of books.');
        }

        const booksByGenre = new Map(tableBodies.map(tbody => [tbody.dataset.genreId, []]));

        books.forEach(book => {
            if (Number(book.is_hidden) === 1) return;

            const genreBooks = booksByGenre.get(String(book.genre_id));
            if (genreBooks) {
                genreBooks.push(book);
            }
        });

        tableBodies.forEach(tbody => {
            const emptyState = tbody.querySelector('[data-empty-state]');
            const genreBooks = booksByGenre.get(tbody.dataset.genreId) || [];

            tbody.replaceChildren();

            genreBooks.slice(0, MAX_PREVIEW_BOOKS_PER_GENRE).forEach(book => {
                const row = document.createElement('tr');
                ['', book.title, book.author, book.year].forEach(value => {
                    const cell = document.createElement('td');
                    cell.textContent = value == null ? '' : String(value);
                    row.appendChild(cell);
                });
                tbody.appendChild(row);
            });

            if (genreBooks.length > 0) {
                const row = document.createElement('tr');
                row.className = 'catalog-see-more-row';

                const cell = document.createElement('td');
                cell.className = 'catalog-see-more-cell';
                cell.colSpan = 4;

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'catalog-see-more';
                button.dataset.bsToggle = 'modal';
                button.dataset.bsTarget = '#loginModal';

                const leftPageText = document.createElement('span');
                leftPageText.textContent = 'Continue reading this genre';
                button.appendChild(leftPageText);

                const rightPageText = document.createElement('span');
                rightPageText.append(document.createTextNode('Sign in to explore the full catalog '));

                const icon = document.createElement('i');
                icon.className = 'bi bi-arrow-right';
                icon.setAttribute('aria-hidden', 'true');
                rightPageText.appendChild(icon);
                button.appendChild(rightPageText);

                cell.appendChild(button);
                row.appendChild(cell);
                tbody.appendChild(row);
            }

            if (emptyState) {
                emptyState.hidden = genreBooks.length > 0;
                tbody.appendChild(emptyState);
            }
        });
    } catch (error) {
        console.error('Error loading catalog preview:', error);
    }
}

$(document).ready(function () {
    loadCatalogPreview();

    const $navLinks = $('.navbar-nav .nav-link');
    const $sections = $('section[id], #catalog, #services, #about, #inquire');

    function updateActiveNav() {
        let currentId = '';
        const scrollPosition = $(window).scrollTop() + 240;

        $sections.each(function () {
            const $section = $(this);
            const sectionTop = $section.offset().top;
            const sectionBottom = sectionTop + $section.outerHeight();

            if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                currentId = $section.attr('id');
                return false;
            }
        });

        $navLinks.each(function () {
            const $link = $(this);
            const href = $link.attr('href');
            const isActive = href === '#' ? currentId === '' : href === `#${currentId}`;

            $link.toggleClass('active', isActive);

            if (isActive) {
                $link.attr('aria-current', 'page');
            } else {
                $link.removeAttr('aria-current');
            }
        });
    }

    // Click nav link behavior
    $('.navbar-nav .nav-link').on('click', function () {
        const $navbarCollapse = $('#main-navigation');

        if ($navbarCollapse.hasClass('show')) {
            $navbarCollapse.collapse('hide');
        }
    });

    // Smooth scroll for section links
    $('a[href^="#"]').on('click', function (event) {
        const targetId = $(this).attr('href');

        if (!targetId || targetId === '#' || !$(targetId).length) {
            return;
        }

        event.preventDefault();

        $('html, body').animate({
            scrollTop: $(targetId).offset().top
        }, 400, function () {
            updateActiveNav();
        });
    });

    // Keep nav in sync while scrolling
    $(window).on('scroll', updateActiveNav);
    updateActiveNav();

    $('[data-auth-form]').on('submit', async function (event) {
        event.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const message = form.querySelector('[data-auth-message]');
        const action = form.dataset.authForm;
        const payload = Object.fromEntries(formData.entries());

        if (action === 'signup' && payload.password !== payload.confirm_password) {
            message.textContent = 'Passwords do not match.';
            message.classList.add('text-danger');
            message.classList.remove('text-success');
            return;
        }

        try {
            const response = await fetch(API_URL + 'auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...payload, action })
            });
            const responseText = await response.text();
            let result;

            try {
                result = JSON.parse(responseText);
            } catch {
                throw new Error(`The server returned an invalid response (HTTP ${response.status}). Check that PHP is running and the auth endpoint is reachable.`);
            }

            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Unable to complete your request.');
            }

            if (action === 'login' || action === 'admin_login') {
                const destination = action === 'admin_login' ? '../admin-pages/admin.php' : '../user-pages/users.php';
                window.location.href = destination;
                return;
            }

            form.reset();
            message.textContent = 'Account created. You can now log in.';
            message.classList.add('text-success');
            message.classList.remove('text-danger');
        } catch (error) {
            message.textContent = error.message;
            message.classList.add('text-danger');
            message.classList.remove('text-success');
        }
    });
});
