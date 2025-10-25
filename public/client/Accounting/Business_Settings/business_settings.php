<?php

include ('../../../components/sidebar.php'); 

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Business Settings</title>
  <script>
  sessionStorage.setItem("userEmail", <?= json_encode($_SESSION['user_email'] ?? '') ?>);
</script>
<link rel="stylesheet" href="./business.css">
<body>

<div class="forms-container">
  <div class="form-column">
    <!-- A. Basic Profile -->
    <form id="basicProfileForm">
      <h2 style="margin-top: 0;">A. Basic Profile</h2>

      <div class="form-group">
        <label for="businessLegalName">Business Legal Name</label>
        <input type="text" id="businessLegalName" maxlength="255" placeholder="Staffify BPO & Digital Agency Inc.">
        <small>255 chars max</small>
      </div>

      <div class="form-group">
        <label for="tradeName">Trade Name / Brand</label>
        <input type="text" id="tradeName" placeholder="Staffify">
        <small>Optional</small>
      </div>

      <div class="form-group">
        <label for="registrationType">Business Registration Type</label>
        <select id="registrationType">
          <option value="">---Select---</option>
          <option value="DTI - Sole Prop">DTI - Sole Prop</option>
          <option value="SEC - Partnership">SEC - Partnership</option>
          <option value="SEC - Corporation">SEC - Corporation</option>
          <option value="SEC - OPC">SEC - OPC</option>
          <option value="CDA - Co-op">CDA - Co-op</option>
        </select>
        <small>Drives other logic</small>
      </div>

      <div class="form-group">
        <label for="registrationNo">Registration No. (BN / SEC / CDA)</label>
        <input type="text" id="registrationNo" maxlength="20" placeholder="2025-12345678">
        <small>Alphanumeric, max 20</small>
      </div>

      <div class="form-group">
        <label for="dateOfRegistration">Date of Registration</label>
        <input type="date" id="dateOfRegistration">
        <small>yyyy-mm-dd</small>
      </div>

      <div class="form-group">
        <label for="industryCode">Industry / NAICS / PSIC Code</label>
        <input type="text" id="industryCode" placeholder="62090 – Other IT Service Activities">
        <small>For analytics</small>
      </div>

      <div class="form-group">
        <label for="businessDescription">Description / Nature of Business</label>
        <textarea id="businessDescription" rows="3" maxlength="500" placeholder="Digital transformation & BPO services"></textarea>
        <small>500 characters max</small>
      </div>
    </form>

    <!-- C. Address & IDs -->
    <form id="addressForm">
      <h2 style="margin-top: 0;">C. Address & IDs</h2>

      <div class="form-group">
        <label for="businessTin">Business TIN</label>
        <input type="text" id="businessTin" placeholder="123-456-789-000" maxlength="15" pattern="\d{3}-\d{3}-\d{3}-\d{3}">
        <small>12 digits, mask</small>
      </div>

      <div class="form-group">
        <label for="rdoCode">RDO Code</label>
        <input type="text" id="rdoCode" placeholder="055" maxlength="3" pattern="\d{3}">
        <small>3 digits</small>
      </div>

      <div class="form-group">
        <label for="officialAddress">Official Address</label>
        <textarea id="officialAddress" rows="3" maxlength="300" placeholder="Blk 1 Lot 10, SBMA, Olongapo City, 2200"></textarea>
        <small>Max 300 characters</small>
      </div>

      <div class="form-group">
        <label for="zipCode">ZIP Code</label>
        <input type="text" id="zipCode" placeholder="2200" maxlength="4" pattern="\d{4}">
        <small>4 digits</small>
      </div>

      <div class="form-group">
        <label for="contactPhone">Contact Phone No.</label>
        <input type="text" id="contactPhone" placeholder="+63 917 123 4567" pattern="^\+?\d{10,15}$">
        <small>International format</small>
      </div>

      <div class="form-group">
        <label for="officialEmail">Official Email</label>
        <input type="email" id="officialEmail" placeholder="finance@stafify.com">
        <small>Must be a valid email address</small>
      </div>
    </form>

    <!-- G. Regulatory & Compliance -->
    <form id="regulatoryForm">
      <h2 style="margin-top: 0;">G. Regulatory & Compliance</h2>

      <div class="form-group">
        <label for="sss">SSS No.</label>
        <input type="text" id="sss" placeholder="34-5678901-2">
        <small>Optional</small>
      </div>

      <div class="form-group">
        <label for="phic">PHIC No.</label>
        <input type="text" id="phic" placeholder="12-345678901-2">
        <small>Optional</small>
      </div>

      <div class="form-group">
        <label for="hdmf">HDMF (Pag-IBIG) No.</label>
        <input type="text" id="hdmf" placeholder="1234-5678-9012">
        <small>Optional</small>
      </div>

      <div class="form-group">
        <label for="peza">PEZA / BOI Cert No.</label>
        <input type="text" id="peza" placeholder="Enter if applicable">
        <small>If applicable</small>
      </div>

      <div class="form-group">
        <label for="permits">FDA / DOLE / TESDA Permits</label>
        <textarea id="permits" rows="3" placeholder="List numbers"></textarea>
      </div>
    </form>

    <!-- I. Miscellaneous -->
    <form id="miscSettingsForm">
      <h2 style="margin-top: 0;">I. Miscellaneous</h2>

      <div class="form-group">
        <label for="enableMultiBranch">Enable Multi-Branch</label>
        <label class="switch">
          <input type="checkbox" id="enableMultiBranch">
          <span class="slider round"></span>
        </label>
        <small>Unlocks branch dimension</small>
      </div>

      <div class="form-group">
        <label for="inventoryTracking">Inventory Tracking Mode</label>
        <select id="inventoryTracking">
          <option value="">---Select---</option>
          <option>Perpetual</option>
          <option>Periodic</option>
        </select>
        <small>Impacts COGS journals</small>
      </div>

      <div class="form-group">
        <label for="useWeightedCost">Use Weighted Avg Cost</label>
        <label class="switch">
          <input type="checkbox" id="useWeightedCost">
          <span class="slider round"></span>
        </label>
        <small>Inventory valuation</small>
      </div>

      <div class="form-group">
        <label for="enableAuditTrail">Enable Audit Trail</label>
        <label class="switch">
          <input type="checkbox" id="enableAuditTrail">
          <span class="slider round"></span>
        </label>
        <small>Logs script actions</small>
      </div>

      <div class="form-group">
        <label for="lastUpdated">Last Settings Update</label>
        <input type="text" id="lastUpdated" value="2025-05-01 23:15" readonly>
        <small>Set by script</small>
      </div>
    </form>
  </div>

  <div class="form-column">
    <!-- B. Tax Settings -->
    <form id="taxSettingsForm">
      <h2 style="margin-top: 0;">B. Tax Settings</h2>

      <div class="form-group">
        <label for="taxType">Tax Type</label>
       <select id="taxType">
  <option value="Non-VAT (3%)" <?php echo ($settings['tax_type'] ?? '') === 'Non-VAT (3%)' ? 'selected' : ''; ?>>Non-VAT (3%)</option>
  <option value="VAT (12%)" <?php echo ($settings['tax_type'] ?? 'VAT (12%)') === 'VAT (12%)' ? 'selected' : ''; ?>>VAT (12%)</option>
  <option value="Zero-Rated" <?php echo ($settings['tax_type'] ?? '') === 'Zero-Rated' ? 'selected' : ''; ?>>Zero-Rated</option>
  <option value="Tax-Exempt" <?php echo ($settings['tax_type'] ?? '') === 'Tax-Exempt' ? 'selected' : ''; ?>>Tax-Exempt</option>
