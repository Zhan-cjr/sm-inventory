<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW all_sales_items AS
            SELECT
                'POS' as source,
                ti.id as id,
                ti.transaction_id as reference_id,
                t.transaction_date as transaction_date,
                t.local_transaction_id as transaction_number,
                t.branch_id,
                ti.product_id,
                ti.quantity as quantity,
                ti.unit_price as unit_price,
                ti.discount_per_item as discount_per_item,
                (ti.quantity * (ti.unit_price - ti.discount_per_item)) as subtotal,
                ti.created_at as created_at
            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            WHERE t.is_voided = 0
            
            UNION ALL
            
            SELECT
                'ECOMMERCE' as source,
                eoi.id as id,
                eoi.ecommerce_order_id as reference_id,
                eo.created_at as transaction_date,
                eo.id as transaction_number,
                eo.branch_id,
                eoi.product_id,
                eoi.quantity as quantity,
                eoi.price as unit_price,
                IF(eoi.quantity > 0, ((eoi.price * eoi.quantity) - eoi.subtotal) / eoi.quantity, 0) as discount_per_item,
                eoi.subtotal as subtotal,
                eoi.created_at as created_at
            FROM ecommerce_order_items eoi
            JOIN ecommerce_orders eo ON eoi.ecommerce_order_id = eo.id
            WHERE eo.status = 'COMPLETED'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS all_sales_items");
    }
};
