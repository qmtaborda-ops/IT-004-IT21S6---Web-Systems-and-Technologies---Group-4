document.addEventListener('DOMContentLoaded', function () {
    /* De Leon, Charles Jacob G, Barte, Aaron John M., Cosme, Jan Yvan G., Lucena, Rowvic Joshua C., Masiglat, Ivan Rudolf S., Taborda, Mico S  */
    const termsCheck = document.getElementById('termsCheck');
    const confirmBtn = document.getElementById('confirmBorrowBtn');
    const borrowDateInput = document.getElementById('borrowDate');
    const returnDateInput = document.getElementById('returnDate');
    const dateError = document.getElementById('dateError');
    const notification = document.getElementById('borrowNotification');
    const borrowForm = document.getElementById('borrowForm');
    const borrowModal = document.getElementById('borrowModal');

    termsCheck.addEventListener('change', function () {
        confirmBtn.disabled = !this.checked;
    });

    const today = new Date().toISOString().split('T')[0];
    borrowDateInput.setAttribute('min', today);
    returnDateInput.setAttribute('min', today);

    borrowDateInput.addEventListener('change', function () {
        const borrowVal = this.value;
        if (!borrowVal) return;

        const borrow = new Date(borrowVal);

        const defaultReturn = new Date(borrow);
        defaultReturn.setMonth(defaultReturn.getMonth() + 1);

        const maxReturn = new Date(borrow);
        maxReturn.setMonth(maxReturn.getMonth() + 2);

        const defaultStr = defaultReturn.toISOString().split('T')[0];
        const maxStr = maxReturn.toISOString().split('T')[0];

        returnDateInput.value = defaultStr;
        returnDateInput.setAttribute('max', maxStr);
        returnDateInput.setAttribute('min', borrowVal);

        returnDateInput.dispatchEvent(new Event('change'));
    });

    returnDateInput.addEventListener('change', function () {
        const borrowVal = borrowDateInput.value;
        const returnVal = this.value;

        if (!borrowVal || !returnVal) return;

        const borrow = new Date(borrowVal);
        const ret = new Date(returnVal);
        const diffDays = Math.ceil((ret - borrow) / (1000 * 60 * 60 * 24));

        if (ret < borrow) {
            dateError.textContent = "Return date cannot be earlier than the borrow date.";
            dateError.style.display = 'block';
        } else if (diffDays > 61) {
            dateError.textContent = "Return date cannot exceed 2 months from the borrow date.";
            dateError.style.display = 'block';
        } else {
            dateError.style.display = 'none';
        }
    });

    confirmBtn.addEventListener('click', function () {
        if (!borrowDateInput.value || !returnDateInput.value) {
            alert("Please select both dates.");
            return;
        }
        if (!termsCheck.checked) {
            alert("Please agree to the terms.");
            return;
        }
        if (dateError.style.display === 'block') {
            alert("Please fix the date errors.");
            return;
        }

        notification.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });

        borrowForm.reset();
        confirmBtn.disabled = true;
        dateError.style.display = 'none';

        const modalInstance = bootstrap.Modal.getInstance(borrowModal);
        modalInstance.hide();
    });

});

const API_URL = 'http://localhost/IT-004-IT21S6---Web-Systems-and-Technologies---Group-4/database/';
let selectedBookId = null;
let selectedBookTitle = '';

// Helper function to hide/show the empty messages
function updateEmptyMessage(pane) {
    const message = pane.querySelector(".library-empty-state");
    if (!message) return;
    const rows = pane.querySelectorAll("tbody tr");
    message.style.display = rows.length === 0 ? "block" : "none";
}

async function loadUserBooks() {
    try {
        const res = await fetch(API_URL + 'get_books.php');
        const books = await res.json();

        // Clear existing rows
        document.querySelectorAll(".tab-pane tbody").forEach(tbody => tbody.innerHTML = "");

        books.forEach(book => {
            if (book.is_hidden == 1) return;

            const pane = document.getElementById(book.genre_id);
            if (pane) {
                const tbody = pane.querySelector("tbody");
                const tr = document.createElement("tr");

                // Simplified status logic (Ready for future copies update)
                const statusTag = book.status.toLowerCase().replace(/\s+/g, "");
                const statusBadge = `<span class='book-status book-status-${statusTag}'>${book.status}</span>`;

                let actionHtml = "";
                if (book.status === "Available") {
                    actionHtml = `<button class="btn btn-sm btn-success borrow-btn" data-id="${book.id}" data-bs-toggle="modal" data-bs-target="#borrowModal"><i class="bi bi-book"></i> Borrow</button>`;
                }

                tr.innerHTML = `
                    <td>${book.title}</td>
                    <td>${book.author}</td>
                    <td>${book.year}</td>
                    <td>${statusBadge}</td>
                    <td>${book.call_no}</td>
                    <td class="text-center">${actionHtml}</td>
                `;
                tbody.appendChild(tr);
            }
        });

        // Hide empty states for tabs that now have books (OUTSIDE THE LOOP)
        document.querySelectorAll(".tab-pane").forEach(updateEmptyMessage);

        // Attach click events to the new borrow buttons
        document.querySelectorAll('.borrow-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedBookId = this.getAttribute('data-id');
                selectedBookTitle = this.closest('tr').querySelector('td').textContent.trim();
            });
        });

    } catch (err) {
        console.error("Error loading books:", err);
    }
}

