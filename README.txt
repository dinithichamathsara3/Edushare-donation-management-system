EDUSHARE — DOCUMENT REQUIREMENTS UPDATE

Requirements: PHP 8.1+ (8.2 recommended), PDO MySQL, fileinfo, mbstring,
Apache with .htaccess enabled and MariaDB 10.4+ (standard XAMPP).

EXISTING INSTALLATION — keep real records
1. Back up your current edushare folder and BOTH databases in phpMyAdmin.
2. Replace the application files with this edushare folder. KEEP your existing
   uploads folder and includes/config.php connection settings. Also keep any
   existing private/verification documents when applying a future update.
3. Open phpMyAdmin > Import and import edushare/sql/upgrade.sql.
   This is additive: no users, donations or requests are deleted.
4. Open http://localhost/edushare/login.php and choose the correct login role.
   Existing administrators keep their current email and password.
5. Existing receivers must submit an institution application using the
   verification page. They are not automatically approved.
6. Delete setup.php from an already configured installation.

NEW INSTALLATION
1. Copy edushare into C:\xampp\htdocs\ and start Apache and MySQL.
2. Check includes/config.php (base URL /edushare and your database settings).
3. Visit http://localhost/edushare/setup.php. Create your own administrator
   email/password. Setup imports database.sql AND upgrade.sql.
4. DELETE setup.php after successful setup.
5. Visit http://localhost/edushare/login.php and choose Administrator.
6. Register real donor and institution accounts. Verify the institution as admin.

A normal donation is published immediately. Administrators can withdraw
suspicious listings. Institution documents are served only through an authorized
admin download route; Apache must honor private/.htaccess. PHP's built-in server
is not suitable for serving verification documents because it ignores .htaccess.

HOW TO DEMONSTRATE THE NEW FEATURES
- Register an institution with its unique registration number and supporting
  PDF/JPG/PNG. It starts pending and cannot claim or create requirements.
- Admin > Institution verification: approve, reject or request more information.
- Approved institution > My requirements: create a normal or urgent requirement.
- Admin > Requirements / urgent review: approve urgent priority.
- Donor > Donate an item: set subject, grade, quantity, location and collection
  method. Nearby needs / matching ranks schools against your selected donation.
- Enter valid latitude/longitude in profiles and donation listings for measured
  distance filters (5, 10 or 25 km); city/district filters work without coordinates.
- Donor offers a partial quantity; institution accepts. Or institution claims a
  quantity; donor accepts. Quantities are reserved only when accepted.
- Donor updates Accepted > Preparing > Ready > Delivered; recipient confirms
  receipt > Completed. The requirement's received amount and donor impact update.
- Admin > Reports and charts: see real totals and export CSV.

WHAT WAS ADDED
Institution fields/documents/verification and duplicate-number prevention;
requirements and urgency approval; quantity-aware offers and claims; matching and
location filtering; donation workflow/history; donor impact; reports and charts;
admin notices, activity review and contact inbox; public information/browse pages;
donation editing; extra filters; required donation fields; secure initial setup.
Existing authentication, layouts, categories, notifications and member management
are reused. Claims and donor offers share the requests table; no duplicate parallel
claim system was added. UI JavaScript and CSS remain in assets/js and assets/css.

DATA AND MIGRATION NOTES
No demo users, donations or statistics are created. Categories are reference data.
The updater does not delete sample records that may already exist in your database.
Existing claims requested the entire listing. The one-time migration assigns
them the original listing quantity; new claims preserve their partial quantities.
Review older real records if items were actually handed over in different amounts.
Tracking history begins with new actions; historical events are not invented.
The migration uses MariaDB ADD COLUMN IF NOT EXISTS syntax, matching XAMPP.

VALIDATION
See VALIDATION.txt for checks completed and limitations of this environment.
