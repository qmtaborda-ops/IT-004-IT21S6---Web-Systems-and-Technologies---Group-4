# IT-004-IT21S6---Web-Systems-and-Technologies---Group-4

# Book Details Patch (admin cover image + description)

Admins can add a description and a cover image when adding/editing a book.
Users see them in a details popup (click a book title).
Images are stored INSIDE the database, so sharing the database also shares the images.

## Apply
1. Copy database/, admin-pages/ and user-pages/ from this folder into the project root
   (e.g. C:\xampp\htdocs\library\) and choose Replace for existing files.
2. Follow database/HOW-TO-USE-DB.md: paste its SQL into phpMyAdmin -> SQL -> Go.
   It already includes the upgrade, creates what is missing and is safe to run more than once.
   (database/add-book-details.sql is the same upgrade on its own; you don't need it separately.)
3. Ctrl+F5 in the browser, log in as admin, add a book with an image.

## Changed files
database/save_book.php, delete_book.php, get_books.php, HOW-TO-USE-DB.md
admin-pages/admin.php, admin.js
user-pages/users.php, users.js

## New files
database/get_cover.php (serves the image from the database), database/add-book-details.sql

## Notes
- Only a logged-in admin can save/delete books and upload images.
- Image limit: 800KB, JPG/PNG/WEBP/GIF.
- Not compatible with quantitypatch/ unless merged.
