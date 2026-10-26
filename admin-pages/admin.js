const API_URL = '../database/';
let currentPane = null;   
let editingRow = null;   

function get(id) {
  return document.getElementById(id);
}

function escapeHtml(value) {
  const div = document.createElement("div");
  div.textContent = value == null ? "" : String(value);
  return div.innerHTML;
}

function showModal() {
  bootstrap.Modal.getOrCreateInstance(get("addModal")).show();
}

function hideModal() {
  bootstrap.Modal.getOrCreateInstance(get("addModal")).hide();
}

function coverUrl(book) {
  return API_URL + "get_cover.php?id=" + book.id + "&v=" + book.cover_hash;
}

function setStatus(row, status) {
  const tag = status.toLowerCase().replace(/\s+/g, "");
  row.cells[4].innerHTML = "<span class='book-status book-status-" + tag + "'>" + status + "</span>";
}

function updateEmptyMessage(pane) {
  const message = pane.querySelector(".book-empty-state");
  if (!message) return;
  const rows = pane.querySelectorAll("tbody tr");
  message.style.display = rows.length === 0 ? "" : "none";
}

function makeRow(book) {
  const row = document.createElement("tr");
  row.dataset.id = book.id; 
  row.dataset.description = book.description || "";
  row.dataset.cover = book.cover_hash ? coverUrl(book) : "";
  row.dataset.price = book.price || "0";
  
  if (book.is_hidden == 1) row.classList.add("row-hidden");

  let pendingBadge = "";
  if (book.pending_count && parseInt(book.pending_count) > 0) {
      pendingBadge = `<span class="badge bg-warning text-dark ms-2 manage-req-btn" data-id="${book.id}" style="cursor:pointer;">${book.pending_count} Pending</span>`;
  }

  // Make the borrower name clickable
  let borrowerHtml = "-";
  if (book.borrower_name) {
      borrowerHtml = `<a href="#" class="manage-req-btn text-decoration-none text-dark fw-bold" data-id="${book.id}">${book.borrower_name}</a>`;
  }

  row.innerHTML =
    "<td></td><td></td><td></td><td></td><td></td><td></td>" +
    "<td><span class='book-borrower-empty'>" + borrowerHtml + pendingBadge + "</span></td>" +
    "<td class='text-end book-actions'>" +
    `<i class='bi ${book.is_hidden == 1 ? "bi-eye-slash" : "bi-eye"}'></i>` +
    "<i class='bi bi-pencil-square'></i><i class='bi bi-trash3'></i>" +
    "</td>";
    
  if (book.cover_hash) {
    const thumb = document.createElement("img");
    thumb.src = coverUrl(book);
    thumb.alt = "";
    thumb.style.cssText = "height:36px;width:26px;object-fit:cover;border-radius:3px;margin-right:8px;vertical-align:middle;";
    row.cells[0].appendChild(thumb);
  }
  row.cells[0].appendChild(document.createTextNode(book.title));
  row.cells[1].textContent = book.author;
  row.cells[2].textContent = book.year;
  row.cells[3].textContent = book.quantity || 0;
  setStatus(row, book.status);
  row.cells[5].textContent = book.call_no;
  
  return row;
}

async function loadBooks() {
  try {
    const res = await fetch(API_URL + 'get_books.php?t=' + new Date().getTime());
    const books = await res.json();

    document.querySelectorAll(".tab-pane tbody").forEach(tbody => tbody.innerHTML = "");

    books.forEach(book => {
      const pane = get(book.genre_id);
      if (pane) {
        pane.querySelector("tbody").appendChild(makeRow(book));
      }
    });

    document.querySelectorAll(".tab-pane").forEach(updateEmptyMessage);
  } catch (err) {
    console.error("Failed to load books:", err);
  }
}

async function saveBook(event) {
  event.preventDefault();
  const form = new FormData();
  if (editingRow) form.append("id", editingRow.dataset.id);
  form.append("title", get("addTitle").value.trim());
  form.append("author", get("addAuthor").value.trim());
  form.append("year", get("addYear").value.trim());
  form.append("quantity", get("addQuantity").value);
  form.append("price", get("addPrice").value);
  form.append("call_no", get("addCall").value.trim());
  form.append("status", get("addStatus").value);
  form.append("genre", get("addGenre").value);
  form.append("description", get("addDescription").value.trim());
  form.append("remove_cover", get("removeCover").checked ? "1" : "0");
  
  const file = get("addCover").files[0];
  if (file) form.append("cover", file);

  const genre = get("addGenre").value;

  try {
    const res = await fetch(API_URL + 'save_book.php', { method: "POST", body: form });
    const result = await res.json();
    
    if (!result.success) {
      alert("Could not save: " + (result.error || "Unknown error"));
      return;
    }

    hideModal();
    loadBooks(); 
    bootstrap.Tab.getOrCreateInstance(get(genre + "-tab")).show();
  } catch (err) {
    console.error("Failed to save book:", err);
  }
}

