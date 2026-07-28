@extends('admin.layouts.app')
@section('title', 'Sales Analytics | Admin')
@section('header', 'Sales Analytics')
@section('content')
<style>
  /* Canvas drawing resolution must never control the layout size. */
  #mainChart { display:block; width:100% !important; height:100% !important; max-width:100%; }
</style>

<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
  <div>
    <h1 class="text-2xl font-black tracking-tight">Sales Analytics</h1>
    <p class="text-sm text-[#5a7a5c] mt-0.5">{{ date('F j, Y', $ts) }} · Week {{ date('M j', strtotime($weekStart)) }} – {{ date('M j, Y', strtotime($weekEnd)) }}</p>
  </div>
  <form method="GET" action="{{ route('admin.reports.index') }}">
    <input type="date" name="date" value="{{ $selectedDate }}" max="{{ date('Y-m-d') }}"
           class="border border-[rgba(27,94,32,0.12)] rounded-xl px-4 py-2.5 text-sm font-bold bg-white focus:outline-none focus:ring-2 focus:ring-[#52b788]/40"
           onchange="this.form.submit()" />
  </form>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-xl border p-5 shadow-sm"><p class="text-xs text-[#5a7a5c] font-bold">💰 Today's Sales</p><p class="text-2xl font-black text-[#17611f]">₱{{ number_format($daySales,2) }}</p><p class="text-[11px] text-[#9e9e9e] mt-1">{{ $dayOrders }} orders</p></div>
  <div class="bg-white rounded-xl border p-5 shadow-sm"><p class="text-xs text-[#5a7a5c] font-bold">📅 This Week</p><p class="text-2xl font-black text-[#17611f]">₱{{ number_format($weekSales,2) }}</p><p class="text-[11px] text-[#9e9e9e] mt-1">{{ date('M j', strtotime($weekStart)) }}–{{ date('M j', strtotime($weekEnd)) }}</p></div>
  <div class="bg-white rounded-xl border p-5 shadow-sm"><p class="text-xs text-[#5a7a5c] font-bold">🗓️ This Month</p><p class="text-2xl font-black text-[#17611f]">₱{{ number_format($monthSales,2) }}</p><p class="text-[11px] text-[#9e9e9e] mt-1">{{ date('F Y',$ts) }}</p></div>
  <div class="bg-white rounded-xl border p-5 shadow-sm"><p class="text-xs text-[#5a7a5c] font-bold">📦 Total Orders</p><p class="text-2xl font-black">{{ $totalOrders }}</p><p class="text-[11px] text-[#9e9e9e] mt-1">Avg ₱{{ number_format($avgOrder,2) }}</p></div>
</div>

<!-- Top Filter - All Trends first, Revenue second -->
<div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] p-4 mb-6 shadow-sm flex flex-wrap items-center justify-between gap-4">
  <div class="flex items-center gap-3">
    <span class="text-sm font-black">View Report:</span>
    <div class="relative">
      <select id="reportTypeDropdown" class="appearance-none pl-4 pr-9 py-2.5 rounded-full border bg-[#f4faf5] text-sm font-bold text-[#0e3f14] focus:outline-none focus:ring-2 focus:ring-[#17611f]/20 min-w-[300px] cursor-pointer">
        <option value="all">All Trends – Overview</option>
        <option value="revenue" selected>Revenue Trend</option>
        <option value="orders">Orders Count Trend</option>
        <option value="customer">Customer Growth (30d)</option>
        <option value="bestseller">Best Seller Product</option>
        <option value="category">Sales by Category</option>
        <option value="delivery">Delivery vs Pick-Up</option>
        <option value="status">Order Status Breakdown</option>
        <option value="detail7">7-Day Revenue Detail</option>
        <option value="combo">Sales Amount vs Quantity Sold (Combo)</option>
        <option value="lowselling">Low Selling Products</option>
      </select>
      <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#17611f]">▼</span>
    </div>
  </div>
  <span class="text-[11px] text-[#5a7a5c] hidden md:inline">Hover charts for exact date & value • No clutter labels</span>
</div>

