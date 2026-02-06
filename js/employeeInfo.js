// ===== GLOBAL VARIABLES =====
let selectedEmployeeId = null;
let selectedEmployeeName = null;

// ===== NAVIGATION =====
function openAddEmployeeForm() {
    window.location.href = 'addEmployee.php';
}

function viewEmployeeDetails(employeeId) {
    window.location.href = 'employeeInfo.php?id=' + encodeURIComponent(employeeId);
}

// ===== DOM READY =====
document.addEventListener('DOMContentLoaded', function () {
    const employeeItems = document.querySelectorAll('.employee-list-item');
    const contextMenu = document.getElementById('contextMenu');
    const searchInput = document.getElementById('searchEmployee');

    // ===== SEARCH FUNCTIONALITY =====
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();

            employeeItems.forEach(item => {
                const empNo = item.querySelector('.emp-no')?.textContent.toLowerCase() || '';
                const empName = item.querySelector('.emp-name')?.textContent.toLowerCase() || '';

                item.style.display =
                    empNo.includes(searchTerm) || empName.includes(searchTerm)
                        ? 'block'
                        : 'none';
            });
        });
    }

    // ===== CONTEXT MENU =====
    if (employeeItems.length && contextMenu) {
        employeeItems.forEach(item => {
            item.addEventListener('contextmenu', function (e) {
                e.preventDefault();

                selectedEmployeeId = this.dataset.employeeId;
                selectedEmployeeName = this.dataset.employeeName;

                contextMenu.style.display = 'block';
                contextMenu.style.left = `${e.pageX}px`;
                contextMenu.style.top = `${e.pageY}px`;
            });
        });

        document.addEventListener('click', () => {
            contextMenu.style.display = 'none';
        });
    }
});

// ===== CONTEXT MENU ACTIONS =====
function viewEmployee() {
    if (selectedEmployeeId) {
        viewEmployeeDetails(selectedEmployeeId);
    }
}

// ===== MOVE EMPLOYEE (EOC / RUNAWAY) =====
function moveEmployee() {
    if (!selectedEmployeeId) {
        alert('No employee selected.');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'move-employee-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="this.parentElement.remove()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-xmark"></i> Move Employee</h3>
                <button class="modal-close" onclick="this.closest('.move-employee-modal').remove()">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Move <strong>${selectedEmployeeName}</strong> (${selectedEmployeeId}) to:</p>

                <form id="moveEmployeeForm" method="POST" action="moveEOC1.php">
                    <input type="hidden" name="employee_id" value="${selectedEmployeeId}">

                    <label>Status <span style="color:red">*</span></label>
                    <select name="status" required>
                        <option value="">Select Status</option>
                        <option value="EOC">EOC (End of Contract)</option>
                        <option value="RUNAWAY">Runaway</option>
                    </select>

                    <label style="margin-top:10px;">Remarks</label>
                    <textarea name="remarks" rows="3" placeholder=""></textarea>

                    <div class="modal-actions">
                        <button type="button" onclick="this.closest('.move-employee-modal').remove()">
                            Cancel
                        </button>
                        <button type="submit" class="danger">
                            Confirm Move
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('moveEmployeeForm').addEventListener('submit', function (e) {
        const status = this.status.value;

        if (!status) {
            e.preventDefault();
            alert('Please select a status.');
            return;
        }

        if (!confirm(`Move employee to ${status}? This will remove them from active list.`)) {
            e.preventDefault();
        }
    });
}

// ===== DELETE EMPLOYEE =====
// Fixed version - properly handles POST request
function deleteEmployee() {
    if (!selectedEmployeeId) {
        alert('No employee selected.');
        return;
    }

    // Show confirmation with employee details
    const confirmMessage = `Are you sure you want to DELETE employee ${selectedEmployeeName} (${selectedEmployeeId})?`;
    
    if (!confirm(confirmMessage)) {
        return;
    }

    // Double confirmation for safety
    if (!confirm(`FINAL CONFIRMATION: Delete ${selectedEmployeeName}?`)) {
        return;
    }

    // Create and submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'config/deleteEmployee.php';  // Adjusted path (no ../)

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'employee_id';
    input.value = selectedEmployeeId;

    form.appendChild(input);
    document.body.appendChild(form);
    
    // Log for debugging
    console.log('Deleting employee:', selectedEmployeeId);
    
    form.submit();
}

// Alternative version using fetch API (more modern, with feedback)
function deleteEmployeeModern() {
    if (!selectedEmployeeId) {
        alert('No employee selected.');
        return;
    }

    // Show confirmation
    const confirmMessage = `Are you sure you want to DELETE employee ${selectedEmployeeName} (${selectedEmployeeId})?\n\n⚠️ This action CANNOT be undone!`;
    
    if (!confirm(confirmMessage)) {
        return;
    }

    // Double confirmation
    if (!confirm(`FINAL CONFIRMATION: Delete ${selectedEmployeeName}?`)) {
        return;
    }

    // Show loading indicator
    const contextMenu = document.getElementById('contextMenu');
    if (contextMenu) {
        contextMenu.style.display = 'none';
    }

    // Create form data
    const formData = new FormData();
    formData.append('employee_id', selectedEmployeeId);

    // Send request
    fetch('config/deleteEmployee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    })
    .then(data => {
        if (data) {
            console.log('Response:', data);
        }
        // Reload page to see results
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the employee.');
        window.location.reload();
    });
}