loadUserBooks();

document.getElementById('confirmBorrowBtn').addEventListener('click', async function () {
    const borrowDateInput = document.getElementById('borrowDate');
    const returnDateInput = document.getElementById('returnDate');
    const termsCheck = document.getElementById('termsCheck');
    const dateError = document.getElementById('dateError');
    const notification = document.getElementById('borrowNotification');
    const borrowForm = document.getElementById('borrowForm');
    const borrowModal = document.getElementById('borrowModal');

    if (!borrowDateInput.value || !returnDateInput.value || !termsCheck.checked || dateError.style.display === 'block') {
        return;
    }

    if (selectedBookId) {
        try {
            const borrowedOn = borrowDateInput.value;
            const dueOn = returnDateInput.value;
            const bookId = selectedBookId;
            const bookTitle = selectedBookTitle;

            const borrowRes = await fetch(API_URL + 'borrow_book.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    book_id: selectedBookId,
                    borrow_date: borrowDateInput.value,
                    return_date: returnDateInput.value
                })
            });

            const result = await borrowRes.json();

            if (result.success) {
                notification.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });

                borrowForm.reset();
                this.disabled = true;
                dateError.style.display = 'none';

                saveBorrowRecord(bookId, bookTitle, borrowedOn, dueOn);

                const modalInstance = bootstrap.Modal.getInstance(borrowModal);
                modalInstance.hide();

                loadUserBooks();
            } else {
                alert("Error: " + result.error);
            }
        } catch (err) {
            console.error(err);
            alert("Could not connect to the server.");
        }
    }
});

/* ---------- My Account modal (front-end only) ---------- */
const FINE_PER_DAY = 10;
const BORROW_STORE_KEY = 'bookersClubBorrows';

function loadBorrowRecords() {
    try {
        return JSON.parse(localStorage.getItem(BORROW_STORE_KEY)) || [];
    } catch (err) {
        return [];
    }
}

function storeBorrowRecords(records) {
    try {
        localStorage.setItem(BORROW_STORE_KEY, JSON.stringify(records));
    } catch (err) {
        console.error(err);
    }
}

function saveBorrowRecord(bookId, title, borrowDate, returnDate) {
    const records = loadBorrowRecords();
    records.push({
        book_id: String(bookId),
        title: title,
        borrow_date: borrowDate,
        return_date: returnDate,
        status: 'In Process',
        seenOut: false,
        closed: false,
        outcome: '',
        price: null
    });
    storeBorrowRecords(records);
}

async function syncBorrowRecords() {
    const records = loadBorrowRecords();
    if (!records.some(function (r) { return !r.closed; })) return records;

    try {
        const res = await fetch(API_URL + 'get_books.php');
        const books = await res.json();
        const byId = {};
        books.forEach(function (b) { byId[String(b.id)] = b; });

        records.forEach(function (r) {
            if (r.closed) return;
            const book = byId[r.book_id];
            if (!book) return;

            if (book.status === 'Available') {
                if (r.seenOut) {
                    r.closed = true;
                    r.outcome = 'Returned';
                }
            } else if (book.status === 'Damaged' || book.status === 'Missing') {
                r.closed = true;
                r.outcome = book.status;
                r.price = book.price != null ? Number(book.price) : null;
            } else {
                r.seenOut = true;
                r.status = book.status;
            }
        });
        storeBorrowRecords(records);
    } catch (err) {
        console.error(err);
    }
    return records;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : value;
    return div.innerHTML;
}

