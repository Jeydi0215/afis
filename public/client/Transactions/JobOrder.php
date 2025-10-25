<?php
include ('../../components/sidebar.php'); 
// include('logout.php');

// Database connection for toolkit functionality
$host = '127.0.0.1';
$dbname = 'afis';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get user ID from session (you'll need to adjust this based on your authentication)
$user_id = $_SESSION['user_id'] ?? 0;

// Handle form submission for adding toolkits
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_toolkit'])) {
    $sales_title = $_POST['sales_title'] ?? '';
    $form_url = $_POST['form_url'] ?? '';
    $response_url = $_POST['response_url'] ?? '';
    $icon = $_POST['icon'] ?? 'communication.gif';
    $type = $_POST['type'] ?? 'Form+Sheet';
    
    // Validate required fields based on type
    $valid = true;
    if (empty($sales_title)) $valid = false;
    if ($type === 'Video' && empty($form_url)) $valid = false;
    if ($type === 'Sheet' && empty($response_url)) $valid = false;
    if ($type === 'Form' && empty($form_url)) $valid = false;
    if ($type === 'Slides' && empty($form_url)) $valid = false;
    if ($type === 'Folder' && empty($form_url)) $valid = false;
    if ($type === 'Form+Sheet' && (empty($form_url) || empty($response_url))) $valid = false;
    
    if ($valid) {
        try {
            // For types that don't use a field, set it to empty string
            if ($type === 'Video' || $type === 'Slides' || $type === 'Folder') {
                $response_url = '';
            } elseif ($type === 'Sheet') {
                $form_url = '';
            } elseif ($type === 'Form') {
                $response_url = '';
            }

            if (isset($_POST['toolkit_id']) && !empty($_POST['toolkit_id'])) {
                // Update existing toolkit
                $stmt = $pdo->prepare("UPDATE sales_toolkit SET sales_title = :sales_title, form_url = :form_url, response_url = :response_url, icon = :icon, type = :type WHERE sales_id = :toolkit_id AND user_id = :user_id");
                $stmt->execute([
                    ':toolkit_id' => $_POST['toolkit_id'],
                    ':user_id' => $user_id,
                    ':sales_title' => $sales_title,
                    ':form_url' => $form_url,
                    ':response_url' => $response_url,
                    ':icon' => $icon,
                    ':type' => $type
                ]);
            } else {
                // Insert new toolkit
                $stmt = $pdo->prepare("INSERT INTO sales_toolkit (user_id, sales_title, form_url, response_url, icon, type, is_approved) VALUES (:user_id, :sales_title, :form_url, :response_url, :icon, :type, 0)");
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':sales_title' => $sales_title,
                    ':form_url' => $form_url,
                    ':response_url' => $response_url,
                    ':icon' => $icon,
                    ':type' => $type
                ]);
            }
            
            // Refresh the page to show the new toolkit
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields";
    }
}

// Get user's sales toolkit items
$stmt = $pdo->prepare("
    SELECT st.* 
    FROM sales_toolkit st 
    WHERE st.user_id = :user_id OR st.is_approved = 1 
    GROUP BY st.sales_id 
    ORDER BY st.created_at DESC
");
$stmt->execute([':user_id' => $user_id]);
$toolkits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available icons from the assets folder
$icons_path = 'assets/crm_sales/';
$icons = array();
if (is_dir($icons_path)) {
    $files = scandir($icons_path);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'gif') {
            $icons[] = $file;
        }
    }
}

