"""
Demand Forecaster Module (Phase 2 - Machine Learning)

This module is designed to use advanced time-series forecasting (like Prophet or XGBoost)
to predict future demand based on historical transaction data.

Note:
This module is currently in 'Placeholder' status for Phase 1.
It will be activated in Phase 2 (Month 6+) once sufficient historical data
is gathered in the new POS system.

Future implementation will look like:
1. Fetch 1+ years of daily sales data from `transaction_items`.
2. Apply `prophet` to account for:
   - Weekly seasonality (e.g., weekends are busier).
   - Yearly seasonality (e.g., Ramadhan, Christmas).
3. Return AI-driven `suggested_qty` instead of simple moving averages.
"""

def forecast_demand_ml(product_id, branch_id=None, days_ahead=30):
    # Placeholder: currently falls back to returning an empty model or error
    raise NotImplementedError("ML Demand Forecasting will be activated in Phase 2 once 6 months of data is collected.")