</select>
        <small>Used by COA & journals</small>
      </div>

      <div class="form-group">
        <label for="books-select">Books of Accounts Type</label>
        <div class="custom-multiselect" id="books-select">
          <div class="selected-items" onclick="toggleDropdown()">Select...</div>
          <div class="dropdown-options" id="dropdown-options">
            <label><input type="checkbox" value="Non-computerized" onchange="updateSelected()"> Non-computerized</label>
            <label><input type="checkbox" value="Loose-leaf" onchange="updateSelected()"> Loose-leaf</label>
            <label><input type="checkbox" value="CAS" onchange="updateSelected()"> CAS</label>
          </div>
        </div>
        <small>Compliance reference</small>
      </div>

      <div class="form-group">
        <label for="accountingMethod">Accounting Method</label>
        <select id="accountingMethod">
          <option>---Select---</option>
          <option>Accrual</option>
          <option>Cash</option>
        </select>
        <small>Affects journal logic</small>
      </div>

      <div class="form-group">
        <label for="fiscalStart">Fiscal Year Start Month</label>
        <select id="fiscalStart">
          <option>---Select Month---</option>
          <option>January</option>
          <option>February</option>
          <option>March</option>
          <option>April</option>
          <option>May</option>
          <option>June</option>
          <option>July</option>
          <option>August</option>
          <option>September</option>
          <option>October</option>
          <option>November</option>
          <option>December</option>
        </select>
        <small>FY reports</small>
      </div>

      <div class="form-group">
        <label for="quarterCutoff">Quarter Period Cut-off</label>
        <select id="quarterCutoff">
          <option>---Select---</option>
          <option>Calendar Quarter</option>
          <option>13-Week</option>
          <option>Custom</option>
        </select>
        <small>VAT & ITR timing</small>
      </div>

      <div class="form-group">
        <label for="withholdingToggle" style="font-weight: bold; display: block; margin-bottom: 8px;">Withholding Agent?</label>
        <label class="switch">
          <input type="checkbox" id="withholdingToggle" name="withholding_agent">
          <span class="slider round"></span>
        </label>
        <small>Enables 0619E/F & 1601-EQ/FQ</small>
      </div>
    </form>

    <!-- D. Locale / Preferences -->
    <form id="localeForm">
      <h2 style="margin-top: 0;">D. Locale / Preferences</h2>

      <div class="form-group">
        <label for="currency">Base Currency</label>
        <select id="currency">
          <option>---Select---</option>
          <option>PHP</option>
          <option>USD</option>
          <option>EUR</option>
        </select>
        <small>Controls number-formatting</small>
      </div>

      <div class="form-group">
        <label for="timezone">Time Zone</label>
        <select id="timezone">
          <option>---Select---</option>
          <option>Asia/Manila (UTC+8)</option>
          <option>America/New_York (UTC-5)</option>
          <option>Europe/London (UTC+0)</option>
        </select>
        <small>For <code>Utilities.formatDate</code></small>
      </div>

      <div class="form-group">
        <label for="weekStart">Week Start Day</label>
        <select id="weekStart">
          <option>Sunday</option>
          <option>Monday</option>
        </select>
        <small>Sales-report logic</small>
      </div>

      <div class="form-group">
        <label for="dateFormat">Date Format</label>
        <select id="dateFormat">
          <option>YYYY-MM-DD</option>
          <option>MM-DD-YYYY</option>
          <option>DD-MM-YYYY</option>
        </select>
        <small>Optional</small>
      </div>

      <div class="form-group">
        <label for="numberFormat">Number Formatting</label>
        <select id="numberFormat">
          <option>1 234,56</option>
          <option>1,234.56</option>
        </select>
        <small>Overrides locale</small>
      </div>

      <div class="form-group">
        <label for="logoUpload">Logo Upload</label>
        <input type="file" id="logoUpload" accept="image/*">
        <small>Store Drive URL</small>
      </div>

      <div class="form-group">
        <label for="brandColor">Brand Color (hex)</label>
        <input type="color" id="brandColor" value="#2459E0">
        <small>For PDFs/slides</small>
      </div>
    </form>

    <!-- H. Document Defaults -->
    <form id="documentDefaultsForm">
      <h2 style="margin-top: 0;">H. Document Defaults</h2>

      <div class="form-group">
        <label for="orPrefix">Official Receipt Prefix</label>
        <input type="text" id="orPrefix" placeholder="OR-">
        <small>10 chars</small>
      </div>

      <div class="form-group">
        <label for="siPrefix">Sales Invoice Prefix</label>
        <input type="text" id="siPrefix" placeholder="SI-">
      </div>

      <div class="form-group">
        <label for="nextOr">Next OR Number</label>
        <input type="number" id="nextOr" placeholder="1001">
        <small>Auto-increment seed</small>
      </div>

      <div class="form-group">
        <label for="nextSi">Next SI Number</label>
        <input type="number" id="nextSi" placeholder="5001">
      </div>

      <div class="form-group">
        <label for="pdfTemplate">PDF Template</label>
        <select id="pdfTemplate">
          <option value="">---Select---</option>
          <option>Standard</option>
          <option>Minimal</option>
          <option>Custom</option>
        </select>
        <small>Selects HTML-to-PDF</small>
      </div>
    </form>
  </div>
