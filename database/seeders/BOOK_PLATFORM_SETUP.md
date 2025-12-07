# Book Selling Platform - Database Setup Guide

## Commands to Reset Database and Run Migrations

### Option 1: Fresh Migration (Recommended)
This will drop all tables and recreate them, then run all migrations and seeders:

```bash
php artisan migrate:fresh --seed
```

### Option 2: Reset and Migrate Separately
If you want more control:

```bash
# Drop all tables and re-run all migrations
php artisan migrate:fresh

# Run the seeders
php artisan db:seed
```

### Option 3: Reset Specific Tables Only
If you only want to reset permissions/roles tables:

```bash
# Rollback permission migrations
php artisan migrate:rollback --step=1

# Run migrations again
php artisan migrate

# Seed permissions
php artisan db:seed --class=PermissionsSeeder
```

## Roles Created

1. **Admin** - Full access to all platform features
2. **Seller** - Can manage books, view orders, and access seller panel
3. **User** - Can browse, search, purchase books, and manage wishlist

## Permissions Created (60+ permissions)

### Admin Permissions:
- User Management (view, create, edit, delete users)
- Seller Management (view, approve, reject sellers)
- Category Management (view, create, edit, delete categories)
- Book Management (view all, edit any, delete any books)
- Order Management (view all, manage orders)
- Role & Permission Management (full control)
- System Administration (admin panel, settings, logs, notifications)

### Seller Permissions:
- Book Management (view own, create, edit own, delete own)
- Book Browsing (browse, search, view details, filter)
- Order Management (view own, update order status)
- Profile Management (view/update own profile, seller profile)
- Sales Analytics (view analytics, reports, statistics)
- Seller Panel Access

### User Permissions:
- Book Browsing (browse, search, view details, filter)
- Order Management (create orders, view own orders)
- Profile Management (view/update own profile)
- Wishlist Management (manage, add, remove)

## After Running Migrations

After running the migrations, you should:
1. Create an admin user and assign the Admin role
2. Test the permissions system
3. Verify all permissions are assigned correctly

