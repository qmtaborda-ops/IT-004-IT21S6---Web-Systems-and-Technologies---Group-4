const API_URL = '../database/';
const MAX_PREVIEW_BOOKS_PER_GENRE = 3;
const previewBooks = new Map();

function showBookDetails(book) {
    document.getElementById('previewDetailsTitle').textContent = book.title || 'Untitled';
    document.getElementById('previewDetailsAuthor').textContent = book.author || 'Unknown author';
    document.getElementById('previewDetailsYear').textContent = book.year || 'Not specified';
    document.getElementById('previewDetailsCall').textContent = book.call_no || 'Not specified';
    document.getElementById('previewDetailsDescription').textContent =
        book.description && book.description.trim() ? book.description : 'No description available for this book.';

    const cover = document.getElementById('previewDetailsCover');
    const noCover = document.getElementById('previewDetailsNoCover');
    if (book.cover_hash) {
        cover.src = API_URL + 'get_cover.php?id=' + encodeURIComponent(book.id) + '&v=' + encodeURIComponent(book.cover_hash);
        cover.alt = 'Cover of ' + (book.title || 'this book');
        cover.hidden = false;
        noCover.hidden = true;
    } else {
        cover.removeAttribute('src');
        cover.hidden = true;
        noCover.hidden = false;
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('previewDetailsModal')).show();
}

function renderCatalogCarousel(books) {
    const track = document.getElementById('catalog-carousel-track');
    track.replaceChildren();

    const visibleBooks = books.filter(book => book.cover_hash);
    const coverGroup = document.createElement('div');
    coverGroup.className = 'catalog-carousel-group d-flex align-items-center gap-2';
    coverGroup.setAttribute('role', 'group');
    coverGroup.setAttribute('aria-label', 'Book covers');

    visibleBooks.forEach(book => {
        const cover = document.createElement('img');
        cover.className = 'catalog-carousel-cover';
        cover.src = API_URL + 'get_cover.php?id=' + encodeURIComponent(book.id) + '&v=' + encodeURIComponent(book.cover_hash);
        cover.alt = 'Cover of ' + (book.title || 'this book');
        cover.loading = 'eager';
        coverGroup.appendChild(cover);
    });

    track.appendChild(coverGroup);

    function updateCarouselMotion() {
        const groupWidth = coverGroup.getBoundingClientRect().width;
        const viewportWidth = track.parentElement.clientWidth;
        if (visibleBooks.length <= 1) {
            track.style.removeProperty('--carousel-duration');
            track.style.removeProperty('--carousel-distance');
            track.style.animation = 'none';
            return;
        }
        track.style.animation = '';
        const groupCount = Math.max(2, Math.ceil(viewportWidth / groupWidth) + 1);

        while (track.children.length < groupCount) {
            const duplicateGroup = coverGroup.cloneNode(true);
            duplicateGroup.removeAttribute('role');
            duplicateGroup.removeAttribute('aria-label');
            duplicateGroup.setAttribute('aria-hidden', 'true');
            track.appendChild(duplicateGroup);
        }

        while (track.children.length > groupCount) {
            track.lastElementChild.remove();
        }

        track.style.setProperty('--carousel-steps', String(Math.max(1, Math.round(groupWidth * window.devicePixelRatio))));
        track.style.setProperty('--carousel-duration', `${groupWidth / 60}s`);
        track.style.setProperty('--carousel-distance', `${groupWidth}px`);
    }

    updateCarouselMotion();
    window.addEventListener('resize', updateCarouselMotion, { passive: true });
}

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

        tableBodies.forEach(tbody => {
            const headerRow = tbody.closest('table').querySelector('thead tr');
            if (headerRow.querySelector('.catalog-mobile-heading')) return;

            ['Cover & Title', 'Author & Year'].forEach(label => {
                const heading = document.createElement('th');
                heading.className = 'catalog-mobile-heading';
                heading.scope = 'col';
                heading.textContent = label;
                headerRow.appendChild(heading);
            });
        });

        books.forEach(book => {
            if (Number(book.is_hidden) === 1) return;
            previewBooks.set(String(book.id), book);

            const genreBooks = booksByGenre.get(String(book.genre_id));
            if (genreBooks) {
                genreBooks.push(book);
            }
        });
        renderCatalogCarousel(Array.from(previewBooks.values()));

        tableBodies.forEach(tbody => {
            const emptyState = tbody.querySelector('[data-empty-state]');
            const genreBooks = booksByGenre.get(tbody.dataset.genreId) || [];

            tbody.replaceChildren();

            genreBooks.slice(0, MAX_PREVIEW_BOOKS_PER_GENRE).forEach(book => {
                const row = document.createElement('tr');

                const coverCell = document.createElement('td');
                coverCell.className = 'catalog-cover-cell';
                if (book.cover_hash) {
                    const cover = document.createElement('img');
                    cover.className = 'catalog-book-cover';
                    cover.src = API_URL + 'get_cover.php?id=' + encodeURIComponent(book.id) + '&v=' + encodeURIComponent(book.cover_hash);
                    cover.alt = 'Cover of ' + (book.title || 'this book');
                    cover.loading = 'lazy';
                    coverCell.appendChild(cover);
                } else {
                    const placeholder = document.createElement('i');
                    placeholder.className = 'bi bi-book catalog-book-cover-placeholder';
                    placeholder.setAttribute('aria-label', 'No cover image');
                    coverCell.appendChild(placeholder);
                }
                row.appendChild(coverCell);

                const titleCell = document.createElement('td');
                titleCell.className = 'catalog-title-cell';
                const titleEntry = document.createElement('div');
                titleEntry.className = 'catalog-title-entry';
                const mobileCover = coverCell.firstElementChild.cloneNode(true);
                mobileCover.classList.remove('catalog-book-cover', 'catalog-book-cover-placeholder');
                mobileCover.classList.add(book.cover_hash ? 'catalog-mobile-cover' : 'catalog-mobile-cover-placeholder');
                titleEntry.appendChild(mobileCover);

                const titleButton = document.createElement('button');
                titleButton.type = 'button';
                titleButton.className = 'catalog-book-details';
                titleButton.dataset.bookId = String(book.id);
                titleButton.textContent = book.title || 'Untitled';
                titleButton.setAttribute('aria-label', 'View details for ' + (book.title || 'untitled book'));
                titleEntry.appendChild(titleButton);
                titleCell.appendChild(titleEntry);
                row.appendChild(titleCell);

                const authorCell = document.createElement('td');
                authorCell.className = 'catalog-author-cell';
                const authorName = document.createElement('span');
                authorName.className = 'catalog-author-name';
                authorName.textContent = book.author == null ? '' : String(book.author);
                authorCell.appendChild(authorName);
                const mobileYear = document.createElement('span');
                mobileYear.className = 'catalog-mobile-year';
                mobileYear.textContent = book.year == null || book.year === '' ? '' : 'Year: ' + String(book.year);
                authorCell.appendChild(mobileYear);
                row.appendChild(authorCell);

                const yearCell = document.createElement('td');
                yearCell.className = 'catalog-year-cell';
                yearCell.textContent = book.year == null ? '' : String(book.year);
                row.appendChild(yearCell);
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

document.addEventListener('click', event => {
    const button = event.target.closest('.catalog-book-details');
    if (!button) return;
    const book = previewBooks.get(button.dataset.bookId);
    if (book) showBookDetails(book);
});

document.getElementById('previewDetailsCover').addEventListener('error', function () {
    this.hidden = true;
    document.getElementById('previewDetailsNoCover').hidden = false;
});

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
                credentials: 'include',
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
