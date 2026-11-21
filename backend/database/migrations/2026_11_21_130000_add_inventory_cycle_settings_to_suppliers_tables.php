<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'default_lead_time_days')) {
                $table->integer('default_lead_time_days')->default(3)->after('default_po_expired_days');
            }
            if (!Schema::hasColumn('suppliers', 'default_inventory_days')) {
                $table->integer('default_inventory_days')->default(14)->after('default_lead_time_days');
            }
        });

        Schema::table('supplier_divisions', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_divisions', 'lead_time_days')) {
                $table->integer('lead_time_days')->nullable()->after('email');
            }
            if (!Schema::hasColumn('supplier_divisions', 'inventory_days')) {
                $table->integer('inventory_days')->nullable()->after('lead_time_days');
            }
        });

        if (!Schema::hasTable('supplier_branch_settings')) {
            Schema::create('supplier_branch_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignUuid('supplier_division_id')->nullable()->constrained('supplier_divisions')->cascadeOnDelete();
                $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->integer('lead_time_days')->nullable();
                $table->integer('inventory_days')->default(14);
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['supplier_id', 'branch_id', 'supplier_division_id'], 'sbs_sup_branch_div_idx');
            });
        }

        // Berikan izin EDIT_SUPPLIER_CYCLE secara otomatis kepada pengguna yang sudah memiliki TOGGLE_SUPPLIER_GR_PO
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'custom_authorizations')) {
            $users = \Illuminate\Support\Facades\DB::table('users')
                ->whereNotNull('custom_authorizations')
                ->get(['id', 'custom_authorizations']);

            foreach ($users as $u) {
                $auths = is_string($u->custom_authorizations)
                    ? (json_decode($u->custom_authorizations, true) ?: [])
                    : [];
                if (is_array($auths) && in_array('TOGGLE_SUPPLIER_GR_PO', $auths, true) && !in_array('EDIT_SUPPLIER_CYCLE', $auths, true)) {
                    $auths[] = 'EDIT_SUPPLIER_CYCLE';
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $u->id)
                        ->update(['custom_authorizations' => json_encode(array_values($auths))]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_branch_settings');

        Schema::table('supplier_divisions', function (Blueprint $table) {
            $cols = array_filter(['lead_time_days', 'inventory_days'], fn ($c) => Schema::hasColumn('supplier_divisions', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $cols = array_filter(['default_lead_time_days', 'default_inventory_days'], fn ($c) => Schema::hasColumn('suppliers', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

