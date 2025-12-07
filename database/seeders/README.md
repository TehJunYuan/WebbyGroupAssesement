# Permissions Seeder

This seeder creates default permissions and roles using database queries.

## What it creates:

### Permissions (24 permissions):
- **User Management**: view users, create users, edit users, delete users
- **Role & Permission Management**: view/edit/create/delete/assign roles and permissions
- **Content Management**: view/create/edit/delete/publish content
- **System Administration**: access admin panel, manage settings, view reports, manage system

### Roles (5 roles):
- **Super Admin**: Has all permissions
- **Admin**: Has most administrative permissions
- **Editor**: Can manage content
- **Moderator**: Can moderate content and users
- **User**: Basic user role (no permissions assigned by default)

## How to Run:

### Option 1: Using Artisan Seeder (Recommended)
```bash
php artisan db:seed --class=PermissionsSeeder
```

### Option 2: Run all seeders
```bash
php artisan db:seed
```

### Option 3: Using SQL Scripts

#### For SQLite:
```bash
sqlite3 database/database.sqlite < database/seeds/create-permissions.sql
```

#### For MySQL:
```bash
mysql -u your_username -p your_database < database/seeds/create-permissions-mysql.sql
```

## The Seeder Uses:

- `DB::table()` - Laravel Query Builder
- `insertOrIgnore()` - Prevents duplicate entries
- Direct SQL queries for role-permission assignments
- Config-based table names (respects your permission config)

## Customization:

Edit `database/seeders/PermissionsSeeder.php` to:
- Add more permissions
- Create custom roles
- Assign different permissions to roles
- Change guard name

