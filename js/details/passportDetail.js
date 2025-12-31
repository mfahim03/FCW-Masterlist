// Passport detail charts
let passportDeptChart = null;
let passportNatChart = null;
let passportMonthlyChart = null;

// Show passport detail view
function showPassportDetail(status) {
    const mainHeader = document.getElementById('passport-main-header');
    const mainContent = document.getElementById('passport-main-content');
    const detailView = document.getElementById('passport-detail-view');
    const detailTitle = document.getElementById('passport-detail-title');
    
    // Update title based on status
    const titles = {
        'expired': 'Expired Passports',
        'expiring': 'Expiring Soon Passports',
        'active': 'Active Passports'
    };
    detailTitle.textContent = titles[status];

    window.currentPassportStatus = status;  
    
    // Hide main content, show detail view
    mainHeader.style.display = 'none';
    mainContent.style.display = 'none';
    detailView.style.display = 'block';
    
    // Fetch and display data
    fetchPassportDetail(status);
    
    // Scroll to top of the section
    document.getElementById('passport-section').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Hide passport detail view
function hidePassportDetail() {
    const mainHeader = document.getElementById('passport-main-header');
    const mainContent = document.getElementById('passport-main-content');
    const detailView = document.getElementById('passport-detail-view');
    
    // Show main content, hide detail view
    mainHeader.style.display = 'block';
    mainContent.style.display = 'block';
    detailView.style.display = 'none';
    
    // Destroy existing charts
    if (passportDeptChart) {
        passportDeptChart.destroy();
        passportDeptChart = null;
    }
    if (passportNatChart) {
        passportNatChart.destroy();
        passportNatChart = null;
    }
    if (passportMonthlyChart) {
        passportMonthlyChart.destroy();
        passportMonthlyChart = null;
    }
    
    // Scroll to top of passport section
    document.getElementById('passport-section').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Fetch passport detail data via AJAX
function fetchPassportDetail(status) {
    console.log('Fetching passport detail for status:', status);
    
    fetch(`config/passportDetailSQL.php?status=${status}`)
        .then(response => {
            console.log('Passport - Response status:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Passport - Error response text:', text);
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.text();
        })
        .then(text => {
            console.log('Passport - Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Passport - Parsed data:', data);
                
                if (data.success) {
                    createPassportDetailCharts(data.department, data.nationality, data.monthly);
                } else {
                    console.error('Passport - Server returned error:', data);
                    showPassportError();
                }
            } catch (parseError) {
                console.error('Passport - JSON parse error:', parseError);
                showPassportError();
            }
        })
        .catch(error => {
            console.error('Passport - Fetch error:', error);
            showPassportError();
        });
}

function showPassportError() {
    const deptContainer = document.getElementById('passportDepartmentChart')?.parentElement;
    const natContainer = document.getElementById('passportNationalityChart')?.parentElement;
    const monthlyContainer = document.getElementById('passportMonthlyChart')?.parentElement;
    
    if (deptContainer) {
        deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
    if (natContainer) {
        natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
    if (monthlyContainer) {
        monthlyContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown by Passport Expiry</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
}

// Create passport detail charts (PIE + BAR)
function createPassportDetailCharts(departmentData, nationalityData, monthlyData) {
    console.log('Creating passport charts with data:', { departmentData, nationalityData, monthlyData });
    
    // Destroy existing charts if they exist
    if (passportDeptChart) {
        passportDeptChart.destroy();
        passportDeptChart = null;
    }
    if (passportNatChart) {
        passportNatChart.destroy();
        passportNatChart = null;
    }
    if (passportMonthlyChart) {
        passportMonthlyChart.destroy();
        passportMonthlyChart = null;
    }
    
    // Generate colors
    function generateColors(count) {
        const colors = [];
        const hueStep = 360 / count;
        for (let i = 0; i < count; i++) {
            colors.push(`hsl(${i * hueStep}, 70%, 60%)`);
        }
        return colors;
    }
    
    function generateHoverColors(colors) {
        return colors.map(color => {
            const match = color.match(/hsl\((\d+),\s*([\d.]+)%,\s*([\d.]+)%\)/);
            if (match) {
                const [, h, s, l] = match;
                return `hsl(${h}, ${s}%, ${Math.max(0, l - 10)}%)`;
            }
            return color;
        });
    }
    
    // Department Chart
    // Get fresh reference each time
    let deptContainer = document.getElementById('passportDepartmentChart')?.parentElement;
    if (!departmentData || departmentData.length === 0) {
        if (deptContainer) {
            deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No department data available</p>';
        }
    } else {
        // Get fresh reference again after potential innerHTML changes
        deptContainer = document.getElementById('passportDepartmentChart')?.parentElement;
        if (deptContainer) {
            deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><canvas id="passportDepartmentChart"></canvas>';
        }
        
        const deptLabels = departmentData.map(item => item.label);
        const deptCounts = departmentData.map(item => item.count);
        const deptColors = generateColors(deptLabels.length);
        const deptHoverColors = generateHoverColors(deptColors);
        
        const deptCtx = document.getElementById('passportDepartmentChart')?.getContext('2d');
        if (deptCtx) {
            passportDeptChart = new Chart(deptCtx, {
                type: 'pie',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        data: deptCounts,
                        backgroundColor: deptColors,
                        borderColor: '#fff',
                        borderWidth: 2,
                        hoverBackgroundColor: deptHoverColors,
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'right', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            font: { weight: 'bold', size: 11 },
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${percentage}%`;
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
            
            const totalDept = deptCounts.reduce((a, b) => a + b, 0);
            // Get fresh container reference for title update
            const currentDeptContainer = document.getElementById('passportDepartmentChart')?.parentElement;
            const deptTitle = currentDeptContainer?.querySelector('.chart-title');
            if (deptTitle) {
                deptTitle.innerHTML = `<i class="fa-solid fa-building"></i> Employees by Department <small style="opacity: 0.7;">(Total: ${totalDept})</small>`;
            }
        }
    }
    
    // Nationality Chart
    let natContainer = document.getElementById('passportNationalityChart')?.parentElement;
    if (!nationalityData || nationalityData.length === 0) {
        if (natContainer) {
            natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No nationality data available</p>';
        }
    } else {
        // Get fresh reference again
        natContainer = document.getElementById('passportNationalityChart')?.parentElement;
        if (natContainer) {
            natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><canvas id="passportNationalityChart"></canvas>';
        }
        
        const natLabels = nationalityData.map(item => item.label);
        const natCounts = nationalityData.map(item => item.count);
        const natColors = generateColors(natLabels.length);
        const natHoverColors = generateHoverColors(natColors);
        
        const natCtx = document.getElementById('passportNationalityChart')?.getContext('2d');
        if (natCtx) {
            passportNatChart = new Chart(natCtx, {
                type: 'pie',
                data: {
                    labels: natLabels,
                    datasets: [{
                        data: natCounts,
                        backgroundColor: natColors,
                        borderColor: '#fff',
                        borderWidth: 2,
                        hoverBackgroundColor: natHoverColors,
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'right', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            font: { weight: 'bold', size: 11 },
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${percentage}%`;
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
            
            const totalNat = natCounts.reduce((a, b) => a + b, 0);
            // Get fresh container reference
            const currentNatContainer = document.getElementById('passportNationalityChart')?.parentElement;
            const natTitle = currentNatContainer?.querySelector('.chart-title');
            if (natTitle) {
                natTitle.innerHTML = `<i class="fa-solid fa-globe"></i> Employees by Nationality <small style="opacity: 0.7;">(Total: ${totalNat})</small>`;
            }
        }
    }
   
    // Monthly Breakdown Bar Chart
    let monthlyContainer = document.getElementById('passportMonthlyChart')?.parentElement;
    if (!monthlyData || monthlyData.length === 0) {
        if (monthlyContainer) {
            const statusTitles = {
                'expired': 'Expired Passports',
                'expiring': 'Expiring Soon Passports',
                'active': 'Active Passports'
            };
            const statusText = statusTitles[window.currentPassportStatus] || 'Passports';
            monthlyContainer.innerHTML = `<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> ${statusText} - Monthly Breakdown</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No monthly data available</p>`;
        }
    } else {
        // Get fresh reference again
        monthlyContainer = document.getElementById('passportMonthlyChart')?.parentElement;
        if (monthlyContainer) {
            const statusTitles = {
                'expired': 'Expired Passports',
                'expiring': 'Expiring Soon Passports',
                'active': 'Active Passports'
            };
            const statusText = statusTitles[window.currentPassportStatus] || 'Passports';
            monthlyContainer.innerHTML = `<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> ${statusText} - Monthly Breakdown</div><canvas id="passportMonthlyChart"></canvas>`;
        }
        
        const monthlyLabels = monthlyData.map(item => item.label);
        const monthlyCounts = monthlyData.map(item => item.count);
        
        const monthlyCtx = document.getElementById('passportMonthlyChart')?.getContext('2d');
        if (monthlyCtx) {
            passportMonthlyChart = new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Number of Employees',
                        data: monthlyCounts,
                        backgroundColor: 'rgba(153, 102, 255, 0.7)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(153, 102, 255, 0.9)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Employees: ${context.raw}`;
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#333',
                            font: { weight: 'bold', size: 11 },
                            formatter: function(value) {
                                return value > 0 ? value : '';
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
            
            const totalMonthly = monthlyCounts.reduce((a, b) => a + b, 0);
            // Get fresh container reference
            const currentMonthlyContainer = document.getElementById('passportMonthlyChart')?.parentElement;
            const monthlyTitle = currentMonthlyContainer?.querySelector('.chart-title');
            if (monthlyTitle) {
                const statusTitles = {
                    'expired': 'Expired Passports',
                    'expiring': 'Expiring Soon Passports',
                    'active': 'Active Passports'
                };
                const statusText = statusTitles[window.currentPassportStatus] || 'Passports';
                monthlyTitle.innerHTML = `<i class="fa-solid fa-calendar-alt"></i> ${statusText} - Monthly Breakdown <small style="opacity: 0.7;">(Total: ${totalMonthly})</small>`;
            }
        }
    }
    
    console.log('All passport charts created successfully');
}