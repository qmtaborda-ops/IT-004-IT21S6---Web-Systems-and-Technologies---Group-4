<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../landing-page/landing.html");
    exit;
}
$firstName = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booker's Club · Browse the Library</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=EB+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
        <link href="userStyles.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    </head>
    <body>
        <!--  HEADER  -->
        <div class="library-catalog-header">
            <div class="d-flex align-items-center gap-3">
                <img src="../images/CompanyLogo.png" alt="Company Logo" class="library-logo">
                <div>
                    <h1>Booker's Club</h1>
                    <span class="library-role-badge">Browse our collection</span>
                </div>
            </div>
            <div class="library-user-tools">
                <div class="library-user-greeting dropdown">
                    <button type="button" class="library-user-btn" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account menu">
                        <span>Have a good day, <?php echo htmlspecialchars($firstName); ?>.</span>
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-menu" aria-labelledby="userMenuBtn">
                        <li><button type="button" class="dropdown-item" data-open-view="account"><i class="bi bi-person-circle me-2"></i>My Account</button></li>
                        <li><button type="button" class="dropdown-item" data-open-view="history"><i class="bi bi-clock-history me-2"></i>Borrowed History</button></li>
                        <li><button type="button" class="dropdown-item" data-open-view="liabilities"><i class="bi bi-cash-coin me-2"></i>Liabilities</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../database/log_out.php"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
                    </ul>
                </div>
                <div class="library-user-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="bookSearch" placeholder="Search by title, author, or call number..." aria-label="Search books">
                </div>
            </div>
        </div>

        <!--  GENRE TABS  -->
        <ul class="nav nav-tabs" id="genreTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="scifi-tab" data-bs-toggle="tab" data-bs-target="#scifi" type="button" role="tab">Sci-Fi & Cyberpunk</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fantasy-tab" data-bs-toggle="tab" data-bs-target="#fantasy" type="button" role="tab">Fantasy & Supernatural</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="action-tab" data-bs-toggle="tab" data-bs-target="#action" type="button" role="tab">Action, Adventure & Martial Arts</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="romance-tab" data-bs-toggle="tab" data-bs-target="#romance" type="button" role="tab">Romance & Drama</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="mystery-tab" data-bs-toggle="tab" data-bs-target="#mystery" type="button" role="tab">Mystery, Horror & Psychological</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="academics-tab" data-bs-toggle="tab" data-bs-target="#academics" type="button" role="tab">Academics, Non-Fiction & Skill Building</button>
            </li>
        </ul>

        <!--  TAB CONTENT  -->
        <div class="tab-content" id="genreTabsContent">
            <!-- 1. Sci-Fi & Cyberpunk -->
            <div class="tab-pane fade show active" id="scifi" role="tabpanel" aria-labelledby="scifi-tab">
                <div class="library-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Year</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Book No.</th>
                                <th class="text-center">Borrow</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                     <div class="library-empty-state">
                        <i class="bi bi-journal-x"></i>
                        <p>No titles in this genre yet</p>
                        <span>Check back soon for new arrivals</span>
                    </div>
                </div>
            </div>

            <!-- 2. Fantasy & Supernatural -->
            <div class="tab-pane fade" id="fantasy" role="tabpanel" aria-labelledby="fantasy-tab">
                <div class="library-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Year</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Book No.</th>
                                <th class="text-center">Borrow</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="library-empty-state">
                        <i class="bi bi-journal-x"></i>
                        <p>No titles in this genre yet</p>
                        <span>Check back soon for new arrivals</span>
                    </div>
                </div>
            </div>

            <!-- 3. Action, Adventure & Martial Arts -->
            <div class="tab-pane fade" id="action" role="tabpanel" aria-labelledby="action-tab">
                <div class="library-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Year</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Book No.</th>
                                <th class="text-center">Borrow</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="library-empty-state">
                        <i class="bi bi-journal-x"></i>
                        <p>No titles in this genre yet</p>
                        <span>Check back soon for new arrivals</span>
                    </div>
                </div>
            </div>

            <!-- 4. Romance & Drama -->
            <div class="tab-pane fade" id="romance" role="tabpanel" aria-labelledby="romance-tab">
                <div class="library-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Year</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Book No.</th>
                                <th class="text-center">Borrow</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="library-empty-state">
                        <i class="bi bi-journal-x"></i>
                        <p>No titles in this genre yet</p>
                        <span>Check back soon for new arrivals</span>
                    </div>
                </div>
            </div>

            <!-- 5. Mystery, Horror & Psychological -->
            <div class="tab-pane fade" id="mystery" role="tabpanel" aria-labelledby="mystery-tab">
                <div class="library-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Year</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Book No.</th>
                                <th class="text-center">Borrow</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="library-empty-state">
                        <i class="bi bi-journal-x"></i>
                        <p>No titles in this genre yet</p>
                        <span>Check back soon for new arrivals</span>
                    </div>
                </div>
            </div>

            <!-- 6. Academics, Non-Fiction & Skill Building -->
            <div class="tab-pane fade" id="academics" role="tabpanel" aria-labelledby="academics-tab">
                <div class="library-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Year</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Book No.</th>
                                <th class="text-center">Borrow</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="library-empty-state">
                        <i class="bi bi-journal-x"></i>
                        <p>No titles in this genre yet</p>
                        <span>Check back soon for new arrivals</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!--  FOOTER  -->
        <div class="library-footer">
            <span>Booker's Club · Group 4 prototype</span>
        </div>

        <!-- Book details modal (Missing from your code!) -->
        <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Book details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-5 text-center">
                                <img id="detailsCover" alt="Book cover" style="display:none; max-width:100%; max-height:420px; border-radius:6px;">
                                <div id="detailsNoCover" class="text-muted border rounded" style="display:none; height:320px; align-items:center; justify-content:center; flex-direction:column;">
                                    <i class="bi bi-book" style="font-size:3rem;"></i>
                                    <span>No cover image</span>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <span id="detailsStatus" class="book-status"></span>
                                <h3 id="detailsTitle" class="mt-2 mb-1"></h3>
                                <p class="mb-3">by <span id="detailsAuthor"></span></p>
                                <h6 class="text-uppercase" style="letter-spacing:.06em;">Overview</h6>
                                <p id="detailsDescription" style="white-space:pre-line;"></p>
                                <hr>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted text-uppercase">Year</small>
                                        <div id="detailsYear"></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted text-uppercase">Book No.</small>
                                        <div id="detailsCall"></div>
                                    </div>
                                </div>
                                <div id="detailsBorrowWrap" class="mt-4" style="display:none;">
                                    <button type="button" class="btn btn-success" id="detailsBorrowBtn"><i class="bi bi-book"></i> Borrow this book</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borrow Modal -->
        <div class="modal fade" id="borrowModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="dialog" aria-labelledby="borrowModalLabel" aria-modal="true">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="borrowModalLabel">Borrow Book</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="borrowForm">
                            <div class="mb-3">
                                <label for="borrowQuantity" class="form-label">Quantity to Borrow</label>
                                <input type="number" class="form-control" id="borrowQuantity" min="1" value="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="borrowDate" class="form-label">Borrow Date</label>
                                <input type="date" class="form-control" id="borrowDate" required>
                            </div>
                            <div class="mb-3">
                                <label for="returnDate" class="form-label">Return Date (Max 2 months)</label>
                                <input type="date" class="form-control" id="returnDate" required>
                                <div id="dateError" class="text-danger mt-1" style="display:none; font-size: 0.875em;">
                                    Return date cannot exceed 2 months from the borrow date.
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="termsCheck" required>
                                <label class="form-check-label" for="termsCheck">
                                    I have read the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a> of borrowing a book.
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirmBorrowBtn" disabled>Confirm Borrow</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="borrowNotification" class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
            <i class="bi bi-check-circle-fill me-2"></i> <strong>Borrow request sent successfully!</strong> Please go to the librarian for confirmation.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Terms Modal -->
        <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable" role="dialog" aria-labelledby="termsModalLabel" aria-modal="true">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="termsModalLabel">Terms and Conditions</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>By submitting a borrow request, you agree to the following conditions:</p>
                        <ol>
                            <li class="mb-2">A borrow request must include a borrow date and a return date.</li>
                            <li class="mb-2">The return date must not be earlier than the borrow date and must be within two months of it.</li>
                            <li class="mb-2">A request is subject to librarian confirmation.</li>
                            <li class="mb-2">Books must be returned by the agreed return date. Late returns incur a fine of 1% of the book price per overdue day, capped at the book price.</li>
                            <li class="mb-2">The borrower is responsible for the book while it is in their possession.</li>
                            <li>Damaged or missing books incur a liability equal to the listed price of the book.</li>
                        </ol>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Modal -->
        <div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="dropdown me-3">
                            <button class="btn account-menu-btn" type="button" id="accountMenuBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open menu">
                                <i class="bi bi-list"></i>
                            </button>
                            <ul class="dropdown-menu account-menu" aria-labelledby="accountMenuBtn">
                                <li><button type="button" class="dropdown-item account-menu-item active" data-view="account"><i class="bi bi-person-circle me-2"></i>My Account</button></li>
                                <li><button type="button" class="dropdown-item account-menu-item" data-view="history"><i class="bi bi-clock-history me-2"></i>Borrowed History</button></li>
                                <li><button type="button" class="dropdown-item account-menu-item" data-view="liabilities"><i class="bi bi-cash-coin me-2"></i>Liabilities</button></li>
                            </ul>
                        </div>
                        <h2 class="modal-title fs-5" id="accountModalLabel"><span id="accountViewTitle">My Account</span></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body account-modal-body">
                        <div id="accountView">
                            <h3 class="account-section-title">Currently Borrowing Books</h3>
                            <div class="library-table-wrap">
                                <table class="table">
                                    <thead>
                                        <tr><th>Title</th><th>Borrowed</th><th>Due</th><th>Status</th></tr>
                                    </thead>
                                    <tbody id="currentBorrowsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="historyView" class="d-none">
                            <h3 class="account-section-title">Borrowed History</h3>
                            <div class="library-table-wrap">
                                <table class="table">
                                    <thead>
                                        <tr><th>Title</th><th>Borrowed</th><th>Due</th><th>Result</th></tr>
                                    </thead>
                                    <tbody id="historyBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="liabilitiesView" class="d-none">
                            <h3 class="account-section-title">Liabilities</h3>
                            <p class="account-note">Late returns cost 1% of the book price per overdue day, capped at the book price. Damaged or missing books cost the full listed price.</p>
                            <div class="library-table-wrap">
                                <table class="table">
                                    <thead>
                                        <tr><th>Title</th><th>Reason</th><th class="text-end">Amount</th></tr>
                                    </thead>
                                    <tbody id="liabilitiesBody"></tbody>
                                    <tfoot>
                                        <tr><th colspan="2" class="text-end">Total Due</th><th class="text-end" id="liabilitiesTotal">&#8369;0.00</th></tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" class="btn btn-success" id="payLiabilityBtn" disabled>
                                    <i class="bi bi-wallet2 me-1"></i>Pay liability
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <a href="../database/log_out.php" class="btn btn-success"><i class="bi bi-box-arrow-right me-1"></i>Log Out</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" id="paymentForm">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="paymentModalLabel">Pay liability</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Amount to pay: <strong id="paymentAmount">&#8369;0.00</strong></p>
                        <label class="form-label" for="paymentMethod">Payment method</label>
                        <select class="form-select mb-3" id="paymentMethod" required>
                            <option value="cash">Cash at the library</option>
                            <option value="gcash">GCash</option>
                            <option value="card">Card</option>
                        </select>
                        <div id="cashPaymentInstructions" class="alert alert-secondary">
                            Present your account name and this payment request to the librarian. Pay at the library counter.
                        </div>
                        <div id="gcashPaymentInstructions" class="alert alert-info d-none">
                            Send the exact amount to the library's official GCash account, then enter the reference number below. Do not send money to an unverified number.
                        </div>
                        <div id="cardPaymentInstructions" class="alert alert-warning d-none">
                            Card processing is not connected yet. This submits a card-payment request only; never enter your card number or CVV here.
                        </div>
                        <div id="gcashReferenceWrap" class="mb-3 d-none">
                            <label class="form-label" for="gcashReference">GCash reference number</label>
                            <input class="form-control" id="gcashReference" inputmode="numeric" pattern="[0-9]{13}" minlength="13" maxlength="13" autocomplete="off">
                            <div class="form-text">Enter exactly 13 digits.</div>
                        </div>
                        <div id="cardDetailsWrap" class="d-none">
                            <label class="form-label" for="cardReference">Card payment reference</label>
                            <input class="form-control mb-2" id="cardReference" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off">
                            <div class="form-text mb-3">Enter the exact 16-digit reference provided by the card payment gateway. Never enter a card number or CVV here.</div>
                        </div>
                        <label class="form-label" for="paymentNotes">Notes (optional)</label>
                        <textarea class="form-control" id="paymentNotes" maxlength="500" rows="2" placeholder="Transaction details for the librarian"></textarea>
                        <div id="paymentMessage" class="small mt-3" role="status"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Submit payment request</button>
                    </div>
                </form>
            </div>
        </div>
        
         <script src="../search.js?v=18" defer></script>
        <script src="users.js?v=18"></script>
    </body>
</html>
