// Dashboard Charts for FCW System
document.addEventListener('DOMContentLoaded', () => {
    
    // ===== WORK PERMIT CHART =====
    const permitCtx = document.getElementById('permitExpiryChart');
    
    if (permitCtx && typeof permitLabels !== 'undefined' && typeof permitCounts !== 'undefined') {
        const permitData = {
            labels: permitLabels,
            datasets: [{
                label: 'Number of Work Permits Expiring',
                data: permitCounts,
                backgroundColor: 'rgba(19, 21, 92, 0.7)',
                borderColor: 'rgba(19, 21, 92, 1)',
                borderWidth: 2,
                tension: 0,
                fill: true
            }]
        };

        const permitConfig = {
            type: 'line',
            data: permitData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Permits: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10
                        },
                        title: {
                            display: true,
                            text: 'Number of Employees'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month & Year'
                        }
                    }
                }
            }
        };

        new Chart(permitCtx.getContext('2d'), permitConfig);
    }

    // ===== DEPARTMENT BAR CHART =====
    const departmentCtx = document.getElementById('departmentChart');

    if (departmentCtx && typeof departmentLabels !== 'undefined' && typeof departmentCounts !== 'undefined') {
        // Generate dynamic colors for each department
        const backgroundColors = departmentLabels.map((_, index) => {
            const hue = (index * 360) / departmentLabels.length;
            return `hsla(${hue}, 70%, 60%, 0.7)`;
        });
        
        const borderColors = departmentLabels.map((_, index) => {
            const hue = (index * 360) / departmentLabels.length;
            return `hsla(${hue}, 70%, 50%, 1)`;
        });

        const departmentData = {
            labels: departmentLabels,
            datasets: [{
                label: 'Number of Employees',
                data: departmentCounts,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 2
            }]
        };

        const departmentConfig = {
            type: 'bar',
            data: departmentData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y', // This changes to horizontal bars
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                // Use context.parsed.x for horizontal bars
                                return 'Employees: ' + context.parsed.x;
                            }
                        }
                    }
                },
                scales: {
                    x: { // Now x-axis shows the count
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10,
                            callback: function(value) {
                                return value; // Format if needed
                            }
                        },
                        title: {
                            display: true,
                            text: 'Number of Employees',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            color: '#333'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: { // Now y-axis shows departments
                        title: {
                            display: true,
                            text: 'Department',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            color: '#333'
                        },
                        ticks: {
                            autoSkip: false,
                            font: {
                                size: 11
                            }
                            // No rotation needed for horizontal bars
                        },
                        grid: {
                            display: false // Remove horizontal grid lines
                        }
                    }
                },
                // Optional: Add animation configuration
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        };
        
        new Chart(departmentCtx.getContext('2d'), departmentConfig);
    }

    // ===== CONTRACT STATUS CHART =====
    const contractCtx = document.getElementById('contractExpiryChart');
    
    if (contractCtx && typeof contractLabels !== 'undefined' && 
        typeof contractExtendCounts !== 'undefined' && 
        typeof contractNotExtendCounts !== 'undefined') {
        
        const contractData = {
            labels: contractLabels,
            datasets: [
                {
                    label: 'EXTEND',
                    data: contractExtendCounts,
                    backgroundColor: 'rgba(33, 136, 56, 0.7)',
                    borderColor: 'rgba(33, 136, 56, 1)',
                    borderWidth: 2,
                    tension: 0,
                    fill: true
                },
                {
                    label: 'NOT EXTEND',
                    data: contractNotExtendCounts,
                    backgroundColor: 'rgba(211, 47, 47, 0.7)',
                    borderColor: 'rgba(211, 47, 47, 1)',
                    borderWidth: 2,
                    tension: 0,
                    fill: true
                }
            ]
        };

        const contractConfig = {
            type: 'line',
            data: contractData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
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
                            stepSize: 5
                        },
                        title: {
                            display: true,
                            text: 'Number of Employees'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month & Year'
                        }
                    }
                }
            }
        };

        new Chart(contractCtx.getContext('2d'), contractConfig);
    }

    // ===== PASSPORT CHART WITH YEAR SELECTOR =====
    const passportCtx = document.getElementById('passportExpiryChart');

    if (passportCtx && typeof passportLabels !== 'undefined' && typeof passportCounts !== 'undefined') {
        
        // Group data by year
        const yearData = {};
        passportLabels.forEach((label, index) => {
            const year = label.match(/\d{4}/)?.[0];
            if (year) {
                if (!yearData[year]) {
                    yearData[year] = { labels: [], counts: [] };
                }
                yearData[year].labels.push(label);
                yearData[year].counts.push(passportCounts[index]);
            }
        });
        
        const years = Object.keys(yearData).sort();
        let currentIndex = 0;
        let chart = null;
        
        // Create controls
        const container = passportCtx.parentElement;
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
        container.insertBefore(controls, passportCtx);
        
        // Update display
        function update() {
            const year = years[currentIndex];
            const data = yearData[year];
            const total = data.counts.reduce((a, b) => a + b, 0);
            
            yearDisplay.innerHTML = `
                <div class="year-text">${year}</div>
                <div class="count-text">${total} passport${total !== 1 ? 's' : ''} expiring</div>
            `;
            
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex === years.length - 1;
            
            if (chart) {
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = data.counts;
                chart.update();
            } else {
                chart = new Chart(passportCtx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Passports Expiring',
                            data: data.counts,
                            backgroundColor: 'rgba(231, 76, 60, 0.6)',
                            borderColor: 'rgba(231, 76, 60, 1)',
                            borderWidth: 2,
                            tension: 0,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            datalabels: {
                                display: true,
                                align: 'top',
                                anchor: 'end',
                                offset: 4,
                                color: '#e74c3c',
                                font: { weight: 'bold', size: 11 },
                                formatter: (value) => value > 0 ? value : ''
                            },
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Passports: ' + context.parsed.y;
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
                                    text: 'Number of Employees' 
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
});