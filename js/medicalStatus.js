// ===== MEDICAL STATUS MODAL =====
function openMedicalModal(employeeNo, currentStatus) {
    document.getElementById('medicalModal').style.display = 'block';
    document.getElementById('modalEmployeeNo').value = employeeNo;
    document.getElementById('modalMedicalStatus').value = currentStatus;
}

function closeMedicalModal() {
    document.getElementById('medicalModal').style.display = 'none';
}

function saveMedicalStatus() {
    const employeeNo = document.getElementById('modalEmployeeNo').value;
    const status = document.getElementById('modalMedicalStatus').value;

    const data = new URLSearchParams();
    data.append('employeeNo', employeeNo);
    data.append('status', status);

    fetch('updateMedicalStatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data.toString()
    })
    .then(response => response.text())
    .then(result => {
        if (result.trim() === "success") {
            // Find the row and update it
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0 && cells[0].textContent.trim() === employeeNo) {
                    const medicalCell = cells[7];
                    
                    // Update the cell content based on status
                    if (status === 'Complete') {
                        // Complete: No button
                        medicalCell.className = 'medical-complete';
                        medicalCell.textContent = 'Complete';
                    } else {
                        // Incomplete: Keep the button
                        medicalCell.className = 'medical-incomplete';
                        medicalCell.innerHTML = 'Incomplete<button class="status-btn" onclick="openMedicalModal(\'' + employeeNo + '\', \'Incomplete\')"><i class="fa-solid fa-pencil"></i></button>';
                    }
                }
            });
            
            closeMedicalModal();
            alert("Medical checkup status updated successfully!");
        } else {
            alert("Failed to update status: " + result);
        }
    })
    .catch(err => {
        console.error(err);
        alert("An error occurred while updating.");
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('medicalModal');
    if (event.target == modal) {
        closeMedicalModal();
    }
}

// ===== LOGOUT CONFIRMATION =====
document.querySelector('.logout-link').addEventListener('click', (e) => {
    e.preventDefault();
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "logout.php";
    }
});

function filterByMonth() {
    const month = document.getElementById('monthFilter').value;
    if (month) {
        window.location.href = '?month=' + month;
    } else {
        window.location.href = '?';
    }
}