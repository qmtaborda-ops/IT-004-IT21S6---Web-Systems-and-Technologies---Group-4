document.addEventListener('DOMContentLoaded', function () {
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
        returnDateInput.value = defaultReturn.toISOString().split('T')[0];
        returnDateInput.setAttribute('max', maxReturn.toISOString().split('T')[0]);
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
});

const API_URL = '../database/';
let selectedBookId = null;
let selectedBookTitle = '';
let bookCache = {};

function updateEmptyMessage(pane) {
    const message = pane.querySelector(".library-empty-state");
    if (!message) return;
    const rows = pane.querySelectorAll("tbody tr");
    message.style.display = rows.length === 0 ? "block" : "none";
}

let detailsBookId = null;

const greetingText = document.querySelector('.library-user-btn span').textContent;
const currentUserName = greetingText.replace('Have a good day, ', '').replace('.', '').trim();

document.addEventListener('click', function (event) {
    const link = event.target.closest('.book-details-link');
    if (!link) return;
    event.preventDefault();
    const book = bookCache[link.dataset.id];
    if (!book) return;
    detailsBookId = String(book.id);

    document.getElementById('detailsTitle').textContent = book.title;
    document.getElementById('detailsAuthor').textContent = book.author;
    document.getElementById('detailsYear').textContent = book.year;
    document.getElementById('detailsCall').textContent = book.call_no;
    document.getElementById('detailsDescription').textContent =
        book.description && book.description.trim() ? book.description : 'No description available for this book.';

    // THE FINAL LOGIC
    let displayStatus = 'Available';
    if (book.quantity <= 0) {
        displayStatus = 'Out of Stock';
    }
    if (book.borrower_name && book.borrower_name.includes(currentUserName)) {
        displayStatus = 'Borrowed';
    }

    const statusEl = document.getElementById('detailsStatus');
    statusEl.textContent = displayStatus;
    statusEl.className = 'book-status book-status-' + displayStatus.toLowerCase().replace(/\s+/g, '');

    const img = document.getElementById('detailsCover');
    const placeholder = document.getElementById('detailsNoCover');
    if (book.cover_hash) {
        img.src = API_URL + 'get_cover.php?id=' + book.id + '&v=' + book.cover_hash;
        img.style.display = 'inline-block';
        placeholder.style.display = 'none';
    } else {
        img.style.display = 'none';
        placeholder.style.display = 'flex';
    }

    document.getElementById('detailsBorrowWrap').style.display = (displayStatus === 'Available' && book.quantity > 0) ? '' : 'none';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('detailsModal')).show();
});

document.getElementById('detailsBorrowBtn').addEventListener('click', function () {
    const book = bookCache[detailsBookId];
    if (!book) return;
    selectedBookId = String(book.id);
    selectedBookTitle = book.title;
    
    const qtyInput = document.getElementById('borrowQuantity');
    if (qtyInput) {
        qtyInput.setAttribute('max', book.quantity);
        qtyInput.value = 1;
    }

    const detailsEl = document.getElementById('detailsModal');
    detailsEl.addEventListener('hidden.bs.modal', function openBorrow() {
        detailsEl.removeEventListener('hidden.bs.modal', openBorrow);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('borrowModal')).show();
    });
    bootstrap.Modal.getOrCreateInstance(detailsEl).hide();
});

