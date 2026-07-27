@extends('admin.layouts.app')
@section('title', 'Sales Analytics | Admin')
@section('header', 'Sales Analytics')
@section('content')

<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-black mb-1">📊 Sales Analytics Dashboard</h1>
    <p class="text-sm text-[#5a7a5c]">
      {{ date('F j, Y', $ts) }} · Week: {{ date('M j', strtotime($weekStart)) }} – {{ date('M j, Y', strtotime($weekEnd)) }} · All charts interactive
    </p>
  </div>
  <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
    <input type="date" name="date" value="{{ $selectedDate }}" max="{{ date('Y-m-d') }}"
           class="border border-[rgba(27,94,32,0.12)] rounded-xl px-4 py-2.5 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 bg-white"
           onchange="this.form.submit()" />
  </form>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <p class="text-xs text-[#5a7a5c] font-bold flex items-center gap-1"><span>💰</span> Today's Sales</p>
    <p class="text-2xl font-black text-[#17611f]">₱{{ number_format($daySales, 2) }}</p>
    <p class="text-[11px] text-[#9e9e9e] mt-1">{{ $dayOrders }} order(s) · {{ $selectedDate }}</p>
  </div>
  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <p class="text-xs text-[#5a7a5c] font-bold">📅 This Week</p>
    <p class="text-2xl font-black text-[#17611f]">₱{{ number_format($weekSales, 2) }}</p>
    <p class="text-[11px] text-[#9e9e9e] mt-1">{{ date('M j', strtotime($weekStart)) }} – {{ date('M j', strtotime($weekEnd)) }}</p>
  </div>
  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <p class="text-xs text-[#5a7a5c] font-bold">🗓️ This Month</p>
    <p class="text-2xl font-black text-[#17611f]">₱{{ number_format($monthSales, 2) }}</p>
    <p class="text-[11px] text-[#9e9e9e] mt-1">{{ date('F Y', $ts) }}</p>
  </div>
  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <p class="text-xs text-[#5a7a5c] font-bold">📦 Total Orders</p>
    <p class="text-2xl font-black">{{ $totalOrders }}</p>
    <p class="text-[11px] text-[#9e9e9e] mt-1">Avg ₱{{ number_format($avgOrder,2) }}</p>
  </div>
  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <p class="text-xs text-[#5a7a5c] font-bold">🚚 Delivery</p>
    <p class="text-2xl font-black">{{ $deliveryCount }}</p>
    <p class="text-[11px] text-[#9e9e9e] mt-1">{{ date('F', $ts) }}</p>
  </div>
  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <p class="text-xs text-[#5a7a5c] font-bold">🛍️ Pick-Up</p>
    <p class="text-2xl font-black">{{ $pickupCount }}</p>
    <p class="text-[11px] text-[#9e9e9e] mt-1">{{ date('F', $ts) }}</p>
  </div>
  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <p class="text-xs text-[#5a7a5c] font-bold">👥 Customers</p>
    <p class="text-2xl font-black">{{ $totalCust }} <span class="text-sm text-green-600">+{{ $newCust }} today</span></p>
    <p class="text-[11px] text-[#9e9e9e] mt-1">Total registered</p>
  </div>
  <div class="bg-white rounded-xl border p-5 shadow-sm">
    <p class="text-xs text-[#5a7a5c] font-bold">🏆 Best Seller</p>
    <p class="text-sm font-black truncate">{{ $bestSellers->first()->product_name ?? '—' }}</p>
    <p class="text-[11px] text-[#5a7a5c] mt-1">{{ $bestSellers->first()->total_qty ?? 0 }} sold</p>
  </div>
</div>

