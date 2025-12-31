    let permitDeptChart = null;
    let permitNatChart = null;
    let permitMonthlyChart = null;

// Show permit detail view
function showPermitDetail(status) {
    const mainHeader = document.getElementById('permit-main-header');
    const mainContent = document.getElementById('permit-main-content');
    const detailView = document.getElementById('permit-detail-view');
    const detailTitle = document.getElementById('permit-detail-title');
    
    // Update title based on status
    const titles = {
        'expired': 'Expired Permits',
        'expiring': 'Expiring Soon Permits',
        'active': 'Active Permits'
    };
    detailTitle.textContent = titles[status];

    window.currentPermitStatus = status;
    
    // Hide main content, show detail view
    mainHeader.style.display = 'none';
    mainContent.style.display = 'none';
    detailView.style.display = 'block';
    
    // Fetch and display data
    fetchPermitDetail(status);
    
    // Scroll to top of the section
    document.getElementById('work-permit-section').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Hide permit detail view
function hidePermitDetail() {
    const mainHeader = document.getElementById('permit-main-header');
    const mainContent = document.getElementById('permit-main-content');
    const detailView = document.getElementById('permit-detail-view');
    
    // Show main content, hide detail view
    mainHeader.style.display = 'block';
    mainContent.style.display = 'block';
    detailView.style.display = 'none';
    
    // Destroy existing charts
    if (permitDeptChart) {
        permitDeptChart.destroy();
        permitDeptChart = null;
    }
    if (permitNatChart) {
        permitNatChart.destroy();
        permitNatChart = null;
    }
    if (permitMonthlyChart) {
        permitMonthlyChart.destroy();
        permitMonthlyChart = null;
    }
    
    // Scroll to top of work permit section
    document.getElementById('work-permit-section').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Fetch permit detail data via AJAX
function fetchPermitDetail(status) {
    console.log('Fetching permit detail for status:', status);
    
    fetch(`config/permitDetailSQL.php?status=${status}`)
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error response text:', text);
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                if (data.success) {
                    createDetailCharts(data.department, data.nationality, data.monthly);
                } else {
                    console.error('Server returned error:', data);
                    showError();
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                showError();
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showError();
        });
}

function showError() {
    const deptContainer = document.getElementById('permitDepartmentChart')?.parentElement;
    const natContainer = document.getElementById('permitNationalityChart')?.parentElement;
    const monthlyContainer = document.getElementById('permitMonthlyChart')?.parentElement;
    
    if (deptContainer) {
        deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
    if (natContainer) {
        natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
    if (monthlyContainer) {
        monthlyContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
}

// Create detail charts (PIE + BAR)
function createDetailCharts(departmentData, nationalityData, monthlyData) {
    console.log('Creating charts with data:', { departmentData, nationalityData, monthlyData });
    
    // Destroy existing charts if they exist
    if (permitDeptChart) permitDeptChart.destroy();
    if (permitNatChart) permitNatChart.destroy();
    if (permitMonthlyChart) permitMonthlyChart.destroy();
    
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
    const deptContainer = document.getElementById('permitDepartmentChart')?.parentElement;
    if (!departmentData || departmentData.length === 0) {
        if (deptContainer) {
            deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No department data available</p>';
        }
    } else {
        if (!document.getElementById('permitDepartmentChart') && deptContainer) {
            deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><canvas id="permitDepartmentChart"></canvas>';
        }
        
        const deptLabels = departmentData.map(item => item.label);
        const deptCounts = departmentData.map(item => item.count);
        const deptColors = generateColors(deptLabels.length);
        const deptHoverColors = generateHoverColors(deptColors);
        
        const deptCtx = document.getElementById('permitDepartmentChart')?.getContext('2d');
        if (deptCtx) {
            permitDeptChart = new Chart(deptCtx, {
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
            const deptTitle = deptContainer.querySelector('.chart-title');
            if (deptTitle) {
                deptTitle.innerHTML = `<i class="fa-solid fa-building"></i> Employees by Department <small style="opacity: 0.7;">(Total: ${totalDept})</small>`;
            }
        }
    }
    
    // Nationality Chart
    const natContainer = document.getElementById('permitNationalityChart')?.parentElement;
    if (!nationalityData || nationalityData.length === 0) {
        if (natContainer) {
            natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No nationality data available</p>';
        }
    } else {
        if (!document.getElementById('permitNationalityChart') && natContainer) {
            natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><canvas id="permitNationalityChart"></canvas>';
        }
        
        const natLabels = nationalityData.map(item => item.label);
        const natCounts = nationalityData.map(item => item.count);
        const natColors = generateColors(natLabels.length);
        const natHoverColors = generateHoverColors(natColors);
        
        const natCtx = document.getElementById('permitNationalityChart')?.getContext('2d');
        if (natCtx) {
            permitNatChart = new Chart(natCtx, {
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
            const natTitle = natContainer.querySelector('.chart-title');
            if (natTitle) {
                natTitle.innerHTML = `<i class="fa-solid fa-globe"></i> Employees by Nationality <small style="opacity: 0.7;">(Total: ${totalNat})</small>`;
            }
        }
    }
    
    // Monthly Breakdown Bar Chart
    const monthlyContainer = document.getElementById('permitMonthlyChart')?.parentElement;
    if (!monthlyData || monthlyData.length === 0) {
        if (monthlyContainer) {
            const statusTitles = {
                'expired': 'Expired Permits',
                'expiring': 'Expiring Soon Permits',
                'active': 'Active Permits'
            };
            const statusText = statusTitles[window.currentPermitStatus] || 'Permits';
            monthlyContainer.innerHTML = `<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> ${statusText} - Monthly Breakdown</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No monthly data available</p>`;
        }
    } else {
        if (!document.getElementById('permitMonthlyChart') && monthlyContainer) {
            const statusTitles = {
                'expired': 'Expired Permits',
                'expiring': 'Expiring Soon Permits',
                'active': 'Active Permits'
            };
            const statusText = statusTitles[window.currentPermitStatus] || 'Permits';
            monthlyContainer.innerHTML = `<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> ${statusText} - Monthly Breakdown</div><canvas id="permitMonthlyChart"></canvas>`;
        }
        
        const monthlyLabels = monthlyData.map(item => item.label);
        const monthlyCounts = monthlyData.map(item => item.count);
        
        const monthlyCtx = document.getElementById('permitMonthlyChart')?.getContext('2d');
        if (monthlyCtx) {
            permitMonthlyChart = new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Number of Employees',
                        data: monthlyCounts,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(54, 162, 235, 0.9)'
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
            const monthlyTitle = monthlyContainer.querySelector('.chart-title');
            if (monthlyTitle) {
                const statusTitles = {
                    'expired': 'Expired Permits',
                    'expiring': 'Expiring Soon Permits',
                    'active': 'Active Permits'
                };
                const statusText = statusTitles[window.currentPermitStatus] || 'Permits';
                monthlyTitle.innerHTML = `<i class="fa-solid fa-calendar-alt"></i> ${statusText} - Monthly Breakdown <small style="opacity: 0.7;">(Total: ${totalMonthly})</small>`;
            }
        }
    }
    
    console.log('All charts created successfully');
}