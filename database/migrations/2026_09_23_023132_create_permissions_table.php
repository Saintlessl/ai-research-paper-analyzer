<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        // Insert default permissions and super admin role
        DB::table('roles')->insertOrIgnore([
            ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()]
        ]);

        $permissions = [
            ['name' => 'access_admin_panel', 'description' => 'Bisa mengakses Admin Dashboard'],
            ['name' => 'manage_users', 'description' => 'Bisa mengubah role user'],
            ['name' => 'manage_jobs', 'description' => 'Bisa retry background jobs'],
            ['name' => 'view_logs', 'description' => 'Bisa melihat system logs'],
            ['name' => 'assign_reviewers', 'description' => 'Bisa assign reviewer ke paper'],
            ['name' => 'manage_role_permissions', 'description' => 'Bisa mengelola role dan permissions'],
        ];
        
        DB::table('permissions')->insertOrIgnore(
            array_map(fn($p) => array_merge($p, ['created_at' => now(), 'updated_at' => now()]), $permissions)
        );

        // Assign default permissions to Admin (everything except manage_role_permissions for now, SuperAdmin handles it)
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $adminPermissions = DB::table('permissions')->where('name', '!=', 'manage_role_permissions')->get();
            $pivots = [];
            foreach ($adminPermissions as $perm) {
                $pivots[] = ['role_id' => $adminRole->id, 'permission_id' => $perm->id];
            }
            DB::table('permission_role')->insertOrIgnore($pivots);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        DB::table('roles')->where('name', 'super_admin')->delete();
    }
};
