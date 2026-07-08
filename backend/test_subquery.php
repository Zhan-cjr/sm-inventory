<?php
$subquery = DB::table('transactions as t')
    ->select([
        't.id',
        't.local_transaction_id',
        't.transaction_date',
        't.branch_id',
        't.final_amount',
        't.payment_method',
        't.payment_details',
        DB::raw("'OFFLINE' as transaction_source"),
        DB::raw("COALESCE((
            SELECT SUM(
                COALESCE(
                    (SELECT SUM(sbd.quantity * sb.cost_price) 
                     FROM stock_batch_deductions sbd 
                     JOIN stock_batches sb ON sbd.stock_batch_id = sb.id 
                     WHERE sbd.transaction_item_id = ti.id),
                    ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0)
                )
            )
            FROM transaction_items ti
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = t.branch_id
            WHERE ti.transaction_id = t.id
        ), 0) as raw_cogs")
    ])
    ->where('t.is_voided', false)
    ->unionAll(
        DB::table('ecommerce_orders as eo')
            ->select([
                'eo.id',
                'eo.id as local_transaction_id',
                'eo.created_at as transaction_date',
                'eo.branch_id',
                'eo.total_amount as final_amount',
                'eo.payment_method',
                DB::raw("NULL as payment_details"),
                DB::raw("'ONLINE' as transaction_source"),
                DB::raw("COALESCE((
                    SELECT SUM(
                        COALESCE(
                            (SELECT SUM(sbd.quantity * sb.cost_price) 
                             FROM stock_batch_deductions sbd 
                             JOIN stock_batches sb ON sbd.stock_batch_id = sb.id 
                             WHERE sbd.ecommerce_order_item_id = ei.id),
                            ei.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0)
                        )
                    )
                    FROM ecommerce_order_items ei
                    JOIN products p ON ei.product_id = p.id
                    LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = eo.branch_id
                    WHERE ei.ecommerce_order_id = eo.id
                ), 0) as raw_cogs")
            ])
            ->where('eo.status', 'COMPLETED')
    );

$results = \App\Models\Transaction::query()->fromSub($subquery, 'transactions')->limit(3)->get();
print_r($results->toArray());
