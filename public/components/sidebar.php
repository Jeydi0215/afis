<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/../'); 
}

// Go into client/config/config.php
include(BASE_PATH . 'client/config/config.php');

$full_name = 'Guest'; // default

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT full_name, image_path, user_position FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']); 
    $stmt->execute();
    $stmt->bind_result($full_name, $image_path, $user_position);
    $stmt->fetch();
    $stmt->close(); 
}
?>

<link rel="stylesheet" href="/components/css/sidebar.css">

<div class="sidebar-container">
        <div class="sidebar">
        <nav id="sidebarMenu">
            <div class="sidebarLogo">
                <img src="https://www.stafify.com/cdn/shop/files/e50lj9u5c9xat9j7z3ne_752x.png?v=1613708232" class="menu-text site-logo" alt="Stafify Logo">
                <img src="https://res.cloudinary.com/dt1vbprub/image/upload/v1741661073/Stafify_Icon_onet8q.jpg" class="site-icon" alt="Stafify Icon">
            </div>
            <!-- Add Profile Navigation Section -->
            <a href="#" class="flex gap-3 items-center card-profile">
                <div class="flex-shrink-0">
                <img src="<?= htmlspecialchars($image_path) ?>" alt="Profile Picture" class="profile-pic">
</div>
                <div class="details-profile menu-text">
                    <span class="profile-name">
                    <span id="username-display" class="profile-name">
                        <?= htmlspecialchars($full_name) ?>
                        </span>
                    </span>
                    <p class="profile-dept">
                        <span class="position-display"></span>
                        <span class="separator"> <?= htmlspecialchars($user_position)?> </span>
                        <span class="department-display"></span>
                    </p>
                </div>
            </a>
       
 
                <select class="hqdropdown" id="hq" name="hqselect">
                   <option value="" selected disabled>Headquarters</option>
                  <option value="Customer">Customer</option>
                  <option value="Supplier">Supplier</option>
                </select>

              <!-- <ul class="flex flex-col gap-2 sidebarMenuItems">
              <li class="sidebarMenuItem has-dropdown">
                      <a href="#" class="justify-between nav-link" onclick="toggleDropdown(event, 'attendanceDropdown9')">
                          <span class="flex items-center">
                          <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-buildings"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 21v-15c0 -1 1 -2 2 -2h5c1 0 2 1 2 2v15" /><path d="M16 8h2c1 0 2 1 2 2v11" /><path d="M3 21h18" /><path d="M10 12v0" /><path d="M10 16v0" /><path d="M10 8v0" /><path d="M7 12v0" /><path d="M7 16v0" /><path d="M7 8v0" /><path d="M17 12v0" /><path d="M17 16v0" /></svg>
                              <span class="menu-text">Headquarters</span>
                          </span>
                          <svg  xmlns="http://www.w3.org/2000/svg"  width="23"  height="23"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon-tabler icons-tabler-outline icon-tabler-chevron-down">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M6 9l6 6l6 -6" />
                          </svg>
                      </a>
                      <ul class="dropdown attendanceDropdown9 desktop">
                      <li class="sidebarMenuItem">
                          <a href="supplier.php" data-page="time-tracking" class="nav-link">
                          <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-tax"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8.487 21h7.026a4 4 0 0 0 3.808 -5.224l-1.706 -5.306a5 5 0 0 0 -4.76 -3.47h-1.71a5 5 0 0 0 -4.76 3.47l-1.706 5.306a4 4 0 0 0 3.808 5.224" /><path d="M15 3q -1 4 -3 4t -3 -4z" /><path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" /><path d="M12 10v1" /><path d="M12 17v1" /></svg>
                          <span class="menu-text">Supplier</span>
                          </a>
                      </li>
                      <li class="sidebarMenuItem">
                          <a href="customer.php" data-page="shift-management" class="nav-link ">
                          <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-chart-histogram"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 3v18h18" /><path d="M20 18v3" /><path d="M16 16v5" /><path d="M12 13v8" /><path d="M8 16v5" /><path d="M3 11c6 0 5 -5 9 -5s3 5 9 5" /></svg>
                          <span class="menu-text">Customers</span>
                          </a>
                      </li>
                      
                      </ul>
                  </li>  -->

                <ul class="flex flex-col gap-2 sidebarMenuItems">
                <li class="sidebarMenuItem">
                    <a href="/client/dashboard.php" class="nav-link">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-layout-grid"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /></svg>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>
      


                <li class="sidebarMenuItem has-dropdown">
                    <a href="#" class="justify-between nav-link" onclick="toggleDropdown(event, 'attendanceDropdown')">
                        <span class="flex items-center">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-file-spreadsheet"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M8 11h8v7h-8z" /><path d="M8 15h8" /><path d="M11 11v7" /></svg>
                            <span class="menu-text">Accounting</span>
                        </span>
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="23"  height="23"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon-tabler icons-tabler-outline icon-tabler-chevron-down">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M6 9l6 6l6 -6" />
                        </svg>
                    </a>
                    <ul class="dropdown attendanceDropdown desktop">
                    <li class="sidebarMenuItem">
                        <a href="../client/Accounting/Business_Settings/business_settings.php" data-page="time-tracking" class="nav-link">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-tax"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8.487 21h7.026a4 4 0 0 0 3.808 -5.224l-1.706 -5.306a5 5 0 0 0 -4.76 -3.47h-1.71a5 5 0 0 0 -4.76 3.47l-1.706 5.306a4 4 0 0 0 3.808 5.224" /><path d="M15 3q -1 4 -3 4t -3 -4z" /><path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" /><path d="M12 10v1" /><path d="M12 17v1" /></svg>
                        <span class="menu-text">Business Settings</span>
                        </a>
                    </li>
                    
                    <li class="sidebarMenuItem">
                        <a href="../client/Accounting/capitalization.php" data-page="leave-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-database-dollar"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3" /><path d="M4 6v6c0 1.657 3.582 3 8 3c.415 0 .822 -.012 1.22 -.035" /><path d="M20 10v-4" /><path d="M4 12v6c0 1.657 3.582 3 8 3c.352 0 .698 -.009 1.037 -.025" /><path d="M21 15h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" /><path d="M19 21v1m0 -8v1" /></svg>
                        <span class="menu-text">Capitalization</span>
                        </a>
                    </li>
                    <li class="sidebarMenuItem">
                        <a href="../client/Accounting/Chart_of_Accounts/chart_of_accounts.php" data-page="shift-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-chart-histogram"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 3v18h18" /><path d="M20 18v3" /><path d="M16 16v5" /><path d="M12 13v8" /><path d="M8 16v5" /><path d="M3 11c6 0 5 -5 9 -5s3 5 9 5" /></svg>
                        <span class="menu-text">Chart of Accounts</span>
                        </a>
                    </li>
                    
                  
                    </ul>
                </li>
                
                <li class="sidebarMenuItem has-dropdown">
  <a href="#" class="justify-between nav-link" onclick="toggleDropdown(event, 'attendanceDropdown2')">
    <span class="flex items-center">
    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-right-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 7l-18 0" /><path d="M18 10l3 -3l-3 -3" /><path d="M6 20l-3 -3l3 -3" /><path d="M3 17l18 0" /></svg>
      <span class="menu-text">Transactions</span>
    </span>
    <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="icon-tabler icon-tabler-chevron-down">
      <path stroke="none" d="M0 0h24v24H0z"/>
      <path d="M6 9l6 6l6 -6" />
    </svg>
  </a>

  <ul class="dropdown attendanceDropdown2 desktop" id="attendanceDropdown2">
    <li class="sidebarMenuItem">
      <a href="../client/Transactions/JobOrder.php" class="nav-link">
      <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-book-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 4v16h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12z" /><path d="M19 16h-12a2 2 0 0 0 -2 2" /><path d="M9 8h6" /></svg>
        <span class="menu-text">Revenue Logsheet</span>
      </a>
    </li>

    <li class="sidebarMenuItem">
      <a href="../client/Transactions/cashDisbursement.php" class="nav-link">
      <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-dollar"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 21h-7a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v3" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h12.5" /><path d="M21 15h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" /><path d="M19 21v1m0 -8v1" /></svg>
        <span class="menu-text">Expense Logsheet</span>
      </a>
    </li>

    <li class="sidebarMenuItem">
      <a href="#" class="nav-link">
      <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-report-money"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" /><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" /><path d="M12 17v1m0 -8v1" /></svg>    
        <span class="menu-text">Journal Logsheet</span>
      </a>
    </li>
    
    <li class="sidebarMenuItem">
      <a href="#" class="nav-link">
      <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-report-money"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" /><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" /><path d="M12 17v1m0 -8v1" /></svg>    
        <span class="menu-text">Inventory Logsheet</span>
      </a>
    </li>
  </ul>
