// Global variables
let expenseChart = null;
let expenses = [];
let currentYear = new Date().getFullYear();
// Sample data for demonstration
const sampleData = [
    { id: 1, category: 'Food & Dining', amount: 450.00, month: 1, year: 2024, date: '2024-01-15' },
    { id: 2, category: 'Transportation', amount: 220.00, month: 1, year: 2024, date: '2024-01-20' },
    { id: 3, category: 'Shopping', amount: 380.00, month: 2, year: 2024, date: '2024-02-05' },
    { id: 4, category: 'Bills & Utilities', amount: 650.00, month: 2, year: 2024, date: '2024-02-10' },
    { id: 5, category: 'Food & Dining', amount: 520.00, month: 3, year: 2024, date: '2024-03-12' },
    { id: 6, category: 'Entertainment', amount: 180.00, month: 3, year: 2024, date: '2024-03-25' },
    { id: 7, category: 'Healthcare', amount: 320.00, month: 4, year: 2024, date: '2024-04-08' },
    { id: 8, category: 'Travel', amount: 890.00, month: 4, year: 2024, date: '2024-04-20' },
    // November 2025 sample data
    { id: 9, category: 'Food & Dining', amount: 585.00, month: 11, year: 2025, date: '2025-11-05' },
    { id: 10, category: 'Transportation', amount: 245.00, month: 11, year: 2025, date: '2025-11-08' },
    { id: 11, category: 'Shopping', amount: 420.00, month: 11, year: 2025, date: '2025-11-15' },
    { id: 12, category: 'Bills & Utilities', amount: 720.00, month: 11, year: 2025, date: '2025-11-18' },
    { id: 13, category: 'Entertainment', amount: 155.00, month: 11, year: 2025, date: '2025-11-22' },
    { id: 14, category: 'Healthcare', amount: 180.00, month: 11, year: 2025, date: '2025-11-25' },
    { id: 15, category: 'Other', amount: 95.00, month: 11, year: 2025, date: '2025-11-28' },
];
// Initialize application
function initApp() {
    // Set current year in form
    document.getElementById('year').value = currentYear;
    
    // Load data from localStorage or use sample data
    loadExpenses();
    
    // Initialize chart
    initChart();
    
    // Update stats
    updateStats();
    
    // Render table
    renderExpensesTable();
    
    // Set up event listeners
    setupEventListeners();
    
    // Show welcome toast on first load
    if (!localStorage.getItem('expenseTrackerVisited')) {
        showToast('Welcome!', 'This is a demo version using localStorage. In production, connect to MySQL backend.', 'info');
        localStorage.setItem('expenseTrackerVisited', 'true');
    }
    
    // Set chart year to 2025 if data exists
    if (expenses.some(exp => exp.year === 2025)) {
        const chartYearSelect = document.getElementById('chartYear');
        if (chartYearSelect) {
            chartYearSelect.value = '2025';
            updateChart();
        }
    }
}
// Load expenses from localStorage
function loadExpenses() {
    const saved = localStorage.getItem('expenses');
    if (saved) {
        expenses = JSON.parse(saved);
    } else {
        // Use sample data for first time users
        expenses = sampleData;
        saveExpenses();
    }
}

// Save expenses to localStorage (simulating MySQL)
function saveExpenses() {
    localStorage.setItem('expenses', JSON.stringify(expenses));
    // In production, this would be an AJAX call to PHP/MySQL backend
    console.log('Expenses saved to localStorage (simulating MySQL insert/update)');
}

