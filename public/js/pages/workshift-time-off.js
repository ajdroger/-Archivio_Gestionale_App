/**
 * WorkShift Time Off Management
 * Handles modal logic for Leave Requests
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initial setup if needed
});

// Global Trigger Functions
window.openRequestModal = function () {
    const modal = document.getElementById('requestModal');
    if (modal) {
        modal.classList.remove('hidden');
    } else {
        console.error('Modal #requestModal not found');
    }
}

window.closeRequestModal = function () {
    const modal = document.getElementById('requestModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Ensure the button in HTML calls this or uses inline class manipulation
// The template already has onclick="document.getElementById('requestModal').classList.remove('hidden')"
// We can formalize it here if we want cleaner HTML, but for now we support both.
