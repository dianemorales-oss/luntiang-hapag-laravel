@extends('admin.layouts.app')
@section('title', 'Sales Analytics | Admin')
@section('header', 'Sales Analytics')
@section('content')

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
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const daily7 = @json($daily7Filled);
const daily30 = @json($daily30Filled);
const monthly12 = @json($monthly12Filled);
const bestSellers = @json($bestSellers);
const categorySales = @json($categorySales);
const statusBreakdown = @json($statusBreakdown);
const customerGrowth = @json($cust30Filled);
const deliveryCount = {{ $deliveryCount }};
const pickupCount = {{ $pickupCount }};

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
  const titles={ revenue:['📈','Revenue Trend'], orders:['📦','Orders Count Trend'], customer:['👥','Customer Growth (30d)'], bestseller:['🏆','Best Seller Product'], category:['🥗','Sales by Category'], delivery:['🚚','Delivery vs Pick-Up'], status:['📋','Order Status Breakdown'], detail7:['📅','7-Day Revenue Detail'], combo:['📊','Sales Amount vs Quantity Sold'] };
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
});
</script>
@endpush
@endsection
