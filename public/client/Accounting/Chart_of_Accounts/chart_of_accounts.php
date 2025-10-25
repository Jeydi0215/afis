<?php
include ('../../../components/sidebar.php'); 

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chart of Accounts</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="chart.css">

</head>
<body>
  <div class="main-container">
  <h2>
      <span class="material-icons">account_tree</span>
      Chart of Accounts
      <button id="exportBtn" class="btn export-btn" style="margin-left: auto;">
        <span class="material-icons">download</span>
        Export
      </button>
  </h2>

    <div class="dashboard">
      <!-- Left side - Account List -->
      <div class="card">
  <div class="card-header">
    <h3 class="card-title">
      <span class="material-icons">list_alt</span>
      Account Directory
    </h3>
    <div class="header-actions">
      <div class="search-input">
        <span class="material-icons">search</span>
        <input type="text" class="form-control" id="accountSearch" placeholder="Search accounts...">
      </div>
    </div>
  </div>
        
        <!-- Bulk Actions -->
        <div class="bulk-actions hidden" id="bulkActions">
          <button class="btn btn-primary" id="bulkEditBtn">
            <span class="material-icons">edit</span>
            Edit Selected
          </button>
          <button class="btn btn-danger" id="bulkDeleteBtn">
            <span class="material-icons">delete</span>
            Delete Selected
          </button>
          <button class="btn" id="cancelBulkAction">
            Cancel
          </button>
        </div>

        <div class="table-responsive">
    <table class="account-table" id="defaultAccountsList">
      <thead>
        <tr>
          <th data-sort="" width="40"><input type="checkbox" id="selectAllCheckbox"></th>
          <th data-sort="number">Number <span class="sort-icon"></span></th>
          <th data-sort="name">Account Name <span class="sort-icon"></span></th>
          <!-- <th data-sort="group">Group <span class="sort-icon"></span></th> -->
          <th data-sort="type">Type <span class="sort-icon"></span></th>
          <th data-sort="status">Status <span class="sort-icon"></span></th>
          <th data-sort="">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- Accounts will be populated here -->
      </tbody>
    </table>
  </div>

        <div class="card-footer">
    
  </div>
