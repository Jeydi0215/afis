// Initialize modal and load invoices when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Create modal if it doesn't exist
    if (!document.getElementById('invoice-modal')) {
        const modal = document.createElement('div');
        modal.id = 'invoice-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div id="modal-content" class="modal-content">
                <div class="modal-loading">Loading invoice details...</div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Load invoices
    loadInvoices();

    // Close modal when clicking outside of it
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('invoice-modal');
        if (event.target === modal) {
            closeModal();
        }
    });
});

function loadInvoices() {
    showLoading(true);
    
    fetch('fetch_invoices.php')
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { 
                    throw new Error(err.details || err.error || 'Failed to load invoices'); 
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.details || data.error);
            }
            displayInvoices(data.data);
        })
        .catch(error => {
            console.error('Error:', error);
            
            // User-friendly error messages
            let message = error.message;
            if (error.message.includes('notFound')) {
                message = "The spreadsheet or sheet was not found. Please check:\n" +
                         "1. Correct Spreadsheet ID\n" +
                         "2. Correct Sheet Name\n" +
                         "3. Sharing permissions";
            } else if (error.message.includes('Sheet')) {
                message = "Sheet not found. Please verify the sheet name exists in the spreadsheet.";
            }
            
            alert("Error loading invoices:\n\n" + message);
        })
        .finally(() => showLoading(false));
}

function displayInvoices(invoices) {
    const container = document.getElementById('invoices-container');
    container.innerHTML = '';
    
    if (!invoices || invoices.length === 0) {
        container.innerHTML = '<p class="no-invoices">No invoices found</p>';
        return;
    }
    
    invoices.forEach(invoice => {
        const card = document.createElement('div');
        card.className = 'invoice-card';
        card.innerHTML = `
            <div class="invoice-header">
                <span class="invoice-id">OR #${invoice.id}</span>
                <span class="invoice-date">${invoice.date}</span>
            </div>
            <div class="invoice-details">
                <div><strong>Company:</strong> ${invoice.company}</div>
                <div><strong>Client:</strong> ${invoice.client}</div>
                <div><strong>Service:</strong> ${invoice.service}</div>
            </div>
            <div class="invoice-actions">
                <button class="btn btn-view" data-id="${invoice.rowNumber}">View</button>
                ${invoice.email ? `<button class="btn btn-email" data-id="${invoice.rowNumber}" data-email="${invoice.email}">Email</button>` : ''}
            </div>
        `;
        container.appendChild(card);
    });

    // Add event listeners to all view buttons
    document.querySelectorAll('.btn-view').forEach(button => {
        button.addEventListener('click', function() {
            viewInvoice(this.getAttribute('data-id'));
        });
    });

    // Add event listeners to all email buttons
    document.querySelectorAll('.btn-email').forEach(button => {
        button.addEventListener('click', function() {
            sendEmail(this.getAttribute('data-email'), this.getAttribute('data-id'));
        });
    });
}

function showLoading(isLoading) {
    const loadingElement = document.getElementById('loading');
    if (loadingElement) {
        loadingElement.style.display = isLoading ? 'flex' : 'none';
    }
}

function viewInvoice(invoiceId) {
    const modal = document.getElementById('invoice-modal');
    const modalContent = document.getElementById('modal-content');
    
    if (!modal || !modalContent) {
        console.error('Modal elements not found');
        return;
    }

    // Show loading state
    modalContent.innerHTML = '<div class="modal-loading">Loading invoice details...</div>';
    modal.style.display = 'block';

    // Fetch invoice details (replace with your actual API call)
    fetch(`get_invoice_details.php?id=${invoiceId}`)
        .then(response => response.json())
        .then(invoice => {
            // Format the modal content to match your design
            modalContent.innerHTML = `
                <div class="modal-header">
                    <h1>INVOICE</h1>
                    <span class="close-modal" onclick="closeModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="invoice-meta">
                        <p><strong>Invoice #:</strong> ${invoice.id}</p>
                        <p><strong>Date:</strong> ${invoice.date}</p>
                    </div>
                    
                    <hr class="divider">
                    
                    <div class="bill-to">
                        <h2>Bill To:</h2>
                        <p>${invoice.client}</p>
                        ${invoice.client_address ? `<p>${invoice.client_address}</p>` : ''}
                        ${invoice.client_email ? `<p>${invoice.client_email}</p>` : ''}
                    </div>
                    
                    <hr class="divider">
                    
                    <div class="service-table">
                        <h3>Service</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>${invoice.service || 'Service'}</td>
                                    <td>${invoice.service_description || 'Service rendered'}</td>
                                    <td>${invoice.amount || '$N/A'}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="invoice-status">
                        <p><strong>Status:</strong> ${invoice.status || 'N/A'}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-receipt" onclick="viewReceipt(${invoice.id})">View Receipt</button>
                    ${invoice.email ? `<button class="btn btn-email" onclick="sendEmail('${invoice.email}', ${invoice.id})">Email PDF</button>` : ''}
                </div>
            `;
        })
        .catch(error => {
            modalContent.innerHTML = `
                <div class="modal-error">
                    <p>Error loading invoice details:</p>
                    <p>${error.message}</p>
                    <button class="btn btn-close" onclick="closeModal()">Close</button>
                </div>
            `;
        });
}

// Add this new function
function viewReceipt(invoiceId) {
    // Implement receipt viewing functionality
    window.open(`receipt.php?id=${invoiceId}`, '_blank');
}
function closeModal() {
    const modal = document.getElementById('invoice-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function sendEmail(email, invoiceId) {
    if (!confirm(`Send invoice #${invoiceId} to ${email}?`)) {
        return;
    }

    showLoading(true);
    
    // Simulate API call (replace with actual fetch)
    setTimeout(() => {
        showLoading(false);
        alert(`Invoice #${invoiceId} sent successfully to ${email}`);
    }, 1500);
    
    // Actual implementation would be:
    /*
    fetch(`send_invoice_email.php?id=${invoiceId}&email=${encodeURIComponent(email)}`)
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            if (data.success) {
                alert(`Invoice sent successfully to ${email}`);
            } else {
                alert('Error sending invoice: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            showLoading(false);
            alert('Error: ' + error.message);
        });
    */
}

// Make functions available globally
window.viewInvoice = viewInvoice;
window.closeModal = closeModal;
window.sendEmail = sendEmail;