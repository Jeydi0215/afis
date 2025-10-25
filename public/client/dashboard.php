<?php

include ('../components/sidebar.php'); 

// include('logout.php');

// Connect to database


// Fetch category data from MySQL
$categories = [];
$totals = [];

$query = "SELECT category, COUNT(*) as total FROM products GROUP BY category";
$result = $conn->query($query);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
    $totals[] = $row['total'];
  }
}

// Total Inventory Value - fetch only the total value
$inventory_value = 0;
$inventory_query = "SELECT SUM(price * qty) as total_inventory FROM products";
$inventory_result = $conn->query($inventory_query);
if ($inventory_result && $inventory_row = $inventory_result->fetch_assoc()) {
  $inventory_value = $inventory_row['total_inventory'] ?: 0;
}

// Format numbers for display
function formatCurrency($value) {
  return '$' . number_format($value, 2);
}

// Demo data for metrics (since we're not fetching from sheets)
$accounts_payable = 145780.50;
$accounts_receivable = 98650.25;  
$invoice_worth = 215320.75;

// Calculate change percentages (demo data)
$ap_change = 12.5;
$ar_change = -5.3;
$invoice_change = 8.7;
$inventory_change = 3.2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Financial Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: 'Quicksand', sans-serif;
      background: #f8f9fa;
    }

    .main-content {
      margin-left: 120px;
      padding: 20px;
    }

    .sortable-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .sortable-section {
      background: #ffffff;
      padding: 20px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
      cursor: grab;
    }

    .sortable-section:active {
      cursor: grabbing;
    }

    .sortable-section h3 {
      margin-top: 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .grid-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .sortable-metrics .grid-item {
      flex: 1;
      min-width: 200px;
      height: 180px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.05);
      cursor: move;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .sortable-metrics .grid-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }

    .segment {
      background: #f8f9fa;
      width: 100%;
      height: 100%;
      border-radius: 12px;
      padding: 15px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-sizing: border-box;
    }

    .segment h4 {
      margin: 0;
      font-size: 16px;
      color: #444;
      font-weight: 600;
    }

    .segment .value {
      font-size: 30px;
      font-weight: bold;
      color: #2c3e50;
      margin: 15px 0;
    }

    .segment .change {
      font-size: 14px;
      color: #2ecc71;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .segment .change.negative {
      color: #e74c3c;
    }

    .chart-box {
      width: 100%;
    }

    .chart-row {
      display: flex;
      gap: 40px;
      justify-content: flex-start;
      flex-wrap: wrap;
    }

    .chart-card {
      background: #ffffff;
      padding: 15px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-direction: column;
      align-items: center;
      cursor: grab;
      width: 45%;
      transition: transform 0.2s;
    }

    .chart-card:hover {
      transform: translateY(-5px);
    }

    .chart-card:active {
      cursor: grabbing;
    }

    .pie {
      width: 300px !important;
      height: 300px !important;
    }

    .time-box {
      flex: 1;
      min-width: 250px;
      background: white;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
      text-align: center;
      transition: transform 0.2s;
    }
    
    .time-box:hover {
      transform: translateY(-5px);
    }

    /* Export buttons */
    .export-bar {
      display: flex;
      gap: 15px;
      margin-bottom: 0;
      flex-wrap: wrap;
    }

    .export-btn {
      padding: 10px 15px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
      color: white;
    }

    .export-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .export-btn.pdf {
      background-color: #e74c3c;
    }

    .export-btn.excel {
      background-color: #27ae60;
    }

    .export-btn.csv {
      background-color: #3498db;
    }

    /* Date picker */
    .filter-section {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: center;
      margin-bottom: 0;
    }

    .date-picker {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .date-picker label {
      font-weight: 600;
    }

    .date-picker input[type="date"] {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-family: 'Quicksand', sans-serif;
    }

    /* Predictive insights */
    .insights-box {
    
      border-radius: 12px;
      padding: 20px;
      color: black;
    }

    .insights-box h3 {
      margin-top: 0;
      color: black;
      
    }

    .insights-content {
      display: flex;
      flex-direction: column;
      gap: 15px;
      
    }

    .insight-item {
      background: rgba(255,255,255,0.2);
      padding: 15px;
      border-radius: 8px;
      border:1px solid black;
    }

    .insight-item h4 {
      margin-top: 0;
      font-size: 16px;
    }

    .insight-item p {
      margin-bottom: 0;
    }
.page-title {
    font-weight: bold;
    font-size: 24px;
    color: #1f2937;
    margin: 0 0 20px 0; /* Add bottom margin for spacing */
    margin-left: 120px; /* Match the sidebar width to align with main content */
    padding: 20px 20px 0 20px; /* Add padding for better spacing */
    position: relative; /* Keep relative positioning */
    top: -12%; /* Remove the negative top positioning */
    left: 20px;
    display: block;
    visibility: visible;
    opacity: 1;
    background: #f8f9fa; /* Match body background */
    z-index: 10; /* Ensure it appears above other elements */
}

  </style>

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body>
  <h1 class="page-title">
              Dashboard
            </h1>
<div class="main-content">
  <!-- Export buttons and filters with right alignment -->
  <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
    <div class="export-bar">
      <button class="export-btn pdf" onclick="exportToPDF()">
        <i class="fas fa-file-pdf"></i> Export to PDF
      </button>
      <button class="export-btn excel" onclick="exportToExcel()">
        <i class="fas fa-file-excel"></i> Export to Excel
      </button>
      <button class="export-btn csv" onclick="exportToCSV()">
        <i class="fas fa-file-csv"></i> Export to CSV
      </button>
    </div>
  </div>

  <!-- Date picker with right alignment -->
  <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
    <div class="filter-section">
      <div class="date-picker">
        <label for="start-date">From:</label>
        <input type="date" id="start-date" name="start-date">
      </div>
      <div class="date-picker">
        <label for="end-date">To:</label>
        <input type="date" id="end-date" name="end-date">
      </div>
      <div class="filter-dropdown" style="margin-left: 10px;">
        <label for="filter-category">Category:</label>
        <select id="filter-category" name="filter-category" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Quicksand', sans-serif;">
          <option value="all">All Categories</option>
          <?php foreach($categories as $category): ?>
            <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="export-btn" style="background-color: #7f8c8d;" onclick="applyDateFilter()">
        <i class="fas fa-filter"></i> Apply Filter
      </button>
    </div>
  </div>

  <div id="sortableSections" class="sortable-list">
    <!-- Time Row -->
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
      <div class="time-box">
        <h3 style="font-weight: bold;">PH Time</h3>
        <div id="clock-us" style="font-size: 2.5rem; font-weight: bold; color: black;"></div>
      </div>
      <div class="time-box">
        <h3 style="font-weight: bold;">US Time</h3>
        <div id="clock" style="font-size: 2.5rem; font-weight: bold; color: black;"></div>
      </div>
    </div>

    <!-- Sortable Metric Section -->
    <div class="sortable-section">
      <h3>Key Financial Metrics</h3>
      <div id="sortableMetrics" class="grid-container sortable-metrics">
        <div class="grid-item">
          <div class="segment">
            <h4>Accounts Payable</h4>
            <div class="value"><?php echo formatCurrency($accounts_payable); ?></div>
            <div class="change <?php echo $ap_change < 0 ? 'negative' : ''; ?>">
              <i class="fas fa-<?php echo $ap_change < 0 ? 'arrow-down' : 'arrow-up'; ?>"></i>
              <?php echo abs($ap_change); ?>%
            </div>
          </div>
        </div>
        <div class="grid-item">
          <div class="segment">
            <h4>Accounts Receivable</h4>
            <div class="value"><?php echo formatCurrency($accounts_receivable); ?></div>
            <div class="change <?php echo $ar_change < 0 ? 'negative' : ''; ?>">
              <i class="fas fa-<?php echo $ar_change < 0 ? 'arrow-down' : 'arrow-up'; ?>"></i>
              <?php echo abs($ar_change); ?>%
            </div>
          </div>
        </div>
        <div class="grid-item">
          <div class="segment">
            <h4>Invoice Worth</h4>
            <div class="value"><?php echo formatCurrency($invoice_worth); ?></div>
            <div class="change <?php echo $invoice_change < 0 ? 'negative' : ''; ?>">
              <i class="fas fa-<?php echo $invoice_change < 0 ? 'arrow-down' : 'arrow-up'; ?>"></i>
              <?php echo abs($invoice_change); ?>%
            </div>
          </div>
        </div>
        <div class="grid-item">
          <div class="segment">
            <h4>Total Inventory Value</h4>
            <div class="value"><?php echo formatCurrency($inventory_value); ?></div>
            <div class="change <?php echo $inventory_change < 0 ? 'negative' : ''; ?>">
              <i class="fas fa-<?php echo $inventory_change < 0 ? 'arrow-down' : 'arrow-up'; ?>"></i>
              <?php echo abs($inventory_change); ?>%
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Predictive Insights Box -->
    <div class="sortable-section insights-box">
      <h3>Predictive Insights</h3>
      <div class="insights-content">
        <div class="insight-item">
          <h4>Cash Flow Forecast</h4>
          <p>Based on current trends, expected cash flow for next month: <strong>+8.3%</strong></p>
        </div>
        <div class="insight-item">
          <h4>Inventory Optimization</h4>
          <p>Consider reducing stock levels for category "Electronics" by 15% to optimize holding costs.</p>
        </div>
        <div class="insight-item">
          <h4>Payment Trends</h4>
          <p>Accounts receivable aging is improving. 30-day collection rate increased by 12% this quarter.</p>
        </div>
      </div>
    </div>

    <!-- Chart Section with Swappable Charts -->
    <div class="sortable-section chart-box">
      <h3>
        Product Categories Comparison
        <div style="display: flex; gap: 10px;">
          <button class="export-btn" style="background-color: #7f8c8d; padding: 5px 10px; font-size: 12px;" onclick="exportChartToPDF()">
            <i class="fas fa-download"></i> Export Charts
          </button>
        </div>
      </h3>
      <div id="sortableCharts" class="chart-row">
        <div class="chart-card">
          <h4>Product Distribution</h4>
          <canvas class="pie" id="myChart1"></canvas>
        </div>
        <div class="chart-card">
          <h4>Value Distribution</h4>
          <canvas class="pie" id="myChart2"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
  // Sort full sections
  new Sortable(document.getElementById('sortableSections'), {
    animation: 200,
    handle: ".sortable-section",
    ghostClass: "dragging"
  });

  // Sort metric cards
  new Sortable(document.getElementById('sortableMetrics'), {
    animation: 150,
    ghostClass: "dragging"
  });

  // Sort chart cards
  new Sortable(document.getElementById('sortableCharts'), {
    animation: 150,
    ghostClass: "dragging"
  });
</script>

<!-- Chart.js Pie Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const chartLabels = <?php echo json_encode($categories); ?>;
  const chartData = <?php echo json_encode($totals); ?>;

  const ctx1 = document.getElementById('myChart1').getContext('2d');
  const chart1 = new Chart(ctx1, {
    type: 'pie',
    data: {
      labels: chartLabels,
      datasets: [{
        label: 'Product Categories',
        data: chartData,
        backgroundColor: ['#2ecc71', '#e67e22', '#3498db', '#9b59b6', '#f1c40f', '#1abc9c']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' }
      }
    }
  });

  const ctx2 = document.getElementById('myChart2').getContext('2d');
  const chart2 = new Chart(ctx2, {
    type: 'pie',
    data: {
      labels: chartLabels,
      datasets: [{
        label: 'Value Distribution',
        data: chartData.map(val => val * Math.floor(Math.random() * 100) + 50),
        backgroundColor: ['#1abc9c', '#d35400', '#2980b9', '#8e44ad', '#f39c12', '#27ae60']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' }
      }
    }
  });
</script>

<!-- Clock Scripts -->
<script>
  function updateClocks() {
    const now = new Date();
    
    // Philippines Time (UTC+8)
    const phTime = new Date(now.getTime());
    phTime.setHours(phTime.getHours() + 8 - now.getTimezoneOffset() / 60);
    const phTimeString = phTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('clock').textContent = phTimeString;
    
    // US Time (Using local browser time)
    const usTimeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('clock-us').textContent = usTimeString;
  }
  
  setInterval(updateClocks, 1000);
  updateClocks();
</script>

<!-- Export Functions -->
<script>
  // Export functions
  function exportToPDF() {
    alert('Exporting dashboard to PDF...');
    // Actual PDF generation would be implemented here
    // Using a library like jsPDF or making a backend request
  }
  
  function exportToExcel() {
    alert('Exporting data to Excel...');
    // Excel export implementation
  }
  
  function exportToCSV() {
    alert('Exporting data to CSV...');
    // CSV export implementation
  }
  
  function exportChartToPDF() {
    alert('Exporting charts to PDF...');
    // Chart-specific export implementation
  }
  
  function applyDateFilter() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const category = document.getElementById('filter-category').value;
    
    if(!startDate || !endDate) {
      alert('Please select both start and end dates');
      return;
    }
    
    alert(`Applying filter for date range: ${startDate} to ${endDate} and category: ${category}`);
    
    // Create form data for the AJAX request
    const formData = new FormData();
    formData.append('action', 'filter_dashboard');
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    formData.append('category', category);
    
    // Send AJAX request to reload the dashboard data
    fetch('dashboard_filter.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        // Update metrics
        if(data.metrics) {
          if(data.metrics.accounts_payable) 
            document.querySelector('.grid-item:nth-child(1) .value').textContent = data.metrics.accounts_payable;
          if(data.metrics.accounts_receivable) 
            document.querySelector('.grid-item:nth-child(2) .value').textContent = data.metrics.accounts_receivable;
          if(data.metrics.invoice_worth) 
            document.querySelector('.grid-item:nth-child(3) .value').textContent = data.metrics.invoice_worth;
          if(data.metrics.inventory_value) 
            document.querySelector('.grid-item:nth-child(4) .value').textContent = data.metrics.inventory_value;
        }
        
        // Update charts
        if(data.chart_data && data.chart_labels) {
          chart1.data.labels = data.chart_labels;
          chart1.data.datasets[0].data = data.chart_data;
          chart1.update();
          
          chart2.data.labels = data.chart_labels;
          chart2.data.datasets[0].data = data.chart_data.map(val => val * Math.floor(Math.random() * 100) + 50);
          chart2.update();
        }
      } else {
        alert('Error applying filter: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to apply filter. Please try again.');
    });
  }
</script>

</body>
</html>