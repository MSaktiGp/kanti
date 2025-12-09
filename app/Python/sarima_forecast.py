import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import statsmodels.api as sm
from statsmodels.tsa.statespace.sarimax import SARIMAX
import json
import warnings

# Abaikan warning agar output bersih
warnings.filterwarnings("ignore")

# 1. DATA INPUT - All 10 commodities from dashboard (index.blade.php)
# Base prices and historical ranges for each commodity
commodities_data = {
    "Cabai Merah": {"base": 45000, "volatility": 5000},
    "Bawang Merah": {"base": 32000, "volatility": 3500},
    "Bawang Putih": {"base": 38000, "volatility": 4000},
    "Beras": {"base": 14000, "volatility": 1500},
    "Daging Ayam Ras": {"base": 35000, "volatility": 3000},
    "Telur Ayam Ras": {"base": 28000, "volatility": 2500},
    "Cabai Rawit": {"base": 55000, "volatility": 6000},
    "Daging Sapi": {"base": 130000, "volatility": 10000},
    "Gula Pasir": {"base": 17000, "volatility": 2000},
    "Minyak Goreng": {"base": 16000, "volatility": 2000}
}

# Regions from dashboard (index.blade.php)
regions = [
    'Kota Jambi', 'Kab. Kerinci', 'Kab. Merangin', 'Muaro Jambi', 
    'Kab. Batanghari', 'Kab. Sarolangun', 'Kab. Bungo', 'Kab. Tebo', 
    'Tanjung Jabung Barat', 'Tanjung Jabung Timur', 'Kota Sungai Penuh'
]


def generate_history_for_commodity(commodity_name, region, num_weeks=12):
    """Generate synthetic historical price data for a commodity in a region."""
    data = commodities_data.get(commodity_name, {"base": 30000, "volatility": 3000})
    base_price = data["base"]
    volatility = data["volatility"]
    
    history = []
    current_price = base_price
    
    for week in range(1, num_weeks + 1):
        # Add seasonal pattern + random noise
        seasonal = np.sin(week / 4) * (volatility * 0.5)
        noise = np.random.normal(0, volatility * 0.3)
        current_price += seasonal + noise
        current_price = max(current_price, base_price * 0.5)  # Floor price
        
        history.append({
            "week": f"2025-W{30 + week}",
            "price": max(int(current_price), 1000)
        })
    
    return history


def run_sarima_forecast(commodity_name, region_name, data_json=None):
    """Run SARIMA forecast for a single commodity and region."""
    
    print(f"\n{'='*60}")
    print(f"Forecasting: {commodity_name} - {region_name}")
    print(f"{'='*60}")
    
    # If no JSON provided, generate synthetic history
    if data_json is None:
        history = generate_history_for_commodity(commodity_name, region_name)
        data = {
            "commodity": commodity_name,
            "region": region_name,
            "history": history
        }
    else:
        data = json.loads(data_json)
    
    df = pd.DataFrame(data['history'])
    df['price'] = pd.to_numeric(df['price'])
    
    # Membuat Time Series
    series = df['price']
    
    # 3. KONFIGURASI MODEL SARIMA
    # Parameter (p,d,q)(P,D,Q)s untuk data musiman mingguan
    order = (1, 1, 1)
    seasonal_order = (1, 1, 1, 4) 
    
    try:
        model = SARIMAX(series, 
                        order=order, 
                        seasonal_order=seasonal_order, 
                        enforce_stationarity=False, 
                        enforce_invertibility=False)
        
        model_fit = model.fit(disp=False)
        
        # 4. FORECASTING (4 Minggu ke depan)
        n_steps = 4
        forecast_result = model_fit.get_forecast(steps=n_steps)
        forecast_mean = forecast_result.predicted_mean
        conf_int = forecast_result.conf_int()
        
        # Menghitung MAPE (Akurasi pada data latih)
        fitted_values = model_fit.fittedvalues
        mape = np.mean(np.abs((fitted_values - series) / series)) * 100
        
        print(f"Model MAPE (Error Rate): {mape:.2f}%")
        print(f"Akurasi: {100-mape:.2f}%")
        print(f"\nHarga Saat Ini: Rp {series.iloc[-1]:,.0f}")
        print(f"\n--- PREDIKSI 4 MINGGU KEDEPAN ---")
        
        results = []
        for i, price in enumerate(forecast_mean):
            lower = conf_int.iloc[i, 0]
            upper = conf_int.iloc[i, 1]
            print(f"Minggu +{i+1}: Rp {price:,.0f} (Range: Rp {lower:,.0f} - {upper:,.0f})")
            results.append({
                "week": i + 1,
                "forecast": float(price),
                "lower_bound": float(lower),
                "upper_bound": float(upper)
            })
        
        return {
            "commodity": commodity_name,
            "region": region_name,
            "current_price": float(series.iloc[-1]),
            "mape": float(mape),
            "accuracy": float(100 - mape),
            "forecast_weeks": results
        }
    
    except Exception as e:
        print(f"Error fitting model for {commodity_name}: {str(e)}")
        return None


