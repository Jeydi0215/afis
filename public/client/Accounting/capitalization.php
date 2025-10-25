<?php

include ('../../components/sidebar.php'); 
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Capitalizations Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
          --primary-color: #1F5497;
          --secondary-color: #28a745;
          --danger-color: #e74c3c;
          --warning-color: #f39c12;
          --light-gray: #f5f5f5;
          --medium-gray: #e0e0e0;
          --dark-gray: #333;
          --text-color: #444;
          --border-radius: 8px;
          --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
          --transition: all 0.3s ease;
        }

        html, body {
          overflow-x: hidden;
          overflow-y: auto;
        }

        body {
          font-family: "Quicksand", 'Segoe UI', sans-serif;
          margin: 0;
          padding: 0;
          background-color: #f0f2f5;
          color: var(--text-color);
          line-height: 1.6;
        }

        .container {
           width: 100%;
          padding: 30px;
          margin-top: 2%;
          border-radius: var(--border-radius);
          box-shadow: var(--box-shadow);
        }

     .page-title {
    font-weight: bold;
    font-size: 24px;
    color: #1f2937;
    margin: 0 0 20px 0; /* Add bottom margin for spacing */
    margin-left: 120px; /* Match the sidebar width to align with main content */
    padding: 20px 20px 0 20px; /* Add padding for better spacing */
    position: relative; /* Keep relative positioning */
    top: 2%; /* Remove the negative top positioning */
    left: 2%;
    display: block;
    visibility: visible;
    opacity: 1;
    z-index: 10; /* Ensure it appears above other elements */
     border-bottom: none !important;
}

        .filters-section {
          background: #f8f9fa;
          border-radius: var(--border-radius);
          padding: 25px;
          margin-top:-50px;
          margin-bottom: 30px;
          border: 1px solid var(--medium-gray);
        }
        
        .filter-row {
          display: flex;
          gap: 20px;
          margin-bottom: 15px;
          flex-wrap: wrap;
          align-items: end;
        }
        
        .filter-group {
          flex: 1;
          min-width: 200px;
        }
        
        label {
          display: block;
          margin-bottom: 5px;
          font-weight: 600;
          color: var(--text-color);
        }
        
        input, select {
          width: 100%;
          padding: 10px;
          border: 1px solid #ccc;
          border-radius: var(--border-radius);
          font-size: 14px;
          transition: var(--transition);
          font-family: "Quicksand", sans-serif;
          box-sizing: border-box;
        }
        
        input:focus, select:focus {
          outline: none;
          border-color: var(--primary-color);
          box-shadow: 0 0 0 2px rgba(31, 84, 151, 0.2);
        }

        .btn {
          background-color: #4A4A4A;
          color: white;
          border: none;
          padding: 10px 20px;
          border-radius: var(--border-radius);
          cursor: pointer;
          font-weight: 500;
          transition: var(--transition);
          font-family: "Quicksand", sans-serif;
        }

        .btn:hover {
          background-color: #333;
        }

        .btn-create {
          background-color: #4A4A4A;
          color: white;
          border: none;
          padding: 12px 24px;
          font-size: 16px;
          border-radius: 5px;
          cursor: pointer;
          position: absolute;
          right: 2.5%;
          top: 10%;
          transform: translateY(-50%);
        }

        .btn-create:hover {
          background-color: #333;
        }
        
        .summary-cards {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
          gap: 20px;
          margin-bottom: 30px;
        }
        
        .summary-card {
          background: #4A4A4A;
          color: white;
          padding: 25px;
          border-radius: var(--border-radius);
          text-align: center;
          box-shadow: var(--box-shadow);
        }
        
        .summary-card h3 {
          font-size: 1.8rem;
          margin-bottom: 10px;
          font-weight: 600;
        }
        
        .summary-card p {
          font-size: 1.1rem;
          opacity: 0.9;
          margin: 0;
        }

        /* Table styling matching your invoice design */
        .invoices-list {
          margin-top: 25px;
          overflow-x: auto;
        }

        .invoices-table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
          min-width: 1400px;
          left:-100px;
        }

        .invoices-table th, 
        .invoices-table td {
          padding: 12px;
          text-align: left;
          border-bottom: 1px solid #ddd;
        }

        .invoices-table th {
          background-color: #4A4A4A;
          color: white;
          font-weight: 600;
          position: sticky;
          top: 0;
          z-index: 1;
        }

        .invoices-table tr:nth-child(even) {
          background-color: #f2f2f2;
        }

        .invoices-table tr:hover {
          background-color: #e6e6e6;
        }

        .invoices-table td.amount {
          text-align: right;
          font-weight: 600;
        }

        .amount {
          font-weight: 600;
          text-align: right;
        }

        .status-active {
          background: var(--secondary-color);
          color: white;
          padding: 4px 8px;
          border-radius: 12px;
          font-size: 11px;
          font-weight: bold;
          display: inline-block;
        }
        
        .status-completed {
          background: #6c757d;
          color: white;
          padding: 4px 8px;
          border-radius: 12px;
          font-size: 11px;
          font-weight: bold;
          display: inline-block;
        }
        
        .status-disposed {
          background: var(--danger-color);
          color: white;
          padding: 4px 8px;
          border-radius: 12px;
          font-size: 11px;
          font-weight: bold;
          display: inline-block;
        }
        
        .progress-bar {
          width: 100%;
          height: 8px;
          background: var(--light-gray);
          border-radius: 4px;
          overflow: hidden;
          margin-top: 5px;
        }
        
        .progress-fill {
          height: 100%;
          background: linear-gradient(45deg, var(--secondary-color), #20c997);
          transition: width 0.3s ease;
        }
        
        .category-tag {
          background: var(--light-gray);
          color: var(--text-color);
          padding: 3px 8px;
          border-radius: 10px;
          font-size: 11px;
          font-weight: 600;
          display: inline-block;
        }
        
        .years-remaining {
          font-weight: bold;
          color: var(--text-color);
        }

        .no-invoices {
          text-align: center;
          padding: 60px 20px;
          color: #999;
        }

        .no-invoices h3 {
          font-size: 24px;
          margin-bottom: 15px;
          color: var(--dark-gray);
        }

        .no-invoices p {
          font-size: 16px;
          max-width: 500px;
          margin: 0 auto;
        }

        .asset-name {
          font-weight: 600;
          color: var(--dark-gray);
          margin-bottom: 4px;
        }

        .asset-description {
          font-size: 12px;
          color: #6c757d;
          font-style: italic;
        }

        /* Action buttons matching your invoice style */
        .action-buttons {
          display: flex;
          gap: 12px;
          justify-content: center;
          flex-wrap: wrap;
        }

       .action-icon-btn {
  background-color: #f0f2f5;
  color: #333;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease-in-out;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  cursor: pointer;
  padding: 0; /* remove browser default padding */
}

.action-icon-btn svg {
  width: 22px;
  height: 22px;
  display: block; /* remove inline spacing */
  margin: auto;   /* force center */
}


        .action-icon-btn:hover {
          background-color: ##4A4A4A;
          color: white;
          transform: scale(1.1);
        }
        
        @media (max-width: 1200px) {
          .container {
            margin: 20px;
            padding: 20px;
          }

          .invoice-list-header,
          .invoice-row {
            grid-template-columns: 200px 100px 120px 100px 120px 100px 100px 130px 120px 150px;
            gap: 10px;
            font-size: 13px;
          }
        }

        @media (max-width: 768px) {
          .filter-row {
            flex-direction: column;
          }
          
          .page-title {
            font-size: 20px;
            padding: 15px;
          }
          
          .summary-cards {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
          }

          .invoices-list {
            font-size: 12px;
          }

          .invoices-table {
            grid-template-columns: 1fr;
            gap: 5px;
            min-width: auto;
          }

          .invoices-table thead {
            display: none;
          }

          .invoices-table tbody,
          .invoices-table tr,
          .invoices-table td {
            display: block;
            width: 100%;
          }

          .invoices-table tr {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background: white;
          }

          .invoices-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            text-align: left !important;
          }

          .invoices-table td:before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--primary-color);
            min-width: 120px;
          }

          .btn-create {
            position: static;
            transform: none;
            width: 100%;
            margin-bottom: 20px;
          }
        }
        .main-content{
              margin-left:100px;
              width: 96%;
        }
    </style>
