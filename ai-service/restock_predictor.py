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
        branch_filter_sales = f"AND t.branch_id = '{branch_id}'" if branch_id else ""
        query_sales = f"""
            SELECT 
                ti.product_id,
                SUM(CASE WHEN t.transaction_date >= DATE_SUB(NOW(), INTERVAL {days_history} DAY) THEN ti.quantity ELSE 0 END) as sold_30d,
                SUM(CASE WHEN t.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN ti.quantity ELSE 0 END) as sold_90d
            FROM transaction_items ti
            JOIN transactions t ON t.id = ti.transaction_id
            WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
              AND t.is_voided = 0
              {branch_filter_sales}
            GROUP BY ti.product_id
        """
        sales_df = pd.read_sql(query_sales, conn)

        if sales_df.empty:
            return []

        # Fetch Products data (current stock and lead_time_days)
        # We need sum of current stock across all branches, or just the product's master stock if it exists.
        # Often in Laravel, stock is in `stocks` table per branch.
        branch_filter_stocks = f"AND st.branch_id = '{branch_id}'" if branch_id else ""
        query_products = f"""
            SELECT 
                p.id as product_id,
                p.name as product_name,
                p.sku,
                p.supplier_id,
                s.name as supplier_name,
                COALESCE(p.lead_time_days, 7) as lead_time_days,
                (SELECT COALESCE(SUM(st.quantity), 0) FROM stocks st WHERE st.product_id = p.id {branch_filter_stocks}) as current_stock
            FROM products p
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE p.is_active = 1
        """
        products_df = pd.read_sql(query_products, conn)

        # Instead of generic merge, we map directly to ensure zero-sales products are handled
        results = []
        for _, row in products_df.iterrows():
            prod_id = row['product_id']
            # Find sales data
            sales_row = sales_df[sales_df['product_id'] == prod_id] if not sales_df.empty else pd.DataFrame()
            
            sold_30d = float(sales_row['sold_30d'].iloc[0]) if not sales_row.empty else 0.0
            sold_90d = float(sales_row['sold_90d'].iloc[0]) if not sales_row.empty else 0.0
            
            # Stockout Paradox Logic:
            if sold_30d == 0 and sold_90d > 0:
                daily_velocity = sold_90d / 90
            else:
                daily_velocity = sold_30d / days_history

            current_stock = float(row['current_stock'])
            lead_time_days = int(row['lead_time_days'])
            
            # Safety stock is estimated as lead_time * velocity
            safety_stock = lead_time_days * daily_velocity
            
            # Target stock is how much we want to have on hand to cover target_days_supply
            target_stock = (target_days_supply * daily_velocity) + safety_stock
            
            suggested_order = 0
            if current_stock <= safety_stock:
                suggested_order = max(0, target_stock - current_stock)
                
            results.append({
                "product_id": row['product_id'],
                "sku": row['sku'],
                "name": row['product_name'],
                "supplier_name": row['supplier_name'] if pd.notna(row['supplier_name']) else "Umum",
                "current_qty": current_stock,
                "total_sold_30d": sold_30d,
                "ads": round(daily_velocity, 2),
                "lead_time": lead_time_days,
                "reorder_point": round(safety_stock, 2),
                "suggested_qty": int(suggested_order),
                "status": "CRITICAL" if current_stock < safety_stock else ("REORDER" if current_stock <= target_stock and target_stock > 0 else "OK")
            })

        return results

    except Exception as e:
        print(f"Error in restock prediction: {e}")
        return []
    finally:
        conn.close()