def run_batch_forecast_with_visualization():
    """Run SARIMA forecast for all 10 commodities with visualizations."""
    print("\n" + "="*70)
    print("BATCH SARIMA FORECASTING - ALL COMMODITIES")
    print("="*70)
    
    all_results = []
    num_commodities = len(commodities_data)
    
    # Create figure with subplots for all commodities (5 rows x 2 cols)
    fig, axes = plt.subplots(5, 2, figsize=(16, 20))
    axes = axes.flatten()
    
    for idx, (commodity_name, _) in enumerate(commodities_data.items()):
        # Use first region as representative (you can loop all regions if needed)
        region = regions[0]
        
        # Generate and forecast
        history = generate_history_for_commodity(commodity_name, region)
        df = pd.DataFrame(history)
        df['price'] = pd.to_numeric(df['price'])
        series = df['price']
        
        try:
            # Fit SARIMA model
            model = SARIMAX(series, 
                            order=(1, 1, 1), 
                            seasonal_order=(1, 1, 1, 4),
                            enforce_stationarity=False, 
                            enforce_invertibility=False)
            model_fit = model.fit(disp=False)
            
            # Forecast
            n_steps = 4
            forecast_result = model_fit.get_forecast(steps=n_steps)
            forecast_mean = forecast_result.predicted_mean
            conf_int = forecast_result.conf_int()
            
            # Calculate MAPE
            fitted_values = model_fit.fittedvalues
            mape = np.mean(np.abs((fitted_values - series) / series)) * 100
            
            # Store result
            forecast_weeks = []
            for i, price in enumerate(forecast_mean):
                forecast_weeks.append({
                    "week": i + 1,
                    "forecast": float(price),
                    "lower_bound": float(conf_int.iloc[i, 0]),
                    "upper_bound": float(conf_int.iloc[i, 1])
                })
            
            result = {
                "commodity": commodity_name,
                "region": region,
                "current_price": float(series.iloc[-1]),
                "mape": float(mape),
                "accuracy": float(100 - mape),
                "forecast_weeks": forecast_weeks
            }
            all_results.append(result)
            
            # VISUALIZATION on subplot
            ax = axes[idx]
            last_idx = df.index[-1]
            forecast_index = np.arange(last_idx + 1, last_idx + 1 + n_steps)
            
            # Plot history
            ax.plot(df.index, df['price'], label='Data Historis', marker='o', color='#2563eb', linewidth=2)
            
            # Plot forecast
            plot_forecast_x = np.insert(forecast_index, 0, last_idx)
            plot_forecast_y = np.insert(forecast_mean.values, 0, df['price'].iloc[-1])
            ax.plot(plot_forecast_x, plot_forecast_y, label='Prediksi SARIMA', 
                   linestyle='--', marker='x', color='#dc2626', linewidth=2)
            
            # Fill CI
            ax.fill_between(forecast_index, 
                           conf_int.iloc[:, 0], 
                           conf_int.iloc[:, 1], 
                           color='#fca5a5', alpha=0.3, label='95% CI')
            
            ax.set_title(f"{commodity_name}\nMAPE: {mape:.1f}% | Akurasi: {100-mape:.1f}%", 
                        fontweight='bold', fontsize=10)
            ax.set_xlabel('Minggu')
            ax.set_ylabel('Harga (Rp)')
            ax.legend(fontsize=8)
            ax.grid(True, linestyle=':', alpha=0.5)
            
            print(f"✓ {commodity_name}: MAPE {mape:.2f}% | Akurasi {100-mape:.2f}%")
        
        except Exception as e:
            print(f"✗ {commodity_name}: Error - {str(e)}")
    
    # Adjust layout dan simpan
    plt.tight_layout()
    plt.savefig('forecast_all_commodities.png', dpi=150, bbox_inches='tight')
    print(f"\n✓ Grafik disimpan ke: forecast_all_commodities.png")
    plt.show()
    
    # Summary statistics
    print(f"\n{'='*70}")
    print(f"SUMMARY: Berhasil memproses {len(all_results)}/{num_commodities} komoditas")
    print(f"{'='*70}\n")
    
    return all_results


# EXECUTION OPTIONS
if __name__ == "__main__":
    # Option 1: Run batch forecast for all 10 commodities with visualizations
    print("\n🔬 Running Batch SARIMA Forecast for All 10 Commodities...\n")
    batch_results = run_batch_forecast_with_visualization()
    
    # Option 2: Run single commodity forecast (example)
    # Uncomment to test single forecast:
    # print("\n📊 Example: Single Commodity Forecast")
    # single_result = run_sarima_forecast("Cabai Merah", "Kab. Kerinci")
    
    print("\n✅ Forecast Complete!")