// js/flightDate.js - Enhanced with PDF upload functionality

/**
 * Edit Flight Date
 * Shows the edit form for flight date
 */
function editFlightDate(empNo, currentDate) {
    // Hide display, show edit form
    document.getElementById('display-' + empNo).style.display = 'none';
    document.getElementById('edit-' + empNo).classList.add('active');
    
    // Focus on input
    document.getElementById('input-' + empNo).focus();
}

/**
 * Cancel Edit Flight Date
 * Hides the edit form and shows the display
 */
function cancelEditFlightDate(empNo) {
    // Show display, hide edit form
    document.getElementById('display-' + empNo).style.display = 'flex';
    document.getElementById('edit-' + empNo).classList.remove('active');
}

/**
 * Save Flight Date
 * Sends AJAX request to update flight date
 */
function saveFlightDate(empNo) {
    const dateInput = document.getElementById('input-' + empNo);
    const flightDate = dateInput.value;
    
    // Show loading state
    const saveBtn = event.target.closest('.save-flight-date-btn');
    const originalContent = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    saveBtn.disabled = true;
    
    // Send AJAX request
    fetch('config/updateFlightDate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'empNo=' + encodeURIComponent(empNo) + '&FlightDate=' + encodeURIComponent(flightDate)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update display text
            const displayText = document.querySelector('#display-' + empNo + ' .flight-date-text');
            if (flightDate) {
                const dateObj = new Date(flightDate);
                const formattedDate = dateObj.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }).replace(/\//g, '-');
                displayText.textContent = formattedDate;
                displayText.classList.remove('empty');
            } else {
                displayText.textContent = 'Not set';
                displayText.classList.add('empty');
            }
            
            // Hide edit form, show display
            cancelEditFlightDate(empNo);
            
            // Show success message
            showAlert('Flight date updated successfully!', 'success');
        } else {
            // Show error message
            showAlert(data.message || 'Failed to update flight date', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while updating flight date', 'error');
    })
    .finally(() => {
        // Restore button
        saveBtn.innerHTML = originalContent;
        saveBtn.disabled = false;
    });
}

/**
 * Open Remarks Upload Modal
 */
function openRemarksModal(empNo) {
    document.getElementById('modalEmpNo').value = empNo;
    document.getElementById('remarksModal').classList.add('active');
    
    // Reset form
    document.getElementById('remarksUploadForm').reset();
    updateFileLabel();
}

/**
 * Close Remarks Upload Modal
 */
function closeRemarksModal() {
    document.getElementById('remarksModal').classList.remove('active');
    document.getElementById('remarksUploadForm').reset();
    updateFileLabel();
}

/**
 * Update File Label
 */
function updateFileLabel() {
    const fileInput = document.getElementById('remarksPDF');
    const fileLabel = document.getElementById('fileLabel');
    const fileLabelText = document.getElementById('fileLabelText');
    
    if (fileInput.files && fileInput.files.length > 0) {
        const fileName = fileInput.files[0].name;
        fileLabelText.textContent = fileName;
        fileLabel.classList.add('has-file');
    } else {
        fileLabelText.textContent = 'Choose PDF file or drag here';
        fileLabel.classList.remove('has-file');
    }
}

/**
 * Show Alert Message
 * Displays a temporary alert message
 */
function showAlert(message, type) {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.innerHTML = `
        <span class="alert-close" onclick="this.parentElement.remove()">&times;</span>
        <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    // Insert at top of body
    document.body.insertBefore(alert, document.body.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 5000);
}

// Add Enter key support for flight date input and modal close on Escape
document.addEventListener('DOMContentLoaded', function() {
    // Flight date input keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const target = e.target;
            if (target.classList.contains('flight-date-input')) {
                // Find the employee number from the input id
                const empNo = target.id.replace('input-', '');
                saveFlightDate(empNo);
            }
        } else if (e.key === 'Escape') {
            const target = e.target;
            if (target.classList.contains('flight-date-input')) {
                // Find the employee number from the input id
                const empNo = target.id.replace('input-', '');
                cancelEditFlightDate(empNo);
            }
            
            // Close modal on Escape
            const modal = document.getElementById('remarksModal');
            if (modal && modal.classList.contains('active')) {
                closeRemarksModal();
            }
        }
    });
    
    // Remarks upload form submission
    const remarksForm = document.getElementById('remarksUploadForm');
    if (remarksForm) {
        remarksForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const empNo = document.getElementById('modalEmpNo').value;
            const fileInput = document.getElementById('remarksPDF');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                showAlert('Please select a PDF file', 'error');
                return;
            }
            
            const file = fileInput.files[0];
            
            // Validate file type
            if (file.type !== 'application/pdf') {
                showAlert('Please select a valid PDF file', 'error');
                return;
            }
            
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                showAlert('File size must be less than 10MB', 'error');
                return;
            }
            
            // Create FormData
            const formData = new FormData();
            formData.append('empNo', empNo);
            formData.append('remarksPDF', file);
            
            // Show loading state
            const submitBtn = remarksForm.querySelector('.modal-btn-primary');
            const originalBtnContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
            submitBtn.disabled = true;
            
            // Send AJAX request
            fetch('config/uploadRemarksPDF.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Remarks PDF uploaded successfully!', 'success');
                    closeRemarksModal();
                    
                    // Reload page to show updated PDF link
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert(data.message || 'Failed to upload PDF', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while uploading PDF', 'error');
            })
            .finally(() => {
                // Restore button
                submitBtn.innerHTML = originalBtnContent;
                submitBtn.disabled = false;
            });
        });
    }
    
    // Close modal when clicking outside
    const modal = document.getElementById('remarksModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRemarksModal();
            }
        });
    }
    
    // Drag and drop support for file input
    const fileLabel = document.getElementById('fileLabel');
    if (fileLabel) {
        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileLabel.style.borderColor = '#17a2b8';
            fileLabel.style.background = 'linear-gradient(135deg, #d1ecf1, #bee5eb)';
        });
        
        fileLabel.addEventListener('dragleave', function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('remarksPDF');
            if (!fileInput.files || fileInput.files.length === 0) {
                fileLabel.style.borderColor = '#ced4da';
                fileLabel.style.background = 'linear-gradient(135deg, #f8f9fa, #e9ecef)';
            }
        });
        
        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('remarksPDF');
            
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                updateFileLabel();
            }
            
            fileLabel.style.borderColor = '#17a2b8';
        });
    }
});