// Initialize Chart.js
function initChart() {
    const ctx = document.getElementById('expenseChart').getContext('2d');
    
    // Prepare data
    const monthlyData = getMonthlyData();
    
    // Chart configuration
    const config = {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Monthly Expenses (€)',
                data: monthlyData,
                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
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
                            return '€' + context.parsed.y.toFixed(2);
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
    
    expenseChart = new Chart(ctx, config);
}

// Get monthly data for chart
function getMonthlyData() {
    const year = document.getElementById('chartYear')?.value || currentYear;
    const monthlyData = new Array(12).fill(0);
    
    expenses
        .filter(exp => exp.year == year)
        .forEach(exp => {
            monthlyData[exp.month - 1] += exp.amount;
        });
    
    return monthlyData;
}

// Update chart data
function updateChart() {
    if (expenseChart) {
        expenseChart.data.datasets[0].data = getMonthlyData();
        expenseChart.update('active');
    }
}
// Update statistics cards
function updateStats() {
    // Prioritize showing November 2025 stats if data exists
    const targetMonth = expenses.some(exp => exp.year === 2025 && exp.month === 11) ? 11 : new Date().getMonth() + 1;
    const targetYear = expenses.some(exp => exp.year === 2025) ? 2025 : new Date().getFullYear();
    
    // Current month total
    const thisMonthTotal = expenses
        .filter(exp => exp.month === targetMonth && exp.year === targetYear)
        .reduce((sum, exp) => sum + exp.amount, 0);
    
    // Average monthly for target year
    const yearData = expenses.filter(exp => exp.year === targetYear);
    const monthlyTotals = new Array(12).fill(0);
    yearData.forEach(exp => {
        monthlyTotals[exp.month - 1] += exp.amount;
    });
    
    const nonZeroMonths = monthlyTotals.filter(total => total > 0);
    const averageMonthly = nonZeroMonths.length > 0 
        ? nonZeroMonths.reduce((sum, total) => sum + total, 0) / nonZeroMonths.length 
        : 0;
    
    // Unique categories
    const uniqueCategories = [...new Set(expenses.map(exp => exp.category))].length;
    
    // Update cards
    document.querySelectorAll('expense-stats-card')[0].setAttribute('value', `€${thisMonthTotal.toFixed(2)}`);
    document.querySelectorAll('expense-stats-card')[1].setAttribute('value', `€${averageMonthly.toFixed(2)}`);
    document.querySelectorAll('expense-stats-card')[2].setAttribute('value', uniqueCategories.toString());
}
// Render expenses table
function renderExpensesTable() {
    const tbody = document.getElementById('expensesTable');
    const sortedExpenses = [...expenses].sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 10);
    
    if (sortedExpenses.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-secondary-500 dark:text-secondary-400">
                    <div class="flex flex-col items-center gap-3">
                        <i data-feather="inbox" class="w-12 h-12 opacity-50"></i>
                        No expenses recorded yet. Add your first expense!
                    </div>
                </td>
            </tr>
        `;
        feather.replace();
        return;
    }
    
    tbody.innerHTML = sortedExpenses.map(expense => `
        <tr class="hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors">
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="font-medium">${getMonthName(expense.month)} ${expense.year}</span>
                    <span class="text-xs text-secondary-500">${formatDate(expense.date)}</span>
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded-full text-xs font-medium">
                    ${expense.category}
                </span>
            </td>
            <td class="px-4 py-3 font-semibold text-primary-600 dark:text-primary-400">
                €${expense.amount.toFixed(2)}
            </td>
            <td class="px-4 py-3">
                <button onclick="deleteExpense(${expense.id})" class="delete-btn text-secondary-500 hover:text-red-500 transition-colors" title="Delete expense">
                    <i data-feather="trash-2" class="w-4 h-4"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    feather.replace();
}

// Get month name
function getMonthName(month) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return months[month - 1];
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
}

// Delete expense
function deleteExpense(id) {
    if (confirm('Are you sure you want to delete this expense?')) {
        expenses = expenses.filter(exp => exp.id !== id);
        saveExpenses();
        updateChart();
        updateStats();
        renderExpensesTable();
        showToast('Success!', 'Expense deleted successfully.', 'success');
    }
}