</div>

<!-- Save Button -->
<button id="saveSettingsBtn" style="
  display: block;
  margin: 40px auto;
  margin-top:80px;
  padding: 12px 24px;
  font-size: 16px;
  background-color: #333;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
">
  Save All Settings
</button>
 <script>
        // Error display system
        const errorSystem = {
            element: null,
            init() {
                this.element = document.createElement('div');
                this.element.id = 'errorDisplay';
                this.element.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 15px;
                    background: #ffebee;
                    color: #c62828;
                    border: 1px solid #ef9a9a;
                    border-radius: 4px;
                    max-width: 400px;
                    display: none;
                    z-index: 1000;
                    white-space: pre-line;
                    font-family: monospace;
                    font-size: 12px;
                `;
                document.body.appendChild(this.element);
            },
            show(message, duration = 5000, type = 'error') {
                // Style based on message type
                if (type === 'success') {
                    this.element.style.background = '#e8f5e8';
                    this.element.style.color = '#2e7d32';
                    this.element.style.borderColor = '#81c784';
                } else if (type === 'info') {
                    this.element.style.background = '#e3f2fd';
                    this.element.style.color = '#1565c0';
                    this.element.style.borderColor = '#64b5f6';
                } else {
                    this.element.style.background = '#ffebee';
                    this.element.style.color = '#c62828';
                    this.element.style.borderColor = '#ef9a9a';
                }
                
                this.element.innerHTML = message;
                this.element.style.display = 'block';
                if (duration > 0) {
                    setTimeout(() => this.hide(), duration);
                }
            },
            hide() {
                this.element.style.display = 'none';
            }
        };

        // Books of Accounts dropdown
        const booksDropdown = {
            init() {
                this.container = document.getElementById('books-select');
                this.selectedDisplay = document.querySelector('.selected-items');
                this.optionsContainer = document.getElementById('dropdown-options');
                
                if (!this.container || !this.selectedDisplay || !this.optionsContainer) {
                    console.warn('Books dropdown elements not found');
                    return;
                }
                
                // Event listeners
                this.selectedDisplay.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggle();
                });
                
                document.addEventListener('click', () => this.hide());
                
                document.querySelectorAll('#dropdown-options input[type="checkbox"]')
                    .forEach(checkbox => {
                        checkbox.addEventListener('change', () => this.updateSelected());
                    });
            },
            toggle() {
                this.optionsContainer.style.display = 
                    this.optionsContainer.style.display === 'block' ? 'none' : 'block';
            },
            hide() {
                this.optionsContainer.style.display = 'none';
            },
            updateSelected() {
                const selected = Array.from(
                    document.querySelectorAll('#dropdown-options input[type="checkbox"]:checked')
                ).map(cb => cb.value);
                
                this.selectedDisplay.textContent = selected.length > 0 
                    ? selected.join(', ') 
                    : 'Select...';
            },
            getSelected() {
                return Array.from(
                    document.querySelectorAll('#dropdown-options input[type="checkbox"]:checked')
                ).map(cb => cb.value).join(', ');
            }
        };

        // Form handling
        const settingsForm = {
            async saveSettings() {
                try {
                    // Validate first
                    const errors = this.validateForm();
                    if (errors.length > 0) {
                        errorSystem.show(errors.join('\n'));
                        return;
                    }
                    
                    errorSystem.show('Saving settings...', 1000, 'info');
                    
                    // Gather all form data
                    const formData = this.gatherFormData();
                    
                    console.log('Form data being sent:', formData);
                    
                    // Send to server
                    const response = await fetch('save_settings.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(formData)
                    });

                    // Handle response
                    let result;
                    try {
                        const responseText = await response.text();
                        console.log('Raw server response:', responseText);
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error(`Invalid JSON response from server: ${parseError.message}`);
                    }
                    
                    console.log('Parsed server response:', result);
                    
                    // Handle different response scenarios
                    if (!response.ok) {
                        throw new Error(`Server error ${response.status}: ${result.message || 'Unknown error'}`);
                    }

                    if (!result.success) {
                        // Show detailed error info if available
                        let errorMsg = result.message || 'Server reported failure';
                        if (result.debug) {
                            errorMsg += '\n\nDebug Info:\n';
                            if (result.debug.error_line) {
                                errorMsg += `• Error on line ${result.debug.error_line}\n`;
                            }
                            if (result.debug.affected_rows !== undefined) {
                                errorMsg += `• Affected rows: ${result.debug.affected_rows}\n`;
                            }
                            if (result.debug.record_exists !== undefined) {
                                errorMsg += `• Record exists: ${result.debug.record_exists}\n`;
                            }
                        }
                        throw new Error(errorMsg);
                    }

                    // Success handling
                    let successMsg = result.message || 'Settings saved successfully!';
                    if (result.affected_rows === 0) {
                        successMsg += '\n(No changes detected - data may be identical)';
                    } else if (result.affected_rows) {
                        successMsg += `\n(${result.affected_rows} record(s) updated)`;
                    }
                    
                    errorSystem.show(successMsg, 4000, 'success');
                    
                    // Update timestamp
                    document.getElementById('lastUpdated').value = new Date().toLocaleString();
                    
                    // Show debug info in console for development
                    if (result.debug && Object.keys(result.debug).length > 0) {
                        console.log('Debug information:', result.debug);
                    }
                    
                } catch (error) {
                    console.error('Save error:', error);
                    errorSystem.show(`Save failed:\n${error.message}`);
                }
            },
            
            validateForm() {
                const errors = [];
                
                // Required fields validation
                const requiredFields = [
                    { id: 'businessLegalName', name: 'Business Legal Name' },
                    { id: 'registrationType', name: 'Business Registration Type' }
                ];
                
                requiredFields.forEach(field => {
                    const element = document.getElementById(field.id);
                    if (!element || !element.value.trim()) {
                        errors.push(`• ${field.name} is required`);
                    }
                });
                
                // Books of accounts validation
                if (booksDropdown.getSelected && booksDropdown.getSelected() === '') {
                    errors.push('• Please select at least one Book of Accounts type');
                }
                
                // Email validation if provided
                const email = document.getElementById('officialEmail');
                if (email && email.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email.value.trim())) {
                        errors.push('• Please enter a valid email address');
                    }
                }
                
                // Phone validation if provided
                const phone = document.getElementById('contactPhone');
                if (phone && phone.value.trim()) {
                    const phoneRegex = /^[\d\-\+\(\)\s]+$/;
                    if (!phoneRegex.test(phone.value.trim())) {
                        errors.push('• Please enter a valid phone number');
                    }
                }
                
                return errors;
            },
            
            gatherFormData() {
                // Helper functions with better error handling
                const getValue = (id) => {
                    const element = document.getElementById(id);
                    return element ? element.value.trim() : '';
                };
                
                const getChecked = (id) => {
                    const element = document.getElementById(id);
                    return element ? (element.checked ? 1 : 0) : 0;
                };
                
                const getNumber = (id) => {
                    const element = document.getElementById(id);
                    if (!element || !element.value) return 0;
                    const val = parseInt(element.value);
                    return isNaN(val) ? 0 : val;
                };

                const formData = {
                    // A. Basic Profile
                    business_legal_name: getValue('businessLegalName'),
                    trade_name: getValue('tradeName'),
                    registration_type: getValue('registrationType'),
                    registration_no: getValue('registrationNo'),
                    date_of_registration: getValue('dateOfRegistration'),
                    industry_code: getValue('industryCode'),
                    business_description: getValue('businessDescription'),

                    // B. Tax Settings
                    tax_type: getValue('taxType'),
                    books_of_accounts: booksDropdown.getSelected ? booksDropdown.getSelected() : '',
                    accounting_method: getValue('accountingMethod'),
                    fiscal_start_month: getValue('fiscalStart'),
                    quarter_cutoff: getValue('quarterCutoff'),
                    withholding_agent: getChecked('withholdingToggle'),

                    // C. Address & IDs
                    business_tin: getValue('businessTin'),
                    rdo_code: getValue('rdoCode'),
                    official_address: getValue('officialAddress'),
                    zip_code: getValue('zipCode'),
                    contact_phone: getValue('contactPhone'),
                    official_email: getValue('officialEmail'),

                    // D. Locale / Preferences
                    currency: getValue('currency'),
                    timezone: getValue('timezone'),
                    week_start: getValue('weekStart'),
                    date_format: getValue('dateFormat'),
                    number_format: getValue('numberFormat'),

                    // G. Regulatory & Compliance
                    sss_no: getValue('sss'),
                    phic_no: getValue('phic'),
                    hdmf_no: getValue('hdmf'),
                    peza_cert_no: getValue('peza'),
                    permits: getValue('permits'),

                    // H. Document Defaults
                    or_prefix: getValue('orPrefix'),
                    si_prefix: getValue('siPrefix'),
                    next_or_number: getNumber('nextOr'),
                    next_si_number: getNumber('nextSi'),
                    pdf_template: getValue('pdfTemplate'),

                    // I. Miscellaneous
                    enable_multi_branch: getChecked('enableMultiBranch'),
                    inventory_tracking_mode: getValue('inventoryTracking'),
                    use_weighted_avg_cost: getChecked('useWeightedCost'),
                    enable_audit_trail: getChecked('enableAuditTrail')
                };
                
                // Remove empty string values to let PHP use null defaults
                Object.keys(formData).forEach(key => {
                    if (formData[key] === '') {
                        formData[key] = null;
                    }
                });
                
                return formData;
            }
        };

        // Initialize everything when DOM loads
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Initializing settings form...');
            
            errorSystem.init();
            booksDropdown.init();
            
            // Set up save button
            const saveBtn = document.getElementById('saveSettingsBtn');
            if (saveBtn) {
                saveBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    settingsForm.saveSettings();
                });
                console.log('Save button initialized');
            } else {
                console.warn('Save button not found (ID: saveSettingsBtn)');
            }
            
            console.log('Settings form initialization complete');
        });
    </script>
</body>
</html>