async function deleteRow(icon) {
  const row = icon.closest("tr");
  const id = row.dataset.id;

  if (confirm("Delete \"" + row.cells[0].textContent + "\"?")) {
    try {
      const res = await fetch(API_URL + 'delete_book.php', {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id })
      });
      const result = await res.json();

      if (result.success) {
        loadBooks();
      } else {
        alert("Could not delete: " + (result.error || "Unknown error"));
      }
    } catch (err) {
      console.error("Failed to delete book:", err);
      alert("Could not reach the server.");
    }
  }
}

async function openPaymentRequestsModal() {
  const modalElement = get("paymentRequestsModal");
  const body = get("paymentRequestsBody");
  bootstrap.Modal.getOrCreateInstance(modalElement).show();
  body.innerHTML = "<p class='text-center fst-italic'>Loading payment requests...</p>";
  try {
    const response = await fetch(API_URL + "get_payment_requests.php", { credentials: "include" });
    const payments = await response.json();
    if (!Array.isArray(payments) || payments.length === 0) {
      body.innerHTML = "<p class='text-center fst-italic'>No payment requests.</p>";
      return;
    }
    body.innerHTML = payments.map(function (payment) {
      const actions = payment.status === "Pending"
        ? "<button class='btn btn-sm btn-success payment-action' data-id='" + payment.id + "' data-status='Paid'>Mark paid</button> " +
          "<button class='btn btn-sm btn-outline-danger payment-action' data-id='" + payment.id + "' data-status='Rejected'>Reject</button>"
        : "<span class='badge bg-secondary'>" + escapeHtml(payment.status) + "</span>";
      return "<div class='border-bottom py-2'><strong>" + escapeHtml(payment.user_name) + "</strong> (" + escapeHtml(payment.email) + ")" +
        "<br><span>" + escapeHtml(payment.method.toUpperCase()) + " · &#8369;" + Number(payment.amount).toFixed(2) + "</span>" +
        (payment.gcash_reference ? "<br><small>GCash ref: " + escapeHtml(payment.gcash_reference) + "</small>" : "") +
        (payment.card_last4 ? "<br><small>Card ending in: ****" + escapeHtml(payment.card_last4) + "</small>" : "") +
        (payment.notes ? "<br><small>" + escapeHtml(payment.notes) + "</small>" : "") +
        "<div class='mt-2'>" + actions + "</div></div>";
    }).join("");
    body.querySelectorAll(".payment-action").forEach(function (button) {
      button.addEventListener("click", async function () {
        const response = await fetch(API_URL + "manage_payment.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "include",
          body: JSON.stringify({ payment_id: this.dataset.id, status: this.dataset.status })
        });
        const result = await response.json();
        if (!result.success) {
          alert(result.error || "Could not update payment.");
          return;
        }
        openPaymentRequestsModal();
      });
    });
  } catch (error) {
    body.innerHTML = "<p class='text-danger'>" + escapeHtml(error.message) + "</p>";
  }
}

