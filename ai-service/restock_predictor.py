import pandas as pd
import mysql.connector
import os
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "sm_inventory")
    )

def predict_restock_needs(days_history=30, target_days_supply=30, branch_id=None):
    """
    Analyzes sales velocity and compares with stock to suggest restocking.
    Calculations:
    - Net Sales = Sales (where is_voided=0)
    - Daily Velocity = Net Sales / days_history
    - Safety Stock = Daily Velocity * lead_time_days
    - Target Stock = (Daily Velocity * target_days_supply) + Safety Stock
    - Suggested Order = Target Stock - Current Stock
    """
    conn = get_db_connection()
    try:
        # Fetch Sales data for the last N days (ONLY is_voided=false)
        # Assuming transactions has `is_voided` and `transaction_type`
        # In a generic POS, transaction_type='sale' and 'return' might exist, or just positive/negative quantities.
        # We sum all quantities for successful transactions.
        if branch_id and str(branch_id).strip() and str(branch_id).lower() != 'null':
            branch_filter_sales = f"AND t.branch_id = '{branch_id}'"
            query_products = f"""
                SELECT 
                    p.id as product_id,
                    p.name as product_name,
                    p.sku,
                    p.supplier_id,
                    s.name as supplier_name,
                    COALESCE(p.lead_time_days, 7) as lead_time_days,
                    COALESCE(st_filter.quantity_on_hand, 0) as current_stock,
                    COALESCE(st_filter.desired_inventory_days, {target_days_supply}) as dynamic_target_days
                FROM products p
                JOIN stocks st_filter ON st_filter.product_id = p.id AND st_filter.branch_id = '{branch_id}' AND st_filter.is_active = 1
                LEFT JOIN suppliers s ON s.id = p.supplier_id
                WHERE p.is_active = 1
            """
        else:
            branch_filter_sales = ""
            query_products = f"""
                SELECT 
                    p.id as product_id,
                    p.name as product_name,
                    p.sku,
                    p.supplier_id,
                    s.name as supplier_name,
                    COALESCE(p.lead_time_days, 7) as lead_time_days,
                    COALESCE(st.current_stock, 0) as current_stock,
                    COALESCE(st.dynamic_target_days, {target_days_supply}) as dynamic_target_days
                FROM products p
                LEFT JOIN (
                    SELECT 
                        product_id,
                        SUM(quantity_on_hand) as current_stock,
                        MAX(desired_inventory_days) as dynamic_target_days
                    FROM stocks
                    WHERE is_active = 1
                    GROUP BY product_id
                ) st ON st.product_id = p.id
                LEFT JOIN suppliers s ON s.id = p.supplier_id
                WHERE p.is_active = 1
            """
            
        query_sales = f"""
            SELECT 
                ti.product_id,
                SUM(CASE WHEN t.transaction_date >= DATE_SUB(NOW(), INTERVAL {days_history} DAY) THEN ti.quantity ELSE 0 END) as sold_30d,
                SUM(CASE WHEN t.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN ti.quantity ELSE 0 END) as sold_90d
            FROM transaction_items ti
            JOIN transactions t ON t.id = ti.transaction_id
            WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
              AND (t.is_voided = 0 OR t.is_voided IS NULL)
              {branch_filter_sales}
            GROUP BY ti.product_id
        """
        sales_df = pd.read_sql(query_sales, conn)
        products_df = pd.read_sql(query_products, conn)

        # Dictionary lookup for O(1) matching instead of slow O(N*M) iterrows
        sales_dict = sales_df.set_index('product_id').to_dict('index') if not sales_df.empty else {}
        results = []

        for p in products_df.to_dict('records'):
            prod_id = p['product_id']
            sales_info = sales_dict.get(prod_id)
            
            sold_30d = float(sales_info['sold_30d']) if sales_info else 0.0
            sold_90d = float(sales_info['sold_90d']) if sales_info else 0.0
            
            # Stockout Paradox Logic:
            if sold_30d == 0 and sold_90d > 0:
                daily_velocity = sold_90d / 90
            else:
                daily_velocity = sold_30d / days_history

            current_stock = float(p['current_stock'])
            lead_time_days = int(p['lead_time_days'])
            product_target_days = int(p['dynamic_target_days'])

            if daily_velocity > 0:
                safety_stock = round(daily_velocity * 3, 2)
                reorder_point = round((lead_time_days * daily_velocity) + safety_stock, 2)
                target_stock = round((product_target_days * daily_velocity) + safety_stock, 2)
                
                if current_stock < 0:
                    suggested_order = int(abs(current_stock) + target_stock)
                    status = "CRITICAL"
                elif current_stock == 0:
                    suggested_order = int(max(1, target_stock))
                    status = "CRITICAL"
                elif current_stock <= reorder_point:
                    suggested_order = int(max(0, target_stock - current_stock))
                    status = "CRITICAL" if current_stock <= safety_stock else ("REORDER" if suggested_order > 0 else "OK")
                else:
                    suggested_order = 0
                    status = "OK"
            else:
                # ADS == 0: Tidak ada penjualan (Dead stock / slow moving), tidak perlu order
                safety_stock = 0.0
                reorder_point = 0.0
                target_stock = 0.0
                suggested_order = 0
                status = "OK"

            results.append({
                "product_id": prod_id,
                "sku": p['sku'],
                "name": p['product_name'],
                "supplier_name": p['supplier_name'] if pd.notna(p['supplier_name']) and p['supplier_name'] else "Umum",
                "current_qty": current_stock,
                "total_sold_30d": sold_30d,
                "ads": round(daily_velocity, 2),
                "lead_time": lead_time_days,
                "target_days": product_target_days,
                "reorder_point": round(reorder_point, 2),
                "suggested_qty": int(suggested_order),
                "status": status
            })

        return results

    except Exception as e:
        print(f"Error in restock prediction: {e}")
        return []
    finally:
        conn.close()