// Show toast notification
function showToast(title, message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');
    
    // Configure based on type
    const configs = {
        success: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-600 dark:text-green-400', icon: 'check-circle' },
        error: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400', icon: 'x-circle' },
        info: { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-600 dark:text-blue-400', icon: 'info' },
        warning: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-600 dark:text-yellow-400', icon: 'alert-triangle' }
    };
    
    const config = configs[type];
    
    toastIcon.className = `w-10 h-10 rounded-full flex items-center justify-center ${config.bg} ${config.text}`;
    toastIcon.innerHTML = `<i data-feather="${config.icon}" class="w-5 h-5"></i>`;
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    
    // Show toast
    toast.classList.remove('hidden', 'translate-y-4', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
    toast.classList.remove('border-transparent');
    toast.classList.add(type === 'success' ? 'border-green-500' : type === 'error' ? 'border-red-500' : type === 'warning' ? 'border-yellow-500' : 'border-blue-500');
    
    feather.replace();
    
    // Hide after 4 seconds
    setTimeout(() => {
        toast.classList.add('translate-y-4', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
        setTimeout(() => {
            toast.classList.add('hidden');
        }, 300);
    }, 4000);
}

// Setup event listeners
function setupEventListeners() {
    // Form submission
    document.getElementById('expenseForm').addEventListener('submit', handleAddExpense);
    
    // Chart year selection
    document.getElementById('chartYear').addEventListener('change', updateChart);
    
    // Refresh button
    document.getElementById('refreshChart').addEventListener('click', () => {
        updateChart();
        showToast('Chart refreshed!', 'Data updated successfully.', 'success');
    });
    
    // Theme toggle (if added to navbar)
    document.addEventListener('theme-toggle', () => {
        // Re-render chart with new theme colors
        setTimeout(() => {
            if (expenseChart) {
                expenseChart.destroy();
                initChart();
            }
        }, 100);
    });
}
// Handle add expense form
function handleAddExpense(e) {
    e.preventDefault();
    
    const day = parseInt(document.getElementById('day').value);
    const month = parseInt(document.getElementById('month').value);
    const year = parseInt(document.getElementById('year').value);
    const time = document.getElementById('time').value;
    
    // Build date string in YYYY-MM-DD format
    const date = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    
    const formData = {
        category: document.getElementById('category').value,
        amount: parseFloat(document.getElementById('amount').value),
        day: day,
        month: month,
        year: year,
        time: time,
        date: date
    };
// Create new expense
    const newExpense = {
        id: Date.now(), // Simple ID generation
        ...formData
    };
    
    // Add to expenses array
    expenses.push(newExpense);
    
    // Save to localStorage (simulating MySQL)
    saveExpenses();
    
    // Update UI
    updateChart();
    updateStats();
    renderExpensesTable();
    
    // Reset form
    document.getElementById('expenseForm').reset();
    document.getElementById('year').value = currentYear;
    // Show success message
    showToast('Expense Added!', `${formData.category} - €${formData.amount.toFixed(2)}`, 'success');
}

// Load theme preference and apply it
function applySavedTheme() {
    const savedTheme = localStorage.getItem('theme');
    const isDark = savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches);
    
    if (isDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

// Call applySavedTheme on initial load
document.addEventListener('DOMContentLoaded', () => {
    applySavedTheme();
});
// Export data to JSON (simulating MySQL export)
function exportData() {
    const dataStr = JSON.stringify(expenses, null, 2);
    const blob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `expenses_${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    showToast('Export Complete!', 'Data exported as JSON file.', 'info');
}

// In production, these functions would be replaced with actual AJAX calls to PHP backend:
/*
// Example PHP/MySQL Backend Implementation:
// File: api/expenses.php
<?php
header('Content-Type: application/json');
$pdo = new PDO('mysql:host=localhost;dbname=expenses_db', 'username', 'password');

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $stmt = $pdo->query('SELECT * FROM expenses ORDER BY date DESC');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO expenses (category, amount, month, year, date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['category'], $data['amount'], $data['month'], $data['year'], $data['date']]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;
        
    case 'DELETE':
        $id = $_GET['id'];
        $stmt = $pdo->prepare('DELETE FROM expenses WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;
}
?>
*/

// Make functions globally available
window.deleteExpense = deleteExpense;
window.exportData = exportData;