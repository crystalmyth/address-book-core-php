# Address Book Application
![Address Book Logo](public/logo.png){:height="30px" width="30px"} 

This is a simple Address Book application built with PHP, using basic MVC principles, and Bootstrap 5 for styling. It supports CRUD operations for address book entries and cities, along with notifications, pagination, and search functionality.

## Features
- **CRUD for Address Book**: Create, Read, Update, and Delete address book entries.
- **CRUD for Cities**: Manage city data.
- **Notifications**: Displays success or error messages after operations.
- **Search Functionality**: Search address book entries by name, email, or phone.
- **Pagination**: Dynamically displays pagination links.
- **Responsive Design**: Built with Bootstrap 5, ensuring mobile-first responsiveness.
- **Dynamic Styling**: Utilizes responsive utility classes (`d-sm-block`, `d-md-flex`) for flexible layouts.
- **Fake Data Seeder**: Populate the database with fake data using external APIs.
- **Export Options**: Export address book data to JSON, CSV, or XML formats.
---

## Installation

### Prerequisites

- PHP >= 7.4
- Composer
- MySQL Database
- Node.js and npm (for SASS and Bootstrap)

### Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/your-repo/address-book.git
   cd address-book
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Set Up Environment Variables:**
   - Copy the example `.env` file:
     ```bash
     cp .env.example .env
     ```
   - Update the `.env` file with your configuration:
     ```dotenv
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=address_book
     DB_USERNAME=root
     DB_PASSWORD=
     ```

4. **Run Migrations**
   Execute the migration script to create the required tables:
   ```bash
   composer run create:table
   ```

5. **Seed the Database**
   Populate the database with fake data:
   ```bash
   composer run seeder
   ```

6. **Compile Assets**
   Compile SASS files to CSS:
   ```bash
   npm run sass
   ```

7. **Start the Server**
   Start a PHP development server:
   ```bash
   composer run start
   ```

7. **Stop the Server**
   Start a PHP development server:
   ```bash
   composer run stop
   ```

---

## Folder Structure

```plaintext
address-book/
├── public/              # Public assets and entry point
├── src/
│   ├── Controllers/     # Controller files
│   ├── Models/          # Model files
│   ├── Views/           # View templates
│   ├── Helpers/         # Helper classes (e.g., Router, Database)
│   ├── Migrations/      # SQL migration files
│   ├── Seeds/           # Database seeder scripts
│   ├── Config.php       # Configuration class
│   ├── Export.php       # Export functionality (new)
│   └── ...              # Other application files
├── assets/
│   ├── scss/            # SASS files
│   └── css/             # Compiled CSS
├── vendor/              # Composer dependencies
├── .env                 # Environment variables
├── composer.json        # Composer configuration
├── package.json         # npm configuration
└── README.md            # Project documentation
```

---

## Usage

### Search Functionality

Use the search form to query the address book:
```html
<form action="/" method="get">
    <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Search by *" value="<?= $q ?? '' ?>">
        <button type="submit" class="btn btn-dark">Search</button>
    </div>
</form>
```
Search is implemented using a `LIKE` query:
```sql
WHERE name LIKE :q OR email LIKE :q OR phone LIKE :q
```

### Pagination

Dynamic pagination links:
```php
for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++) {
    echo "<a href='?page=$i' class='page-link'>" . $i . "</a>";
}
```

### Notifications

Set notifications in the session:
```php
$_SESSION['notification'] = [
    'type' => 'success', // or 'error'
    'message' => 'Operation completed successfully.'
];
```
Display and unset notifications:
```php
if (isset($_SESSION['notification'])) {
    echo "<div class='alert alert-{$_SESSION['notification']['type']}'>{$_SESSION['notification']['message']}</div>";
    unset($_SESSION['notification']);
}
```

---

## API Integration

Use `curl_multi` for asynchronous API calls:
```php
$multiCurl = curl_multi_init();
$curlHandles = [];
$apiUrl = 'https://jsonplaceholder.typicode.com/users?page=';

for ($i = 1; $i <= 10; $i++) {
    $ch = curl_init($apiUrl . $i);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_multi_add_handle($multiCurl, $ch);
    $curlHandles[] = $ch;
}

// Execute and fetch responses
$running = null;
do {
    curl_multi_exec($multiCurl, $running);
} while ($running);

$responses = [];
foreach ($curlHandles as $ch) {
    $responses[] = curl_multi_getcontent($ch);
    curl_multi_remove_handle($multiCurl, $ch);
}
curl_multi_close($multiCurl);
```