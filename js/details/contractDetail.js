// Contract detail charts
let contractDeptChart = null;
let contractNatChart = null;
let contractMonthlyChart = null;

// Show contract detail view
function showContractDetail(contract) {
    // Check if the card has data (is clickable)
    const card = event?.target?.closest('.stat-card');
    if (card && !card.classList.contains('clickable')) {
        return; // Don't proceed if card is not clickable
    }
    
    const mainHeader = document.getElementById('contract-main-header');
    const mainContent = document.getElementById('contract-main-content');
    const detailView = document.getElementById('contract-detail-view');
    const detailTitle = document.getElementById('contract-detail-title');
    
    // Update title based on contract
    const titles = {
        'extend': 'Contract Extend',
        'not_extend': 'Contract Not Extend'
    };
    detailTitle.textContent = titles[contract];
    
    // Hide main content, show detail view
    mainHeader.style.display = 'none';
    mainContent.style.display = 'none';
    detailView.style.display = 'block';
    
    // Fetch and display data
    fetchContractDetail(contract);
    
    // Scroll to top of the section
    document.getElementById('contract-section').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Update contract card interactivity based on data availability
function updateContractCardInteractivity() {
    // Get the stat cards
    const extendCard = document.querySelector('#contract-main-content .stat-card.completed');
    const notExtendCard = document.querySelector('#contract-main-content .stat-card.expired');
    
    // Get the counts from PHP
    const extendCount = extendCard?.querySelector('.stat-number')?.textContent.replace(/,/g, '') || '0';
    const notExtendCount = notExtendCard?.querySelector('.stat-number')?.textContent.replace(/,/g, '') || '0';
    
    // Update extend card
    if (extendCard) {
        if (parseInt(extendCount) === 0) {
            extendCard.classList.remove('clickable');
            extendCard.style.cursor = 'default';
            extendCard.style.opacity = '0.9';
            extendCard.onclick = null;
            const small = extendCard.querySelector('small');
            if (small) small.textContent = 'No data available';
        } else {
            extendCard.classList.add('clickable');
            extendCard.style.cursor = 'pointer';
            extendCard.style.opacity = '1';
        }
    }
    
    // Update not extend card
    if (notExtendCard) {
        if (parseInt(notExtendCount) === 0) {
            notExtendCard.classList.remove('clickable');
            notExtendCard.style.cursor = 'default';
            notExtendCard.style.opacity = '0.9';
            notExtendCard.onclick = null;
            const small = notExtendCard.querySelector('small');
            if (small) small.textContent = 'No data available';
        } else {
            notExtendCard.classList.add('clickable');
            notExtendCard.style.cursor = 'pointer';
            notExtendCard.style.opacity = '1';
        }
    }
}

// Hide contract detail view
function hideContractDetail() {
    const mainHeader = document.getElementById('contract-main-header');
    const mainContent = document.getElementById('contract-main-content');
    const detailView = document.getElementById('contract-detail-view');
    
    // Show main content, hide detail view
    mainHeader.style.display = 'block';
    mainContent.style.display = 'block';
    detailView.style.display = 'none';
    
    // Destroy existing charts
    if (contractDeptChart) {
        contractDeptChart.destroy();
        contractDeptChart = null;
    }
    if (contractNatChart) {
        contractNatChart.destroy();
        contractNatChart = null;
    }
    if (contractMonthlyChart) {
        contractMonthlyChart.destroy();
        contractMonthlyChart = null;
    }
    
    // Scroll to top of contract section
    document.getElementById('contract-section').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Fetch contract detail data via AJAX
function fetchContractDetail(contract) {
    console.log('Fetching contract detail for:', contract);
    
    fetch(`config/contractDetailSQL.php?contract=${contract}`)
        .then(response => {
            console.log('Contract - Response status:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Contract - Error response text:', text);
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.text();
        })
        .then(text => {
            console.log('Contract - Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Contract - Parsed data:', data);
                
                if (data.success) {
                    createContractDetailCharts(data.department, data.nationality, data.monthly);
                } else {
                    console.error('Contract - Server returned error:', data);
                    showContractError();
                }
            } catch (parseError) {
                console.error('Contract - JSON parse error:', parseError);
                showContractError();
            }
        })
        .catch(error => {
            console.error('Contract - Fetch error:', error);
            showContractError();
        });
}

function showContractError() {
    const deptContainer = document.getElementById('contractDepartmentChart')?.parentElement;
    const natContainer = document.getElementById('contractNationalityChart')?.parentElement;
    const monthlyContainer = document.getElementById('contractMonthlyChart')?.parentElement;
    
    if (deptContainer) {
        deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
    if (natContainer) {
        natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
    if (monthlyContainer) {
        monthlyContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown by Permit Expiry</div><p style="text-align: center; padding: 40px; color: #d9534f;"><i class="fa-solid fa-exclamation-circle"></i><br><br>Error loading data</p>';
    }
}

// Create contract detail charts (PIE + BAR)
function createContractDetailCharts(departmentData, nationalityData, monthlyData) {
    console.log('Creating contract charts with data:', { departmentData, nationalityData, monthlyData });
    
    // Destroy existing charts if they exist
    if (contractDeptChart) {
        contractDeptChart.destroy();
        contractDeptChart = null;
    }
    if (contractNatChart) {
        contractNatChart.destroy();
        contractNatChart = null;
    }
    if (contractMonthlyChart) {
        contractMonthlyChart.destroy();
        contractMonthlyChart = null;
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
    let deptContainer = document.getElementById('contractDepartmentChart')?.parentElement;
    if (!departmentData || departmentData.length === 0) {
        if (deptContainer) {
            deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No department data available</p>';
        }
    } else {
        deptContainer = document.getElementById('contractDepartmentChart')?.parentElement;
        if (deptContainer) {
            deptContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><canvas id="contractDepartmentChart"></canvas>';
        }
        
        const deptLabels = departmentData.map(item => item.label);
        const deptCounts = departmentData.map(item => item.count);
        const deptColors = generateColors(deptLabels.length);
        const deptHoverColors = generateHoverColors(deptColors);
        
        const deptCtx = document.getElementById('contractDepartmentChart')?.getContext('2d');
        if (deptCtx) {
            contractDeptChart = new Chart(deptCtx, {
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
            const currentDeptContainer = document.getElementById('contractDepartmentChart')?.parentElement;
            const deptTitle = currentDeptContainer?.querySelector('.chart-title');
            if (deptTitle) {
                deptTitle.innerHTML = `<i class="fa-solid fa-building"></i> Employees by Department <small style="opacity: 0.7;">(Total: ${totalDept})</small>`;
            }
        }
    }
    
    // Nationality Chart
    let natContainer = document.getElementById('contractNationalityChart')?.parentElement;
    if (!nationalityData || nationalityData.length === 0) {
        if (natContainer) {
            natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No nationality data available</p>';
        }
    } else {
        natContainer = document.getElementById('contractNationalityChart')?.parentElement;
        if (natContainer) {
            natContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><canvas id="contractNationalityChart"></canvas>';
        }
        
        const natLabels = nationalityData.map(item => item.label);
        const natCounts = nationalityData.map(item => item.count);
        const natColors = generateColors(natLabels.length);
        const natHoverColors = generateHoverColors(natColors);
        
        const natCtx = document.getElementById('contractNationalityChart')?.getContext('2d');
        if (natCtx) {
            contractNatChart = new Chart(natCtx, {
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
            const currentNatContainer = document.getElementById('contractNationalityChart')?.parentElement;
            const natTitle = currentNatContainer?.querySelector('.chart-title');
            if (natTitle) {
                natTitle.innerHTML = `<i class="fa-solid fa-globe"></i> Employees by Nationality <small style="opacity: 0.7;">(Total: ${totalNat})</small>`;
            }
        }
    }
    
    // Monthly Breakdown Bar Chart
    let monthlyContainer = document.getElementById('contractMonthlyChart')?.parentElement;
    if (!monthlyData || monthlyData.length === 0) {
        if (monthlyContainer) {
            monthlyContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown by Permit Expiry</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No monthly data available</p>';
        }
    } else {
        monthlyContainer = document.getElementById('contractMonthlyChart')?.parentElement;
        if (monthlyContainer) {
            monthlyContainer.innerHTML = '<div class="chart-title"><i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown by Permit Expiry</div><canvas id="contractMonthlyChart"></canvas>';
        }
        
        const monthlyLabels = monthlyData.map(item => item.label);
        const monthlyCounts = monthlyData.map(item => item.count);
        
        const monthlyCtx = document.getElementById('contractMonthlyChart')?.getContext('2d');
        if (monthlyCtx) {
            contractMonthlyChart = new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Number of Employees',
                        data: monthlyCounts,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(75, 192, 192, 0.9)'
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
            const currentMonthlyContainer = document.getElementById('contractMonthlyChart')?.parentElement;
            const monthlyTitle = currentMonthlyContainer?.querySelector('.chart-title');
            if (monthlyTitle) {
                monthlyTitle.innerHTML = `<i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown by Permit Expiry <small style="opacity: 0.7;">(Total: ${totalMonthly})</small>`;
            }
        }
    }
    
    console.log('All contract charts created successfully');
}

// Call this function when the page loads to set initial card states
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(updateContractCardInteractivity, 100);
});