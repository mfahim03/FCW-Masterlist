// Employee Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addEmployeeForm') || document.getElementById('employeeForm');
    
    if (form) {
        // Calculate YOS when hire date changes
        const hireDateInput = form.querySelector('input[name="hire_date"]');
        const yosInput = form.querySelector('input[name="yos"]');
        
        if (hireDateInput && yosInput) {
            hireDateInput.addEventListener('change', function() {
                if (this.value) {
                    const hireDate = new Date(this.value);
                    const today = new Date();
                    const years = Math.floor((today - hireDate) / (365.25 * 24 * 60 * 60 * 1000));
                    yosInput.value = years >= 0 ? years : 0;
                }
            });
            
            // Calculate on page load if hire date exists
            if (hireDateInput.value) {
                hireDateInput.dispatchEvent(new Event('change'));
            }
        }
        
        // Form validation
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let hasError = false;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    hasError = true;
                    field.style.borderColor = 'red';
                    field.addEventListener('input', function() {
                        this.style.borderColor = '';
                    });
                }
            });
            
            if (hasError) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
        });
    }
});

// Image preview for add form
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('placeholderIcon');
            if (preview && placeholder) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}