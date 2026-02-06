// EOC/Runaway Detail Charts and Functions
let eocOverviewChart = null;
let eocDepartmentDetailChart = null;
let eocNationalityDetailChart = null;

// Initialize EOC Overview Chart - WITH YEAR SELECTOR
document.addEventListener('DOMContentLoaded', () => {
    const eocMonthlyCtx = document.getElementById('eocMonthlyChart');
    
    if (eocMonthlyCtx && typeof eocHireDateLabels !== 'undefined') {
        
        // Group data by year
        const yearData = {};
        eocHireDateLabels.forEach((label, index) => {
            const year = label.match(/\d{4}/)?.[0];
            if (year) {
                if (!yearData[year]) {
                    yearData[year] = { labels: [], eocCounts: [], runawayCounts: [] };
                }
                yearData[year].labels.push(label);
                yearData[year].eocCounts.push(eocHireDateEOCCounts[index]);
                yearData[year].runawayCounts.push(eocHireDateRunawayCounts[index]);
            }
        });
        
        const years = Object.keys(yearData).sort();
        let currentIndex = 0;
        
        // Create controls
        const container = eocMonthlyCtx.parentElement;
        const controls = document.createElement('div');
        controls.className = 'chart-controls';
        
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '&#8249; Previous';
        
        const yearDisplay = document.createElement('div');
        yearDisplay.className = 'year-display';
        
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = 'Next &#8250;';
        
        controls.appendChild(prevBtn);
        controls.appendChild(yearDisplay);
        controls.appendChild(nextBtn);
        container.insertBefore(controls, eocMonthlyCtx);
        
        // Update display
        function update() {
            const year = years[currentIndex];
            const data = yearData[year];
            const totalEOC = data.eocCounts.reduce((a, b) => a + b, 0);
            const totalRunaway = data.runawayCounts.reduce((a, b) => a + b, 0);
            const totalAll = totalEOC + totalRunaway;
            
            yearDisplay.innerHTML = `
                <div class="year-text">${year}</div>
                <div class="count-text">${totalAll} record${totalAll !== 1 ? 's' : ''} (EOC: ${totalEOC}, Runaway: ${totalRunaway})</div>
            `;
            
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex === years.length - 1;
            
            if (eocOverviewChart) {
                eocOverviewChart.data.labels = data.labels;
                eocOverviewChart.data.datasets[0].data = data.eocCounts;
                eocOverviewChart.data.datasets[1].data = data.runawayCounts;
                eocOverviewChart.update();
            } else {
                const eocData = {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'EOC',
                            data: data.eocCounts,
                            backgroundColor: 'rgba(211, 47, 47, 0.7)',
                            borderColor: 'rgba(211, 47, 47, 1)',
                            borderWidth: 2,
                            tension: 0,
                            fill: true
                        },
                        {
                            label: 'Runaway',
                            data: data.runawayCounts,
                            backgroundColor: 'rgba(245, 124, 0, 0.7)',
                            borderColor: 'rgba(245, 124, 0, 1)',
                            borderWidth: 2,
                            tension: 0,
                            fill: true
                        }
                    ]
                };

                eocOverviewChart = new Chart(eocMonthlyCtx.getContext('2d'), {
                    type: 'line',
                    data: eocData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            datalabels: {
                                display: true,
                                align: 'top',
                                anchor: 'end',
                                offset: 4,
                                font: { weight: 'bold', size: 11 },
                                formatter: (value) => value > 0 ? value : ''
                            },
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 5,
                                    callback: (value) => Number.isInteger(value) ? value : ''
                                },
                                title: {
                                    display: true,
                                    text: 'Number of Records'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            }
                        }
                    }
                });
            }
        }
        
        // Navigation
        prevBtn.onclick = () => {
            if (currentIndex > 0) {
                currentIndex--;
                update();
            }
        };
        
        nextBtn.onclick = () => {
            if (currentIndex < years.length - 1) {
                currentIndex++;
                update();
            }
        };
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') prevBtn.click();
            if (e.key === 'ArrowRight') nextBtn.click();
        });
        
        // Start
        update();
    }
    
    // Update card interactivity on load
    setTimeout(updateEocCardInteractivity, 100);
});

