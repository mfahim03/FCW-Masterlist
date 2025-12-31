// Employee Info JavaScript Functions

function openAddEmployeeForm() {
    window.location.href = 'addEmployee.php';
}

function viewEmployeeDetails(employeeId) {
    window.location.href = 'employeeInfo.php?id=' + employeeId;
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchEmployee');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const employeeItems = document.querySelectorAll('.employee-list-item');
            
            employeeItems.forEach(function(item) {
                const empNo = item.querySelector('.emp-no').textContent.toLowerCase();
                const empName = item.querySelector('.emp-name').textContent.toLowerCase();
                
                if (empNo.includes(searchTerm) || empName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});

// Context menu for employee list items
document.addEventListener('DOMContentLoaded', function() {
    const employeeItems = document.querySelectorAll('.employee-list-item');
    const contextMenu = document.getElementById('contextMenu');
    
    if (employeeItems && contextMenu) {
        employeeItems.forEach(function(item) {
            item.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                
                selectedEmployeeId = this.dataset.employeeId;
                selectedEmployeeName = this.dataset.employeeName;
                
                contextMenu.style.display = 'block';
                contextMenu.style.left = e.pageX + 'px';
                contextMenu.style.top = e.pageY + 'px';
            });
        });
        
        // Hide context menu when clicking elsewhere
        document.addEventListener('click', function() {
            contextMenu.style.display = 'none';
        });
    }
});

function viewEmployee() {
    if (selectedEmployeeId) {
        window.location.href = 'employeeInfo.php?id=' + encodeURIComponent(selectedEmployeeId);
    }
}

function deleteEmployee() {
    if (selectedEmployeeId && selectedEmployeeName) {
        if (confirm('Are you sure you want to delete employee: ' + selectedEmployeeName + '?')) {
            window.location.href = 'deleteEmployee.php?id=' + encodeURIComponent(selectedEmployeeId);
        }
    }
}