<!-- FOCUSED CARD - like first screenshot -->
<div id="focusCard" class="bg-white rounded-[18px] border border-[rgba(27,94,32,0.08)] shadow-sm p-5 mb-6">
  <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-4">
    <div>
      <div class="flex items-center gap-2.5">
        <span id="focusIcon" class="w-7 h-7 rounded-lg bg-[#e8f5e9] flex items-center justify-center text-sm">📈</span>
        <h2 id="focusTitle" class="text-[18px] font-black">Revenue Trend</h2>
        <span class="hidden sm:inline-flex bg-[#0d3311] text-white text-[11px] font-bold px-2.5 py-1 rounded-full">Focused</span>
      </div>
      <p id="focusSubtitle" class="text-[12px] text-[#5a7a5c] mt-1 ml-[38px]">Select range & chart type – Line vs Vertical Bar, Horizontal, Pie, Combo. Hover for date/value.</p>
      <div id="focusLegend" class="flex items-center gap-4 mt-3 ml-[38px] text-[11px]">
        <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-full bg-[#0d3311] inline-block"></span> Revenue (₱)</span>
        <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-full border-2 border-[#81c784] bg-white inline-block"></span> Orders</span>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
      <div id="rangePills" class="flex items-center bg-[#f0f7f0] rounded-full p-1 border">
        <button data-range="7" class="range-btn active px-3.5 py-1.5 text-xs font-black rounded-full bg-[#0d3311] text-white shadow-sm">7 Days</button>
        <button data-range="30" class="range-btn px-3.5 py-1.5 text-xs font-bold rounded-full text-[#3a5a3c] hover:bg-white">30 Days</button>
        <button data-range="12m" class="range-btn px-3.5 py-1.5 text-xs font-bold rounded-full text-[#3a5a3c] hover:bg-white">12 Months</button>
      </div>

      <div class="relative">
        <select id="graphTypeDropdown" class="appearance-none pl-3 pr-8 py-2 rounded-full border bg-[#0d3311] text-white text-xs font-bold shadow-sm cursor-pointer min-w-[170px]">
          <option value="line" selected>📈 Line</option>
          <option value="bar">📊 Vertical Bar</option>
          <option value="horizontalBar">📊 Horizontal Bar</option>
          <option value="pie">🥧 Pie</option>
          <option value="combo">📊+📈 Combo (Bar + Line)</option>
        </select>
        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-white/80 text-[10px]">▼</span>
      </div>
    </div>
  </div>

  <div class="relative h-[380px] w-full"><canvas id="mainChart"></canvas></div>

  <div id="lowSellingTable" class="hidden"></div>

  <div class="mt-4 flex items-center justify-between text-[10px] text-[#9e9e9e] border-t border-[rgba(27,94,32,0.06)] pt-3">
    <span>Distinct colors • Hover for exact date & value</span>
    <span>Data ending {{ date('M j, Y', $ts) }}</span>
  </div>

  <div id="detail7BarsFocus" class="hidden mt-6">
    <h3 class="text-sm font-black mb-3">📅 7-Day Breakdown</h3>
    <div class="space-y-2">
      @foreach ($daily7Filled as $d)
        @php $maxRev = collect($daily7Filled)->max('rev') ?: 1; $pct = $maxRev>0 ? ($d['rev']/$maxRev)*100 : 0; @endphp
        <div class="flex items-center gap-3">
          <span class="text-xs font-bold w-10">{{ $d['short'] }}</span>
          <div class="flex-1 bg-[#eef6ee] rounded-full h-7 overflow-hidden"><div class="bg-[#0d3311] h-full rounded-full flex items-center pl-3 text-xs text-white font-bold" style="width:{{ max($pct,12) }}%">₱{{ number_format($d['rev'],0) }}</div></div>
          <span class="text-xs text-[#9e9e9e] w-12 text-right">{{ $d['cnt'] }}</span>
        </div>
      @endforeach
    </div>
  </div>
</div>

<!-- ALL TRENDS GRID -->
<div id="allReportsGrid" class="hidden">
  <div class="bg-white rounded-[18px] border p-5 mb-6">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-[15px] font-black flex items-center gap-2"><span class="w-6 h-6 rounded bg-[#e8f5e9] flex items-center justify-center">📈</span> Revenue Trend (7 Days)</h3>
      <div class="flex bg-[#f0f7f0] rounded-full p-1 gap-1" id="allRangePills">
        <button data-range-all="7" class="range-all-btn active px-3 py-1 text-xs font-black rounded-full bg-[#0d3311] text-white">7 Days</button>
        <button data-range-all="30" class="range-all-btn px-3 py-1 text-xs font-bold rounded-full text-[#3a5a3c]">30 Days</button>
        <button data-range-all="12m" class="range-all-btn px-3 py-1 text-xs font-bold rounded-full text-[#3a5a3c]">12 Months</button>
      </div>
    </div>
    <div class="h-[280px] relative"><canvas id="allRevenue"></canvas></div>
    <p class="text-[10px] text-[#9e9e9e] mt-2">Hover for exact date • No x-labels clutter</p>
  </div>

  <div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border p-5"><h3 class="text-[13px] font-black mb-3">📦 Orders Count Trend</h3><div class="h-[180px] relative"><canvas id="miniOrders"></canvas></div></div>
    <div class="bg-white rounded-2xl border p-5"><h3 class="text-[13px] font-black mb-3">👥 Customer Growth</h3><div class="h-[180px] relative"><canvas id="miniCustomer"></canvas></div></div>
    <div class="bg-white rounded-2xl border p-5"><h3 class="text-[13px] font-black mb-3">🏆 Best Sellers – Vertical Bar</h3><div class="h-[200px] relative"><canvas id="miniBest"></canvas></div></div>
    <div class="bg-white rounded-2xl border p-5"><h3 class="text-[13px] font-black mb-3">🥗 Sales by Category</h3><div class="h-[200px] relative"><canvas id="miniCategory"></canvas></div></div>
    <div class="bg-white rounded-2xl border p-5"><h3 class="text-[13px] font-black mb-3">🚚 Delivery vs Pick-Up – Pie</h3><div class="h-[200px] relative flex items-center justify-center"><canvas id="miniDelivery"></canvas></div></div>
    <div class="bg-white rounded-2xl border p-5"><h3 class="text-[13px] font-black mb-3">📋 Order Status – Horizontal Bar</h3><div class="h-[200px] relative"><canvas id="miniStatus"></canvas></div></div>
    <div class="bg-white rounded-2xl border p-5 lg:col-span-2"><h3 class="text-[13px] font-black mb-3">📊 Sales Amount vs Quantity Sold – Combo</h3><div class="h-[260px] relative"><canvas id="miniCombo"></canvas></div></div>

    <div class="bg-white rounded-2xl border p-5 lg:col-span-2">
      <h3 class="text-[13px] font-black mb-3">📉 Low Selling Products – Lowest Performance</h3>
      <div class="h-[200px] relative"><canvas id="miniLowSelling"></canvas></div>
      <div class="mt-4 overflow-x-auto">
        <table class="w-full text-xs">
          <thead><tr class="text-[10px] uppercase text-[#5a7a5c] border-b"><th class="text-left py-1.5">Product Name</th><th class="text-left py-1.5">Units Sold</th><th class="text-left py-1.5">Revenue</th><th class="text-left py-1.5">Remaining Stock</th></tr></thead>
          <tbody id="miniLowSellingTable"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<!-- Report detail tables -->
<div class="grid xl:grid-cols-3 gap-6 mb-6">
  <div class="xl:col-span-1 bg-white rounded-2xl border p-5 shadow-sm"><h2 class="font-black mb-4">Top Customers</h2>
    @forelse($topCustomers as $customer)<div class="flex justify-between gap-3 border-b last:border-0 py-3"><div class="min-w-0"><p class="text-sm font-bold truncate">{{ $customer->customer_name }}</p><p class="text-[11px] text-[#5a7a5c] truncate">{{ $customer->email }} · {{ $customer->order_count }} completed</p></div><b class="text-sm text-[#17611f] whitespace-nowrap">₱{{ number_format($customer->total_spent,2) }}</b></div>@empty <p class="text-sm text-[#9e9e9e] py-6 text-center">No completed-order customers in this period.</p>@endforelse
  </div>
  <div class="xl:col-span-2 bg-white rounded-2xl border overflow-hidden shadow-sm"><div class="p-5 border-b"><h2 class="font-black">Completed Orders · {{ date('F Y',$ts) }}</h2><p class="text-xs text-[#5a7a5c] mt-1">Database-fresh completed transactions only.</p></div><div class="overflow-x-auto"><table class="w-full text-xs"><thead class="bg-[#f4faf5] text-[#5a7a5c] uppercase"><tr><th class="p-3 text-left">Order</th><th class="p-3 text-left">Customer</th><th class="p-3 text-left">Products</th><th class="p-3 text-left">Amount</th><th class="p-3 text-left">Payment</th><th class="p-3 text-left">Completed</th></tr></thead><tbody>@forelse($reportOrders as $order)<tr class="border-t"><td class="p-3 font-bold">{{ $order->order_number }}</td><td class="p-3">{{ $order->customer_name }}</td><td class="p-3">{{ $order->items->sum('quantity') }} items</td><td class="p-3 font-bold text-[#17611f]">₱{{ number_format($order->total,2) }}</td><td class="p-3">{{ strtoupper($order->payment_method) }}</td><td class="p-3">{{ $order->updated_at->format('M j, Y') }}</td></tr>@empty<tr><td colspan="6" class="p-8 text-center text-[#9e9e9e]">No completed orders for this period.</td></tr>@endforelse</tbody></table></div></div>
</div>

@push('scripts')
<script src="{{ asset('assets/chart.umd.min.js') }}"></script>
<script type="text/plain" id="legacyReportChartRenderer">
const daily7 = @json($daily7Filled);
const daily30 = @json($daily30Filled);
const monthly12 = @json($monthly12Filled);
const bestSellers = @json($bestSellers);
const categorySales = @json($categorySales);
const statusBreakdown = @json($statusBreakdown);
const customerGrowth = @json($cust30Filled);
const deliveryCount = {{ $deliveryCount }};
const pickupCount = {{ $pickupCount }};
const lowSelling = @json($lowSelling ?? []);

const DISTINCT = ['#0d3311','#1976d2','#ff9800','#e91e63','#9c27b0','#00acc1','#f9a825','#43a047','#ef6c00','#5e35b1','#00897b','#d81b60'];
const primary = '#0d3311';
const blue = '#1976d2';

let mainChart=null, miniCharts={}, currentRange='7', currentGraphType='line', currentReportType='revenue';

function cChart(id,cfg){ const el=document.getElementById(id); if(!el) return; if(miniCharts[id]) miniCharts[id].destroy(); miniCharts[id]=new Chart(el.getContext('2d'), cfg); }
function cMain(cfg){ const el=document.getElementById('mainChart'); if(!el) return; if(mainChart) mainChart.destroy(); mainChart=new Chart(el.getContext('2d'), cfg); }

function baseOpts(){
  return {
    responsive:true, maintainAspectRatio:false,
    interaction:{ mode:'index', intersect:false },
    plugins:{
      legend:{ display:true, position:'top', labels:{ usePointStyle:true, boxWidth:10, font:{ family:'Nunito', weight:'700', size:11 } } },
      tooltip:{
        backgroundColor:'rgba(13,51,17,0.94)', titleFont:{ family:'Nunito', weight:'800' }, bodyFont:{ family:'Nunito' }, padding:12, cornerRadius:10,
        callbacks:{
          title: (items)=> items[0]?.label || '',
          label: (c)=>{ let v=c.parsed.y ?? c.parsed; if((c.dataset.label||'').includes('Revenue')||(c.dataset.label||'').includes('Amount')) return `${c.dataset.label}: ₱${Number(v).toLocaleString(undefined,{minimumFractionDigits:2})}`; return `${c.dataset.label}: ${v}`; }
        }
      }
    },
    scales:{ x:{ grid:{ display:false }, ticks:{ display:false } }, y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' } } }
  };
}
function getSrc(){ if(currentRange==='7') return daily7; if(currentRange==='30') return daily30; return monthly12; }

function renderFocus(){
  const rep=currentReportType, gtype=currentGraphType, src=getSrc();
  const titles={ revenue:['📈','Revenue Trend'], orders:['📦','Orders Count Trend'], customer:['👥','Customer Growth (30d)'], bestseller:['🏆','Best Seller Product'], category:['🥗','Sales by Category'], delivery:['🚚','Delivery vs Pick-Up'], status:['📋','Order Status Breakdown'], detail7:['📅','7-Day Revenue Detail'], combo:['📊','Sales Amount vs Quantity Sold'], lowselling:['📉','Low Selling Products'] };
  const [ic,tt]=titles[rep]||titles.revenue;
  document.getElementById('focusIcon').textContent=ic; document.getElementById('focusTitle').textContent=tt;

  let labels,datasets,type,opts=baseOpts();
  // Hide legend for pie with many dates
  if(gtype==='pie'){ opts.plugins.legend.display=false; }

  if(rep==='revenue'){
    labels=src.map(r=> r.label); const rev=src.map(r=> r.rev), cnt=src.map(r=> r.cnt);
    if(gtype==='pie'){ type='pie'; datasets=[{ data: rev, backgroundColor: DISTINCT }]; opts.scales={}; }
    else if(gtype==='horizontalBar'){ type='bar'; opts.indexAxis='y'; datasets=[{ label:'Revenue (₱)', data: rev, backgroundColor: primary, borderRadius:8 }, { label:'Orders', data: cnt, backgroundColor: blue, borderRadius:8 }]; }
    else if(gtype==='combo'){ type='bar'; datasets=[{ label:'Revenue (₱)', data: rev, backgroundColor: primary, borderRadius:6 }, { label:'Orders', data: cnt, type:'line', borderColor: blue, borderWidth:2.5, tension:0.4, yAxisID:'y1' }]; opts.scales.y1={ position:'right', grid:{ display:false }, beginAtZero:true }; }
    else { type=gtype==='line'?'line':'bar'; datasets=[{ label:'Revenue (₱)', data: rev, borderColor: primary, backgroundColor: gtype==='line'?'rgba(13,51,17,0.1)':primary, fill: gtype==='line', tension:0.4, borderWidth:2.5, borderRadius:6 }, { label:'Orders', data: cnt, borderColor: blue, backgroundColor: gtype==='line'?'rgba(25,118,210,0.12)':'#90caf9', type: gtype==='line'?'line':'bar', tension:0.35 }]; }
    document.getElementById('detail7BarsFocus').classList.add('hidden');
  }
  else if(rep==='orders'){
    labels=src.map(r=> r.label); const cnt=src.map(r=> r.cnt);
    if(gtype==='pie'){ type='pie'; datasets=[{ data: cnt, backgroundColor: DISTINCT }]; opts.scales={}; opts.plugins.legend.display=false; }
    else if(gtype==='horizontalBar'){ type='bar'; opts.indexAxis='y'; datasets=[{ label:'Orders', data: cnt, backgroundColor: blue, borderRadius:8 }]; }
    else { type=gtype==='line'?'line':'bar'; datasets=[{ label:'Orders Count', data: cnt, borderColor: primary, backgroundColor: gtype==='line'?'rgba(13,51,17,0.1)':primary, fill: gtype==='line', tension:0.4, borderWidth:2.5, borderRadius:6 }]; }
    document.getElementById('detail7BarsFocus').classList.add('hidden');
  }
  else if(rep==='customer'){
    labels=customerGrowth.map(r=> r.label); const cnt=customerGrowth.map(r=> r.cnt);
    if(gtype==='pie'){ type='pie'; datasets=[{ data: cnt, backgroundColor: DISTINCT }]; opts.scales={}; opts.plugins.legend.display=false; }
    else if(gtype==='horizontalBar'){ type='bar'; opts.indexAxis='y'; datasets=[{ label:'New Customers', data: cnt, backgroundColor: '#7b1fa2', borderRadius:6 }]; }
    else { type=gtype==='line'?'line':'bar'; datasets=[{ label:'New Customers', data: cnt, borderColor: '#7b1fa2', backgroundColor: gtype==='line'?'rgba(123,31,162,0.12)':'#ba68c8', fill: gtype==='line', tension:0.4, borderWidth:2.5, borderRadius:6 }]; }
    document.getElementById('detail7BarsFocus').classList.add('hidden');
  }
  else if(rep==='bestseller'){
    labels=bestSellers.map(b=> (b.product_name||'').slice(0,18)); const qty=bestSellers.map(b=> b.total_qty), rev=bestSellers.map(b=> parseFloat(b.revenue));
    if(gtype==='pie'){ type='pie'; datasets=[{ data: qty, backgroundColor: DISTINCT }]; opts.scales={}; }
    else if(gtype==='horizontalBar'){ type='bar'; opts.indexAxis='y'; datasets=[{ label:'Qty Sold', data: qty, backgroundColor: DISTINCT, borderRadius:8 }]; }
    else if(gtype==='combo'){ type='bar'; datasets=[{ label:'Sales Amount (₱)', data: rev, backgroundColor:'#9e9e9e', borderRadius:6 }, { label:'Quantity Sold', data: qty, type:'line', borderColor: blue, borderWidth:2.5, tension:0.35 }]; opts.scales={ y:{ title:{ display:true, text:'Sales Amount' } }, y1:{ position:'right', grid:{ display:false }, title:{ display:true, text:'Qty' } } }; datasets[0].yAxisID='y'; datasets[1].yAxisID='y1'; }
    else if(gtype==='line'){ type='line'; datasets=[{ label:'Qty Sold', data: qty, borderColor: primary, backgroundColor:'rgba(13,51,17,0.08)', fill:true, tension:0.35 }]; }
    else { type='bar'; datasets=[{ label:'Qty Sold', data: qty, backgroundColor: DISTINCT, borderRadius:8 }]; }
    document.getElementById('detail7BarsFocus').classList.add('hidden');
  }
  else if(rep==='category'){
    labels=categorySales.map(c=> c.name); const rev=categorySales.map(c=> parseFloat(c.rev));
    if(gtype==='pie'){ type='pie'; datasets=[{ data: rev, backgroundColor: DISTINCT }]; opts.scales={}; }
    else if(gtype==='horizontalBar'){ type='bar'; opts.indexAxis='y'; datasets=[{ label:'Revenue', data: rev, backgroundColor: DISTINCT, borderRadius:8 }]; }
    else if(gtype==='combo'){ type='bar'; datasets=[{ label:'Revenue (₱)', data: rev, backgroundColor: DISTINCT[0], borderRadius:6 }, { label:'Qty', data: categorySales.map(c=> c.qty), type:'line', borderColor: blue, borderWidth:2.5 }]; }
    else { type=gtype==='line'?'line':'bar'; datasets=[{ label:'Revenue', data: rev, borderColor: primary, backgroundColor: gtype==='line'?'rgba(13,51,17,0.08)':primary, fill: gtype==='line', tension:0.35, borderRadius:8 }]; if(gtype!=='line') datasets[0].backgroundColor=DISTINCT; }
    document.getElementById('detail7BarsFocus').classList.add('hidden');
  }
  else if(rep==='delivery'){
    labels=['Delivery','Pick-Up']; const data=[deliveryCount,pickupCount];
    if(gtype==='pie'){ type='pie'; datasets=[{ data: data, backgroundColor:[primary, blue] }]; opts.scales={}; }
    else if(gtype==='horizontalBar'){ type='bar'; opts.indexAxis='y'; datasets=[{ label:'Orders', data: data, backgroundColor:[primary, blue], borderRadius:8 }]; }
    else { type=gtype==='line'?'line':'bar'; datasets=[{ label:'Orders', data: data, backgroundColor: gtype==='line'?'rgba(13,51,17,0.1)':[primary, blue], borderColor: primary, borderRadius:10, fill: gtype==='line' }]; }
    document.getElementById('detail7BarsFocus').classList.add('hidden');
  }
  else if(rep==='status'){
    labels=statusBreakdown.map(s=> s.status); const cnt=statusBreakdown.map(s=> s.cnt);
    if(gtype==='pie'){ type='pie'; datasets=[{ data: cnt, backgroundColor: DISTINCT }]; opts.scales={}; }
    else if(gtype==='horizontalBar'){ type='bar'; opts.indexAxis='y'; datasets=[{ label:'Count', data: cnt, backgroundColor: DISTINCT, borderRadius:8 }]; }
    else { type=gtype==='line'?'line':'bar'; datasets=[{ label:'Count', data: cnt, backgroundColor: gtype==='line'?'rgba(13,51,17,0.08)':DISTINCT, borderRadius:8, fill: gtype==='line' }]; }
    document.getElementById('detail7BarsFocus').classList.add('hidden');
  }
  else if(rep==='detail7'){
    labels=daily7.map(r=> r.short); const rev=daily7.map(r=> r.rev);
    if(gtype==='pie'){ type='pie'; datasets=[{ data: rev, backgroundColor: DISTINCT }]; opts.scales={}; opts.plugins.legend.display=false; }
    else if(gtype==='horizontalBar'){ type='bar'; opts.indexAxis='y'; datasets=[{ label:'Revenue', data: rev, backgroundColor: primary, borderRadius:8 }]; }
    else { type=gtype==='line'?'line':'bar'; datasets=[{ label:'Revenue', data: rev, borderColor: primary, backgroundColor: gtype==='line'?'rgba(13,51,17,0.08)':primary, fill: gtype==='line', tension:0.35, borderRadius:8 }]; }
    document.getElementById('detail7BarsFocus').classList.remove('hidden');
  }
  else if(rep==='combo'){
    const lbl=bestSellers.map(b=> (b.product_name||'').slice(0,12)); const qty=bestSellers.map(b=> b.total_qty); const rev=bestSellers.map(b=> parseFloat(b.revenue));
    labels=lbl; type='bar'; datasets=[{ label:'Sum of SalesAmount', data: rev, backgroundColor:'#9e9e9e', borderRadius:4 }, { label:'Sum of Quantity', data: qty, type:'line', borderColor: blue, borderWidth:2.5, tension:0.35, yAxisID:'y1' }]; opts.scales={ x:{ ticks:{ display:false } }, y:{ beginAtZero:true }, y1:{ position:'right', grid:{ display:false }, beginAtZero:true } };
    document.getElementById('detail7BarsFocus').classList.add('hidden');
  }
  else if(rep==='lowselling'){
    const lbl=lowSelling.map(p=> (p.product_name||'').slice(0,16));
    const qty=lowSelling.map(p=> p.total_qty);
    const rev=lowSelling.map(p=> parseFloat(p.revenue));
    const stock=lowSelling.map(p=> p.stock);
    labels=lbl;
    if(gtype==='pie'){
      type='pie'; datasets=[{ data: qty, backgroundColor: DISTINCT }]; opts.scales={}; opts.plugins.legend.display=false;
    } else if(gtype==='horizontalBar'){
      type='bar'; opts.indexAxis='y';
      datasets=[{ label:'Units Sold', data: qty, backgroundColor: '#ef6c00', borderRadius:6 }, { label:'Revenue', data: rev, backgroundColor: '#9e9e9e', borderRadius:6 }];
    } else if(gtype==='combo'){
      type='bar';
      datasets=[
        { label:'Units Sold', data: qty, backgroundColor: '#ef6c00', borderRadius:6 },
        { label:'Revenue', data: rev, type:'line', borderColor: blue, borderWidth:2.5, tension:0.35, yAxisID:'y1' },
        { label:'Stock', data: stock, type:'line', borderColor: '#43a047', borderDash:[5,5], borderWidth:2 }
      ];
      opts.scales={ y:{ beginAtZero:true, title:{ display:true, text:'Sold / Stock' } }, y1:{ position:'right', grid:{ display:false }, title:{ display:true, text:'Revenue' } } };
      datasets[1].yAxisID='y1';
    } else {
      type=gtype==='line'?'line':'bar';
      datasets=[{ label:'Units Sold', data: qty, backgroundColor: gtype==='line'?'rgba(239,108,0,0.12)':'#ef6c00', borderColor:'#ef6c00', fill: gtype==='line', tension:0.35, borderRadius:6 }];
    }
    document.getElementById('detail7BarsFocus').classList.add('hidden');
    // Also render table below chart via custom HTML
    const lowTable = document.getElementById('lowSellingTable');
    if(lowTable){
      lowTable.innerHTML = `
        <div class="overflow-x-auto mt-4">
          <table class="w-full text-sm">
            <thead><tr class="text-[11px] uppercase text-[#5a7a5c] border-b"><th class="text-left py-2">Product Name</th><th class="text-left py-2">Units Sold</th><th class="text-left py-2">Revenue</th><th class="text-left py-2">Remaining Stock</th></tr></thead>
            <tbody>${lowSelling.map(p=>`<tr class="border-b border-gray-50 hover:bg-gray-50"><td class="py-2.5 font-bold">${p.product_name}</td><td class="py-2.5">${p.total_qty}</td><td class="py-2.5">₱${Number(p.revenue).toLocaleString(undefined,{minimumFractionDigits:2})}</td><td class="py-2.5"><span class="px-2 py-0.5 rounded-full text-xs ${p.stock>20?'bg-green-100 text-green-700': p.stock>0?'bg-amber-100 text-amber-700':'bg-red-100 text-red-700'}">${p.stock}</span></td></tr>`).join('')}</tbody>
          </table>
        </div>`;
      lowTable.classList.remove('hidden');
    }
  } else {
    // Fallback hide low table
    const lowTable=document.getElementById('lowSellingTable');
    if(lowTable) lowTable.classList.add('hidden');
  }

  // Hide low table for non-lowselling
  if(rep!=='lowselling'){
    const lowTable=document.getElementById('lowSellingTable');
    if(lowTable) lowTable.classList.add('hidden');
  }

  cMain({ type: type, data:{ labels: labels, datasets: datasets }, options: opts });
}

function renderAll(){
  const src=getSource();
  cChart('allRevenue',{ type:'line', data:{ labels: src.map(r=> r.label), datasets:[{ label:'Revenue (₱)', data: src.map(r=> r.rev), borderColor: primary, backgroundColor:'rgba(13,51,17,0.1)', fill:true, tension:0.4 }, { label:'Orders', data: src.map(r=> r.cnt), borderColor: blue, backgroundColor:'rgba(25,118,210,0.12)', fill:false, tension:0.35, borderDash:[6,6] }] }, options:{ responsive:true, maintainAspectRatio:false, interaction:{ mode:'index', intersect:false }, plugins:{ legend:{ display:true, position:'top', labels:{ boxWidth:10, font:{ size:11 } } }, tooltip:{ backgroundColor:'rgba(13,51,17,0.9)' } }, scales:{ x:{ ticks:{ display:false } }, y:{ beginAtZero:true } } } });
  cChart('miniOrders',{ type:'line', data:{ labels: src.map(r=> r.label), datasets:[{ label:'Orders', data: src.map(r=> r.cnt), borderColor: primary, backgroundColor:'rgba(13,51,17,0.08)', fill:true, tension:0.38 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ display:false } }, y:{ beginAtZero:true } } } });
  cChart('miniCustomer',{ type:'line', data:{ labels: customerGrowth.slice(-(currentRange==='7'?7:30)).map(r=> r.label), datasets:[{ label:'New', data: customerGrowth.slice(-(currentRange==='7'?7:30)).map(r=> r.cnt), borderColor:'#7b1fa2', backgroundColor:'rgba(123,31,162,0.12)', fill:true, tension:0.38 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ display:false } }, y:{ beginAtZero:true } } } });
  cChart('miniBest',{ type:'bar', data:{ labels: bestSellers.map(b=> b.product_name.slice(0,12)), datasets:[{ label:'Qty', data: bestSellers.map(b=> b.total_qty), backgroundColor: DISTINCT, borderRadius:6 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ display:false } }, y:{ beginAtZero:true } } } });
  cChart('miniCategory',{ type:'bar', data:{ labels: categorySales.map(c=> c.name.slice(0,10)), datasets:[{ data: categorySales.map(c=> parseFloat(c.rev)), backgroundColor: DISTINCT, borderRadius:6, label:'Revenue' }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ display:false } }, y:{ beginAtZero:true } } } });
  cChart('miniDelivery',{ type:'pie', data:{ labels:['Delivery','Pick-Up'], datasets:[{ data:[deliveryCount, pickupCount], backgroundColor:[primary, blue] }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ boxWidth:10, font:{ size:10 } } } } } });
  cChart('miniStatus',{ type:'bar', data:{ labels: statusBreakdown.map(s=> s.status), datasets:[{ data: statusBreakdown.map(s=> s.cnt), backgroundColor: DISTINCT, borderRadius:6 }] }, options:{ responsive:true, maintainAspectRatio:false, indexAxis:'y', plugins:{ legend:{ display:false } } } });
  cChart('miniCombo',{ type:'bar', data:{ labels: bestSellers.map(b=> b.product_name.slice(0,10)), datasets:[{ label:'Sales Amount', data: bestSellers.map(b=> parseFloat(b.revenue)), backgroundColor:'#9e9e9e' }, { label:'Quantity', data: bestSellers.map(b=> b.total_qty), type:'line', borderColor: blue, tension:0.35, yAxisID:'y1' }] }, options:{ responsive:true, maintainAspectRatio:false, interaction:{ mode:'index', intersect:false }, scales:{ x:{ ticks:{ display:false } }, y:{ beginAtZero:true }, y1:{ position:'right', grid:{ display:false }, beginAtZero:true } } } });

  // Low Selling Products
  cChart('miniLowSelling',{ type:'bar', data:{ labels: lowSelling.map(p=> (p.product_name||'').slice(0,12)), datasets:[{ label:'Units Sold', data: lowSelling.map(p=> p.total_qty), backgroundColor: '#ef6c00', borderRadius:6 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ display:false } }, y:{ beginAtZero:true } } } });
  const lowTable=document.getElementById('miniLowSellingTable');
  if(lowTable){
    lowTable.innerHTML = lowSelling.map(p=>`<tr class="border-b border-gray-50 hover:bg-gray-50"><td class="py-2 font-bold">${p.product_name}</td><td class="py-2">${p.total_qty}</td><td class="py-2">₱${Number(p.revenue).toLocaleString(undefined,{minimumFractionDigits:2})}</td><td class="py-2"><span class="px-2 py-0.5 rounded-full text-[10px] ${p.stock>20?'bg-green-100 text-green-700': p.stock>0?'bg-amber-100 text-amber-700':'bg-red-100 text-red-700'}">${p.stock}</span></td></tr>`).join('');
  }
}

