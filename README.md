# Book Selling Platform - Interview Assessment

A comprehensive book selling platform built with Laravel, featuring role-based access control, seller approval system, book management, shopping cart, and order processing capabilities.

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation Guide](#installation-guide)
- [Database Setup](#database-setup)
- [Test Accounts](#test-accounts)
- [Key Features](#key-features)
- [Project Structure](#project-structure)
- [Security Features](#security-features)
- [Documentation](#documentation)

## 🎯 Project Overview

This is a full-featured book selling platform that allows:

- **Admins** to manage users, categories, seller approvals, and system-wide operations
- **Sellers** to register, get approved, upload books, manage inventory, and process orders
- **Customers** to browse books, add items to cart, and place orders

The platform implements robust security measures, role-based permissions, and a comprehensive audit trail system.

## 🛠 Technology Stack

- **Framework**: Laravel 12.x
- **Frontend**: Livewire Volt Components with Flux UI
- **Authentication**: Laravel Fortify (with 2FA support)
- **Permissions**: Spatie Laravel Permission
- **Media Management**: Spatie Media Library
- **URL Slugs**: Spatie Laravel Sluggable
- **Database**: MySQL/MariaDB (or SQLite for development)
- **PHP Version**: 8.2 or higher

## 💻 System Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18.x and npm
- MySQL >= 5.7 or MariaDB >= 10.3 (or SQLite for development)
- Web Server (Apache/Nginx) or PHP built-in server
- GD Library or ImageMagick for image processing

## 📦 Installation Guide

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd WebbyGroupAssesement
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install Node Dependencies

```bash
npm install
```

### Step 4: Environment Configuration

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### Step 5: Configure Database

Edit the `.env` file and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=book_selling_platform
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Note**: For quick testing, you can use SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

Create the SQLite database file:

```bash
touch database/database.sqlite
```

### Step 6: Create Storage Link

```bash
php artisan storage:link
```

### Step 7: Run Database Migrations and Seeders

```bash
php artisan migrate:fresh --seed
```

This command will:
- Drop all existing tables
- Create all database tables
- Seed permissions and roles
- Seed gender data
- Create test user accounts (Admin, Sellers, and Normal Users)

### Step 8: Build Frontend Assets

For development:

```bash
npm run dev
```

For production:

```bash
npm run build
```

### Step 9: Start the Development Server

In a separate terminal, start the Laravel development server:

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## 🗄 Database Setup

### Migration Files

All migrations are consolidated into single files per table:

- `2025_12_07_050000_create_users_table.php` - Users and authentication
- `2025_12_07_050001_create_permission_tables.php` - Roles and permissions
- `2025_12_07_050002_create_media_table.php` - Media library
- `2025_12_07_050003_create_book_categories_table.php` - Book categories
- `2025_12_07_050004_create_genders_table.php` - Gender master data
- `2025_12_07_050005_create_books_table.php` - Books
- `2025_12_07_050006_create_seller_information_table.php` - Seller profiles
- `2025_12_07_050007_create_user_information_table.php` - User profiles
- `2025_12_07_050008_create_carts_table.php` - Shopping cart
- `2025_12_07_050009_create_orders_table.php` - Orders
- `2025_12_07_050010_create_order_items_table.php` - Order items

### Resetting the Database

If you need to reset the database:

```bash
php artisan migrate:fresh --seed
```

### Running Seeders Individually

```bash
# Seed permissions and roles
php artisan db:seed --class=PermissionsSeeder

# Seed genders
php artisan db:seed --class=GenderSeeder

# Seed test users
php artisan db:seed --class=TestUsersSeeder
```

## 👥 Test Accounts

After running the database seeders, the following test accounts are available:

### Admin Account

- **Email**: `admin@example.com`
- **Password**: `password`
- **Role**: Admin
- **Permissions**: Full access to all features
- **Access**: User management, seller approvals, category management, system administration

### Seller Accounts (10 Accounts)

All sellers are pre-approved and ready to use:

| Email | Password | Role | Status |
|-------|----------|------|--------|
| `seller1@example.com` | `password` | Seller | Approved |
| `seller2@example.com` | `password` | Seller | Approved |
| `seller3@example.com` | `password` | Seller | Approved |
| `seller4@example.com` | `password` | Seller | Approved |
| `seller5@example.com` | `password` | Seller | Approved |
| `seller6@example.com` | `password` | Seller | Approved |
| `seller7@example.com` | `password` | Seller | Approved |
| `seller8@example.com` | `password` | Seller | Approved |
| `seller9@example.com` | `password` | Seller | Approved |
| `seller10@example.com` | `password` | Seller | Approved |

**Note**: After first login, sellers need to complete their seller information before accessing seller features.

### Normal User Accounts (10 Accounts)

These accounts have no roles and can browse and purchase books:

| Email | Password | Role | Status |
|-------|----------|------|--------|
| `user1@example.com` | `password` | None | Active |
| `user2@example.com` | `password` | None | Active |
| `user3@example.com` | `password` | None | Active |
| `user4@example.com` | `password` | None | Active |
| `user5@example.com` | `password` | None | Active |
| `user6@example.com` | `password` | None | Active |
| `user7@example.com` | `password` | None | Active |
| `user8@example.com` | `password` | None | Active |
| `user9@example.com` | `password` | None | Active |
| `user10@example.com` | `password` | None | Active |

**Note**: After first login, users need to complete their personal information before accessing the shop.

## ✨ Key Features

### Admin Features

- ✅ User management (view, create, edit, delete)
- ✅ Seller approval system (approve/reject sellers)
- ✅ Book category management
- ✅ Gender master data management
- ✅ Role and permission management
- ✅ View all orders across the platform
- ✅ System administration

### Seller Features

- ✅ Seller registration with information form
- ✅ Book management (create, edit, delete own books)
- ✅ Upload book cover images (2:3 aspect ratio, max 5MB)
- ✅ Inventory management (stock quantity)
- ✅ Order management (view and update order status)
- ✅ Seller dashboard with analytics
- ✅ Profile management

### Customer Features

- ✅ Browse books by category
- ✅ Search books
- ✅ View book details
- ✅ Shopping cart functionality
- ✅ Add items to cart with quantity selector
- ✅ Select cart items for checkout
- ✅ Place orders
- ✅ View order history
- ✅ Profile management

### Security Features

- ✅ Role-based access control (RBAC)
- ✅ Permission-based access control
- ✅ Data encryption for sensitive information
- ✅ Password hashing
- ✅ CSRF protection
- ✅ XSS protection
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ Audit trail for all database operations
- ✅ Soft deletes
- ✅ URL slugs for security
- ✅ Two-factor authentication support

## 📁 Project Structure

```
├── app/
│   ├── Http/
│   │   ├── Middleware/          # Custom middleware
│   │   └── Responses/           # Custom response classes
│   ├── Models/                  # Eloquent models
│   ├── Rules/                   # Custom validation rules
│   └── Scopes/                  # Global scopes
├── database/
│   ├── migrations/              # Database migrations (one per table)
│   └── seeders/                 # Database seeders
├── resources/
│   ├── views/
│   │   ├── livewire/           # Livewire Volt components
│   │   └── components/         # Blade components
├── routes/
│   └── web.php                 # Web routes
├── storage/
│   └── app/public/             # Public storage (book covers)
└── public/                     # Public assets
```

## 🔒 Security Features

For detailed security documentation, see [SECURITY_AND_DATA_PROTECTION.md](SECURITY_AND_DATA_PROTECTION.md)

### Implemented Security Measures

- **Data Encryption**: Sensitive fields (phone numbers, addresses, tax IDs, shipping addresses) are encrypted
- **Password Hashing**: All passwords are automatically hashed
- **Role-Based Access Control**: Fine-grained permission system
- **Audit Trail**: All database operations are tracked
- **Soft Deletes**: Data retention with custom `IsActive` field
- **URL Slugs**: Prevents direct ID access to resources

## 📚 Documentation

Additional documentation files:

- [SECURITY_AND_DATA_PROTECTION.md](SECURITY_AND_DATA_PROTECTION.md) - Security implementation details
- [database/seeders/BOOK_PLATFORM_SETUP.md](database/seeders/BOOK_PLATFORM_SETUP.md) - Database setup guide
- [SELLER_APPROVAL_SYSTEM.md](SELLER_APPROVAL_SYSTEM.md) - Seller approval system documentation

## 🧪 Testing Instructions

### Testing Admin Features

1. Login with `admin@example.com` / `password`
2. Navigate to:
   - User List (`/users-list`) - Manage users
   - Seller Approvals (`/seller-approvals`) - Approve/reject sellers
   - Book Categories (`/book-categories`) - Manage categories
   - Permissions (`/permissions`) - Manage roles and permissions

### Testing Seller Features

1. Login with any seller account (e.g., `seller1@example.com` / `password`)
2. Complete seller information (if not already done)
3. Navigate to:
   - Seller Dashboard (`/seller/panel`) - View analytics
   - My Books (`/seller/books`) - Manage books
   - Orders (`/seller/orders`) - View and manage orders
   - Profile (`/seller/information`) - Update seller information

### Testing Customer Features

1. Login with any user account (e.g., `user1@example.com` / `password`)
2. Complete personal information (if not already done)
3. Navigate to:
   - Shop (`/shop`) - Browse and search books
   - Cart (click cart icon) - View cart items
   - Profile - Update personal information

### Testing Workflow

1. **Seller Workflow**:
   - Login as seller → Complete information → Upload books → View orders

2. **Customer Workflow**:
   - Login as user → Complete information → Browse shop → Add to cart → Checkout → View orders

3. **Admin Workflow**:
   - Login as admin → Approve sellers → Manage categories → View all orders

## 🔧 Troubleshooting

### Issue: Storage link not working

```bash
php artisan storage:link
```

### Issue: Permission denied errors

On Linux/Mac, ensure proper permissions:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Issue: CSS/JS not loading

Build the assets:

```bash
npm run build
```

Or run the dev server:

```bash
npm run dev
```

### Issue: Database connection error

Check your `.env` file database configuration and ensure:
- Database exists
- Credentials are correct
- Database server is running

## 📝 Notes

- All test accounts use the password: `password`
- All sellers are pre-approved for testing convenience
- Email verification is bypassed in seeders for testing
- File uploads are stored in `storage/app/public/books/cover_image`
- Maximum file upload size: 5MB for book covers
- Recommended book cover image size: 300x450 pixels (2:3 aspect ratio)

## 👨‍💻 Development

### Code Style

The project follows Laravel coding standards. To check code style:

```bash
./vendor/bin/pint
```

### Running Tests

```bash
php artisan test
```

## 📄 License

This project is created for interview assessment purposes.

---

**Built with ❤️ using Laravel, Livewire, and Flux UI**
