import pandas as pd
import mysql.connector
import os
import requests
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "sm_inventory")
    )

def suggest_dynamic_pricing(days_history=30):
    """
    Analyzes products with high stock and low velocity to suggest flash sales.
    Phase 1 Rule: 
    - stock > (safety_stock * 3) OR stock > 100 if safety_stock is 0
    - sales in last 30 days is very low.
    """
    conn = get_db_connection()
    try:
        # Get sales data
        query_sales = f"""
            SELECT 
                ti.product_id,
                t.branch_id,
                SUM(ti.quantity) as sold_30d
            FROM transaction_items ti
            JOIN transactions t ON t.id = ti.transaction_id
            WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL {days_history} DAY)
              AND t.is_voided = 0
            GROUP BY ti.product_id, t.branch_id
        """
        sales_df = pd.read_sql(query_sales, conn)

        # Get active stocks
        query_stocks = """
            SELECT 
                s.id as stock_id,
                s.branch_id,
                s.product_id,
                p.name as product_name,
                p.sku,
                s.quantity_on_hand,
                s.safety_stock,
                s.cost_price,
                s.selling_price
            FROM stocks s
            JOIN products p ON p.id = s.product_id
            WHERE s.is_active = 1 AND p.is_active = 1
        """
        stocks_df = pd.read_sql(query_stocks, conn)

        suggestions = []

        for _, stock in stocks_df.iterrows():
            prod_id = stock['product_id']
            branch_id = stock['branch_id']
            qty = float(stock['quantity_on_hand'])
            safety_stock = float(stock['safety_stock']) if pd.notna(stock['safety_stock']) else 0
            cost_price = float(stock['cost_price'])
            selling_price = float(stock['selling_price'])
            
            # Find sales for this branch and product
            sales_row = sales_df[(sales_df['product_id'] == prod_id) & (sales_df['branch_id'] == branch_id)]
            sold_30d = float(sales_row['sold_30d'].iloc[0]) if not sales_row.empty else 0.0
            
            daily_velocity = sold_30d / days_history
            
            # Overstock Logic
            overstock_threshold = max(safety_stock * 3, 50) # Fallback to 50 if safety_stock is 0
            
            if qty > overstock_threshold and daily_velocity < (qty / 120): # Will take > 120 days to sell
                # Suggest Discount
                # Strategy: max 30% discount, but never below cost_price
                
                # Try 20% discount first
                discount_pct = 20
                discount_amount = selling_price * (discount_pct / 100)
                final_price = selling_price - discount_amount
                
                if final_price < cost_price:
                    # Adjust to just 5% above cost if possible
                    final_price = cost_price * 1.05
                    discount_amount = selling_price - final_price
                    if discount_amount <= 0:
                        continue # Cannot discount
                        
                suggestions.append({
                    "branch_id": stock['branch_id'],
                    "product_id": stock['product_id'],
                    "sku": stock['sku'],
                    "product_name": stock['product_name'],
                    "current_stock": qty,
                    "sold_30d": sold_30d,
                    "suggested_discount_amount": float(discount_amount),
                    "original_price": selling_price,
                    "final_price": float(final_price)
                })

        return suggestions

    except Exception as e:
        print(f"Error in dynamic pricing engine: {e}")
        return []
    finally:
        conn.close()

if __name__ == "__main__":
    print(suggest_dynamic_pricing())
