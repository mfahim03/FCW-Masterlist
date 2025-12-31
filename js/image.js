let selectedEmployeeId = null;
let selectedEmployeeName = null;
const contextMenu = document.getElementById('contextMenu');

// Image preview functionality
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            this.value = '';
            return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('Image size should be less than 10MB');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('employeeImagePreview').src = e.target.result;
            document.getElementById('removeImageBtn').style.display = 'inline-block';
            document.getElementById('removeImageFlag').value = '0';
        }
        reader.readAsDataURL(file);
    }
});

// Remove image function
function removeEmployeeImage() {
    if (confirm('Are you sure you want to remove this employee photo?')) {
        // Set to default image
        document.getElementById('employeeImagePreview').src = 'img/default-avatar.png';
        // Clear file input
        document.getElementById('imageInput').value = '';
        // Set flag to remove image on server
        document.getElementById('removeImageFlag').value = '1';
        // Show confirmation
        alert('Photo will be removed when you save the form');
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
        }, 300);
    });
}, 5000);

function openAddEmployeeForm() {
    window.location.href = 'addEmployee.php';
}

function viewEmployeeDetails(employeeId) {
    window.location.href = 'employeeInfo.php?id=' + employeeId;
}