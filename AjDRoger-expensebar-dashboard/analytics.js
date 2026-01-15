// Analytics-specific JavaScript
let categoryChart = null;
let yearlyChart = null;
// Initialize analytics page
function initAnalytics() {
    // Load expenses data
    loadExpenses();
    
    // Initialize charts
    initCategoryChart();
    initYearlyChart();
    
    // Render category statistics table
    renderCategoryStats();
    
    // Show analytics-specific welcome message on first visit
    if (!localStorage.getItem('analyticsPageVisited')) {
        showToast('Analytics Overview', 'Welcome to the advanced analytics page!', 'info');
        localStorage.setItem('analyticsPageVisited', 'true');
    }
    
    // Highlight November 2025 data if available
    if (expenses.some(exp => exp.year === 2025 && exp.month === 11)) {
        showToast('New Data Available', 'November 2025 expenses are now visible in the charts!', 'info');
    }
}
// Initialize category breakdown pie chart
function initCategoryChart() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    // Get category data
    const categoryData = getCategoryData();
    
    const config = {
        type: 'doughnut',
        data: {
            labels: categoryData.labels,
            datasets: [{
                data: categoryData.values,
                backgroundColor: [
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(14, 165, 233, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(107, 114, 128, 0.8)',
                    'rgba(20, 184, 166, 0.8)'
                ],
                borderWidth: 2,
                borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#111827',
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 15,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            return data.labels.map((label, i) => ({
                                text: `${label} (€${data.datasets[0].data[i]?.toFixed(2)})`,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                strokeStyle: data.datasets[0].backgroundColor[i],
                                index: i
                            }));
                        }
                    }
                },
                tooltip: {
                    backgroundColor: document.documentElement.classList.contains('dark') ? 'rgba(3, 7, 18, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                    titleColor: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#111827',
                    bodyColor: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                    borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: €${context.parsed.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    };
    
    categoryChart = new Chart(ctx, config);
}

// Initialize yearly trend chart
function initYearlyChart() {
    const ctx = document.getElementById('yearlyChart').getContext('2d');
    
    // Get yearly data
    const yearlyData = getYearlyData();
    
    const config = {
        type: 'line',
        data: {
            labels: yearlyData.years,
            datasets: [{
                label: 'Yearly Total (€)',
                data: yearlyData.values,
                borderColor: 'rgba(99, 102, 241, 1)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                pointBorderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: 'rgba(99, 102, 241, 1)',
                pointHoverBorderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#111827',
                        font: {
                            size: 14,
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: document.documentElement.classList.contains('dark') ? 'rgba(3, 7, 18, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                    titleColor: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#111827',
                    bodyColor: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                    borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `Total: €${context.parsed.y.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? 'rgba(55, 65, 81, 0.5)' : 'rgba(229, 231, 235, 0.8)'
                    },
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                        callback: function(value) {
                            return '€' + value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };
    
    yearlyChart = new Chart(ctx, config);
}
// Get category data
function getCategoryData() {
    const categoryTotals = {};
    
    expenses.forEach(expense => {
        if (categoryTotals[expense.category]) {
            categoryTotals[expense.category] += expense.amount;
        } else {
            categoryTotals[expense.category] = expense.amount;
        }
    });
    
    // Sort by amount descending
    const sortedCategories = Object.entries(categoryTotals)
        .sort(([,a], [,b]) => b - a)
        .reduce((r, [k, v]) => ({ ...r, [k]: v }), {});
    
    return {
        labels: Object.keys(sortedCategories),
        values: Object.values(sortedCategories)
    };
}
// Get yearly data
function getYearlyData() {
    const yearTotals = {};
    
    expenses.forEach(expense => {
        if (yearTotals[expense.year]) {
            yearTotals[expense.year] += expense.amount;
        } else {
            yearTotals[expense.year] = expense.amount;
        }
    });
    
    // Ensure 2025 is included if it has data
    if (!yearTotals[2025] && expenses.some(exp => exp.year === 2025)) {
        yearTotals[2025] = 0;
    }
    
    // Sort by year
    const sortedYears = Object.entries(yearTotals)
        .sort(([a], [b]) => a - b)
        .reduce((r, [k, v]) => ({ ...r, [k]: v }), {});
    
    return {
        years: Object.keys(sortedYears),
        values: Object.values(sortedYears)
    };
}
// Render category statistics table
function renderCategoryStats() {
    const tbody = document.getElementById('categoryStatsTable');
    
    if (expenses.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-secondary-500 dark:text-secondary-400">
                    <div class="flex flex-col items-center gap-3">
                        <i data-feather="inbox" class="w-12 h-12 opacity-50"></i>
                        No data available for analysis
                    </div>
                </td>
            </tr>
        `;
        feather.replace();
        return;
    }
    
    // Calculate category statistics
    const categoryStats = {};
    expenses.forEach(expense => {
        if (!categoryStats[expense.category]) {
            categoryStats[expense.category] = {
                total: 0,
                count: 0
            };
        }
        categoryStats[expense.category].total += expense.amount;
        categoryStats[expense.category].count += 1;
    });
    
    // Calculate total for percentages
    const grandTotal = Object.values(categoryStats).reduce((sum, stat) => sum + stat.total, 0);
    
    // Sort by total amount descending
    const sortedCategories = Object.entries(categoryStats)
        .sort(([,a], [,b]) => b.total - a.total)
        .map(([category, stats]) => ({
            category,
            ...stats,
            average: stats.total / stats.count,
            percentage: (stats.total / grandTotal) * 100
        }));
    
    tbody.innerHTML = sortedCategories.map(stat => `
        <tr class="hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors">
            <td class="px-4 py-3">
                <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded-full text-xs font-medium">
                    ${stat.category}
                </span>
            </td>
            <td class="px-4 py-3 font-semibold text-primary-600 dark:text-primary-400">
                €${stat.total.toFixed(2)}
            </td>
            <td class="px-4 py-3 text-secondary-700 dark:text-secondary-300">
                ${stat.count}
            </td>
            <td class="px-4 py-3 text-secondary-700 dark:text-secondary-300">
                €${stat.average.toFixed(2)}
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-200 dark:bg-secondary-700 rounded-full h-2">
                        <div class="bg-primary-500 h-2 rounded-full" style="width: ${stat.percentage}%"></div>
                    </div>
                    <span class="text-sm font-medium text-secondary-600 dark:text-secondary-400">
                        ${stat.percentage.toFixed(1)}%
                    </span>
                </div>
            </td>
        </tr>
    `).join('');
    
    feather.replace();
}
// Make function globally available
window.initAnalytics = initAnalytics;