// MANAGE REQUESTS MODAL
async function openRequestsModal(bookId) {
    const requestsModalEl = document.getElementById('requestsModal');
    if (!requestsModalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(requestsModalEl);
    const body = document.getElementById('requestsModalBody');
    body.innerHTML = '<p class="text-center fst-italic">Loading requests...</p>';
    modal.show();

    try {
        const res = await fetch(API_URL + 'get_book_requests.php?book_id=' + bookId, { credentials: 'include' });
        
        if (!res.ok) throw new Error("Could not fetch requests. Make sure database/get_book_requests.php exists.");
        
        const requests = await res.json();
        
        if (requests.length === 0) {
            body.innerHTML = '<p class="text-center fst-italic">No requests found.</p>';
            return;
        }

        body.innerHTML = requests.map(r => `
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <strong>${r.user_name}</strong><br>
                    <small>Requested: ${r.borrow_date} to ${r.return_date}</small>
                </div>
                ${r.status === 'Pending' ? 
                    `<button class="btn btn-sm btn-success approve-btn" data-id="${r.id}">Approve</button>` : 
                    `<button class="btn btn-sm btn-outline-danger return-btn" data-id="${r.id}">Return</button>`
                }
            </div>
        `).join('');

        // Approve Button
        body.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const reqId = this.getAttribute('data-id');
                this.innerHTML = 'Approving...';
                try {
                    const apRes = await fetch(API_URL + 'manage_request.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: reqId, action: 'approve' })
                    });
                    const apData = await apRes.json();
                    if (!apRes.ok) {
                        throw new Error(apData.error || `Request failed (HTTP ${apRes.status}).`);
                    }
                    if (apData.success) {
                        this.outerHTML = `<button class="btn btn-sm btn-outline-danger return-btn" data-id="${reqId}">Return</button>`;
                        loadBooks(); 
                    } else {
                        alert("Error: " + apData.error);
                    }
                } catch (e) {
                    alert("Could not process the approval: " + e.message);
                }
            });
        });

        // Return Button
        body.querySelectorAll('.return-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const reqId = this.getAttribute('data-id');
            const itemCondition = prompt('Condition of this specific returned copy: Good, Damaged, or Missing', 'Good');
            if (!itemCondition || !['Good', 'Damaged', 'Missing'].includes(itemCondition)) {
                alert('Enter exactly Good, Damaged, or Missing.');
                return;
            }
            this.innerHTML = 'Returning...';
                try {
                    const retRes = await fetch(API_URL + 'manage_request.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: reqId, action: 'return', item_condition: itemCondition })
                    });
                    const retData = await retRes.json();
                    if (!retRes.ok) {
                        throw new Error(retData.error || `Request failed (HTTP ${retRes.status}).`);
                    }
                    if (retData.success) {
                        this.outerHTML = '<span class="badge bg-secondary">Returned</span>';
                        loadBooks(); 
                    } else {
                        alert("Error: " + retData.error);
                    }
                } catch (e) {
                    alert("Could not process the return: " + e.message);
                }
            });
        });

    } catch (err) {
        console.error(err);
        body.innerHTML = `<p class="text-danger text-center">${err.message}</p>`;
    }
}

get("addForm").addEventListener("submit", saveBook);
get("paymentRequestsBtn").addEventListener("click", openPaymentRequestsModal);
document.addEventListener("DOMContentLoaded", loadBooks);

document.addEventListener("click", function (event) {
  const clicked = event.target;
  if (clicked.classList.contains("add-btn")) {
    openAddForm(clicked);
  } else if (clicked.classList.contains("bi-pencil-square")) {
    openEditForm(clicked);
  } else if (clicked.classList.contains("bi-trash3")) {
    deleteRow(clicked);
  } else if (clicked.classList.contains("manage-req-btn")) {
    // This now triggers when clicking the yellow badge OR the borrower name
    const bookId = clicked.getAttribute("data-id");
    openRequestsModal(bookId);
  }
});

function openAddForm(button) {
  currentPane = button.closest(".tab-pane");
  editingRow = null;
  get("addModalTitle").textContent = "Add book";
  get("addForm").reset();
  get("addGenre").value = currentPane.id;
  showCoverPreview("");
  showModal();
}

function openEditForm(icon) {
  editingRow = icon.closest("tr");
  const cells = editingRow.cells;
  get("addTitle").value = cells[0].textContent.trim();
  get("addAuthor").value = cells[1].textContent;
  get("addYear").value = cells[2].textContent;
  get("addQuantity").value = cells[3].textContent;
  get("addPrice").value = editingRow.dataset.price || "0";
  get("addStatus").value = cells[4].textContent.trim();
  get("addCall").value = cells[5].textContent;
  get("addGenre").value = icon.closest(".tab-pane").id;
  get("addDescription").value = editingRow.dataset.description || "";
  get("addCover").value = "";
  showCoverPreview(editingRow.dataset.cover || "", true);
  get("addModalTitle").textContent = "Edit book";
  showModal();
}

function showCoverPreview(src, isExisting) {
  get("coverPreviewWrap").style.display = src ? "" : "none";
  get("coverPreview").src = src || "";
  get("removeCoverWrap").style.display = (src && isExisting) ? "" : "none";
  get("removeCover").checked = false;
}

get("addCover").addEventListener("change", function () {
  const file = this.files[0];
  if (!file) { showCoverPreview(editingRow && editingRow.dataset.cover ? editingRow.dataset.cover : "", !!editingRow); return; }
  if (file.size > 800 * 1024) {
    alert("Image is too large (max 800KB).");
    this.value = "";
    return;
  }
  showCoverPreview(URL.createObjectURL(file), false);
});