</div>

      <!-- Right side - Form and Preview -->
      <div>
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <span class="material-icons" id="formIcon">add_circle</span>
              <span id="formTitle">Create New Account</span>
            </h3>
          </div>
          <div id="messageContainer"></div>

          <form id="accountForm">
            <input type="hidden" id="accountId">
            
            <div class="form-group">
              <label class="form-label">Account Category</label>
              <input type="text" class="form-control" id="accountCategory" readonly placeholder="Auto-classified">
            </div>

          <div class="flex-group">
            <div class="form-group">
              <label for="accountType" class="form-label">Account Group</label>
              <select id="accountType" class="form-control">
                <option value="" disabled selected>Select Group</option>
                <option value="Assets">Assets</option>
                <option value="Liabilities">Liabilities</option>
                <option value="Equity">Equity</option>
                <option value="Revenue">Revenue</option>
                <option value="Cost of Sales">Cost of Sales</option>
                <option value="Operating Expenses">Operating Expenses</option>
                <option value="Other Expenses">Other Expenses</option>
                <option value="Other Income">Other Income</option>
                <option value="Year-End Adjustments & Closing Entries">Year-End Adjustments</option>
              </select>
            </div>

            <div class="form-group" id="detailTypeGroup">
              <label for="detailType" class="form-label">Account Type</label>
              <select id="detailType" class="form-control">
                <option value="" disabled selected>Select Group first</option>
              </select>
              <div id="customDetailTypeFields" class="hidden flex-group" style="margin-top: 8px;">
                <input type="text" id="customAccountNumber" class="form-control account-number-input" placeholder="Number">
                <input type="text" id="customAccountName" class="form-control account-name-input" placeholder="Account Name">
              </div>
            </div>
          </div>

          <div class="flex-group">
            <div class="form-group">
              <label for="subAccount" class="form-label">Subaccount</label>
              <select id="subAccount" class="form-control">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>

            <div class="form-group">
              <label for="accountRole" class="form-label">Account Role</label>
              <select id="accountRole" class="form-control">
                <option value="0">Sub Account</option>
                <option value="1">Parent Account</option>
              </select>
            </div>
          </div>

          <div class="form-group" id="parentAccountField" style="display: none;">
            <label for="parentAccount" class="form-label">Parent Account</label>
            <select id="parentAccount" class="form-control">
              <option value="" disabled selected>Select Parent Account</option>
            </select>
            <div id="customParentAccountFields" class="hidden flex-group" style="margin-top: 8px;">
              <input type="text" id="customParentAccountNumber" class="form-control account-number-input" placeholder="Number">
              <input type="text" id="customParentAccountName" class="form-control account-name-input" placeholder="Account Name">
            </div>
          </div>

          <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" class="form-control" rows="2" placeholder="Provide details about this account"></textarea>
          </div>

          <div id="openingBalanceSection" class="hidden">
            <h4 class="section-title">
              <span class="material-icons" style="font-size: 18px;">account_balance_wallet</span>
              Opening Balance
            </h4>
            <div class="flex-group">
              <div class="form-group">
                <label for="openingBalance" class="form-label">Amount</label>
                <input type="number" id="openingBalance" class="form-control" placeholder="0.00">
              </div>
              <div class="form-group">
                <label for="asOfDate" class="form-label">As of Date</label>
                <input type="date" id="asOfDate" class="form-control">
              </div>
            </div>
          </div>

          <div class="action-buttons" id="formActions">
              <button type="button" id="saveAccountBtn" class="btn btn-primary">
                <span class="material-icons">save</span>
                Save Account
              </button>
              <button type="button" id="cancelEditBtn" class="btn hidden">
                Cancel
              </button>
              <button type="button" id="deleteAccountBtn" class="btn btn-danger hidden">
                <span class="material-icons">delete</span>
                Delete
              </button>
            </div>
          </form>
        </div>

        <!-- Preview Section -->
        <div class="card" style="margin-top: 20px;">
          <div class="card-header">
            <h3 class="card-title">
              <span class="material-icons">preview</span>
              Account Preview
            </h3>
          </div>
          <div class="account-preview">
            <div id="accountPreviewContent" class="empty-preview">
              <span class="material-icons">description</span>
              <p>Your account will appear here as you complete the form</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Enhanced main application state
    const appState = {
      detailTypes: {},
      accounts: [],
      currentAccount: null,
      editMode: false,
      selectedAccounts: new Set()
    };

    // Add these constants
    const HIGHLIGHTED_ACCOUNT_GROUPS = {
      '110000': 'Current Assets',
      '120000': 'Non-Current Assets',
      '210000': 'Current Liabilities',
      '220000': 'Non-Current Liabilities'
    };

    // Account number ranges by account type
    const ACCOUNT_NUMBER_RANGES = {
      'Assets': { min: 100000, max: 199999 },
      'Liabilities': { min: 200000, max: 299999 },
      'Equity': { min: 300000, max: 399999 },
      'Revenue': { min: 400000, max: 499999 },
      'Cost of Sales': { min: 500000, max: 599999 },
      'Operating Expenses': { min: 600000, max: 699999 },
      'Other Expenses': { min: 700000, max: 799999 },
      'Other Income': { min: 800000, max: 899999 },
      'Year-End Adjustments & Closing Entries': { min: 900000, max: 999999 }
    };

    // Group badges mapping
    const GROUP_BADGES = {
      'Assets': 'assets-badge',
      'Liabilities': 'liabilities-badge',
      'Equity': 'capital-badge',
      'Revenue': 'income-badge',
      'Cost of Sales': 'expenses-badge',
      'Operating Expenses': 'expenses-badge',
      'Other Expenses': 'expenses-badge',
      'Other Income': 'income-badge',
      'Year-End Adjustments & Closing Entries': 'info-badge'
    };

    // Fetch account data from server
    async function fetchAccountData() {
      try {
        const response = await fetch('fetch_accounts.php');
        const data = await response.json();
        
        if (data && Array.isArray(data)) {
          appState.accounts = data;
          processAccountData(data);
          renderAccountList();
        } else {
          console.error('Invalid data format received from server');
          showMessage('Error loading account data', 'error');
        }
      } catch (error) {
        console.error('Error fetching account data:', error);
        showMessage('Error loading account data', 'error');
      }
    }

    // Process account data into structured format
    function processAccountData(accounts) {
      appState.detailTypes = {};
      
      accounts.forEach(account => {
        const { account_group, account_type, account_number, is_parent, parent_account_number } = account;
        
        if (!account_group || !account_type || account_number === null) return;
        
        // Initialize group if not exists
        if (!appState.detailTypes[account_group]) {
          appState.detailTypes[account_group] = [];
        }
        
        // Create account entry
        let accountEntry = `${account_number} ${account_type}`;
        
        // Mark parent accounts
        if (is_parent) {
          accountEntry = `//PARENT// ${accountEntry}`;
        }
        
        // Include parent info for sub-accounts
        if (parent_account_number) {
          const parentAccount = accounts.find(a => a.account_number == parent_account_number);
          const parentName = parentAccount ? parentAccount.account_type : 'Parent';
          accountEntry += ` //PARENT:${parent_account_number} ${parentName}//`;
        }
        
        appState.detailTypes[account_group].push(accountEntry);
      });
    }

    // Render account list in the table
    function renderAccountList() {
      const tableBody = document.querySelector("#defaultAccountsList tbody");
      tableBody.innerHTML = "";
      let accountCount = 0;
      let activeAccountCount = 0;
      let currentGroup = null;

      // Sort accounts by account number
      const sortedAccounts = [...appState.accounts].sort((a, b) => a.account_number - b.account_number);

      sortedAccounts.forEach(account => {
        accountCount++;
        if (account.is_active !== 0) activeAccountCount++;
        
        const isParent = account.is_parent;
        const accountNumber = account.account_number;
        const accountName = account.account_type;
        const accountGroup = account.account_group;
        const isActive = account.is_active !== 0;
        
        // Check if we need to add a group header
        const groupKey = String(accountNumber).substring(0, 2) + '0000';
        if (HIGHLIGHTED_ACCOUNT_GROUPS[groupKey] && currentGroup !== groupKey) {
          currentGroup = groupKey;
          const groupHeader = document.createElement("tr");
          groupHeader.className = "account-group-header account-group-" + groupKey;
          groupHeader.innerHTML = `
            <td colspan="6">
              <span class="material-icons">folder</span>
              ${HIGHLIGHTED_ACCOUNT_GROUPS[groupKey]}
            </td>
          `;
          tableBody.appendChild(groupHeader);
        }
        
        const row = document.createElement("tr");
        row.dataset.id = account.id;
        if (!isActive) row.classList.add("inactive-account");
        
        row.innerHTML = `
          <td><input type="checkbox" class="account-checkbox" data-id="${account.id}"></td>
          <td><span class="account-number">${accountNumber}</span></td>
          <td>
            <div style="display: flex; align-items: center;">
              <span class="account-group-badge ${GROUP_BADGES[accountGroup] || ''}">${accountGroup.charAt(0)}</span>
              ${accountName}
            </div>
          </td>
          <td><span class="account-type ${isParent ? 'parent' : ''}">${isParent ? "Parent" : "Sub"}</span></td>
          <td>
            <div class="status-toggle">
              <span class="status-indicator status-${isActive ? 'active' : 'inactive'}"></span>
              ${isActive ? 'Active' : 'Inactive'}
            </div>
          </td>
          <td>
            <div class="action-buttons">
              <button class="action-btn edit-btn" title="Edit">
                <span class="material-icons">edit</span>
              </button>
              <button class="action-btn delete delete-btn" title="Delete">
                <span class="material-icons">delete</span>
              </button>
            </div>
          </td>
        `;

        // Add click handler to populate form
        row.addEventListener("click", (e) => {
          if (!e.target.closest('.action-btn') && !e.target.closest('.account-checkbox')) {
            populateFormFromAccount(accountNumber);
          }
        });
        
        // Add double-click handler for edit
        row.addEventListener("dblclick", () => {
          enterEditMode(accountNumber);
        });
        
        // Add edit button handler
        row.querySelector(".edit-btn").addEventListener("click", (e) => {
          e.stopPropagation();
          enterEditMode(accountNumber);
        });
        
        // Add delete button handler
        row.querySelector(".delete-btn").addEventListener("click", (e) => {
          e.stopPropagation();
          deleteAccount(account.id, accountName);
        });
        
        tableBody.appendChild(row);
      });

      // Update account counts
      document.getElementById("accountCount").textContent = accountCount;
      document.getElementById("activeAccountCount").textContent = activeAccountCount;
      
      // Initialize checkbox handlers
      initCheckboxes();
    }

    function enterEditMode(accountNumber) {
      const account = appState.accounts.find(a => a.account_number == accountNumber);
      if (!account) return;
      
      appState.editMode = true;
      appState.currentAccount = account;
      
      // Update form UI
      document.getElementById("formTitle").textContent = "Edit Account";
      document.getElementById("formIcon").textContent = "edit";
      document.getElementById("accountId").value = account.id;
      document.getElementById("cancelEditBtn").classList.remove("hidden");
      document.getElementById("deleteAccountBtn").classList.remove("hidden");
      
      // Populate form with account data
      populateFormFromAccount(accountNumber);
      
      // Scroll to form
      document.querySelector('.card-header').scrollIntoView({ behavior: 'smooth' });
    }

    function exitEditMode() {
      appState.editMode = false;
      appState.currentAccount = null;
      
      // Update form UI
      document.getElementById("formTitle").textContent = "Create New Account";
      document.getElementById("formIcon").textContent = "add_circle";
      document.getElementById("cancelEditBtn").classList.add("hidden");
      document.getElementById("deleteAccountBtn").classList.add("hidden");
      
      // Reset form
      resetForm();
    }

    async function deleteAccount(accountId, accountName) {
      if (!confirm(`Are you sure you want to delete "${accountName}"? This action cannot be undone.`)) {
        return;
      }
      
      try {
        const response = await fetch('delete_account.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ id: accountId })
        });
        
        const data = await response.json();
        if (data.success) {
          showMessage('Account deleted successfully!', 'success');
          await fetchAccountData();
        } else {
          showMessage('Error deleting account: ' + (data.message || 'Unknown error'), 'error');
        }
      } catch (error) {
        console.error('Error deleting account:', error);
        showMessage('Error deleting account: ' + error.message, 'error');
      }
    }

    function initCheckboxes() {
      // Select all checkbox
      document.getElementById("selectAllCheckbox").addEventListener("change", function() {
        const checkboxes = document.querySelectorAll(".account-checkbox");
        checkboxes.forEach(checkbox => {
          checkbox.checked = this.checked;
          toggleAccountSelection(checkbox.dataset.id, checkbox.checked);
        });
        toggleBulkActions();
      });
      
      // Individual account checkboxes
      document.querySelectorAll(".account-checkbox").forEach(checkbox => {
        checkbox.addEventListener("change", function() {
          toggleAccountSelection(this.dataset.id, this.checked);
          toggleBulkActions();
        });
      });
    }

    function toggleAccountSelection(accountId, isSelected) {
      if (isSelected) {
        appState.selectedAccounts.add(accountId);
      } else {
        appState.selectedAccounts.delete(accountId);
        document.getElementById("selectAllCheckbox").checked = false;
      }
    }

    function toggleBulkActions() {
      const bulkActions = document.getElementById("bulkActions");
      if (appState.selectedAccounts.size > 0) {
        bulkActions.classList.remove("hidden");
      } else {
        bulkActions.classList.add("hidden");
      }
    }

    // Populate form with account data
    function populateFormFromAccount(accountNumber) {
  const account = appState.accounts.find(a => a.account_number == accountNumber);
  if (!account) return;

  // Set basic account info
  document.getElementById("accountType").value = account.account_group;
  handleGroupChange();
  
  // Find the account in detailTypes
  const accountEntry = appState.detailTypes[account.account_group]?.find(a => 
    a.includes(accountNumber + ' ' + account.account_type)
  );
  
  if (accountEntry) {
    const detailTypeSelect = document.getElementById("detailType");
    for (let i = 0; i < detailTypeSelect.options.length; i++) {
      if (detailTypeSelect.options[i].value.includes(accountNumber + ' ' + account.account_type)) {
        detailTypeSelect.selectedIndex = i;
        break;
      }
    }
  }
  
  // Set parent/sub-account info
  document.getElementById("accountRole").value = account.is_parent ? "1" : "0";
  document.getElementById("subAccount").value = account.parent_account_number ? "1" : "0";
  
  if (account.parent_account_number) {
    toggleSubaccountField();
    const parentAccount = appState.accounts.find(a => a.account_number == account.parent_account_number);
    if (parentAccount) {
      const parentSelect = document.getElementById("parentAccount");
      for (let i = 0; i < parentSelect.options.length; i++) {
        if (parentSelect.options[i].value.includes(parentAccount.account_number + ' ' + parentAccount.account_type)) {
          parentSelect.selectedIndex = i;
          break;
        }
      }
    }
  }
  
  // Set description
  document.getElementById("description").value = account.description || '';
  
  updatePreview();
}

    // Handle account group selection change
    function handleGroupChange() {
      const groupSelect = document.getElementById("accountType");
      const detailTypeSelect = document.getElementById("detailType");
      const customDetailTypeFields = document.getElementById("customDetailTypeFields");

      // Reset dependent fields
      document.getElementById("accountCategory").value = "";
      document.getElementById("subAccount").value = "0";
      document.getElementById("accountRole").value = "0";
      document.getElementById("parentAccountField").style.display = "none";
      
      // Show the detail type dropdown
      detailTypeSelect.classList.remove("hidden");
      customDetailTypeFields.classList.add("hidden");
      
      // Add "Create New" option to detail type dropdown
      detailTypeSelect.innerHTML = '<option value="" disabled selected>Select Detail Type</option>';
      const createNewOption = new Option('-- Create New Account Type --', 'create-new');
      detailTypeSelect.add(createNewOption);
      
      // Populate with existing account types for this group
      if (appState.detailTypes[groupSelect.value]) {
        appState.detailTypes[groupSelect.value].forEach(type => {
          const isParent = type.startsWith("//PARENT//");
          const displayText = type.replace("//PARENT//", "")
                                 .split(' //PARENT:')[0]
                                 .trim();
          
          const option = new Option(displayText, type);
          if (isParent) {
            option.className = "parent-account";
          }
          detailTypeSelect.add(option);
        });
      }
      
      updatePreview();
    }

    // Toggle subaccount fields visibility
    function toggleSubaccountField() {
      const isSubAccount = document.getElementById("subAccount").value === "1";
      const parentAccountField = document.getElementById("parentAccountField");
      parentAccountField.style.display = isSubAccount ? "block" : "none";
      
      if (isSubAccount) {
        const accountType = document.getElementById("accountType").value;
        const parentSelect = document.getElementById("parentAccount");
        
        // Reset parent account dropdown
        parentSelect.innerHTML = '<option value="" disabled selected>Select Parent Account</option>';
        const createNewOption = new Option('-- Create New Parent Account --', 'create-new');
        parentSelect.add(createNewOption);
        
        // Add existing parent accounts for this group
        if (accountType && appState.detailTypes[accountType]) {
          appState.detailTypes[accountType].forEach(type => {
            if (type.startsWith("//PARENT//")) {
              const displayText = type.replace("//PARENT//", "")
                                    .split(' //PARENT:')[0]
                                    .trim();
              const option = new Option(displayText, type);
              option.className = "parent-account";
              parentSelect.add(option);
            }
          });
        }
      }
      
      updatePreview();
    }

    // Update the account preview
    function updatePreview() {
  const previewContent = document.getElementById("accountPreviewContent");
  const accountType = document.getElementById("accountType").value;
  const detailType = document.getElementById("detailType").value;
  const isSubAccount = document.getElementById("subAccount").value === "1";
  const parentAccount = document.getElementById("parentAccount").value;
  const isParentAccount = document.getElementById("accountRole").value === "1";
  
  if (!accountType) {
    previewContent.innerHTML = `
      <div class="empty-preview">
        <span class="material-icons">description</span>
        <p>Your account will appear here as you complete the form</p>
      </div>`;
    return;
  }
  
  let html = `<div class="account-group-title">
                <span class="material-icons">folder</span>
                ${accountType}
              </div>
              <ul class="account-hierarchy">`;
  
  if (detailType === 'create-new') {
    const customAccountNumber = document.getElementById("customAccountNumber").value || '[New Number]';
    const customAccountName = document.getElementById("customAccountName").value || '[New Account]';
    
    if (isSubAccount && parentAccount === 'create-new') {
      const customParentNumber = document.getElementById("customParentAccountNumber").value || '[New Parent Number]';
      const customParentName = document.getElementById("customParentAccountName").value || '[New Parent Account]';
      
      html += `
        <li>
          <div class="account-name">
            <span class="material-icons folder-icon">folder</span>
            <span class="account-number">${customParentNumber}</span>
            ${customParentName}
            <span class="account-type parent">(Parent Account)</span>
          </div>
          <ul class="account-hierarchy">
            <li>
              <div class="account-name">
                <span class="material-icons account-icon">description</span>
                <span class="account-number">${customAccountNumber}</span>
                ${customAccountName}
              </div>
            </li>
          </ul>
        </li>`;
    } else if (isSubAccount && parentAccount) {
      const parentNumber = parentAccount.split(' ')[0];
      const parentName = parentAccount.replace(parentNumber, '').trim();
      
      html += `
        <li>
          <div class="account-name">
            <span class="material-icons folder-icon">folder</span>
            <span class="account-number">${parentNumber}</span>
            ${parentName}
          </div>
          <ul class="account-hierarchy">
            <li>
              <div class="account-name">
                ${isParentAccount ? 
                  '<span class="material-icons folder-icon">folder</span>' : 
                  '<span class="material-icons account-icon">description</span>'}
                <span class="account-number">${detailType === 'create-new' ? customAccountNumber : detailType.split(' ')[0]}</span>
                ${detailType === 'create-new' ? customAccountName : detailType.replace(detailType.split(' ')[0], '').trim()}
                ${isParentAccount ? '<span class="account-type parent">(Parent Account)</span>' : ''}
              </div>
            </li>
          </ul>
        </li>`;
    } else {
      html += `
        <li>
          <div class="account-name">
            ${isParentAccount ? 
              '<span class="material-icons folder-icon">folder</span>' : 
              '<span class="material-icons account-icon">description</span>'}
            <span class="account-number">${detailType === 'create-new' ? customAccountNumber : detailType.split(' ')[0]}</span>
            ${detailType === 'create-new' ? customAccountName : detailType.replace(detailType.split(' ')[0], '').trim()}
            ${isParentAccount ? '<span class="account-type parent">(Parent Account)</span>' : ''}
          </div>
        </li>`;
    }
  } else if (detailType) {
    // Extract account number and name
    const accountNumber = detailType.split(' ')[0];
    const accountName = detailType.replace(accountNumber, '').trim().split(' //PARENT:')[0];
    const isParentAccountInList = detailType.includes("//PARENT//");
    const showAsParent = isParentAccountInList || isParentAccount;
    
    // Check if this account has a parent in the database
    const parentInfoMatch = detailType.match(/\/\/PARENT:(\d+) (.+?)\/\//);
    
    if ((isSubAccount && parentAccount) || parentInfoMatch) {
      // Show as subaccount with parent
      let parentNumber, parentName;
      
      if (parentInfoMatch) {
        parentNumber = parentInfoMatch[1];
        parentName = parentInfoMatch[2];
      } else {
        parentNumber = parentAccount.split(' ')[0];
        parentName = parentAccount.replace(parentNumber, '').trim();
      }
      
      html += `
        <li>
          <div class="account-name">
            <span class="material-icons folder-icon">folder</span>
            <span class="account-number">${parentNumber}</span>
            ${parentName}
          </div>
          <ul class="account-hierarchy">
            <li>
              <div class="account-name">
                ${showAsParent ? 
                  '<span class="material-icons folder-icon">folder</span>' : 
                  '<span class="material-icons account-icon">description</span>'}
                <span class="account-number">${accountNumber}</span>
                ${accountName}
                ${showAsParent ? '<span class="account-type parent">(Parent Account)</span>' : ''}
              </div>
            </li>
          </ul>
        </li>`;
    } else {
      // Show as top-level account
      html += `
        <li>
          <div class="account-name">
            ${showAsParent ? 
              '<span class="material-icons folder-icon">folder</span>' : 
              '<span class="material-icons account-icon">description</span>'}
            <span class="account-number">${accountNumber}</span>
            ${accountName}
            ${showAsParent ? '<span class="account-type parent">(Parent Account)</span>' : ''}
          </div>
        </li>`;
    }
  } else {
    // Show group only
    html += `
      <li>
        <div class="account-name">
          <span class="material-icons folder-icon">folder</span>
          ${accountType}
        </div>
      </li>`;
  }
  
  html += '</ul>';
  previewContent.innerHTML = html;
}

    // Validate account number
    function validateAccountNumber(accountNumber, accountType) {
      if (!accountNumber || isNaN(accountNumber)) {
        return { valid: false, message: 'Account number must be a number' };
      }
      
      const num = parseInt(accountNumber);
      const range = ACCOUNT_NUMBER_RANGES[accountType];
      
      if (!range) {
        return { valid: false, message: 'Invalid account type' };
      }
      
      if (num < range.min || num > range.max) {
        return { 
          valid: false, 
          message: `Account number must be between ${range.min} and ${range.max} for ${accountType}`
        };
      }
      
      // Check if number already exists
      if (appState.detailTypes[accountType]) {
        const exists = appState.detailTypes[accountType].some(account => {
          const cleanAccount = account.replace("//PARENT//", "").trim();
          return cleanAccount.startsWith(accountNumber + ' ');
        });
        
        if (exists) {
          return { valid: false, message: 'Account number already exists' };
        }
      }
      
      return { valid: true };
    }

    // Suggest an account number based on type
    function suggestAccountNumber(accountType) {
      if (!appState.detailTypes[accountType] || !ACCOUNT_NUMBER_RANGES[accountType]) {
        return '';
      }
      
      const range = ACCOUNT_NUMBER_RANGES[accountType];
      const existingNumbers = [];
      
      // Collect all existing account numbers for this type
      appState.detailTypes[accountType].forEach(account => {
        const cleanAccount = account.replace("//PARENT//", "").trim();
        const accountNumber = parseInt(cleanAccount.split(' ')[0]);
        if (!isNaN(accountNumber)) {
          existingNumbers.push(accountNumber);
        }
      });
      
      // Sort the numbers
      existingNumbers.sort((a, b) => a - b);
      
      // Find the first available gap
      for (let i = range.min; i <= range.max; i += 10) {
        if (!existingNumbers.includes(i)) {
          return i;
        }
      }
      
      // If no gap found, return the next number after the highest existing
      const highestNumber = existingNumbers.length > 0 ? 
        Math.max(...existingNumbers) : range.min;
      return Math.min(highestNumber + 10, range.max);
    }

    // Prepare account data for saving
    function prepareAccountData() {
      const accountType = document.getElementById("accountType").value;
      const detailType = document.getElementById("detailType").value;
      const isSubAccount = document.getElementById("subAccount").value === "1";
      const parentAccount = document.getElementById("parentAccount").value;
      const isParentAccount = document.getElementById("accountRole").value === "1";
      const description = document.getElementById("description").value;
      
      let accountNumber, accountName;
      let parentAccountNumber = null;
      
      if (detailType === 'create-new') {
        accountNumber = document.getElementById("customAccountNumber").value;
        accountName = document.getElementById("customAccountName").value;
        
        if (!accountNumber || !accountName) {
          showMessage('Please enter both account number and name', 'error');
          return null;
        }
        
        const validation = validateAccountNumber(accountNumber, accountType);
        if (!validation.valid) {
          showMessage(validation.message, 'error');
          return null;
        }
        
        if (isSubAccount && parentAccount && parentAccount !== 'create-new') {
          parentAccountNumber = parentAccount.split(' ')[0];
        }
      } else {
        if (!detailType) {
          showMessage('Please select an account type', 'error');
          return null;
        }
        
        accountNumber = detailType.split(' ')[0];
        accountName = detailType.replace(accountNumber, '').trim().split(' //PARENT:')[0];
        
        if (isSubAccount && parentAccount && parentAccount !== 'create-new') {
          parentAccountNumber = parentAccount.split(' ')[0];
        }
      }
      
      return {
        id: document.getElementById("accountId").value || null,
        account_group: accountType,
        account_type: accountName,
        account_number: accountNumber,
        is_parent: isParentAccount ? 1 : 0,
        parent_account_number: parentAccountNumber,
        description: description,
        is_active: 1 // Default to active when creating/updating
      };
    }

    // Save account to database
    async function saveAccountToDatabase(accountData) {
      const saveBtn = document.getElementById("saveAccountBtn");
      const originalBtnText = saveBtn.innerHTML;
      
      try {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner"></span> Saving...';
        
        const endpoint = appState.editMode ? 'update_account.php' : 'save_account.php';
        const method = appState.editMode ? 'PUT' : 'POST';
        
        const response = await fetch(endpoint, {
          method: method,
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(accountData)
        });
        
        const data = await response.json();
        if (data.success) {
          showMessage(`Account ${appState.editMode ? 'updated' : 'saved'} successfully!`, 'success');
          await fetchAccountData();
          
          if (!appState.editMode) {
            highlightNewAccount(accountData.account_number);
          }
          
          resetForm();
          exitEditMode();
        } else {
          showMessage(`Error ${appState.editMode ? 'updating' : 'saving'} account: ${data.message || 'Unknown error'}`, 'error');
        }
      } catch (error) {
        console.error(`Error ${appState.editMode ? 'updating' : 'saving'} account:`, error);
        showMessage(`Error ${appState.editMode ? 'updating' : 'saving'} account: ${error.message}`, 'error');
      } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnText;
      }
    }

    // Add export functionality
    document.getElementById("exportBtn").addEventListener("click", async function() {
      try {
        const response = await fetch('export_accounts.php');
        const blob = await response.blob();
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'chart_of_accounts_export.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showMessage('Export completed successfully!', 'success');
      } catch (error) {
        console.error('Error exporting accounts:', error);
        showMessage('Error exporting accounts: ' + error.message, 'error');
      }
    });

    // Reset form to initial state
    function resetForm() {
      document.getElementById("accountType").value = "";
      document.getElementById("detailType").innerHTML = '<option value="" disabled selected>Select Group first</option>';
      document.getElementById("customDetailTypeFields").classList.add("hidden");
      document.getElementById("subAccount").value = "0";
      document.getElementById("accountRole").value = "0";
      document.getElementById("parentAccountField").style.display = "none";
      document.getElementById("description").value = "";
      document.getElementById("customAccountNumber").value = "";
      document.getElementById("customAccountName").value = "";
      document.getElementById("customParentAccountNumber").value = "";
      document.getElementById("customParentAccountName").value = "";
      
      updatePreview();
    }

    // Highlight newly added account
    function highlightNewAccount(accountNumber) {
      const rows = document.querySelectorAll("#defaultAccountsList tbody tr");
      rows.forEach(row => {
        if (row.querySelector(".account-number").textContent === accountNumber) {
          row.classList.add("highlight");
          setTimeout(() => row.classList.remove("highlight"), 2000);
        }
      });
    }

    // Show message to user
    function showMessage(message, type) {
      const messageContainer = document.getElementById("messageContainer");
      const icon = type === 'success' ? 'check_circle' : 'error';
      messageContainer.innerHTML = `
        <div class="message ${type}-message">
          <span class="material-icons">${icon}</span>
          ${message}
        </div>`;
      
      // Clear message after 5 seconds
      setTimeout(() => {
        messageContainer.innerHTML = '';
      }, 5000);
    }

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      // Set up event listeners
      document.getElementById("accountType").addEventListener("change", handleGroupChange);
      document.getElementById("subAccount").addEventListener("change", toggleSubaccountField);
      document.getElementById("accountRole").addEventListener("change", updatePreview);
      
      // Handle detail type selection
      document.getElementById("detailType").addEventListener("change", function() {
        const customDetailTypeFields = document.getElementById("customDetailTypeFields");
        if (this.value === 'create-new') {
          customDetailTypeFields.classList.remove("hidden");
          // Suggest an account number
          const accountType = document.getElementById("accountType").value;
          const suggestedNumber = suggestAccountNumber(accountType);
          document.getElementById("customAccountNumber").value = suggestedNumber;
        } else {
          customDetailTypeFields.classList.add("hidden");
        }
        
        if (document.getElementById("subAccount").value === "1") {
          toggleSubaccountField();
        }
        updatePreview();
      });
      
      // Handle parent account selection
      document.getElementById("parentAccount").addEventListener("change", function() {
        const customParentAccountFields = document.getElementById("customParentAccountFields");
        if (this.value === 'create-new') {
          customParentAccountFields.classList.remove("hidden");
        } else {
          customParentAccountFields.classList.add("hidden");
        }
        updatePreview();
      });
      
      // Update preview when custom fields change
      document.getElementById("customAccountNumber").addEventListener("input", updatePreview);
      document.getElementById("customAccountName").addEventListener("input", updatePreview);
      document.getElementById("customParentAccountNumber").addEventListener("input", updatePreview);
      document.getElementById("customParentAccountName").addEventListener("input", updatePreview);
      
      // Save button handler
      document.getElementById("saveAccountBtn").addEventListener("click", async function() {
        const accountData = prepareAccountData();
        if (accountData) {
          await saveAccountToDatabase(accountData);
        }
      });
      
      // Search functionality
      document.getElementById("accountSearch").addEventListener("input", function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll("#defaultAccountsList tbody tr");
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchTerm) ? "" : "none";
        });
      });
      
      // Add cancel edit button handler
      document.getElementById("cancelEditBtn").addEventListener("click", exitEditMode);
      
      // Add delete account button handler
      document.getElementById("deleteAccountBtn").addEventListener("click", function() {
        if (appState.currentAccount) {
          deleteAccount(appState.currentAccount.id, appState.currentAccount.account_type);
        }
      });
      
      // Add bulk delete handler
      document.getElementById("bulkDeleteBtn").addEventListener("click", function() {
        if (appState.selectedAccounts.size === 0) return;
        
        if (!confirm(`Are you sure you want to delete ${appState.selectedAccounts.size} selected accounts? This action cannot be undone.`)) {
          return;
        }
        
        // Implement bulk delete logic
        bulkDeleteAccounts();
      });
      
      // Add bulk edit handler
      document.getElementById("bulkEditBtn").addEventListener("click", function() {
        if (appState.selectedAccounts.size === 0) return;
        
        // Implement bulk edit logic
        showBulkEditForm();
      });
      
      // Load initial data
      fetchAccountData();
    });

    async function bulkDeleteAccounts() {
      try {
        const response = await fetch('bulk_delete_accounts.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ ids: Array.from(appState.selectedAccounts) })
        });
        
        const data = await response.json();
        if (data.success) {
          showMessage(`Successfully deleted ${data.deletedCount} accounts`, 'success');
          appState.selectedAccounts.clear();
          await fetchAccountData();
        } else {
          showMessage('Error deleting accounts: ' + (data.message || 'Unknown error'), 'error');
        }
      } catch (error) {
        console.error('Error deleting accounts:', error);
        showMessage('Error deleting accounts: ' + error.message, 'error');
      }
    }

    function showBulkEditForm() {
      // Implement bulk edit form display
      // This would show a modal or expand a section with common fields to edit
      // for all selected accounts
      showMessage('Bulk edit functionality would be implemented here', 'info');
    }

  </script>
</body>
</html>