// Update EOC card interactivity based on data availability
function updateEocCardInteractivity() {
    const eocCard = document.querySelector('#eoc-main-content .stat-card.eoc-card');
    const runawayCard = document.querySelector('#eoc-main-content .stat-card.runaway-card');
    
    const eocCount = eocCard?.querySelector('.stat-number')?.textContent.replace(/,/g, '') || '0';
    const runawayCount = runawayCard?.querySelector('.stat-number')?.textContent.replace(/,/g, '') || '0';
    
    // Update EOC card
    if (eocCard) {
        if (parseInt(eocCount) === 0) {
            eocCard.classList.remove('clickable');
            eocCard.style.cursor = 'default';
            eocCard.style.opacity = '0.9';
            eocCard.onclick = null;
            const small = eocCard.querySelector('small');
            if (small) small.textContent = 'No data available';
        } else {
            eocCard.classList.add('clickable');
            eocCard.style.cursor = 'pointer';
            eocCard.style.opacity = '1';
        }
    }
    
    // Update Runaway card
    if (runawayCard) {
        if (parseInt(runawayCount) === 0) {
            runawayCard.classList.remove('clickable');
            runawayCard.style.cursor = 'default';
            runawayCard.style.opacity = '0.9';
            runawayCard.onclick = null;
            const small = runawayCard.querySelector('small');
            if (small) small.textContent = 'No data available';
        } else {
            runawayCard.classList.add('clickable');
            runawayCard.style.cursor = 'pointer';
            runawayCard.style.opacity = '1';
        }
    }
}