</head>
<body>
         <h1 class="page-title"> Financial Capitalizations Tracker</h1>
    <div class="container">
   
     
        
        <div class="main-content">
            <!-- Filters Section -->
            <div class="filters-section">
                <h3> Filter & Search</h3>
                <form>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search Assets</label>
                            <input type="text" id="search" name="search" placeholder="Search by name or description..." 
                                   value="">
                        </div>
                        <div class="filter-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">All Categories</option>
                                <option value="Real Estate">Real Estate</option>
                                <option value="Machinery">Machinery</option>
                                <option value="Equipment">Equipment</option>
                                <option value="Software">Software</option>
                                <option value="Intangible">Intangible</option>
                                <option value="Vehicles">Vehicles</option>
                                <option value="Furniture">Furniture</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="completed">Fully Depreciated</option>
                                <option value="disposed">Disposed</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>$2,315,000</h3>
                    <p>Total Capitalized Assets</p>
                </div>
                <div class="summary-card">
                    <h3>$2,140,000</h3>
                    <p>Active Assets Value</p>
                </div>
                <div class="summary-card">
                    <h3>7</h3>
                    <p>Active Assets Count</p>
                </div>
                <div class="summary-card">
                    <h3>$181,458</h3>
                    <p>Annual Depreciation</p>
                </div>
            </div>
            
            <!-- Capitalizations List in Invoice Table Style -->
            <div class="invoices-list">
                <table class="invoices-table">
                    <thead>
                        <tr>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Original Cost</th>
                            <th>Useful Life</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Annual Depr.</th>
                            <th>Progress</th>
                            <th>Remaining</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="asset-name">Main Office Building</div>
                                <div class="asset-description">Corporate headquarters building</div>
                            </td>
                            <td>
                                <span class="category-tag">Real Estate</span>
                            </td>
                            <td class="amount">$850,000</td>
                            <td>25 years</td>
                            <td>Straight Line</td>
                            <td>
                                <span class="status-active">Active</span>
                            </td>
                            <td class="amount">$34,000</td>
                            <td>
                                20.0%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 20%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="years-remaining">20.0 years</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-icon-btn" title="View Details"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>
                                    <button class="action-icon-btn" title="Edit Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></button>
                                    <button class="action-icon-btn" title="Delete Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="asset-name">Manufacturing Equipment Line A</div>
                                <div class="asset-description">Primary production line equipment</div>
                            </td>
                            <td>
                                <span class="category-tag">Machinery</span>
                            </td>
                            <td class="amount">$425,000</td>
                            <td>12 years</td>
                            <td>Double Declining</td>
                            <td>
                                <span class="status-active">Active</span>
                            </td>
                            <td class="amount">$35,417</td>
                            <td>
                                31.2%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 31.2%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="years-remaining">8.3 years</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-icon-btn" title="View Details"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>
                                    <button class="action-icon-btn" title="Edit Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></button>
                                    <button class="action-icon-btn" title="Delete Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="asset-name">ERP Software System</div>
                                <div class="asset-description">Enterprise resource planning system</div>
                            </td>
                            <td>
                                <span class="category-tag">Software</span>
                            </td>
                            <td class="amount">$150,000</td>
                            <td>8 years</td>
                            <td>Straight Line</td>
                            <td>
                                <span class="status-active">Active</span>
                            </td>
                            <td class="amount">$18,750</td>
                            <td>
                                28.1%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 28.1%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="years-remaining">5.8 years</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-icon-btn" title="View Details"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>
                                    <button class="action-icon-btn" title="Edit Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></button>
                                    <button class="action-icon-btn" title="Delete Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="asset-name">Company Fleet Vehicles</div>
                                <div class="asset-description">12 company vehicles for sales team</div>
                            </td>
                            <td>
                                <span class="category-tag">Vehicles</span>
                            </td>
                            <td class="amount">$180,000</td>
                            <td>5 years</td>
                            <td>Straight Line</td>
                            <td>
                                <span class="status-active">Active</span>
                            </td>
                            <td class="amount">$36,000</td>
                            <td>
                                13.3%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 13.3%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="years-remaining">4.3 years</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-icon-btn" title="View Details"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>
                                    <button class="action-icon-btn" title="Edit Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></button>
                                    <button class="action-icon-btn" title="Delete Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="asset-name">Warehouse Equipment</div>
                                <div class="asset-description">Forklifts and warehouse machinery</div>
                            </td>
                            <td>
                                <span class="category-tag">Equipment</span>
                            </td>
                            <td class="amount">$95,000</td>
                            <td>10 years</td>
                            <td>Straight Line</td>
                            <td>
                                <span class="status-completed">Completed</span>
                            </td>
                            <td class="amount">$0</td>
                            <td>
                                100.0%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="years-remaining">N/A</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-icon-btn" title="View Details"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>
                                    <button class="action-icon-btn" title="Edit Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></button>
                                    <button class="action-icon-btn" title="Delete Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="asset-name">R&D Laboratory Setup</div>
                                <div class="asset-description">Research and development lab equipment</div>
                            </td>
                            <td>
                                <span class="category-tag">Equipment</span>
                            </td>
                            <td class="amount">$320,000</td>
                            <td>15 years</td>
                            <td>Straight Line</td>
                            <td>
                                <span class="status-active">Active</span>
                            </td>
                            <td class="amount">$21,333</td>
                            <td>
                                18.4%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 18.4%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="years-remaining">12.2 years</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-icon-btn" title="View Details"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>
                                    <button class="action-icon-btn" title="Edit Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></button>
                                    <button class="action-icon-btn" title="Delete Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="asset-name">Patent Portfolio</div>
                                <div class="asset-description">Technology patents and intellectual property</div>
                            </td>
                            <td>
                                <span class="category-tag">Intangible</span>
                            </td>
                            <td class="amount">$220,000</td>
                            <td>20 years</td>
                            <td>Straight Line</td>
                            <td>
                                <span class="status-active">Active</span>
                            </td>
                            <td class="amount">$11,000</td>
                            <td>
                                22.5%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 22.5%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="years-remaining">15.5 years</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-icon-btn" title="View Details"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>
                                    <button class="action-icon-btn" title="Edit Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></button>
                                    <button class="action-icon-btn" title="Delete Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="asset-name">Office Furniture & Fixtures</div>
                                <div class="asset-description">Modern office furniture and fixtures</div>
                            </td>
                            <td>
                                <span class="category-tag">Furniture</span>
                            </td>
                            <td class="amount">$75,000</td>
                            <td>7 years</td>
                            <td>Straight Line</td>
                            <td>
                                <span class="status-active">Active</span>
                            </td>
                            <td class="amount">$10,714</td>
                            <td>
                                40.7%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 40.7%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="years-remaining">4.2 years</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-icon-btn" title="View Details"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>
                                    <button class="action-icon-btn" title="Edit Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></button>
                                    <button class="action-icon-btn" title="Delete Asset"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add some basic interactivity
        document.querySelectorAll('.action-icon-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const title = this.getAttribute('title');
                alert(title + ' functionality would be implemented here');
            });
        });

        // Filter functionality
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Filter functionality would be implemented here');
        });
    </script>
</body>
</html>