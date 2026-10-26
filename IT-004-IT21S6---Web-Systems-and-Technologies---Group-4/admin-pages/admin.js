const API_URL = '../database/';
let currentPane = null;   
let editingRow = null;   

function get(id) {
  return document.getElementById(id);
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
  
  if (book.is_hidden == 1) row.classList.add("row-hidden");

  row.innerHTML =
    "<td></td><td></td><td></td><td></td><td></td><td></td>" +
    "<td><span class='book-borrower-empty'>" + (book.borrower_name || "-") + "</span></td>" +
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
  row.cells[3].textContent = book.quantity || 0; // Qty
  
  setStatus(row, book.status); // Show DB status directly. NO OVERRIDES HERE.
  
  row.cells[5].textContent = book.call_no; // Call No
  
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

get("addForm").addEventListener("submit", saveBook);
document.addEventListener("DOMContentLoaded", loadBooks);

document.addEventListener("click", function (event) {
  const clicked = event.target;
  if (clicked.classList.contains("add-btn")) {
    openAddForm(clicked);
  } else if (clicked.classList.contains("bi-pencil-square")) {
    openEditForm(clicked);
  } else if (clicked.classList.contains("bi-trash3")) {
    deleteRow(clicked);
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