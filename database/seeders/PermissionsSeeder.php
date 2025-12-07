<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get table names from config
        $permissionsTable = config('permission.table_names.permissions', 'permissions');
        $rolesTable = config('permission.table_names.roles', 'roles');
        $roleHasPermissionsTable = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        
        $guardName = 'web';
        $now = now();

        // Define permissions based on Book Selling Platform requirements
        $permissions = [
            // User Management (Admin only)
            ['name' => 'view users', 'guard_name' => $guardName],
            ['name' => 'create users', 'guard_name' => $guardName],
            ['name' => 'edit users', 'guard_name' => $guardName],
            ['name' => 'delete users', 'guard_name' => $guardName],
            ['name' => 'manage users', 'guard_name' => $guardName],
            
            // Seller Management & Approval (Admin only)
            ['name' => 'view sellers', 'guard_name' => $guardName],
            ['name' => 'approve sellers', 'guard_name' => $guardName],
            ['name' => 'reject sellers', 'guard_name' => $guardName],
            ['name' => 'manage seller accounts', 'guard_name' => $guardName],
            
            // Category Management (Admin only)
            ['name' => 'view categories', 'guard_name' => $guardName],
            ['name' => 'create categories', 'guard_name' => $guardName],
            ['name' => 'edit categories', 'guard_name' => $guardName],
            ['name' => 'delete categories', 'guard_name' => $guardName],
            ['name' => 'manage categories', 'guard_name' => $guardName],
            
            // Gender Management (Admin only)
            ['name' => 'view genders', 'guard_name' => $guardName],
            ['name' => 'create genders', 'guard_name' => $guardName],
            ['name' => 'edit genders', 'guard_name' => $guardName],
            ['name' => 'delete genders', 'guard_name' => $guardName],
            ['name' => 'manage genders', 'guard_name' => $guardName],
            
            // Book Management (Admin & Seller)
            ['name' => 'view all books', 'guard_name' => $guardName], // Admin can view all
            ['name' => 'view own books', 'guard_name' => $guardName], // Seller can view own
            ['name' => 'create books', 'guard_name' => $guardName], // Seller can create
            ['name' => 'edit own books', 'guard_name' => $guardName], // Seller can edit own
            ['name' => 'edit any books', 'guard_name' => $guardName], // Admin can edit any
            ['name' => 'delete own books', 'guard_name' => $guardName], // Seller can delete own
            ['name' => 'delete any books', 'guard_name' => $guardName], // Admin can delete any
            ['name' => 'upload book images', 'guard_name' => $guardName], // Media upload
            
            // Book Browsing (All users including guests)
            ['name' => 'browse books', 'guard_name' => $guardName],
            ['name' => 'search books', 'guard_name' => $guardName],
            ['name' => 'view book details', 'guard_name' => $guardName],
            ['name' => 'filter books by category', 'guard_name' => $guardName],
            
            // Order Management
            ['name' => 'view own orders', 'guard_name' => $guardName], // Seller & User
            ['name' => 'view all orders', 'guard_name' => $guardName], // Admin
            ['name' => 'create orders', 'guard_name' => $guardName], // User (purchase)
            ['name' => 'manage orders', 'guard_name' => $guardName], // Admin
            ['name' => 'update order status', 'guard_name' => $guardName], // Admin & Seller
            
            // Profile Management
            ['name' => 'view own profile', 'guard_name' => $guardName], // All authenticated
            ['name' => 'update own profile', 'guard_name' => $guardName], // All authenticated
            ['name' => 'update seller profile', 'guard_name' => $guardName], // Seller specific
            
            // Sales Analytics (Seller)
            ['name' => 'view sales analytics', 'guard_name' => $guardName],
            ['name' => 'view sales reports', 'guard_name' => $guardName],
            ['name' => 'view order statistics', 'guard_name' => $guardName],
            
            // Wishlist (User)
            ['name' => 'manage wishlist', 'guard_name' => $guardName],
            ['name' => 'add to wishlist', 'guard_name' => $guardName],
            ['name' => 'remove from wishlist', 'guard_name' => $guardName],
            
            // Role & Permission Management (Admin only)
            ['name' => 'view roles', 'guard_name' => $guardName],
            ['name' => 'create roles', 'guard_name' => $guardName],
            ['name' => 'edit roles', 'guard_name' => $guardName],
            ['name' => 'delete roles', 'guard_name' => $guardName],
            ['name' => 'assign roles', 'guard_name' => $guardName],
            ['name' => 'view permissions', 'guard_name' => $guardName],
            ['name' => 'create permissions', 'guard_name' => $guardName],
            ['name' => 'edit permissions', 'guard_name' => $guardName],
            ['name' => 'delete permissions', 'guard_name' => $guardName],
            ['name' => 'assign permissions', 'guard_name' => $guardName],
            
            // System Administration (Admin only)
            ['name' => 'access admin panel', 'guard_name' => $guardName],
            ['name' => 'access seller panel', 'guard_name' => $guardName],
            ['name' => 'manage settings', 'guard_name' => $guardName],
            ['name' => 'view system logs', 'guard_name' => $guardName],
            ['name' => 'manage email notifications', 'guard_name' => $guardName],
        ];

        // Insert permissions using query builder (ignore duplicates)
        foreach ($permissions as $permission) {
            DB::table($permissionsTable)->insertOrIgnore([
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name'],
                'IsActive' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Define roles based on case study
        $roles = [
            ['name' => 'Admin', 'guard_name' => $guardName],
            ['name' => 'Seller', 'guard_name' => $guardName],
            ['name' => 'User', 'guard_name' => $guardName],
        ];

        // Insert roles using query builder (ignore duplicates)
        foreach ($roles as $role) {
            DB::table($rolesTable)->insertOrIgnore([
                'name' => $role['name'],
                'guard_name' => $role['guard_name'],
                'IsActive' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Assign permissions to roles using queries
        $this->assignPermissionsToRoles($permissionsTable, $rolesTable, $roleHasPermissionsTable, $guardName);

        $this->command->info('Book Selling Platform permissions and roles created successfully!');
    }

    /**
     * Assign permissions to roles using database queries
     */
    private function assignPermissionsToRoles(
        string $permissionsTable,
        string $rolesTable,
        string $roleHasPermissionsTable,
        string $guardName
    ): void {
        // Get role IDs
        $adminRoleId = DB::table($rolesTable)
            ->where('name', 'Admin')
            ->where('guard_name', $guardName)
            ->value('id');
            
        $sellerRoleId = DB::table($rolesTable)
            ->where('name', 'Seller')
            ->where('guard_name', $guardName)
            ->value('id');
            
        $userRoleId = DB::table($rolesTable)
            ->where('name', 'User')
            ->where('guard_name', $guardName)
            ->value('id');

        // Admin gets all permissions
        if ($adminRoleId) {
            $allPermissionIds = DB::table($permissionsTable)
                ->where('guard_name', $guardName)
                ->pluck('id')
                ->toArray();
                
            foreach ($allPermissionIds as $permissionId) {
                DB::table($roleHasPermissionsTable)->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRoleId,
                ]);
            }
        }

        // Seller permissions
        if ($sellerRoleId) {
            $sellerPermissions = [
                // Book Management (Own books)
                'view own books',
                'create books',
                'edit own books',
                'delete own books',
                'upload book images',
                
                // Book Browsing
                'browse books',
                'search books',
                'view book details',
                'filter books by category',
                
                // Order Management
                'view own orders',
                'update order status',
                
                // Profile Management
                'view own profile',
                'update own profile',
                'update seller profile',
                
                // Sales Analytics
                'view sales analytics',
                'view sales reports',
                'view order statistics',
                
                // Seller Panel Access
                'access seller panel',
            ];
            
            $this->assignPermissionsToRole($roleHasPermissionsTable, $permissionsTable, $sellerRoleId, $sellerPermissions, $guardName);
        }

        // User permissions
        if ($userRoleId) {
            $userPermissions = [
                // Book Browsing
                'browse books',
                'search books',
                'view book details',
                'filter books by category',
                
                // Order Management
                'create orders',
                'view own orders',
                
                // Profile Management
                'view own profile',
                'update own profile',
                
                // Wishlist
                'manage wishlist',
                'add to wishlist',
                'remove from wishlist',
            ];
            
            $this->assignPermissionsToRole($roleHasPermissionsTable, $permissionsTable, $userRoleId, $userPermissions, $guardName);
        }
    }

    /**
     * Assign specific permissions to a role using queries
     */
    private function assignPermissionsToRole(
        string $roleHasPermissionsTable,
        string $permissionsTable,
        int $roleId,
        array $permissionNames,
        string $guardName
    ): void {
        $permissionIds = DB::table($permissionsTable)
            ->where('guard_name', $guardName)
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->toArray();
            
        foreach ($permissionIds as $permissionId) {
            DB::table($roleHasPermissionsTable)->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
}
