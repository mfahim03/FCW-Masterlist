// Global variables
let currentPage = 1;
let itemsPerPage = 10;
let filteredRows = [];
let totalPages = 1;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
    applyPagination();
});

// Initialize search functionality
function initializeSearch() {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentPage = 1;
            filterAndPaginate();
        });
    }
}

// Filter rows based on search and apply pagination
function filterAndPaginate() {
    const searchTerm = document.getElementById('search').value.toLowerCase();
    const tableBody = document.getElementById('tableBody');
    
    if (!tableBody) return;
    
    const allRows = Array.from(tableBody.querySelectorAll('.employee-row'));
    
    // Filter rows based on search term
    if (searchTerm) {
        filteredRows = allRows.filter(row => {
            const empno = row.dataset.empno || '';
            const name = row.dataset.name || '';
            
            return empno.includes(searchTerm) || name.includes(searchTerm);
        });
    } else {
        filteredRows = allRows;
    }
    
    // Calculate total pages
    totalPages = Math.ceil(filteredRows.length / itemsPerPage);
    if (totalPages === 0) totalPages = 1;
    
    if (currentPage > totalPages) {
        currentPage = totalPages;
    }
    
    applyPagination();
}

// Apply pagination to filtered rows
function applyPagination() {
    const tableBody = document.getElementById('tableBody');
    if (!tableBody) return;
    
    const allRows = Array.from(tableBody.querySelectorAll('.employee-row'));
    
    if (filteredRows.length === 0) {
        filteredRows = allRows;
        totalPages = Math.ceil(filteredRows.length / itemsPerPage);
        if (totalPages === 0) totalPages = 1;
    }
    
    // Hide all rows
    allRows.forEach(row => row.style.display = 'none');
    
    // Calculate indices
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    
    // Show current page rows
    const rowsToShow = filteredRows.slice(startIndex, endIndex);
    rowsToShow.forEach(row => row.style.display = '');
    
    updatePaginationUI();
}

// Update pagination UI
function updatePaginationUI() {
    // Update page info
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredRows.length);
    
    document.getElementById('showingStart').textContent = filteredRows.length > 0 ? startIndex + 1 : 0;
    document.getElementById('showingEnd').textContent = endIndex;
    document.getElementById('totalRecordsDisplay').textContent = filteredRows.length;
    
    // Update pagination buttons
    const firstPage = document.getElementById('firstPage');
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    const lastPage = document.getElementById('lastPage');
    
    if (currentPage === 1) {
        firstPage.classList.add('disabled');
        prevPage.classList.add('disabled');
    } else {
        firstPage.classList.remove('disabled');
        prevPage.classList.remove('disabled');
    }
    
    if (currentPage === totalPages) {
        nextPage.classList.add('disabled');
        lastPage.classList.add('disabled');
    } else {
        nextPage.classList.remove('disabled');
        lastPage.classList.remove('disabled');
    }
    
    // Generate page numbers
    const pageNumbers = document.getElementById('pageNumbers');
    pageNumbers.innerHTML = '';
    
    const start_page = Math.max(1, currentPage - 2);
    const end_page = Math.min(totalPages, currentPage + 2);
    
    for (let i = start_page; i <= end_page; i++) {
        if (i === currentPage) {
            const span = document.createElement('span');
            span.className = 'active';
            span.textContent = i;
            pageNumbers.appendChild(span);
        } else {
            const a = document.createElement('a');
            a.href = '#';
            a.textContent = i;
            a.onclick = function() {
                changePage(i);
                return false;
            };
            pageNumbers.appendChild(a);
        }
    }
    
    // Hide pagination if only 1 page
    const paginationContainer = document.getElementById('paginationContainer');
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
    } else {
        paginationContainer.style.display = 'flex';
    }
}

// Pagination functions
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    applyPagination();
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        applyPagination();
    }
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        applyPagination();
    }
}

function lastPage() {
    currentPage = totalPages;
    applyPagination();
}

// Apply filters
function applyFilters() {
    const nationality = document.getElementById('nationalityFilter').value;
    
    // Get the current status from the URL
    const urlParams = new URLSearchParams(window.location.search);
    let currentStatus = urlParams.get('status') || 'EOC';
    
    // Build URL with current status and selected nationality
    let url = window.location.pathname + '?status=' + encodeURIComponent(currentStatus);
    
    if (nationality !== 'all') {
        url += '&nationality=' + encodeURIComponent(nationality);
    }
    
    // Navigate to filtered page
    window.location.href = url;
}

// Download Excel - FIXED VERSION
function downloadExcel() {
    // Get current filters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status') || '';
    const nationality = document.getElementById('nationalityFilter') ? 
                       document.getElementById('nationalityFilter').value : 'all';
    
    // Build export URL with filters
    let exportUrl = 'excel/exportEocRunawayToExcel.php';
    let params = [];
    
    if (status) {
        params.push('status=' + encodeURIComponent(status));
    }
    
    if (nationality && nationality !== 'all') {
        params.push('nationality=' + encodeURIComponent(nationality));
    }
    
    if (params.length > 0) {
        exportUrl += '?' + params.join('&');
    }
    
    // Update button state
    const btn = document.querySelector('.download-btn-pill');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    btn.disabled = true;
    
    // Create hidden iframe for download
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = exportUrl;
    document.body.appendChild(iframe);
    
    // Restore button after delay
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        // Remove iframe after download
        setTimeout(() => {
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 1000);
    }, 1000);
}

// Logout
document.addEventListener('DOMContentLoaded', function() {
    const logoutLink = document.querySelector('.logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "logout.php";
            }
        });
    }
});