</li>
<ul class="flex flex-col gap-2 sidebarMenuItems">
                <li class="sidebarMenuItem">
                    <a href="dashboard.php" class="nav-link">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-books"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" /><path d="M9 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" /><path d="M5 8h4" /><path d="M9 16h4" /><path d="M13.803 4.56l2.184 -.53c.562 -.135 1.133 .19 1.282 .732l3.695 13.418a1.02 1.02 0 0 1 -.634 1.219l-.133 .041l-2.184 .53c-.562 .135 -1.133 -.19 -1.282 -.732l-3.695 -13.418a1.02 1.02 0 0 1 .634 -1.219l.133 -.041z" /><path d="M14 9l4 -1" /><path d="M16 16l3.923 -.98" /></svg>
                        <span class="menu-text">Books of Accounts</span>
                    </a>
                </li>
                
            


                 <li class="sidebarMenuItem has-dropdown">
                    <a href="#" class="justify-between nav-link" onclick="toggleDropdown(event, 'attendanceDropdown5')">
                        <span class="flex items-center">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cash-register"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 15h-2.5c-.398 0 -.779 .158 -1.061 .439c-.281 .281 -.439 .663 -.439 1.061c0 .398 .158 .779 .439 1.061c.281 .281 .663 .439 1.061 .439h1c.398 0 .779 .158 1.061 .439c.281 .281 .439 .663 .439 1.061c0 .398 -.158 .779 -.439 1.061c-.281 .281 -.663 .439 -1.061 .439h-2.5" /><path d="M19 21v1m0 -8v1" /><path d="M13 21h-7c-.53 0 -1.039 -.211 -1.414 -.586c-.375 -.375 -.586 -.884 -.586 -1.414v-10c0 -.53 .211 -1.039 .586 -1.414c.375 -.375 .884 -.586 1.414 -.586h2m12 3.12v-1.12c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-2" /><path d="M16 10v-6c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-4c-.53 0 -1.039 .211 -1.414 .586c-.375 .375 -.586 .884 -.586 1.414v6m8 0h-8m8 0h1m-9 0h-1" /><path d="M8 14v.01" /><path d="M8 17v.01" /><path d="M12 13.99v.01" /><path d="M12 17v.01" /></svg>
                        <span class="menu-text">Cash Management</span>
                        </span>
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="23"  height="23"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon-tabler icons-tabler-outline icon-tabler-chevron-down">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M6 9l6 6l6 -6" />
                        </svg>
                    </a>
                    <ul class="dropdown attendanceDropdown5 desktop">

                    <li class="sidebarMenuItem">
                        <a href="../client/Cash Management/bank.php" data-page="shift-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-mail-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-7a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v5.5" /><path d="M19 16v6" /><path d="M22 19l-3 3l-3 -3" /><path d="M3 7l9 6l9 -6" /></svg>
                        <span class="menu-text">Bank Enrollment</span>
                        </a>
                    </li>
                     <li class="sidebarMenuItem">
                        <a href="../client/Cash Management/bank_reco.php" data-page="leave-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pig-money"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 11v.01" /><path d="M5.173 8.378a3 3 0 1 1 4.656 -1.377" /><path d="M16 4v3.803a6.019 6.019 0 0 1 2.658 3.197h1.341a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-1.342c-.336 .95 -.907 1.8 -1.658 2.473v2.027a1.5 1.5 0 0 1 -3 0v-.583a6.04 6.04 0 0 1 -1 .083h-4a6.04 6.04 0 0 1 -1 -.083v.583a1.5 1.5 0 0 1 -3 0v-2l0 -.027a6 6 0 0 1 4 -10.473h2.5l4.5 -3h0z" /></svg>
                        <span class="menu-text">Bank Reconciliation</span>
                        </a>
                    </li>

                 
                    </ul>
                </li>
          
                
                <!-- <li class="sidebarMenuItem has-dropdown">
                    <a href="#" class="justify-between nav-link" onclick="toggleDropdown(event, 'attendanceDropdown3')">
                        <span class="flex items-center">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-coins"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14c0 1.657 2.686 3 6 3s6 -1.343 6 -3s-2.686 -3 -6 -3s-6 1.343 -6 3z" /><path d="M9 14v4c0 1.656 2.686 3 6 3s6 -1.344 6 -3v-4" /><path d="M3 6c0 1.072 1.144 2.062 3 2.598s4.144 .536 6 0c1.856 -.536 3 -1.526 3 -2.598c0 -1.072 -1.144 -2.062 -3 -2.598s-4.144 -.536 -6 0c-1.856 .536 -3 1.526 -3 2.598z" /><path d="M3 6v10c0 .888 .772 1.45 2 2" /><path d="M3 11c0 .888 .772 1.45 2 2" /></svg>
                        <span class="menu-text">Financial Reports</span>
                        </span>
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="23"  height="23"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon-tabler icons-tabler-outline icon-tabler-chevron-down">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M6 9l6 6l6 -6" />
                        </svg>
                    </a>
                    <ul class="dropdown attendanceDropdown3 desktop">
                    <li class="sidebarMenuItem">
                        <a href="time-tracking.php" data-page="time-tracking" class="nav-link">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-stopwatch"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13a7 7 0 1 0 14 0a7 7 0 0 0 -14 0z" /><path d="M14.5 10.5l-2.5 2.5" /><path d="M17 8l1 -1" /><path d="M14 3h-4" /></svg>
                        <span class="menu-text">Monthly</span>
                        </a>
                    </li>
                    <li class="sidebarMenuItem">
                        <a href="shift-management.php" data-page="shift-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-layout-kanban"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4l6 0" /><path d="M14 4l6 0" /><path d="M4 8m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M14 8m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /></svg>
                        <span class="menu-text">Quarterly</span>
                        </a>
                    </li>
                    <li class="sidebarMenuItem">
                        <a href="leave-management.php" data-page="leave-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-user"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21h-6a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v4.5" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M19 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M22 22a2 2 0 0 0 -2 -2h-2a2 2 0 0 0 -2 2" /></svg>
                        <span class="menu-text">Annually</span>
                        </a>
                    </li>

                    </ul>
                </li> -->

                <li class="sidebarMenuItem has-dropdown">
                    <a href="#" class="justify-between nav-link" onclick="toggleDropdown(event, 'attendanceDropdown4')">
                        <span class="flex items-center">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-credit-card-pay"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-6a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v4.5" /><path d="M3 10h18" /><path d="M16 19h6" /><path d="M19 16l3 3l-3 3" /><path d="M7.005 15h.005" /><path d="M11 15h2" /></svg>
                        <span class="menu-text">Billing & Payments</span>
                        </span>
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="23"  height="23"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon-tabler icons-tabler-outline icon-tabler-chevron-down">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M6 9l6 6l6 -6" />
                        </svg>
                    </a>
                    <ul class="dropdown attendanceDropdown4 desktop">
                    <li class="sidebarMenuItem">
                        <a href="../client/Billings/Invoice/Invoice.php" data-page="time-tracking" class="nav-link">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-speakerphone"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 8a3 3 0 0 1 0 6" /><path d="M10 8v11a1 1 0 0 1 -1 1h-1a1 1 0 0 1 -1 -1v-5" /><path d="M12 8h0l4.524 -3.77a.9 .9 0 0 1 1.476 .692v12.156a.9 .9 0 0 1 -1.476 .692l-4.524 -3.77h-8a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h8" /></svg>
                        <span class="menu-text">E-Invoice</span>
                        </a>
                    </li>

                 
                    <li class="sidebarMenuItem">
                        <a href="../client/Billings/Acknowledge/acknowledge.php" data-page="leave-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-receipt"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2" /></svg>
                        <span class="menu-text">Ack Receipt</span>
                        </a>
                    </li>
                    <li class="sidebarMenuItem">
                        <a href="../client/Billings/Petty Cash/petty_cash.php" data-page="leave-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-credit-card-refund"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-6a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v4.5" /><path d="M3 10h18" /><path d="M7 15h.01" /><path d="M11 15h2" /><path d="M16 19h6" /><path d="M19 16l-3 3l3 3" /></svg>
                        <span class="menu-text">Petty Cash Voucher</span>
                        </a>
                    </li>
                    <li class="sidebarMenuItem">
                        <a href="leave-management.php" data-page="leave-management" class="nav-link ">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-user"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21h-6a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v4.5" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M19 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M22 22a2 2 0 0 0 -2 -2h-2a2 2 0 0 0 -2 2" /></svg>
                        <span class="menu-text">Tax Settings</span>
                        </a>
                    </li>
                    </ul>
                </li>


                <li class="sidebarMenuItem">
                    <a href="email-notification.php" data-page="email-notification" class="nav-link ">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-basket"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M5.001 8h13.999a2 2 0 0 1 1.977 2.304l-1.255 7.152a3 3 0 0 1 -2.966 2.544h-9.512a3 3 0 0 1 -2.965 -2.544l-1.255 -7.152a2 2 0 0 1 1.977 -2.304z" /><path d="M17 10l-2 -6" /><path d="M7 10l2 -6" /></svg>
                    <span class="menu-text"> E-Commerce</span>
                    </a>
                </li>




                <li class="sidebarMenuItem">
                    <a href="email-notification.php" data-page="email-notification" class="nav-link ">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-mail-opened"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l9 6l9 -6l-9 -6l-9 6" /><path d="M21 9v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10" /><path d="M3 19l6 -6" /><path d="M15 13l6 6" /></svg>
                    <span class="menu-text">Email Notification</span>
                    </a>
                </li>
                <li class="sidebarMenuItem">
                    <button class="toggle-btn" onclick="toggleSidebar()">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-layout-sidebar-left-collapse"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M9 4v16" /><path d="M15 10l-2 2l2 2" /></svg>
                    <span class="menu-text">Collapse Sidebar</span>
                    </button>
                </li>
            </ul>
            
        </nav>
        
        <div class="flex max-[1024px]:hidden flex-col items-start gap-3 poweredBy">
            <p>Powered By:</p>
            <img src="https://www.stafify.com/cdn/shop/files/e50lj9u5c9xat9j7z3ne_752x.png?v=1613708232" class="site-logo" alt="Stafify Logo">
        </div>
        </div>
    </div>
      <!-- <div class="sidebar">
        <nav id="sidebarMenu">
          <div class="sidebarMenu-inner">
            <div class="sidebarLogo">
              <img
                src="https://www.stafify.com/cdn/shop/files/e50lj9u5c9xat9j7z3ne_752x.png?v=1613708232"
                class="menu-text site-logo"
                alt="Stafify Logo"
              />
              <img
                src="https://res.cloudinary.com/dt1vbprub/image/upload/v1741661073/Stafify_Icon_onet8q.jpg"
                class="site-icon"
                alt="Stafify Icon"
              />
            </div>
            <a href="#" class="flex gap-10 items-center card-profile">
              <div class="image-profile">
                <img
                  src="https://heroshotphotography.com/wp-content/uploads/2023/03/male-linkedin-corporate-headshot-on-white-square-1024x1024.jpg"
                  alt="Profile Picture"
                  class="rounded-full w-10 h-10 object-cover"
                />
              </div>
              <div class="details-profile menu-text">
                <span class="profile-name">
                  <span class="profile-name">Dowelle Dayle Mon</span>
                </span>
                <p class="profile-dept">
                  Department <span class="separator">-</span> Position
                </p>
              </div>
            </a>
            <ul class="flex flex-col gap-5 sidebarMenuItems">
              <li class="sidebarMenuItem">
                <a
                  href="#"
                  data-page="analytics"
                  class="nav-link"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-chart-scatter"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M3 3v18h18" />
                    <path d="M8 15.015v.015" />
                    <path d="M16 16.015v.015" />
                    <path d="M8 7.03v.015" />
                    <path d="M12 11.03v.015" />
                    <path d="M19 11.03v.015" />
                  </svg>
                  <span class="menu-text">Analytics</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <a
                  href="JobOrder.html"
                  data-page="index"
                  class="nav-link"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-briefcase-2"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                      d="M3 9a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9z"
                    />
                    <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                  </svg>
                  <span class="menu-text">Cash Receipts</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <a
                  href="cashDisbursement.html"
                  data-page="cash-disbursement"
                  class="nav-link active"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-coins"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                      d="M9 14c0 1.657 2.686 3 6 3s6 -1.343 6 -3s-2.686 -3 -6 -3s-6 1.343 -6 3z"
                    />
                    <path d="M9 14v4c0 1.656 2.686 3 6 3s6 -1.344 6 -3v-4" />
                    <path
                      d="M3 6c0 1.072 1.144 2.062 3 2.598s4.144 .536 6 0c1.856 -.536 3 -1.526 3 -2.598c0 -1.072 -1.144 -2.062 -3 -2.598s-4.144 -.536 -6 0c-1.856 .536 -3 1.526 -3 2.598z"
                    />
                    <path d="M3 6v10c0 .888 .772 1.45 2 2" />
                    <path d="M3 11c0 .888 .772 1.45 2 2" />
                  </svg>
                  <span class="menu-text">Cash Disbursement</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <a
                  href="Inventory.php"
                  data-page="inventory"
                  class="nav-link"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-building-warehouse"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M3 21v-13l9 -4l9 4v13" />
                    <path d="M13 13h4v8h-10v-6h6" />
                    <path d="M13 21v-9a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3" />
                  </svg>
                  <span class="menu-text">Inventory</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <a
                  href="#"
                  data-page="templates"
                  class="nav-link"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-template"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                      d="M4 4m0 1a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-14a1 1 0 0 1 -1 -1z"
                    />
                    <path
                      d="M4 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"
                    />
                    <path d="M14 12l6 0" />
                    <path d="M14 16l6 0" />
                    <path d="M14 20l6 0" />
                  </svg>
                  <span class="menu-text">Financial Report</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <button
                  class="toggle-btn"
                  onclick="toggleSidebar()"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-layout-sidebar-left-collapse"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                      d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"
                    />
                    <path d="M9 4v16" />
                    <path d="M15 10l-2 2l2 2" />
                  </svg>
                  <span class="menu-text">Collapse Sidebar</span>
                </button>
              </li>
            </ul>
          </div>

          <div class="Nav-foot">
            <span>Powered by:</span>
            <img
              src="https://www.stafify.com/cdn/shop/files/e50lj9u5c9xat9j7z3ne_180x.png?v=1613708232"
              alt=""
            />
          </div>
        </nav>
      </div> -->
    </div>
    <!-- Sidebar Mobile Overlay -->
    <div class="sidebar-overlay-mobile"></div>
    <!-- Mobile Sidebar -->
    <div class="sidebar-container-mobile">
      <div class="sidebar-mobile">
        <div class="sidebarMenu-inner">
          <!-- Close Button -->
          <button class="sidebar-close-btn" style="color: #333">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="25"
              height="25"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="icon icon-tabler icons-tabler-outline icon-tabler-x"
            >
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M18 6l-12 12" />
              <path d="M6 6l12 12" />
            </svg>
          </button>
          <nav id="sidebarMenu">
            <div class="sidebarLogo">
              <img
                src="https://www.stafify.com/cdn/shop/files/e50lj9u5c9xat9j7z3ne_752x.png?v=1613708232"
                class="site-logo"
                alt="Stafify Logo"
              />
            </div>
            <a href="#" class="flex gap-10 items-center card-profile">
              <div class="image-profile">
                <img
                  src="https://heroshotphotography.com/wp-content/uploads/2023/03/male-linkedin-corporate-headshot-on-white-square-1024x1024.jpg"
                />
              </div>
              <div class="details-profile">
                <span class="profile-name">Dowelle Dayle Mon</span>
                <p class="profile-dept">
                  Department <span class="separator">-</span> Position
                </p>
              </div>
            </a>
            <ul class="flex flex-col gap-5 sidebarMenuItems">
              <li class="sidebarMenuItem">
                <a
                  href="#"
                  data-page="analytics"
                  class="nav-link"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-chart-scatter"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M3 3v18h18" />
                    <path d="M8 15.015v.015" />
                    <path d="M16 16.015v.015" />
                    <path d="M8 7.03v.015" />
                    <path d="M12 11.03v.015" />
                    <path d="M19 11.03v.015" />
                  </svg>
                  <span>Analytics</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <a
                  href="JobOrder.html"
                  data-page="index"
                  class="nav-link"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-briefcase-2"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                      d="M3 9a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9z"
                    />
                    <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                  </svg>
                  <span>Cash Receipts</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <a
                  href="cashDisbursement.php"
                  data-page="cash-disbursement"
                  class="nav-link active"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-coins"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                      d="M9 14c0 1.657 2.686 3 6 3s6 -1.343 6 -3s-2.686 -3 -6 -3s-6 1.343 -6 3z"
                    />
                    <path d="M9 14v4c0 1.656 2.686 3 6 3s6 -1.344 6 -3v-4" />
                    <path
                      d="M3 6c0 1.072 1.144 2.062 3 2.598s4.144 .536 6 0c1.856 -.536 3 -1.526 3 -2.598c0 -1.072 -1.144 -2.062 -3 -2.598s-4.144 -.536 -6 0c-1.856 .536 -3 1.526 -3 2.598z"
                    />
                    <path d="M3 6v10c0 .888 .772 1.45 2 2" />
                    <path d="M3 11c0 .888 .772 1.45 2 2" />
                  </svg>
                  <span>Cash Disbursement</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <a
                  href="Inventory.php"
                  data-page="inventory"
                  class="nav-link"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-building-warehouse"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M3 21v-13l9 -4l9 4v13" />
                    <path d="M13 13h4v8h-10v-6h6" />
                    <path d="M13 21v-9a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3" />
                  </svg>
                  <span>Inventory</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <a
                  href="#"
                  data-page="templates"
                  class="nav-link"
                  style="color: #333"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-template"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                      d="M4 4m0 1a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-14a1 1 0 0 1 -1 -1z"
                    />
                    <path
                      d="M4 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"
                    />
                    <path d="M14 12l6 0" />
                    <path d="M14 16l6 0" />
                    <path d="M14 20l6 0" />
                  </svg>
                  <span>Financial Report</span>
                </a>
              </li>
              <li class="sidebarMenuItem">
                <button
                  class="toggle-btn"
                  onclick="toggleSidebar()"
                  style="color: #fff"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-layout-sidebar-left-collapse"
                  >
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                      d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"
                    />
                    <path d="M9 4v16" />
                    <path d="M15 10l-2 2l2 2" />
                  </svg>
                  <span>Collapse Sidebar</span>
                </button>
              </li>
            </ul>
          </nav>
        </div>
        <div class="Nav-foot">
          <span>Powered by:</span>
          <img
            src="https://www.stafify.com/cdn/shop/files/e50lj9u5c9xat9j7z3ne_180x.png?v=1613708232"
            alt=""
          />
        </div>
      </div>
    </div>
    <div class="main-content">
      <!-- Mobile Navigation -->
      <div class="flex justify-between items-center gap-20 w-full mobile-nav">
        <!-- Mobile Menu -->
        <button class="hamburger-menu">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="30"
            height="30"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="icon-tabler icons-tabler-outline icon-tabler-menu"
          >
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M4 8l16 0" />
            <path d="M4 16l16 0" />
          </svg>
        </button>
        <div class="menu-container">
          <button onclick="toggleMenu(event)" class="kebab-menu">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="icon-tabler icons-tabler-outline icon-tabler-user-circle"
            >
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
              <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
              <path
                d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"
              />
            </svg>
          </button>
          <div class="dropdownMenu menu">
            <button onclick="openProfile()">Profile Settings</button>
            <button onclick="logout()">Logout</button>
          </div>
        </div>
      </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuItems = document.querySelectorAll('.sidebarMenuItem li');

    // Load stored active index
    const activeIndex = localStorage.getItem('activeMenuIndex');
    if (activeIndex !== null && menuItems[activeIndex]) {
        menuItems[activeIndex].classList.add('active');
    }

    // Handle click
    menuItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            menuItems.forEach(li => li.classList.remove('active'));
            item.classList.add('active');
            localStorage.setItem('activeMenuIndex', index);
        });
    });
});
    // Sidebar
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        sidebar.classList.toggle('collapsed');

        if (sidebar.classList.contains('collapsed')) {
            mainContent.style.marginLeft = "70px";
        } else {
            mainContent.style.marginLeft = "260px";
        }
    }

    // Sidebar Load icons
    document.addEventListener("DOMContentLoaded", function() {
        feather.replace();
    });

    // Sidebar Mobile
    document.addEventListener("DOMContentLoaded", function () {
        const menuButton = document.querySelector(".hamburger-menu");
        const closeButton = document.querySelector(".sidebar-close-btn");
        const sidebar = document.querySelector(".sidebar-container-mobile");
        const overlay = document.querySelector(".sidebar-overlay-mobile");

        // Function to open sidebar
        function openSidebar() {
            sidebar.classList.add("active");
            overlay.classList.add("active");
        }

        // Function to close sidebar
        function closeSidebar() {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        }

        // Open Sidebar on Hamburger Click
        menuButton.addEventListener("click", openSidebar);

        // Close Sidebar on Close Button Click
        closeButton.addEventListener("click", closeSidebar);

        // Close Sidebar when clicking outside (on overlay)
        overlay.addEventListener("click", closeSidebar);
    });

    animateLoadingText(); // Start animation
        
   function toggleDropdown(event, dropdownClass) {
    event.preventDefault();
    const dropdown = document.querySelector(`.${dropdownClass}`);

    if (!dropdown) return;

    // Close all other open dropdowns first
    const allDropdowns = document.querySelectorAll('.dropdown'); // use your actual dropdown class
    allDropdowns.forEach(d => {
        if (d !== dropdown && d.classList.contains("open")) {
            d.style.maxHeight = null;
            d.style.opacity = "0";
            d.style.marginBottom = "-5px";
            d.classList.remove("open");
        }
    });

    // Toggle the clicked one
    if (dropdown.classList.contains("open")) {
        dropdown.style.maxHeight = null;
        dropdown.style.opacity = "0";
        dropdown.style.marginBottom = "-5px";
        dropdown.classList.remove("open");
    } else {
        dropdown.style.maxHeight = dropdown.scrollHeight + "px";
        dropdown.style.opacity = "1";
        dropdown.style.marginBottom = "0px";
        dropdown.classList.add("open");
    }
}

//sidebar active
const menuItems = document.querySelectorAll('.sidebarMenuItem li');

menuItems.forEach(item => {
  item.addEventListener('click', () => {
    // remove active class from all
    menuItems.forEach(i => i.classList.remove('active'));
    // add active to clicked one
    item.classList.add('active');
  });
});

    
</script>