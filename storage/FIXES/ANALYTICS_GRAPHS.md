# Analytics Graphs Upgrade – Admin Reports

## What was added

### Controller: `app/Http/Controllers/Admin/ReportController.php`
Enhanced to provide:
- **$daily7Filled**: 7 days ending selected date (filled zero for missing dates) – for day-by-day revenue Detail
- **$daily30Filled**: 30 days filled – for 30-day trend
- **$monthly12Filled**: 12 months filled from `Y-m-01 -11 months` – for yearly trend
- **$cust30Filled**: New customers per day last 30 days
- **$statusBreakdown**: Count + revenue per order status
- **$categorySales**: Top 6 categories by revenue (via joins; safe try/catch)

All data uses `keyBy` + loop fill to avoid gaps in charts.

### View: `resources/views/admin/reports/index.blade.php`
- Added **Chart.js v4.4.4** CDN: `https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js`
- 7 chart canvases:
  1. `revenueChart` – Dual axis: Revenue (₱) + Orders – range toggle 7D/30D/12M – line/bar buttons
  2. `ordersChart` – Orders count – line / vertical bar toggle
  3. `customerChart` – New customers 30d – line/bar
  4. `bestSellersChart` – Best sellers qty – Vertical Bar (default), Line, Horizontal Bar
  5. `categoryChart` – Sales by category – Bar/Line
  6. `deliveryChart` – Delivery vs Pickup – Vertical Bar (default) / Doughnut
  7. `statusChart` – Order status – Bar/Line
  8. Kept original simple 7-day progress bars for quick glance

#### Toggle Logic
- CSS class `.chart-toggle-btn.active` => green bg #17611f
- Each chart group has 2-3 buttons with IDs like `revenue-line-btn`, `revenue-bar-btn`
- JS stores chart instances in `charts{}` object, destroys before recreate (Chart.js requires destroy on type change)
- Range buttons `.chart-range-btn` switch data source: `revenueRange = '7' | '30' | '12m'`
- Global palette: `['#17611f','#52b788', …]` matching design-system

#### Features
- Gradient fill for line charts (primaryGreen alpha .35 → .02)
- Tooltips dark green (#0d3311/92) with Peso formatting `₱X,XX`
- Responsive, maintainAspectRatio false, fixed container heights 260-320px
- Font Nunito matching site
- Doughnut for delivery with hoverOffset

## How to use as admin
1. Go to `/admin/reports` or Sales in sidebar
2. Pick date (max today) – all charts recalc ending that date
3. Top card: Revenue Trend – click 7 Days / 30 Days / 12 Months, then Line vs Bar
4. Each chart has its own Line / Bar toggle at top-right
5. Best Sellers also has Horizontal option for long product names

## No extra install
Chart.js via CDN – works even without `npm run build`. No vendor needed.

If you want offline:
```bash
npm install chart.js
```
Then in `resources/js/app.js`:
```js
import Chart from 'chart.js/auto';
window.Chart = Chart;
```

But CDN is sufficient.

## Future enhancements
- Export CSV/PNG: `chart.toBase64Image()`
- Filter by category / delivery_method
- Compare YoY
- Add low-stock alert chart
- Real-time via polling