// Show EOC Detail View
function showEocDetail(type) {
    // Check if the card has data (is clickable)
    const card = event?.target?.closest('.stat-card');
    if (card && !card.classList.contains('clickable')) {
        return; // Don't proceed if card is not clickable
    }
    
    const mainHeader = document.getElementById('eoc-main-header');
    const mainContent = document.getElementById('eoc-main-content');
    const detailView = document.getElementById('eoc-detail-view');
    const detailTitle = document.getElementById('eoc-detail-title');
    
    // Update title based on type
    const titles = {
        'eoc': 'End of Contract (EOC)',
        'runaway': 'Runaway'
    };
    detailTitle.innerHTML = type === 'eoc' 
        ? titles[type]
        : titles[type];

    window.currentEocType = type;
    
    // Hide main content, show detail view
    mainHeader.style.display = 'none';
    mainContent.style.display = 'none';
    detailView.style.display = 'block';
    
    // Load detail charts
    loadEocDepartmentChart(type);
    loadEocNationalityChart(type);
    
    // Scroll to top of the section
    document.getElementById('eoc-runaway-section').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Hide EOC Detail View
function hideEocDetail() {
    const mainHeader = document.getElementById('eoc-main-header');
    const mainContent = document.getElementById('eoc-main-content');
    const detailView = document.getElementById('eoc-detail-view');
    
    // Show main content, hide detail view
    mainHeader.style.display = 'block';
    mainContent.style.display = 'block';
    detailView.style.display = 'none';
    
    // Destroy existing charts
    if (eocDepartmentDetailChart) {
        eocDepartmentDetailChart.destroy();
        eocDepartmentDetailChart = null;
    }
    if (eocNationalityDetailChart) {
        eocNationalityDetailChart.destroy();
        eocNationalityDetailChart = null;
    }
    
    // Scroll to top of EOC/Runaway section
    document.getElementById('eoc-runaway-section').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Load Department Chart
function loadEocDepartmentChart(type) {
    const ctx = document.getElementById('eocDepartmentChart');
    if (!ctx) return;
    
    if (eocDepartmentDetailChart) {
        eocDepartmentDetailChart.destroy();
        eocDepartmentDetailChart = null;
    }
    
    const isEOC = (type === 'eoc');
    const dataCounts = isEOC ? eocDeptEOCCounts : eocDeptRunawayCounts;
    
    // Check if there's no data
    const totalCount = dataCounts.reduce((a, b) => a + b, 0);
    const container = ctx.parentElement;
    
    if (!dataCounts || totalCount === 0) {
        if (container) {
            container.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No department data available</p>';
        }
        return;
    }
    
    // Recreate canvas if needed
    if (container) {
        container.innerHTML = '<div class="chart-title"><i class="fa-solid fa-building"></i> Employees by Department</div><canvas id="eocDepartmentChart"></canvas>';
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
    
    const colors = generateColors(eocDeptLabels.length);
    const hoverColors = generateHoverColors(colors);
    
    const newCtx = document.getElementById('eocDepartmentChart')?.getContext('2d');
    if (newCtx) {
        eocDepartmentDetailChart = new Chart(newCtx, {
            type: 'doughnut',
            data: {
                labels: eocDeptLabels,
                datasets: [{
                    data: dataCounts,
                    backgroundColor: colors,
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverBackgroundColor: hoverColors,
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        position: 'right', 
                        labels: { 
                            padding: 20, 
                            usePointStyle: true, 
                            pointStyle: 'circle', 
                            font: { size: 11 } 
                        } 
                    },
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
        
        const currentContainer = document.getElementById('eocDepartmentChart')?.parentElement;
        const title = currentContainer?.querySelector('.chart-title');
        if (title) {
            title.innerHTML = `<i class="fa-solid fa-building"></i> Employees by Department <small style="opacity: 0.7;">(Total: ${totalCount})</small>`;
        }
    }
}

// Load Nationality Chart
function loadEocNationalityChart(type) {
    const ctx = document.getElementById('eocNationalityChart');
    if (!ctx) return;
    
    if (eocNationalityDetailChart) {
        eocNationalityDetailChart.destroy();
        eocNationalityDetailChart = null;
    }
    
    const isEOC = (type === 'eoc');
    const dataCounts = isEOC ? eocNatEOCCounts : eocNatRunawayCounts;
    
    // Check if there's no data
    const totalCount = dataCounts.reduce((a, b) => a + b, 0);
    const container = ctx.parentElement;
    
    if (!dataCounts || totalCount === 0) {
        if (container) {
            container.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><p style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox"></i><br><br>No nationality data available</p>';
        }
        return;
    }
    
    // Recreate canvas if needed
    if (container) {
        container.innerHTML = '<div class="chart-title"><i class="fa-solid fa-globe"></i> Employees by Nationality</div><canvas id="eocNationalityChart"></canvas>';
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
    
    const colors = generateColors(eocNatLabels.length);
    const hoverColors = generateHoverColors(colors);
    
    const newCtx = document.getElementById('eocNationalityChart')?.getContext('2d');
    if (newCtx) {
        eocNationalityDetailChart = new Chart(newCtx, {
            type: 'doughnut',
            data: {
                labels: eocNatLabels,
                datasets: [{
                    data: dataCounts,
                    backgroundColor: colors,
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverBackgroundColor: hoverColors,
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        position: 'right', 
                        labels: { 
                            padding: 20, 
                            usePointStyle: true, 
                            pointStyle: 'circle', 
                            font: { size: 11 } 
                        } 
                    },
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
        
        const currentContainer = document.getElementById('eocNationalityChart')?.parentElement;
        const title = currentContainer?.querySelector('.chart-title');
        if (title) {
            title.innerHTML = `<i class="fa-solid fa-globe"></i> Employees by Nationality <small style="opacity: 0.7;">(Total: ${totalCount})</small>`;
        }
    }
}