<!-- CHARTJS CDN -->
@push('styles')
<style>
.chart-toggle-btn{transition:all .18s ease}
.chart-toggle-btn.active{background:#17611f;color:#fff;border-color:#17611f}
.chart-card{transition:all .2s ease}
.chart-card:hover{box-shadow:0 8px 28px rgba(27,94,32,.08)}
</style>
@endpush

<div class="grid lg:grid-cols-2 gap-6 mb-6">

  <!-- Revenue Trend Chart – 7/30 days with line/bar toggle -->
  <div class="chart-card bg-white rounded-2xl border p-5 lg:col-span-2">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="font-black text-[15px]">💹 Revenue Trend</h2>
        <p class="text-[11px] text-[#5a7a5c] mt-0.5">Select range & chart type – Line vs Vertical Bar</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center bg-[#f4faf5] rounded-full p-1 border">
          <button data-range="7" class="chart-range-btn active px-3 py-1 text-xs font-bold rounded-full bg-[#17611f] text-white">7 Days</button>
          <button data-range="30" class="chart-range-btn px-3 py-1 text-xs font-bold rounded-full text-[#5a7a5c] hover:bg-white">30 Days</button>
          <button data-range="12m" class="chart-range-btn px-3 py-1 text-xs font-bold rounded-full text-[#5a7a5c] hover:bg-white">12 Months</button>
        </div>
        <div class="flex items-center bg-white rounded-full p-1 border border-[rgba(27,94,32,0.12)]">
          <button id="revenue-line-btn" class="chart-toggle-btn active px-3 py-1.5 text-xs font-bold rounded-full border border-transparent flex items-center gap-1">📈 Line</button>
          <button id="revenue-bar-btn" class="chart-toggle-btn px-3 py-1.5 text-xs font-bold rounded-full border border-transparent flex items-center gap-1 text-[#5a7a5c]">📊 Bar</button>
        </div>
      </div>
    </div>
    <div class="relative h-[320px] w-full">
      <canvas id="revenueChart"></canvas>
    </div>
    <div class="mt-3 flex items-center gap-4 text-[11px] text-[#9e9e9e]">
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-[#17611f] inline-block"></span> Revenue (₱)</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-[#52b788]/60 inline-block"></span> Orders</span>
    </div>
  </div>

  <!-- Orders Count Chart -->
  <div class="chart-card bg-white rounded-2xl border p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-black text-[15px]">📦 Orders Count Trend</h2>
      <div class="flex items-center bg-white rounded-full p-1 border border-[rgba(27,94,32,0.12)]">
        <button id="orders-line-btn" class="chart-toggle-btn active px-3 py-1.5 text-xs font-bold rounded-full">📈 Line</button>
        <button id="orders-bar-btn" class="chart-toggle-btn px-3 py-1.5 text-xs font-bold rounded-full text-[#5a7a5c]">📊 Bar</button>
      </div>
    </div>
    <div class="relative h-[260px]">
      <canvas id="ordersChart"></canvas>
    </div>
  </div>

  <!-- Customer Growth -->
  <div class="chart-card bg-white rounded-2xl border p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-black text-[15px]">👥 Customer Growth (30d)</h2>
      <div class="flex items-center bg-white rounded-full p-1 border border-[rgba(27,94,32,0.12)]">
        <button id="cust-line-btn" class="chart-toggle-btn active px-3 py-1.5 text-xs font-bold rounded-full">📈 Line</button>
        <button id="cust-bar-btn" class="chart-toggle-btn px-3 py-1.5 text-xs font-bold rounded-full text-[#5a7a5c]">📊 Bar</button>
      </div>
    </div>
    <div class="relative h-[260px]">
      <canvas id="customerChart"></canvas>
    </div>
  </div>

  <!-- Best Sellers Vertical Bar -->
  <div class="chart-card bg-white rounded-2xl border p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-black text-[15px]">🏆 Best Sellers – Vertical Bar</h2>
      <div class="flex items-center bg-white rounded-full p-1 border border-[rgba(27,94,32,0.12)]">
        <button id="best-bar-btn" class="chart-toggle-btn active px-3 py-1.5 text-xs font-bold rounded-full">📊 Vertical Bar</button>
        <button id="best-line-btn" class="chart-toggle-btn px-3 py-1.5 text-xs font-bold rounded-full text-[#5a7a5c]">📈 Line</button>
        <button id="best-hbar-btn" class="chart-toggle-btn px-3 py-1.5 text-xs font-bold rounded-full text-[#5a7a5c]">📊 Horizontal</button>
      </div>
    </div>
    <div class="relative h-[300px]">
      <canvas id="bestSellersChart"></canvas>
    </div>
    <div class="mt-3 space-y-1">
      @foreach ($bestSellers as $i => $bs)
        <div class="flex items-center justify-between text-xs border-b border-[rgba(27,94,32,0.05)] py-1.5">
          <span class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-[#e8f5e9] flex items-center justify-center font-black text-[10px] text-[#17611f]">{{ $i+1 }}</span> <span class="font-bold truncate max-w-[160px]">{{ $bs->product_name }}</span></span>
          <span class="text-[#5a7a5c]">{{ $bs->total_qty }} sold · ₱{{ number_format($bs->revenue,2) }}</span>
        </div>
      @endforeach
    </div>
  </div>

  <!-- Category Sales -->
  <div class="chart-card bg-white rounded-2xl border p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-black text-[15px]">🥗 Sales by Category</h2>
      <div class="flex items-center bg-white rounded-full p-1 border border-[rgba(27,94,32,0.12)]">
        <button id="cat-bar-btn" class="chart-toggle-btn active px-3 py-1.5 text-xs font-bold rounded-full">📊 Bar</button>
        <button id="cat-line-btn" class="chart-toggle-btn px-3 py-1.5 text-xs font-bold rounded-full text-[#5a7a5c]">📈 Line</button>
      </div>
    </div>
    <div class="relative h-[300px]">
      <canvas id="categoryChart"></canvas>
    </div>
  </div>

  <!-- Delivery vs Pickup -->
  <div class="chart-card bg-white rounded-2xl border p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-black text-[15px]">🚚 Delivery vs 🛍️ Pick-Up</h2>
      <div class="flex items-center bg-white rounded-full p-1 border border-[rgba(27,94,32,0.12)]">
        <button id="del-bar-btn" class="chart-toggle-btn active px-3 py-1.5 text-xs font-bold rounded-full">📊 Bar</button>
        <button id="del-doughnut-btn" class="chart-toggle-btn px-3 py-1.5 text-xs font-bold rounded-full text-[#5a7a5c]">🍩 Doughnut</button>
      </div>
    </div>
    <div class="relative h-[260px] flex items-center justify-center">
      <canvas id="deliveryChart"></canvas>
    </div>
    @php $delPct = ($deliveryCount + $pickupCount) > 0 ? round(($deliveryCount / ($deliveryCount + $pickupCount)) * 100) : 0; @endphp
    <div class="mt-4 grid grid-cols-2 gap-3 text-center">
      <div class="bg-[#f4faf5] rounded-xl p-3"><p class="text-xs text-[#5a7a5c]">Delivery</p><p class="text-xl font-black text-[#17611f]">{{ $deliveryCount }}</p><p class="text-[10px] text-[#9e9e9e]">{{ $delPct }}%</p></div>
      <div class="bg-gray-50 rounded-xl p-3"><p class="text-xs text-[#5a7a5c]">Pick-Up</p><p class="text-xl font-black">{{ $pickupCount }}</p><p class="text-[10px] text-[#9e9e9e]">{{ 100-$delPct }}%</p></div>
    </div>
  </div>

  <!-- Order Status Breakdown -->
  <div class="chart-card bg-white rounded-2xl border p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-black text-[15px]">📋 Order Status Breakdown</h2>
      <div class="flex items-center bg-white rounded-full p-1 border border-[rgba(27,94,32,0.12)]">
        <button id="status-bar-btn" class="chart-toggle-btn active px-3 py-1.5 text-xs font-bold rounded-full">📊 Bar</button>
        <button id="status-line-btn" class="chart-toggle-btn px-3 py-1.5 text-xs font-bold rounded-full text-[#5a7a5c]">📈 Line</button>
      </div>
    </div>
    <div class="relative h-[260px]">
      <canvas id="statusChart"></canvas>
    </div>
  </div>

  <!-- 7-day Original simple bar list (keep for quick glance) -->
  <div class="bg-white rounded-2xl border p-5 lg:col-span-2">
    <h2 class="font-black text-lg mb-4">📈 7-Day Revenue Detail (ending {{ date('M j', strtotime($chartEnd)) }})</h2>
    <div class="space-y-2">
      @php $revs = collect($daily7Filled)->pluck('rev')->toArray(); $maxRev = !empty($revs) ? max($revs) : 1; @endphp
      @foreach ($daily7Filled as $d)
        @php $pct = $maxRev > 0 ? ($d['rev'] / $maxRev) * 100 : 0; @endphp
        <div class="flex items-center gap-3">
          <span class="text-xs text-[#5a7a5c] w-24 font-bold">{{ $d['label'] }}</span>
          <div class="flex-1 bg-[#e8f5e9] rounded-full h-6 overflow-hidden">
            <div class="bg-[#17611f] h-full rounded-full flex items-center pl-2 text-xs text-white font-bold" style="width:{{ max($pct, 12) }}%">₱{{ number_format($d['rev'], 0) }}</div>
          </div>
          <span class="text-xs text-[#9e9e9e] w-20 text-right">{{ $d['cnt'] }} orders</span>
        </div>
      @endforeach
    </div>
  </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
// ---------- Data from backend ----------
const daily7 = @json($daily7Filled);
const daily30 = @json($daily30Filled);
const monthly12 = @json($monthly12Filled);
const bestSellers = @json($bestSellers);
const categorySales = @json($categorySales);
const statusBreakdown = @json($statusBreakdown);
const customerGrowth = @json($cust30Filled);
const deliveryCount = {{ $deliveryCount }};
const pickupCount = {{ $pickupCount }};

const primaryGreen = '#17611f';
const lightGreen = '#52b788';
const lightBg = '#e8f5e9';
const palette = ['#17611f','#52b788','#81c784','#2e7d32','#aed581','#388e3c','#66bb6a','#43a047'];

// helpers
function makeGradient(ctx, color1, color2){
  const g = ctx.createLinearGradient(0,0,0,300);
  g.addColorStop(0, color1);
  g.addColorStop(1, color2);
  return g;
}

let charts = {};

function createOrUpdateChart(id, type, data, options){
  const canvas = document.getElementById(id);
  if (!canvas) return;
  if (charts[id]){ charts[id].destroy(); }
  const ctx = canvas.getContext('2d');
  charts[id] = new Chart(ctx, { type: type, data: data, options: options });
}

const commonOptions = {
  responsive:true,
  maintainAspectRatio:false,
  interaction:{ mode:'index', intersect:false },
  plugins:{
    legend:{ display:true, labels:{ usePointStyle:true, boxWidth:8, font:{ family:'Nunito', weight:'700', size:11 } } },
    tooltip:{
      backgroundColor:'rgba(13,51,17,0.92)',
      titleFont:{ family:'Nunito', weight:'800' },
      bodyFont:{ family:'Nunito' },
      padding:10,
      cornerRadius:12,
      callbacks:{
        label: (ctx)=>{
          let v = ctx.parsed.y !== undefined ? ctx.parsed.y : ctx.parsed;
          if (ctx.dataset.label && ctx.dataset.label.includes('Revenue')) return `${ctx.dataset.label}: ₱${Number(v).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})}`;
          return `${ctx.dataset.label}: ${v}`;
        }
      }
    }
  },
  scales:{
    x:{ grid:{ display:false }, ticks:{ font:{ family:'Nunito', size:11, weight:'600' }, color:'#5a7a5c' } },
    y:{ beginAtZero:true, grid:{ color:'rgba(27,94,32,0.08)' }, ticks:{ font:{ family:'Nunito', size:11 }, color:'#5a7a5c' } }
  }
};

// Revenue + Orders combined
let revenueRange = '7';
let revenueType = 'line';

function getRevenueData(){
  let source = revenueRange === '7' ? daily7 : revenueRange === '30' ? daily30 : monthly12;
  const labels = source.map(r=> r.label);
  return {
    labels: labels,
    datasets:[
      {
        label:'Revenue (₱)',
        data: source.map(r=> r.rev),
        borderColor: primaryGreen,
        backgroundColor: (ctx)=>{
          const chart = ctx.chart;
          const {ctx: c, chartArea} = chart;
          if (!chartArea) return 'rgba(23,97,31,0.2)';
          return makeGradient(c, 'rgba(23,97,31,0.35)', 'rgba(23,97,31,0.02)');
        },
        fill: revenueType==='line',
        tension:0.38,
        borderWidth:2.5,
        pointRadius: revenueRange==='12m'?3:0,
        pointHoverRadius:6,
        pointBackgroundColor: primaryGreen,
      },
      {
        label:'Orders',
        data: source.map(r=> r.cnt),
        borderColor: lightGreen,
        backgroundColor: revenueType==='bar' ? 'rgba(82,183,136,0.7)' : 'rgba(82,183,136,0.15)',
        type: revenueType==='line' ? 'line' : 'bar',
        borderDash: revenueType==='line' ? [6,6] : [],
        yAxisID:'y1',
        tension:0.35,
        borderWidth:2,
        pointRadius: revenueRange==='12m'?3:0,
      }
    ]
  };
}
function getRevenueOptions(){
  return {
    ...commonOptions,
    scales:{
      ...commonOptions.scales,
      y1:{ beginAtZero:true, position:'right', grid:{ display:false }, ticks:{ font:{ family:'Nunito', size:10 }, color:'#5a7a5c' } }
    },
    plugins:{
      ...commonOptions.plugins,
      legend:{...commonOptions.plugins.legend, position:'top'}
    }
  };
}
function renderRevenue(){
  const data = getRevenueData();
  const finalType = revenueType; // main type for chart.js; second dataset overrides
  createOrUpdateChart('revenueChart', finalType, data, getRevenueOptions());
}

// Orders only
let ordersType='line';
function renderOrders(){
  let source = revenueRange==='7'?daily7: revenueRange==='30'?daily30: monthly12;
  const data={
    labels: source.map(r=>r.label),
    datasets:[{
      label:'Orders Count',
      data: source.map(r=>r.cnt),
      borderColor: primaryGreen,
      backgroundColor: ordersType==='bar' ? primaryGreen : 'rgba(23,97,31,0.12)',
      fill: ordersType==='line',
      tension:0.4,
      borderWidth:2.5,
      borderRadius: ordersType==='bar'?8:0,
    }]
  };
  createOrUpdateChart('ordersChart', ordersType, data, commonOptions);
}

// Customer growth
let custType='line';
function renderCustomer(){
  const data={
    labels: customerGrowth.map(r=>r.label),
    datasets:[{
      label:'New Customers',
      data: customerGrowth.map(r=>r.cnt),
      borderColor:'#2e7d32',
      backgroundColor: custType==='bar' ? '#81c784' : 'rgba(46,125,50,0.12)',
      fill: custType==='line',
      tension:0.38,
      borderWidth:2.5,
      borderRadius: custType==='bar'?6:0,
    }]
  };
  createOrUpdateChart('customerChart', custType, data, commonOptions);
}

// Best sellers
let bestType='bar';
function renderBest(){
  const labels = bestSellers.map(b=> (b.product_name||'').length>18 ? b.product_name.slice(0,18)+'…' : b.product_name);
  const qty = bestSellers.map(b=> b.total_qty);
  const rev = bestSellers.map(b=> parseFloat(b.revenue));
  const data={
    labels: labels,
    datasets:[
      { label:'Quantity Sold', data: qty, backgroundColor: palette, borderRadius:8, borderSkipped:false },
      { label:'Revenue (₱)', data: rev, backgroundColor:'rgba(23,97,31,0.18)', borderColor:primaryGreen, type: bestType==='bar'?'line':'line', hidden:true }
    ]
  };
  const opts = {...commonOptions, indexAxis: bestType==='hbar'?'y':'x', scales:{...commonOptions.scales, x:{...commonOptions.scales.x, stacked:false}, y:{...commonOptions.scales.y}}};
  // Chart.js v4 uses 'bar' with indexAxis y for horizontal
  let chartJsType = bestType==='hbar' ? 'bar' : bestType;
  if (bestType==='hbar'){ opts.indexAxis='y'; }
  createOrUpdateChart('bestSellersChart', chartJsType, data, opts);
}

// Category
let catType='bar';
function renderCategory(){
  const labels = categorySales.map(c=> c.name);
  const data={
    labels: labels,
    datasets:[
      { label:'Revenue', data: categorySales.map(c=> parseFloat(c.rev)), backgroundColor: primaryGreen, borderRadius:8 },
      { label:'Qty', data: categorySales.map(c=> c.qty), backgroundColor:'rgba(82,183,136,0.6)', borderRadius:8, hidden:true }
    ]
  };
  createOrUpdateChart('categoryChart', catType, data, commonOptions);
}

// Delivery vs Pickup
let delType='bar';
function renderDelivery(){
  if (delType==='doughnut'){
    const data={
      labels:['Delivery','Pick-Up'],
      datasets:[{ data:[deliveryCount, pickupCount], backgroundColor:[primaryGreen, '#c8e6c9'], borderWidth:0, hoverOffset:8 }]
    };
    createOrUpdateChart('deliveryChart','doughnut', data, {
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, padding:18, font:{ family:'Nunito', weight:'700' } } }, tooltip:commonOptions.plugins.tooltip }
    });
  } else {
    const data={
      labels:['Delivery','Pick-Up'],
      datasets:[{ label:'Orders', data:[deliveryCount, pickupCount], backgroundColor:[primaryGreen, '#81c784'], borderRadius:10 }]
    };
    createOrUpdateChart('deliveryChart', 'bar', data, commonOptions);
  }
}

// Status breakdown
let statusType='bar';
function renderStatus(){
  const labels = statusBreakdown.map(s=> s.status);
  const data={
    labels: labels,
    datasets:[{ label:'Count', data: statusBreakdown.map(s=> s.cnt), backgroundColor: palette, borderRadius:8 }]
  };
  createOrUpdateChart('statusChart', statusType, data, commonOptions);
}

// Initial render
document.addEventListener('DOMContentLoaded', ()=>{
  renderRevenue();
  renderOrders();
  renderCustomer();
  renderBest();
  renderCategory();
  renderDelivery();
  renderStatus();

  // Toggle handlers
  document.querySelectorAll('.chart-range-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('.chart-range-btn').forEach(b=>{ b.classList.remove('active','bg-[#17611f]','text-white'); b.classList.add('text-[#5a7a5c]'); });
      btn.classList.add('active','bg-[#17611f]','text-white'); btn.classList.remove('text-[#5a7a5c]');
      revenueRange = btn.dataset.range;
      renderRevenue(); renderOrders();
    });
  });

  function bindToggle(lineBtnId, barBtnId, thirdBtnId, getTypeSetter, renderFn){
    const lineBtn = document.getElementById(lineBtnId);
    const barBtn = document.getElementById(barBtnId);
    const third = thirdBtnId ? document.getElementById(thirdBtnId) : null;
    const all = [lineBtn, barBtn, third].filter(Boolean);
    all.forEach(b=>{
      if (!b) return;
      b.addEventListener('click', ()=>{
        all.forEach(x=> x.classList.remove('active')); 
        all.forEach(x=> x.classList.add('text-[#5a7a5c]'));
        b.classList.add('active'); b.classList.remove('text-[#5a7a5c]');
        const val = b.id.includes('line') ? 'line' : b.id.includes('hbar') ? 'hbar' : b.id.includes('doughnut') ? 'doughnut' : 'bar';
        getTypeSetter(val);
        renderFn();
      });
    });
  }

  bindToggle('revenue-line-btn','revenue-bar-btn', null, (v)=>{ revenueType=v; }, renderRevenue);
  bindToggle('orders-line-btn','orders-bar-btn', null, (v)=>{ ordersType=v; }, renderOrders);
  bindToggle('cust-line-btn','cust-bar-btn', null, (v)=>{ custType=v; }, renderCustomer);
  bindToggle('best-line-btn','best-bar-btn','best-hbar-btn', (v)=>{ bestType=v; }, renderBest);
  bindToggle('cat-line-btn','cat-bar-btn', null, (v)=>{ catType=v; }, renderCategory);
  bindToggle('del-doughnut-btn','del-bar-btn', null, (v)=>{ delType=v; }, renderDelivery);
  bindToggle('status-line-btn','status-bar-btn', null, (v)=>{ statusType=v; }, renderStatus);
});
</script>
@endpush

@endsection