function formatPeso(amount) {
    return '\u20B1' + Number(amount || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function daysOverdue(returnDate) {
    if (!returnDate) return 0;
    const due = new Date(returnDate + 'T00:00:00');
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    return Math.max(0, Math.round((now - due) / 86400000));
}

function accountStatusClass(status) {
    switch ((status || '').toLowerCase()) {
        case 'returned': return 'book-status-available';
        case 'borrowed': return 'book-status-borrowed';
        case 'in process': return 'book-status-inprocess';
        case 'overdue':
        case 'missing': return 'book-status-missing';
        default: return 'book-status-damaged';
    }
}

function accountPill(status) {
    return '<span class="book-status ' + accountStatusClass(status) + '">' + escapeHtml(status) + '</span>';
}

function setAccountMessage(tbodyId, cols, message) {
    document.getElementById(tbodyId).innerHTML =
        '<tr><td colspan="' + cols + '" class="text-center fst-italic">' + message + '</td></tr>';
}

function renderAccount(records) {
    const current = records.filter(function (r) { return !r.closed; }).reverse();
    const history = records.filter(function (r) { return r.closed; }).reverse();

    if (!current.length) {
        setAccountMessage('currentBorrowsBody', 4, 'You have no borrowed books right now');
    } else {
        document.getElementById('currentBorrowsBody').innerHTML = current.map(function (r) {
            const status = daysOverdue(r.return_date) > 0 && r.seenOut ? 'Overdue' : r.status;
            return '<tr><td>' + escapeHtml(r.title) + '</td><td>' + escapeHtml(r.borrow_date) +
                '</td><td>' + escapeHtml(r.return_date) + '</td><td>' + accountPill(status) + '</td></tr>';
        }).join('');
    }

    if (!history.length) {
        setAccountMessage('historyBody', 4, 'No borrowing history yet');
    } else {
        document.getElementById('historyBody').innerHTML = history.map(function (r) {
            return '<tr><td>' + escapeHtml(r.title) + '</td><td>' + escapeHtml(r.borrow_date) +
                '</td><td>' + escapeHtml(r.return_date) + '</td><td>' + accountPill(r.outcome) + '</td></tr>';
        }).join('');
    }

    const liabilities = [];
    current.forEach(function (r) {
        const days = daysOverdue(r.return_date);
        if (r.seenOut && days > 0) {
            liabilities.push({
                title: r.title,
                reason: 'Late return (' + days + ' day' + (days === 1 ? '' : 's') + ' overdue)',
                amount: days * FINE_PER_DAY
            });
        }
    });
    history.forEach(function (r) {
        if (r.outcome === 'Damaged' || r.outcome === 'Missing') {
            liabilities.push({ title: r.title, reason: r.outcome + ' book (price of book)', amount: r.price });
        }
    });

    let total = 0;
    if (!liabilities.length) {
        setAccountMessage('liabilitiesBody', 3, 'No outstanding liabilities');
    } else {
        document.getElementById('liabilitiesBody').innerHTML = liabilities.map(function (l) {
            if (l.amount != null) total += l.amount;
            return '<tr><td>' + escapeHtml(l.title) + '</td><td>' + escapeHtml(l.reason) +
                '</td><td class="text-end">' + (l.amount != null ? formatPeso(l.amount) : 'Price not set') + '</td></tr>';
        }).join('');
    }
    document.getElementById('liabilitiesTotal').textContent = formatPeso(total);
}

const accountModal = document.getElementById('accountModal');
const accountViews = { account: 'accountView', history: 'historyView', liabilities: 'liabilitiesView' };
const accountTitles = { account: 'My Account', history: 'Borrowed History', liabilities: 'Liabilities' };
let pendingAccountView = 'account';

function showAccountView(name) {
    Object.keys(accountViews).forEach(function (key) {
        document.getElementById(accountViews[key]).classList.toggle('d-none', key !== name);
    });
    document.getElementById('accountViewTitle').textContent = accountTitles[name];
    document.querySelectorAll('.account-menu-item').forEach(function (item) {
        item.classList.toggle('active', item.dataset.view === name);
    });
}

document.querySelectorAll('[data-open-view]').forEach(function (item) {
    item.addEventListener('click', function () {
        pendingAccountView = this.dataset.openView;
        bootstrap.Modal.getOrCreateInstance(accountModal).show();
    });
});

document.querySelectorAll('.account-menu-item').forEach(function (item) {
    item.addEventListener('click', function () {
        showAccountView(this.dataset.view);
    });
});

accountModal.addEventListener('show.bs.modal', async function () {
    showAccountView(pendingAccountView);
    pendingAccountView = 'account';
    renderAccount(loadBorrowRecords());
    renderAccount(await syncBorrowRecords());
});