// Function to get icon name without extension
function getIconName($filename) {
    return ucfirst(pathinfo($filename, PATHINFO_FILENAME));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Receipts</title>
    <link rel="stylesheet" href="JobOrder.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    #jobOrderForm button[type="submit"] {
        background-color: #4a6cf7;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        margin-top: 20px;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    #jobOrderForm button[type="submit"]:hover {
        background-color: #3a5ce4;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    #jobOrderForm button[type="submit"]:active {
        transform: translateY(0);
        box-shadow: 0 2px 3px rgba(0,0,0,0.1);
    }

    #jobOrderForm button[type="submit"]:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.3);
    }
    
    /* Sales Toolkit Styles */
    .toolkit-container {
        margin-top: 30px;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .toolkit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .toolkit-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1F2937;
    }
    
    .toolkit-cards {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    
    .toolkit-card {
        min-width: 80px;
        height: 80px;
        background: white;
        border-radius: 8px;
        border: 2px solid transparent;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px;
        transition: all 0.2s;
        position: relative;
    }
    
    .toolkit-card:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    
    .toolkit-card.active {
        border-color: #3B82F6;
        background-color: #F8FAFC;
    }
    
    .toolkit-icon {
        width: 32px;
        height: 32px;
        margin-bottom: 8px;
        object-fit: contain;
    }
    
    .toolkit-label {
        font-size: 0.7rem;
        text-align: center;
        color: #4B5563;
        max-width: 70px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .toolkit-content {
        background: white;
        border-radius: 8px;
        padding: 15px;
        min-height: 400px;
        border: 1px solid #e9ecef;
        margin-bottom: 15px;
        position: relative;
    }
    
    .toolkit-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 400px;
        color: #6c757d;
    }
    
    .toolkit-placeholder i {
        font-size: 3rem;
        margin-bottom: 15px;
    }
    
    .toolkit-iframe {
        width: 100%;
        height: 400px;
        border: none;
        border-radius: 6px;
    }
    
    .toolkit-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .toolkit-btn {
        padding: 8px 16px;
        background-color: #10B981;
        color: white;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .toolkit-btn:hover {
        background-color: #059669;
        color: white;
    }
    
    .toolkit-btn.clean {
        background-color: white;
        color: #2563eb;
        border: 1px solid #d1d5db;
    }
    
    .toolkit-btn.clean:hover {
        background-color: #2563eb;
        color: white;
        border-color: #2563eb;
    }
    
    /* Modal styles for add toolkit */
    .add-toolkit-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 8px;
        padding: 20px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .modal-close {
        cursor: pointer;
        font-size: 1.5rem;
        color: #6b7280;
    }
    
    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .icon-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    
    .icon-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    
    .icon-option:hover {
        background-color: #f3f4f6;
    }
    
    .icon-option.selected {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }
    
    .icon-option img {
        width: 24px;
        height: 24px;
        margin-bottom: 5px;
        object-fit: contain;
    }
    
    .icon-option span {
        font-size: 0.7rem;
        text-align: center;
        color: #4b5563;
    }
    
    .pending-badge {
        position: absolute;
        top: 5px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #FEF3C7;
        color: #92400E;
        padding: 2px 6px;
        border-radius: 9999px;
        font-size: 0.6rem;
        font-weight: 500;
        white-space: nowrap;
        z-index: 2;
        max-width: 90%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .edit-button {
        position: absolute;
        top: 5px;
        right: 5px;
        opacity: 0;
        transition: opacity 0.2s;
        padding: 2px;
        border-radius: 4px;
        background-color: #F3F4F6;
        color: #6B7280;
        border: 1px solid #E5E7EB;
        font-size: 0.7rem;
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    
    .toolkit-card:hover .edit-button {
        opacity: 1;
    }
    
    .edit-button:hover {
        background-color: #E5E7EB;
        color: #374151;
    }
    
    .no-toolkits {
        text-align: center;
        padding: 2rem;
        background-color: #F9FAFB;
        border-radius: 0.5rem;
        margin: 1rem 0;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    </style>
</head>
<body>
    <div class="No_inventory" id="inventoryModal">
        <div id="inventoryBox">
            <div class="Inventory_title">
                <span>Request Form</span>
                <svg class="Close" id="closeInventory" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6l-12 12" />
                    <path d="M6 6l12 12" />
                </svg>
            </div>

            <form id="jobOrderForm">
                <div class="Detail">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="Date" required>
                </div>

                <div class="Detail">
                    <label for="company">Company:</label>
                    <input type="text" id="company" name="Company" placeholder="Company" required>
                </div>

                <div class="Detail">
                    <label for="clientName">Client Name:</label>
                    <input type="text" id="clientName" name="Client Name" placeholder="Client Name" required>
                </div>

                <div class="Detail">
                    <label for="orderNo">Card/Order No:</label>
                    <input type="text" id="orderNo" name="Card/Order No." placeholder="Card/Order No." required>
                </div>

                <div class="Detail">
                    <label for="promotion">Promotion:</label>
                    <input type="text" id="promotion" name="Promotion" placeholder="Promotion">
                </div>

                <div class="Detail">
                    <label for="engagementType">Staff Engagement Type:</label>
                    <input type="text" id="engagementType" name="Staff Engagement Type" placeholder="Staff Engagement Type">
                </div>

                <div class="Detail">
                    <label for="mainService">Main Service:</label>
                    <input type="text" id="mainService" name="Main Service" placeholder="Main Service">
                </div>

                <div class="Detail">
                    <label for="roleCategories">Role Categories:</label>
                    <input type="text" id="roleCategories" name="Role Categories" placeholder="Role Categories">
                </div>

                <div class="Detail">
                    <label for="staffOnDemand">Staff on-Demand:</label>
                    <input type="text" id="staffOnDemand" name="Staff on-Demand" placeholder="Staff on-Demand">
                </div>

                <div class="Detail">
                    <label for="addOnServices">Add on-Services:</label>
                    <input type="text" id="addOnServices" name="Add on-Services" placeholder="Add on-Services">
                </div>

                <div class="Detail">
                    <label for="hourlyRate">Hourly Rate:</label>
                    <input type="number" id="hourlyRate" name="Hourly Rate" placeholder="Hourly Rate">
                </div>

                <div class="Detail">
                    <label for="billableHours">Billable Hours:</label>
                    <input type="number" id="billableHours" name="Billable Hours" placeholder="Billable Hours">
                </div>

                <div class="Detail">
                    <label for="cutOffStart">Cut-Off Start Date:</label>
                    <input type="date" id="cutOffStart" name="Cut-Off Start Date">
                </div>

                <div class="Detail">
                    <label for="cutOffEnd">Cut-Off End Date:</label>
                    <input type="date" id="cutOffEnd" name="Cut-Off End Date">
                </div>

                <div class="Detail">
                    <label for="particulars">Particulars:</label>
                    <input type="text" id="particulars" name="Particulars" placeholder="Particulars">
                </div>

                <div class="Detail">
                    <label for="grossSales">Gross Sales:</label>
                    <input type="number" id="grossSales" name="Gross Sales" placeholder="Gross Sales">
                </div>

                <div class="Detail">
                    <label for="discount">Discount:</label>
                    <input type="number" id="discount" name="Discount" placeholder="Discount">
                </div>

                <div class="Detail">
                    <label for="commissionRating">Employee/ISP Commission Rating:</label>
                    <input type="text" id="commissionRating" name="Employee/ISP Commission Rating" placeholder="Commission Rating">
                </div>

                <div class="Detail">
                    <label for="payoutAmount">Employee/ISP Payout Amount:</label>
                    <input type="number" id="payoutAmount" name="Employee/ISP Payout Amount" placeholder="Payout Amount">
                </div>

                <div class="Detail">
                    <label for="payoutStatus">Employee/ISP Payout Status:</label>
                    <input type="text" id="payoutStatus" name="Employee/ISP Payout Status" placeholder="Payout Status">
                </div>

                <div class="Detail">
                    <label for="employeeIsp">Employee/ISP:</label>     
                    <input type="text" id="employeeIsp" name="Employee/ISP" placeholder="Employee/ISP">
                </div>

                <div class="Detail">
                    <label for="onCallIsp">On-Call ISP:</label>
                    <input type="text" id="onCallIsp" name="On-Call ISP" placeholder="On-Call ISP">
                </div>

                <div class="Detail">
                    <label for="referredBy">AOM/BDS/Referred by:</label>
                    <input type="text" id="referredBy" name="AOM/BDS/Reffered by:" placeholder="AOM/BDS/Reffered by:">
                </div>

                <div class="Detail">
                    <label for="referredBySC">AOM/BDS/Referred by: S.C:</label>
                    <input type="text" id="referredBySC" name="AOM/BDS/Referred by: S.C" placeholder="AOM/BDS/Referred by: S.C">
                </div>

                <div class="Detail">
                    <label for="status">Status:</label>
                    <input type="text" id="status" name="Status" placeholder="Status">
                </div>

                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
    
    <!-- Add Toolkit Modal -->
    <div class="add-toolkit-modal" id="addToolkitModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add New Toolkit</h3>
                <span class="modal-close" onclick="closeAddToolkitModal()">&times;</span>
            </div>
            <form id="addToolkitForm" method="POST" action="">
                <input type="hidden" name="add_toolkit" value="1">
                <input type="hidden" name="toolkit_id" id="toolkitId" value="">
                <input type="hidden" name="icon" id="selectedIcon" value="communication.gif">
                
                <div class="Detail">
                    <label for="salesTitle">Title:</label>
                    <input type="text" id="salesTitle" name="sales_title" placeholder="Toolkit Title" required maxlength="50">
                </div>
                
                <div class="Detail">
                    <label>Icon:</label>
                    <div class="icon-grid">
                        <?php foreach ($icons as $icon): ?>
                            <div class="icon-option" onclick="selectIcon(this)" data-value="<?php echo htmlspecialchars($icon); ?>">
                                <img src="<?php echo $icons_path . htmlspecialchars($icon); ?>" alt="<?php echo getIconName($icon); ?>">
                                <span><?php echo getIconName($icon); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="Detail">
                    <label for="toolkitType">Type:</label>
                    <select id="toolkitType" name="type" required onchange="updateToolkitTypeFields()">
                        <option value="Form+Sheet">Form + Sheet</option>
                        <option value="Form">Form</option>
                        <option value="Sheet">Sheet</option>
                        <option value="Video">Video</option>
                        <option value="Slides">Slides</option>
                        <option value="Folder">Folder</option>
                    </select>
                </div>
                
                <div id="toolkitTypeFields">
                    <div class="Detail" id="formUrlGroup">
                        <label for="formUrl" id="formUrlLabel">Form URL:</label>
                        <input type="url" id="formUrl" name="form_url" placeholder="Form URL">
                    </div>
                    
                    <div class="Detail" id="responseUrlGroup">
                        <label for="responseUrl" id="responseUrlLabel">Response URL:</label>
                        <input type="url" id="responseUrl" name="response_url" placeholder="Response URL">
                    </div>
                </div>
                
                <button type="submit" class="toolkit-btn">Save Toolkit</button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <div class="flex flex-col gap-5 greetings">
          
        </div>
        <!-- <section>
            <div class="table-container">
                <div class="Title">
                    <span class="Open_inventory" id="openForm">Submit Request</span>
                    <select class="select" id="tableSelector">
                        <option value="17F3pkhjR0-cW5ttk22NMa8ClM0Rtuh4UDUkYKZD6W0o">OEDCe</option>
                        <option value="YOUR_PLDT_SHEET_ID">PLDT</option>
                    </select>
                </div>
                <div>
                    <iframe id="sheetFrame" src="https://docs.google.com/spreadsheets/d/17F3pkhjR0-cW5ttk22NMa8ClM0Rtuh4UDUkYKZD6W0o/edit?gid=0#gid=0" style="height:700px; width:100%;"></iframe>
                </div>
            </div>
        </section>
         -->
        <!-- Sales Toolkit Section -->
        <section class="toolkit-container">
            <div class="toolkit-header">
                <h2 class="toolkit-title">Cash Receipts</h2>
                <button class="toolkit-btn" onclick="openAddToolkitModal()">
                    <i class="fas fa-plus"></i> Add Toolkit
                </button>
            </div>
            
            <div class="toolkit-cards" id="toolkitCards">
                <?php if (empty($toolkits)): ?>
                    <div class="no-toolkits">
                        <i class="fas fa-folder-open text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600">No toolkits available. Create your first toolkit!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($toolkits as $toolkit): ?>
                        <div class="toolkit-card-wrapper">
                            <div class="toolkit-card">
                                <?php if ($toolkit['user_id'] == $user_id): ?>
                                    <button class="edit-button" onclick="event.stopPropagation(); openAddToolkitModal(<?php echo htmlspecialchars(json_encode($toolkit), ENT_QUOTES, 'UTF-8'); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!$toolkit['is_approved']): ?>
                                    <span class="pending-badge">Pending</span>
                                <?php endif; ?>
                                
                                <div class="toolkit-content" onclick="loadToolkitContent(<?php echo htmlspecialchars(json_encode($toolkit), ENT_QUOTES, 'UTF-8'); ?>, this.closest('.toolkit-card'))">
                                    <img src="assets/crm_sales/<?php echo basename(htmlspecialchars($toolkit['icon'])); ?>" alt="<?php echo htmlspecialchars($toolkit['sales_title']); ?>" class="toolkit-icon">
                                </div>
                            </div>
                            <span class="toolkit-label"><?php echo htmlspecialchars($toolkit['sales_title']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="toolkit-actions" id="toolkitActions" style="display: none;">
                <a href="#" target="_blank" id="openFormBtn" class="toolkit-btn clean">
                    <i class="fas fa-external-link-alt"></i> Open Form
                </a>
                <a href="#" target="_blank" id="openResponseBtn" class="toolkit-btn clean">
                    <i class="fas fa-external-link-alt"></i> Open Response
                </a>
            </div>
            
            <div class="toolkit-content" id="toolkitContent">
                <div class="toolkit-placeholder">
                    <i class="fas fa-file-alt"></i>
                    <p>Select a toolkit to view its content</p>
                </div>
            </div>
        </section>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form submission
        const jobOrderForm = document.getElementById('jobOrderForm');
        jobOrderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(jobOrderForm);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            fetch('submit_job.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
                .then(response => response.json())
                .then(result => {
                    alert('Request submitted successfully!');
                    jobOrderForm.reset();
                    document.getElementById('inventoryModal').style.display = 'none';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Submission failed!');
                });
        });

        // Dropdown to switch iframes
        const tableSelector = document.getElementById('tableSelector');
        const sheetFrame = document.getElementById('sheetFrame');
        
        tableSelector.addEventListener('change', function() {
            const sheetId = this.value;
            sheetFrame.src = `https://docs.google.com/spreadsheets/d/${sheetId}/edit?gid=0#gid=0`;
        });

        // Modal open/close functionality
        const openFormBtn = document.getElementById('openForm');
        const closeInventoryBtn = document.getElementById('closeInventory');
        const inventoryModal = document.getElementById('inventoryModal');

        openFormBtn.addEventListener('click', function() {
            inventoryModal.style.display = 'flex';
        });

        closeInventoryBtn.addEventListener('click', function() {
            inventoryModal.style.display = 'none';
        });

        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').value = today;
        
        // Initialize toolkit type fields
        updateToolkitTypeFields();
    });
    
    // Sales Toolkit Functions
    let activeToolkit = null;
    let activeToolkitCard = null;
    
    function loadToolkitContent(toolkit, cardElement) {
        if (!toolkit.is_approved) {
            alert('This toolkit is currently pending administrator approval.');
            return;
        }
        
        // Remove active class from previous card
        if (activeToolkitCard) {
            activeToolkitCard.classList.remove('active');
        }
        
        // Add active class to current card
        cardElement.classList.add('active');
        activeToolkitCard = cardElement;
        activeToolkit = toolkit;
        
        const toolkitContent = document.getElementById('toolkitContent');
        const toolkitActions = document.getElementById('toolkitActions');
        
        // Show iframe and hide placeholder
        const embedUrl = getEmbeddableUrl(
            toolkit.response_url || toolkit.form_url, 
            toolkit.type
        );
        
        if (embedUrl) {
            toolkitContent.innerHTML = `
                <iframe class="toolkit-iframe" src="${embedUrl}"></iframe>
            `;
        } else {
            toolkitContent.innerHTML = `
                <div class="toolkit-placeholder">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>No content available for this toolkit</p>
                </div>
            `;
        }
        
        // Show actions if applicable
        if (toolkit.form_url || toolkit.response_url) {
            toolkitActions.style.display = 'flex';
            
            if (toolkit.form_url) {
                document.getElementById('openFormBtn').href = toolkit.form_url;
                document.getElementById('openFormBtn').style.display = 'flex';
            } else {
                document.getElementById('openFormBtn').style.display = 'none';
            }
            
            if (toolkit.response_url) {
                document.getElementById('openResponseBtn').href = toolkit.response_url;
                document.getElementById('openResponseBtn').style.display = 'flex';
            } else {
                document.getElementById('openResponseBtn').style.display = 'none';
            }
            
            // Update button labels based on type
            if (toolkit.type === 'Video') {
                document.getElementById('openFormBtn').innerHTML = '<i class="fas fa-external-link-alt"></i> Watch Video';
                if (toolkit.response_url) {
                    document.getElementById('openResponseBtn').innerHTML = '<i class="fas fa-external-link-alt"></i> Watch Video';
                }
            } else if (toolkit.type === 'Slides') {
                document.getElementById('openFormBtn').innerHTML = '<i class="fas fa-external-link-alt"></i> View Slides';
                if (toolkit.response_url) {
                    document.getElementById('openResponseBtn').innerHTML = '<i class="fas fa-external-link-alt"></i> View Slides';
                }
            } else if (toolkit.type === 'Folder') {
                document.getElementById('openFormBtn').style.display = 'none';
                document.getElementById('openResponseBtn').style.display = 'none';
            } else if (toolkit.type === 'Sheet') {
                document.getElementById('openFormBtn').innerHTML = '<i class="fas fa-external-link-alt"></i> Open Sheet';
                if (toolkit.response_url) {
                    document.getElementById('openResponseBtn').innerHTML = '<i class="fas fa-external-link-alt"></i> Open Sheet';
                }
            } else {
                document.getElementById('openFormBtn').innerHTML = '<i class="fas fa-external-link-alt"></i> Open Form';
                if (toolkit.response_url) {
                    document.getElementById('openResponseBtn').innerHTML = '<i class="fas fa-external-link-alt"></i> Open Response';
                }
            }
        } else {
            toolkitActions.style.display = 'none';
        }
    }
    
    function getEmbeddableUrl(url, type) {
        if (!url) return '';
        
        // YouTube
        if (url.includes('youtube.com/watch')) {
            return url.replace('watch?v=', 'embed/');
        }
        if (url.includes('youtu.be/')) {
            const id = url.split('youtu.be/')[1].split(/[?&]/)[0];
            return 'https://www.youtube.com/embed/' + id;
        }
        
        // Google Drive
        if (url.includes('drive.google.com')) {
            if (url.includes('/folders/')) {
                const folderId = url.match(/\/folders\/([^/?]+)/)?.[1];
                if (folderId) {
                    return `https://drive.google.com/embeddedfolderview?id=${folderId}#grid`;
                }
            }
            if (url.includes('/file/d/')) {
                return url.replace(/\/view.*$/, '/preview');
            }
        }
        
        // Google Sheets
        if (url.includes('docs.google.com/spreadsheets')) {
            if (url.includes('/edit')) {
                return url.replace('/edit', '/preview');
            }
        }
        
        // Google Forms
        if (url.includes('docs.google.com/forms')) {
            if (!url.includes('embedded=true')) {
                if (url.includes('?')) {
                    return url + '&embedded=true';
                } else {
                    return url + '?embedded=true';
                }
            }
        }
        
        return url;
    }
    
    function openAddToolkitModal(toolkit = null) {
        const modal = document.getElementById('addToolkitModal');
        const form = document.getElementById('addToolkitForm');
        const title = document.getElementById('modalTitle');
        const toolkitId = document.getElementById('toolkitId');
        const selectedIcon = document.getElementById('selectedIcon');
        
        if (toolkit) {
            title.textContent = 'Edit Toolkit';
            toolkitId.value = toolkit.sales_id;
            document.getElementById('salesTitle').value = toolkit.sales_title;
            document.getElementById('toolkitType').value = toolkit.type;
            document.getElementById('formUrl').value = toolkit.form_url;
            document.getElementById('responseUrl').value = toolkit.response_url;
            
            // Select the correct icon
            selectedIcon.value = toolkit.icon;
            const iconOptions = document.querySelectorAll('.icon-option');
            iconOptions.forEach(option => {
                option.classList.remove('selected');
                if (option.dataset.value === toolkit.icon) {
                    option.classList.add('selected');
                }
            });
        } else {
            title.textContent = 'Add New Toolkit';
            form.reset();
            toolkitId.value = '';
            document.getElementById('toolkitType').value = 'Form+Sheet';
            
            // Select the default icon
            selectedIcon.value = 'communication.gif';
            const iconOptions = document.querySelectorAll('.icon-option');
            iconOptions.forEach(option => {
                option.classList.remove('selected');
                if (option.dataset.value === 'communication.gif') {
                    option.classList.add('selected');
                }
            });
        }
        
        updateToolkitTypeFields();
        modal.style.display = 'flex';
    }
    
    function closeAddToolkitModal() {
        document.getElementById('addToolkitModal').style.display = 'none';
    }
    
    function selectIcon(element) {
        const icons = document.querySelectorAll('.icon-option');
        icons.forEach(icon => icon.classList.remove('selected'));
        element.classList.add('selected');
        document.getElementById('selectedIcon').value = element.dataset.value;
    }
    
    function updateToolkitTypeFields() {
        const type = document.getElementById('toolkitType').value;
        const formUrlGroup = document.getElementById('formUrlGroup');
        const responseUrlGroup = document.getElementById('responseUrlGroup');
        const formUrlLabel = document.getElementById('formUrlLabel');
        const responseUrlLabel = document.getElementById('responseUrlLabel');
        const formUrlInput = document.getElementById('formUrl');
        const responseUrlInput = document.getElementById('responseUrl');
        
        if (type === 'Video') {
            formUrlGroup.style.display = 'block';
            responseUrlGroup.style.display = 'none';
            formUrlLabel.textContent = 'Video URL';
            formUrlInput.required = true;
            responseUrlInput.required = false;
        } else if (type === 'Slides') {
            formUrlGroup.style.display = 'block';
            responseUrlGroup.style.display = 'none';
            formUrlLabel.textContent = 'Slides URL';
            formUrlInput.required = true;
            responseUrlInput.required = false;
        } else if (type === 'Folder') {
            formUrlGroup.style.display = 'block';
            responseUrlGroup.style.display = 'none';
            formUrlLabel.textContent = 'Folder URL';
            formUrlInput.required = true;
            responseUrlInput.required = false;
        } else if (type === 'Sheet') {
            formUrlGroup.style.display = 'none';
            responseUrlGroup.style.display = 'block';
            responseUrlLabel.textContent = 'Sheet URL';
            formUrlInput.required = false;
            responseUrlInput.required = true;
        } else if (type === 'Form') {
            formUrlGroup.style.display = 'block';
            responseUrlGroup.style.display = 'none';
            formUrlLabel.textContent = 'Form URL';
            formUrlInput.required = true;
            responseUrlInput.required = false;
        } else {
            // Form + Sheet
            formUrlGroup.style.display = 'block';
            responseUrlGroup.style.display = 'block';
            formUrlLabel.textContent = 'Form URL';
            responseUrlLabel.textContent = 'Sheet URL';
            formUrlInput.required = true;
            responseUrlInput.required = true;
        }
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('addToolkitModal');
        if (event.target === modal) {
            closeAddToolkitModal();
        }
    });
    </script>
</body>
</html>