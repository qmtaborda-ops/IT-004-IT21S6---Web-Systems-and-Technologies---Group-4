const API_URL = 'http://localhost/library/';

$(document).ready(function () {
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
                const destination = action === 'admin_login' ? '../admin-pages/admin.html' : '../user-pages/users.html';
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