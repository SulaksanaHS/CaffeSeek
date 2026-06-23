# Project Context (CAFFESEEK)

## Tech Stack
- **Backend:** Laravel 11, PHP
- **Frontend:** Inertia.js with React, Shadcn UI, Tailwind CSS
- **Database:** MySQL (Laragon)

## Database Tables
1. **users:** Stores user information (authentication).
2. **cafes:** Stores cafe details (name, description, location, whatsapp, operational hours, facilities).
3. **cafe_photos:** Stores cafe photos with primary status.
4. **cafe_menus:** Stores menus for each cafe.
5. **cafe_tables:** Stores table capacities for each cafe.
6. **reservations:** Stores customer bookings (cafe_id, customer details, date, status, total_price, proof_of_payment_url).
7. **reservation_items:** Stores menu items selected in a reservation.

## Application Flow
- **Customer:** Can browse cafes, view details (menus, tables, photos), make a reservation, and upload proof of payment.
- **Admin:** Has a dashboard to manage cafes (CRUD cafes with photos, menus, tables), manage reservations (verify status), and view financial reports.
- **Mitra (Upcoming):** Will have a dedicated dashboard to manage their own cafe, view their reservations, and see their own financial reports based on `owner_cafe` assignment.
