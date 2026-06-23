# Sprint Tasks

## TUGAS A - User Management (Admin)
### Backend:
- [ ] Add migration to add `role` column (enum/string: 'admin', 'mitra', default 'mitra') to `users` table.
- [ ] Add migration to add `owner_cafe` column (nullable) to `users` table.
- [ ] Add migration to add `deleted_at` (SoftDeletes) to `users` table.
- [ ] Create `UserController` for User CRUD.
- [ ] Create User: Hardcoded role 'mitra'.
- [ ] Read User: Return name and email.
- [ ] Update User: Update name, email (unique), password (optional).
- [ ] Delete User: Soft delete.

### Frontend:
- [ ] Add User Management menu to Admin Sidebar (below Dashboard).
- [ ] Create User List page using Shadcn UI (similar to Cafe Management).
- [ ] Add Search User functionality.
- [ ] Add Pagination (15 items per page).
- [ ] Add Alerts (bottom right) for success create, update, delete.
- [ ] Add Confirmation Popup before deleting a user.

## TUGAS B - Cafe Assignment & Unique ID
- [ ] Add action in User Management to assign a Cafe ID to a User (sets `owner_cafe`).
- [ ] Create a professional input UI for assigning the cafe.
- [ ] Refactor Cafe PK (`id`) from auto-increment (1, 2, 3) to Unique ID (e.g., UUID/ULID). Update all related foreign keys (`cafe_photos`, `cafe_menus`, `cafe_tables`, `reservations`).

## TUGAS C - Mitra Dashboard
### Backend:
- [ ] Create middleware to restrict `mitra/dashboard` routes to users with `role` = 'mitra'.
- [ ] Scope data (cafes, reservations, reports) to the currently logged-in Mitra based on their `owner_cafe` column.

### Frontend:
- [ ] Create Mitra Dashboard route (`mitra/dashboard`).
- [ ] Mitra Sidebar: Dashboard, Management Cafe Resto, Transaksi Reservasi, Laporan Keuangan.
- [ ] Mitra Dashboard Widgets: Total Meja, Total Menu, Chart Omset, Chart Total Reservasi, Identitas/Data Cafe.
- [ ] Management Cafe Resto: Same UI as Admin, but NO search and NO create cafe button (only edit their own).
- [ ] Transaksi Reservasi: Same UI as Admin (filtered by their cafe).
- [ ] Laporan Keuangan: Same UI as Admin (filtered by their cafe).
