// ===============================
// Employee Detail Edit Functionality (FIXED)
// ===============================

let isEditing = false;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('employeeForm');
    if (!form) return;

    document.getElementById('editBtn')?.addEventListener('click', enableEdit);
    document.getElementById('cancelBtn')?.addEventListener('click', cancelEdit);
    form.addEventListener('submit', handleFormSubmit);

    window.addEventListener('beforeunload', handleBeforeUnload);
    autoHideAlerts();

    // Initialize readonly state
    setViewMode();
});

// ===============================
// MODE CONTROLS
// ===============================

function setViewMode() {
    isEditing = false;

    document.querySelectorAll('#employeeForm input:not([type="hidden"]), textarea').forEach(el => {
        el.setAttribute('readonly', true);
        el.classList.remove('editable');
    });

    document.querySelectorAll('#employeeForm select').forEach(select => {
        select.classList.add('readonly');
    });

    document.getElementById('editBtn').style.display = 'inline-flex';
    document.getElementById('editModeButtons').style.display = 'none';
}

function enableEdit() {
    isEditing = true;

    document.querySelectorAll('#employeeForm input:not([type="hidden"]), textarea').forEach(el => {
        el.removeAttribute('readonly');
        el.classList.add('editable');
    });

    document.querySelectorAll('#employeeForm select').forEach(select => {
        select.classList.remove('readonly');
    });

    document.getElementById('editBtn').style.display = 'none';
    document.getElementById('editModeButtons').style.display = 'flex';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancelEdit() {
    if (confirm('Discard unsaved changes?')) {
        setViewMode();
    }
}

// ===============================
// FORM SUBMISSION
// ===============================

function handleFormSubmit(e) {
    e.preventDefault();

    if (!validateForm()) return;

    if (!confirm('Save changes?')) return;

    isEditing = false;
    e.target.submit();
}

// ===============================
// VALIDATION
// ===============================

function validateForm() {
    let valid = true;
    let errors = [];

    const name = document.querySelector('[name="name"]');
    if (name && !name.value.trim()) {
        valid = false;
        errors.push('Name is required');
        name.style.borderColor = 'red';
    }

    const email = document.querySelector('[name="email"]');
    if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        valid = false;
        errors.push('Invalid email format');
        email.style.borderColor = 'red';
    }

    if (!valid) {
        alert(errors.join('\n'));
    }

    return valid;
}

// ===============================
// SAFETY & UX
// ===============================

function handleBeforeUnload(e) {
    if (!isEditing) return;
    e.preventDefault();
    e.returnValue = '';
}

function autoHideAlerts() {
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(a => a.remove());
    }, 5000);
}
