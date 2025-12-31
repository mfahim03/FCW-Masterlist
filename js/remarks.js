// Auto-save remarks on blur (when user clicks away)
document.querySelectorAll('.editable-remarks').forEach(textarea => {
    // Store original value
    textarea.dataset.originalValue = textarea.value;
    
    // Auto-resize textarea based on content
    autoResize(textarea);
    
    textarea.addEventListener('input', function() {
        autoResize(this);
    });
    
    textarea.addEventListener('blur', function() {
        const employeeNo = this.dataset.employeeNo;
        const newRemarks = this.value.trim();
        const originalValue = this.dataset.originalValue;
        
        // Only save if value changed
        if (newRemarks !== originalValue) {
            saveRemarks(employeeNo, newRemarks, this);
        }
    });
    
    // Optional: Save on Ctrl+Enter
    textarea.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            this.blur(); // Trigger save
        }
    });
});

function saveRemarks(employeeNo, remarks, textareaElement) {
    // Add saving indicator
    textareaElement.style.borderColor = '#ffa500';
    textareaElement.disabled = true;
    
    fetch('model/saveRemarks.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'employeeNo=' + encodeURIComponent(employeeNo) + 
              '&remarks=' + encodeURIComponent(remarks)
    })
    .then(response => response.text())
    .then(result => {
        textareaElement.disabled = false;
        
        if (result.trim() === "success") {
            // Update original value
            textareaElement.dataset.originalValue = remarks;
            
            // Success indicator
            textareaElement.style.borderColor = '#4CAF50';
            setTimeout(() => {
                textareaElement.style.borderColor = '';
            }, 1500);
        } else {
            // Error indicator
            textareaElement.style.borderColor = '#f44336';
            alert("Failed to save remarks. Please try again.");
            
            setTimeout(() => {
                textareaElement.style.borderColor = '';
            }, 2000);
        }
    })
    .catch(err => {
        console.error(err);
        textareaElement.disabled = false;
        textareaElement.style.borderColor = '#f44336';
        alert("An error occurred while saving remarks.");
        
        setTimeout(() => {
            textareaElement.style.borderColor = '';
        }, 2000);
    });
}

function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}