document.addEventListener('DOMContentLoaded', ()=>{
  renderAll();
  renderFocus();
  const reportDropdown=document.getElementById('reportTypeDropdown');
  const graphDropdown=document.getElementById('graphTypeDropdown');
  const focusCard=document.getElementById('focusCard');
  const allGrid=document.getElementById('allReportsGrid');

  function updateView(){
    currentReportType=reportDropdown.value;
    currentGraphType=graphDropdown.value;
    if(currentReportType==='all'){
      focusCard.classList.add('hidden');
      allGrid.classList.remove('hidden');
    } else {
      focusCard.classList.remove('hidden');
      allGrid.classList.add('hidden');
      const rangeReports=['revenue','orders','customer'];
      const rp=document.getElementById('rangePills');
      if(rangeReports.includes(currentReportType)) rp.classList.remove('hidden'); else rp.classList.add('hidden');
      renderFocus();
    }
  }

  reportDropdown.addEventListener('change', updateView);
  graphDropdown.addEventListener('change', ()=>{ currentGraphType=graphDropdown.value; if(currentReportType!=='all') renderFocus(); });

  document.querySelectorAll('.range-btn, .range-all-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const isAll=btn.classList.contains('range-all-btn');
      const group=isAll? document.querySelectorAll('.range-all-btn') : document.querySelectorAll('.range-btn');
      group.forEach(b=>{ b.classList.remove('active','bg-[#0d3311]','text-white'); b.classList.add('text-[#3a5a3c]'); });
      btn.classList.add('active','bg-[#0d3311]','text-white'); btn.classList.remove('text-[#3a5a3c]');
      currentRange=isAll? btn.dataset.rangeAll : btn.dataset.range;
      // sync both groups
      document.querySelectorAll('.range-btn, .range-all-btn').forEach(b=>{
        const r=b.dataset.range || b.dataset.rangeAll;
        if(r===currentRange){ b.classList.add('active','bg-[#0d3311]','text-white'); b.classList.remove('text-[#3a5a3c]'); }
        else { b.classList.remove('active','bg-[#0d3311]','text-white'); b.classList.add('text-[#3a5a3c]'); }
      });
      if(currentReportType==='all') renderAll(); else renderFocus();
    });
  });

  updateView();
  // Default to Revenue like earlier good design, but dropdown order All first
  reportDropdown.value='revenue';
  currentReportType='revenue';
  updateView();

  // Admin Orders emits this after a Completed status is saved. Reloading this
  // server-rendered report retrieves a new completed-only aggregate from MySQL.
  window.addEventListener('storage', event => {
    if (event.key === 'luntiang:completed-order-updated') {
      setTimeout(() => window.location.reload(), 750);
    }
  });
  // Protect against changes made from a different browser/device as well.
  setInterval(() => window.location.reload(), 30000);
});