async function loadUserBooks() {
    try {
        const res = await fetch(API_URL + 'get_books.php?t=' + new Date().getTime());
        const books = await res.json();

        document.querySelectorAll(".tab-pane tbody").forEach(tbody => tbody.innerHTML = "");
        bookCache = {}; 

        books.forEach(book => {
            bookCache[String(book.id)] = book;
            if (book.is_hidden == 1) return;

            const pane = document.getElementById(book.genre_id);
            if (pane) {
                const tbody = pane.querySelector("tbody");
                const tr = document.createElement("tr");

                // THE FINAL LOGIC
                let displayStatus = 'Available';
                if (book.quantity <= 0) {
                    displayStatus = 'Out of Stock';
                }
                if (book.borrower_name && book.borrower_name.includes(currentUserName)) {
                    displayStatus = 'Borrowed';
                }

                const statusTag = displayStatus.toLowerCase().replace(/\s+/g, "");
                const statusBadge = `<span class='book-status book-status-${statusTag}'>${displayStatus}</span>`;

                let actionHtml = "";
                if (displayStatus === "Available" && book.quantity > 0) {
                    actionHtml = `<button class="btn btn-sm btn-success borrow-btn" data-id="${book.id}" data-max="${book.quantity}" data-bs-toggle="modal" data-bs-target="#borrowModal"><i class="bi bi-book"></i> Borrow</button>`;
                }

                const thumb = book.cover_hash
                    ? `<img src="${API_URL}get_cover.php?id=${book.id}&v=${book.cover_hash}" alt="" style="height:40px;width:28px;object-fit:cover;border-radius:3px;margin-right:8px;vertical-align:middle;">`
                    : '';

                tr.innerHTML = `
                    <td>${thumb}<a href="#" class="book-details-link" data-id="${book.id}" style="color:inherit;font-weight:600;text-decoration:underline dotted;">${book.title}</a></td>
                    <td>${book.author}</td>
                    <td>${book.year}</td>
                    <td>${book.quantity || 0}</td>
                    <td>${statusBadge}</td>
                    <td>${book.call_no}</td>
                    <td class="text-center">${actionHtml}</td>
                `;
                tbody.appendChild(tr);
            }
        });

        document.querySelectorAll(".tab-pane").forEach(updateEmptyMessage);

        document.querySelectorAll('.borrow-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedBookId = this.getAttribute('data-id');
                selectedBookTitle = this.closest('tr').querySelector('td').textContent.trim();
                const maxQty = this.getAttribute('data-max');
                const qtyInput = document.getElementById('borrowQuantity');
                if (qtyInput) {
                    qtyInput.setAttribute('max', maxQty);
                    qtyInput.value = 1;
                }
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
    const borrowQuantityInput = document.getElementById('borrowQuantity');
    const termsCheck = document.getElementById('termsCheck');
    const dateError = document.getElementById('dateError');
    const notification = document.getElementById('borrowNotification');
    const borrowForm = document.getElementById('borrowForm');
    const borrowModal = document.getElementById('borrowModal');

    if (!borrowDateInput.value || !returnDateInput.value || !termsCheck.checked || dateError.style.display === 'block') return;

    if (selectedBookId) {
        try {
            const qty = borrowQuantityInput ? borrowQuantityInput.value : 1;

            const borrowRes = await fetch(API_URL + 'borrow_book.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    book_id: selectedBookId,
                    quantity: qty,
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

/* ---------- My Account & History Modals (Database Driven) ---------- */
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

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : value;
    return div.innerHTML;
}

function accountStatusClass(status) {
    switch ((status || '').toLowerCase()) {
        case 'returned': 
        case 'available': return 'book-status-available';
        case 'borrowed': 
        case 'approved': return 'book-status-borrowed';
        case 'in process': 
        case 'pending': return 'book-status-inprocess';
        case 'out of stock':
        case 'overdue':
        case 'missing': return 'book-status-missing';
        default: return 'book-status-damaged';
    }
}

function accountPill(status) {
    return '<span class="book-status ' + accountStatusClass(status) + '">' + escapeHtml(status) + '</span>';
}

async function loadUserHistory() {
    try {
        const res = await fetch(API_URL + 'get_user_history.php?t=' + new Date().getTime(), { credentials: 'include' });
        const records = await res.json();

        const currentTbody = document.getElementById('currentBorrowsBody');
        const historyTbody = document.getElementById('historyBody');

        if (!Array.isArray(records) || records.length === 0) {
            currentTbody.innerHTML = '<tr><td colspan="4" class="text-center fst-italic">No borrowed books found.</td></tr>';
            historyTbody.innerHTML = '<tr><td colspan="4" class="text-center fst-italic">No history found.</td></tr>';
            return;
        }

        const current = records.filter(r => r.status === 'Pending' || r.status === 'Approved');
        const past = records.filter(r => r.status === 'Returned');

        if (current.length === 0) {
            currentTbody.innerHTML = '<tr><td colspan="4" class="text-center fst-italic">You have no borrowed books right now.</td></tr>';
        } else {
            currentTbody.innerHTML = current.map(r => `
                <tr>
                    <td>${escapeHtml(r.title)}</td>
                    <td>${escapeHtml(r.borrow_date)}</td>
                    <td>${escapeHtml(r.return_date)}</td>
                    <td>${accountPill(r.status)}</td>
                </tr>
            `).join('');
        }

        if (past.length === 0) {
            historyTbody.innerHTML = '<tr><td colspan="4" class="text-center fst-italic">No past history yet.</td></tr>';
        } else {
            historyTbody.innerHTML = past.map(r => `
                <tr>
                    <td>${escapeHtml(r.title)}</td>
                    <td>${escapeHtml(r.borrow_date)}</td>
                    <td>${escapeHtml(r.return_date)}</td>
                    <td>${accountPill(r.status)}</td>
                </tr>
            `).join('');
        }
    } catch (err) {
        console.error("Error loading history:", err);
    }
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

accountModal.addEventListener('show.bs.modal', function () {
    showAccountView(pendingAccountView);
    pendingAccountView = 'account';
    loadUserHistory();
});