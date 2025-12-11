# Database Connection Configuration

## Configuration Location

Database connection details are configured in two places:

1. **Environment File**: `.env` (in the root directory)
2. **Configuration File**: `config/database.php`

## Current Configuration Structure

Based on `config/database.php`, the application uses the following database settings:

### Default Connection
- **Driver**: MySQL (default, can be changed via `DB_CONNECTION` env variable)
- **Supported Drivers**: MySQL, PostgreSQL, SQLite, SQL Server

### MySQL Configuration (Default)

The MySQL connection uses these environment variables from `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=forge
DB_USERNAME=forge
DB_PASSWORD=
```

**Configuration Details:**
- **Host**: `127.0.0.1` (default, from `DB_HOST`)
- **Port**: `3306` (default, from `DB_PORT`)
- **Database**: `forge` (default, from `DB_DATABASE`)
- **Username**: `forge` (default, from `DB_USERNAME`)
- **Password**: Empty by default (from `DB_PASSWORD`)
- **Charset**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- **Socket**: Optional (from `DB_SOCKET`)

### Other Supported Databases

#### PostgreSQL
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=forge
DB_USERNAME=forge
DB_PASSWORD=
```

#### SQLite
```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite
```

#### SQL Server
```env
DB_CONNECTION=sqlsrv
DB_HOST=localhost
DB_PORT=1433
DB_DATABASE=forge
DB_USERNAME=forge
DB_PASSWORD=
```

## How to View/Edit Your Database Connection

### Option 1: View .env File
Open the `.env` file in the root directory and look for these lines:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Option 2: Check via Artisan (if .env is configured)
```bash
php artisan tinker
```
Then run:
```php
config('database.connections.mysql');
```

### Option 3: Test Connection
```bash
php artisan migrate:status
```

## Database Tables

The application uses the following tables (from migrations):

1. **users** - User accounts
   - `id`, `name`, `email`, `password`, `api_token`, `remember_token`, `email_verified_at`, `created_at`, `updated_at`

2. **password_resets** - Password reset tokens
   - `email`, `token`, `created_at`

3. **failed_jobs** - Failed queue jobs
   - `id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`

4. **personal_access_tokens** - Sanctum tokens
   - `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`

5. **migrations** - Migration tracking
   - `id`, `migration`, `batch`

## Setting Up Database

1. **Create a database** in your MySQL server:
   ```sql
   CREATE DATABASE blueprintelectricity CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Update `.env` file** with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=blueprintelectricity
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

3. **Run migrations**:
   ```bash
   php artisan migrate
   ```

## Connection String Format

If you need to use a connection string instead, you can set `DATABASE_URL` in `.env`:

```env
DATABASE_URL=mysql://username:password@host:port/database
```

Example:
```env
DATABASE_URL=mysql://root:mypassword@127.0.0.1:3306/blueprintelectricity
```

## Troubleshooting

### Common Issues:

1. **Connection Refused**
   - Check if MySQL server is running
   - Verify host and port are correct
   - Check firewall settings

2. **Access Denied**
   - Verify username and password
   - Check user permissions in MySQL
   - Ensure user has access to the database

3. **Database Not Found**
   - Create the database first
   - Verify database name in `.env`

4. **Check Current Connection**
   ```bash
   php artisan db:show
   ```

## Security Note

⚠️ **Never commit your `.env` file to version control!** It contains sensitive credentials.

The `.env` file should be in `.gitignore` and you should use `.env.example` as a template.

