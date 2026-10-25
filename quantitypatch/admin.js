const API_URL = 'http://localhost/IT-004-IT21S6---Web-Systems-and-Technologies---Group-4/database/';
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

function setStatus(row, status) {
  const tag = status.toLowerCase().replace(/\s+/g, "");
  // This must be row.cells[4], NOT row.cells[3]
  row.cells[4].innerHTML = "<span class='book-status book-status-" + tag + "'>" + status + "</span>";
}

function makeRow(book) {
  const row = document.createElement("tr");
  row.dataset.id = book.id;
  
  if (book.is_hidden == 1) row.classList.add("row-hidden");

  // Note: There are exactly 6 empty <td> tags here now, not 5
  row.innerHTML =
    "<td></td><td></td><td></td><td></td><td></td><td></td>" +
    "<td><span class='book-borrower-empty'>" + (book.borrower_name || "-") + "</span></td>" +
    "<td class='text-end book-actions'>" +
    `<i class='bi ${book.is_hidden == 1 ? "bi-eye-slash" : "bi-eye"}'></i>` +
    "<i class='bi bi-pencil-square'></i><i class='bi bi-trash3'></i>" +
    "</td>";
    
  row.cells[0].textContent = book.title;
  row.cells[1].textContent = book.author;
  row.cells[2].textContent = book.year;
  row.cells[3].textContent = book.quantity || 0; // Qty goes to index 3
  setStatus(row, book.status);                   // Status goes to index 4 (via setStatus)
  row.cells[5].textContent = book.call_no;       // Call No goes to index 5
  
  return row;
}


async function loadBooks() {
  try {
    const res = await fetch(API_URL + 'get_books.php');
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

  const payload = {
    id: editingRow ? editingRow.dataset.id : null,
    title: get("addTitle").value.trim(),
    author: get("addAuthor").value.trim(),
    year: get("addYear").value.trim(),
    quantity: get("addQuantity").value,
    call_no: get("addCall").value.trim(),
    status: get("addStatus").value,
    genre: get("addGenre").value
  };

  try {
    await fetch(API_URL + 'save_book.php', {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    
    hideModal();
    loadBooks();
    bootstrap.Tab.getOrCreateInstance(get(payload.genre + "-tab")).show();
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
  showModal();
}

function openEditForm(icon) {
  editingRow = icon.closest("tr");
  const cells = editingRow.cells;
  get("addTitle").value = cells[0].textContent;
  get("addAuthor").value = cells[1].textContent;
  get("addYear").value = cells[2].textContent;
  get("addQuantity").value = cells[3].textContent;
  get("addStatus").value = cells[4].textContent.trim();
  get("addCall").value = cells[5].textContent;
  get("addGenre").value = icon.closest(".tab-pane").id;
  get("addModalTitle").textContent = "Edit book";
  showModal();
}