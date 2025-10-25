<?php
include ('../../components/sidebar.php'); 
// include('logout.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cash Disbursement</title>
    <link rel="stylesheet" href="CashDisbursement.css">
    <style>
     
      .table-container { padding: 20px; }
      .Title { margin-bottom: 10px; }
      .sheet-frame iframe { border: none; }
      .main-content{
        margin-left:7%;
      }
    </style>
</head>
<body>
  <h1 class="page-title">Cash Disbursement Sheets</h1>
  <div class="main-content">
    <div class="flex flex-col gap-5 greetings">
      
    </div>
    <section>
      <div class="table-container">
        <div class="Title">
          <label for="sheetSelector">View Sheet:</label>
          <select id="sheetSelector" onchange="switchSheet()">
            <option value="CashDisbursement">Cash Disbursement</option>
            <option value="OtherSheet">Other Sheet</option>
          </select>
        </div>
        <div id="CashDisbursement" class="sheet-frame">
          <iframe 
            src="https://docs.google.com/spreadsheets/d/1_gr7kXx3t6JxY_QxV_fX8_zWC1D8WC5yf8NOCMDShjQ/edit?gid=0#gid=0" 
            style="width:100%; height:700px;">
          </iframe>
        </div>
        <div id="OtherSheet" class="sheet-frame" style="display:none">
          <iframe 
            src="https://docs.google.com/spreadsheets/d/YOUR_OTHER_SHEET_ID/edit?gid=0" 
            style="width:100%; height:700px;">
          </iframe>
        </div>
      </div>
    </section>
  </div>

  <script>
    function switchSheet() {
      const selected = document.getElementById('sheetSelector').value;
      document.querySelectorAll('.sheet-frame').forEach(div => {
        div.style.display = div.id === selected ? 'block' : 'none';
      });
    }
    document.addEventListener('DOMContentLoaded', switchSheet);
  </script>
</body>
</html>