</script>
<script>
// Dependency-free chart renderer: keeps Sales Analytics visible even when a CDN,
// extension, or browser policy prevents Chart.js from initializing.
(function () {
  const reportRows = @json($daily7Filled);
  const reportRows30 = @json($daily30Filled);
  const reportRows12 = @json($monthly12Filled);
  const fallbackBestSellers = @json($bestSellers);
  const fallbackCategories = @json($categorySales);
  const fallbackStatuses = @json($statusBreakdown);
  const fallbackDelivery = {delivery: @json($deliveryCount), pickup: @json($pickupCount)};
  const fallbackLowSelling = @json($lowSelling ?? []);
  let fallbackRange = '7';
  function drawSalesFallback() {
    const canvas = document.getElementById('mainChart');
    if (!canvas) return;
    const rect = canvas.getBoundingClientRect();
    if (rect.width < 10 || rect.height < 10) return;
    const ratio = window.devicePixelRatio || 1;
    canvas.width = Math.floor(rect.width * ratio); canvas.height = Math.floor(rect.height * ratio);
    const ctx = canvas.getContext && canvas.getContext('2d'); if (!ctx) return; ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    const w = rect.width, h = rect.height, pad = {l:58,r:22,t:30,b:48};
    ctx.clearRect(0,0,w,h); ctx.fillStyle='#fff'; ctx.fillRect(0,0,w,h);
    const selectedReport = document.getElementById('reportTypeDropdown')?.value || 'revenue';
    const selectedRange = fallbackRange;
    let rows = selectedRange === '30' ? reportRows30 : (selectedRange === '12m' ? reportRows12 : reportRows);
    let labels = rows.map(x => x.short || x.label || '');
    let values = rows.map(x => Number(x.rev || 0));
    if (selectedReport === 'orders') values = rows.map(x => Number(x.cnt || 0));
    if (selectedReport === 'bestseller') { labels = fallbackBestSellers.map(x => x.product_name || 'Product'); values = fallbackBestSellers.map(x => Number(x.revenue || 0)); }
    if (selectedReport === 'category') { labels = fallbackCategories.map(x => x.name || 'Category'); values = fallbackCategories.map(x => Number(x.rev || 0)); }
    if (selectedReport === 'status') { labels = fallbackStatuses.map(x => x.status || 'Status'); values = fallbackStatuses.map(x => Number(x.cnt || 0)); }
    if (selectedReport === 'delivery') { labels = ['Delivery','Pick-Up']; values = [Number(fallbackDelivery.delivery), Number(fallbackDelivery.pickup)]; }
    if (selectedReport === 'lowselling') { labels = fallbackLowSelling.map(x => x.product_name || 'Product'); values = fallbackLowSelling.map(x => Number(x.total_qty || 0)); }
    const max = Math.max(...values, 1);
    const graphType = document.getElementById('graphTypeDropdown')?.value || 'bar';
    const innerW=w-pad.l-pad.r, innerH=h-pad.t-pad.b;
    if(graphType==='pie') { let total=values.reduce((a,b)=>a+b,0)||1, angle=-Math.PI/2; values.forEach((value,i)=>{let slice=value/total*Math.PI*2;ctx.fillStyle=['#17611f','#52b788','#f0a500','#1976d2','#9c27b0','#ef6c00'][i%6];ctx.beginPath();ctx.moveTo(w/2,h/2);ctx.arc(w/2,h/2,Math.min(innerW,innerH)/2.5,angle,angle+slice);ctx.closePath();ctx.fill();angle+=slice;}); }
    else { ctx.strokeStyle='#dfe9df';ctx.lineWidth=1;ctx.font='11px Nunito, sans-serif';ctx.fillStyle='#5a7a5c';for(let i=0;i<=4;i++){const y=pad.t+innerH*i/4;ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(w-pad.r,y);ctx.stroke();ctx.fillText(Math.round(max*(1-i/4)).toLocaleString(),5,y+4);} const step=innerW/Math.max(values.length,1),bw=Math.max(12,step*.55);
      if(graphType==='horizontalBar'){ values.forEach((value,i)=>{const bh=Math.max(16,innerH/values.length*.55),y=pad.t+i*(innerH/values.length)+(innerH/values.length-bh)/2,bar=innerW*(value/max);ctx.fillStyle='#17611f';ctx.fillRect(pad.l,y,bar,bh);ctx.fillStyle='#5a7a5c';ctx.textAlign='left';ctx.fillText(labels[i]||'',pad.l+bar+5,y+bh/2+4);}); }
      else if(graphType==='line'){ctx.strokeStyle='#17611f';ctx.lineWidth=3;ctx.beginPath();values.forEach((value,i)=>{const x=pad.l+i*step+step/2,y=pad.t+innerH-innerH*(value/max);i?ctx.lineTo(x,y):ctx.moveTo(x,y);});ctx.stroke();}
      else {values.forEach((value,i)=>{const bh=innerH*(value/max),x=pad.l+i*step+(step-bw)/2,y=pad.t+innerH-bh;ctx.fillStyle='#17611f';ctx.fillRect(x,y,bw,bh);ctx.fillStyle='#5a7a5c';ctx.textAlign='center';ctx.fillText(labels[i]||'',x+bw/2,h-20);});}
    }
    ctx.textAlign='left';ctx.fillStyle='#17611f';ctx.font='bold 12px Nunito, sans-serif';ctx.fillText((selectedReport === 'bestseller' ? 'Best-Selling Products Revenue' : selectedReport === 'orders' ? 'Completed Orders' : 'Completed Revenue (₱)') + ' — ' + (selectedRange==='12m'?'12 months':selectedRange+' days'),pad.l,pad.t-10);
  }
  window.addEventListener('resize', drawSalesFallback);
  window.addEventListener('load', () => setTimeout(drawSalesFallback, 100));
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(drawSalesFallback, 300);
    document.getElementById('reportTypeDropdown')?.addEventListener('change', event => {
      const all = event.target.value === 'all';
      document.getElementById('focusCard')?.classList.toggle('hidden', all);
      document.getElementById('allReportsGrid')?.classList.toggle('hidden', !all);
      if (!all) setTimeout(drawSalesFallback, 20);
    });
    document.querySelectorAll('.range-btn').forEach(btn => btn.addEventListener('click', () => {
      fallbackRange = btn.dataset.range || '7';
      document.querySelectorAll('.range-btn').forEach(pill => {
        const active = pill === btn;
        pill.classList.toggle('active', active); pill.classList.toggle('bg-[#0d3311]', active); pill.classList.toggle('text-white', active);
        pill.classList.toggle('text-[#3a5a3c]', !active);
      });
      drawSalesFallback();
    }));
    // The native renderer is a bar chart; graph style selection remains available
    // without causing another chart instance to resize the canvas.
    document.getElementById('graphTypeDropdown')?.addEventListener('change', () => drawSalesFallback());
  });
})();
</script>
@endpush
@endsection
