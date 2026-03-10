# PEZA Supplier & Contact Management System (SCMS)

This is a simple prototype of the directory system described for the Philippine Economic Zone Authority.
It uses a **PHP + MySQL** stack running under **XAMPP** (Apache distribution with MariaDB), so you can deploy it on your local Windows machine.

## Features

- Search suppliers by company name, officer, status or category
- Basic admin dashboard to add companies and toggle status
- CSV import for bulk data migration
- Simple data model with companies and officers

## Requirements

1. [Download and install XAMPP](https://www.apachefriends.org/index.html).
2. Start Apache and MySQL from the XAMPP control panel.
3. Copy this `scms` folder into `htdocs` (usually `C:\xampp\htdocs\scms`).
4. Run the SQL script in `database.sql` using phpMyAdmin or the MySQL CLI to create the database and tables.
   - phpMyAdmin is available at [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
5. Open the site in your browser: [http://localhost/scms/](http://localhost/scms/).

### Configuration

`config.php` uses the default XAMPP credentials (`root` user with no password) and connects to database `peza_scms`.
Modify if you set a password or use a different database name.

### Authentication

A simple login page now protects the administrative dashboard (`admin.php`).  By default the credentials are:

```
username: admin
password: admin123
```

The values are defined in `config.php` as `$admin_user` and `$admin_pass_hash`.  To change them, generate a new hash with the built‑in PHP function:

```bash
php -r "echo password_hash('yourpass', PASSWORD_DEFAULT);"
```

Then update the variables in `config.php` or, for a more advanced deployment, store them in a database or an environment variable of your choice.

If you log out, use the "Logout" button in the top‑right of the dashboard or visit `admin.php?action=logout`.

## Data Import

To migrate your existing Excel directory, save it as CSV with columns like `company_name,category,status,remarks`,
then visit `import.php` and upload the file.

## Extending the Prototype

- Add officer management and profile pages
- Implement authentication for the admin dashboard
- Add export to Excel/PDF
- Colour-code rows or add status badges
- Implement audit trail and vendor self‑update portal
- Add PDF export button in directory view

### Importing Excel files

The `import.php` script now understands `.xls` and `.xlsx` formats. To parse these files you need the [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) library:

```bash
cd C:\xampp\htdocs\scms
composer require phpoffice/phpspreadsheet
```

If you don't use Composer the page will fall back to CSV-only import. **Note:** uploading an `.xls`/`.xlsx` file without installing PhpSpreadsheet will now produce an explicit warning and no data will be read; convert to CSV or install the library first.

#### Accepted column layout

### PDF Export

A new **Export PDF** button appears next to the search form. It will generate a printable
PDF of the current filtered list. The feature normally relies on [Dompdf](https://github.com/dompdf/dompdf).
Install it via Composer before using:

```bash
composer require dompdf/dompdf
```

If Dompdf is not installed you will still be able to click the button – the script
will fall back to downloading a **CSV** file instead, so you can export the same
data even without Composer or the library.


The importer is flexible: it examines the first row for header names and maps the following columns automatically. Supported headings include variants like:

- **Company**, **Supplier**, **Company/Suppliers**
- **Officers**
- **Position**
- **Email Address**
- **Contact Number** (or **Phone**)
- **Remarks**
- **Status** (optional)

It will also ignore a leading serial number column such as **No.**. Any row whose remarks contain the word "inactive" will be marked Inactive if no explicit status column exists.

Your scanned example looks like:

```
No. | COMPANY/SUPPLIERS | OFFICERS | POSITION | EMAIL ADDRESS | REMARKS
1   | Quartz Business…  | Jessica… | …        | …@quartz.com.ph | EQUIPMENT/MA
2   | ePartners…        | Kristel… | …        | …@epartners…   | EQUIPMENT/MA
3   | MicroGold…        | Marvin…  | …        | …@microgold…   | INACTIVE
```

Just save that sheet as Excel or CSV and upload it; the script will figure out which column is which and insert the company name plus any status/remarks it finds. A preview of imported rows is shown after upload.

### Admin interface updates

The admin dashboard now includes fields for **officer name**, **position**, **email** and **contact number** when adding a new supplier. Those values are stored in the `officers` table and are visible both in the administration listing and the public directory view.

You can place your PEZA building image (for example `PEZA-background.jpg`) under `scms/images/` and it will automatically show behind every page, scaled to cover the screen. Make sure the filename in the CSS (see the `<style>` block in `index.php`/`admin.php`/`import.php`) matches the actual file name you use. The content containers have a semi‑opaque white background so the text remains readable.

This prototype demonstrates how you could deploy the described system on PEZA's internal network using XAMPP instead of a standalone MySQL server.