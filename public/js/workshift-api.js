/**
 * WorkShift API Client
 * Centralized API handler for WorkShift module
 * Provides class-based interface for shift management interactions.
 */

class WorkShiftAPI {
    constructor(baseUrl) {
        // Ensure baseUrl doesn't end with slash to avoid double slashes if endpoints start with /
        this.baseUrl = baseUrl.replace(/\/$/, '');
        this.headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };
    }

    /**
     * Generic wrapper for fetch requests
     */
    async _request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        console.log(`[WorkShiftAPI] ${options.method || 'GET'} ${url}`);

        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    ...this.headers,
                    ...options.headers
                }
            });

            if (!response.ok) {
                const text = await response.text();
                // Try to parse JSON error if possible
                try {
                    const jsonError = JSON.parse(text);
                    throw new Error(jsonError.error || jsonError.message || `API Error ${response.status}`);
                } catch (e) {
                    throw new Error(text || `Request failed with status ${response.status}`);
                }
            }

            // Handle 204 No Content
            if (response.status === 204) return null;

            return await response.json();
        } catch (error) {
            console.error('[WorkShiftAPI] Request failed:', error);
            throw error;
        }
    }

    /**
     * Fetch shift schedule for a specific date range
     * @param {string} start - YYYY-MM-DD
     * @param {string} end - YYYY-MM-DD
     */
    async getSchedule(start, end) {
        return this._request(`/workshift/api/shifts?start=${start}&end=${end}`);
    }

    /**
     * Save (Create or Update) a shift
     * @param {Object} shiftData 
     */
    async saveShift(shiftData) {
        return this._request('/workshift/api/shifts/save', {
            method: 'POST',
            body: JSON.stringify(shiftData)
        });
    }

    /**
     * Delete a shift by ID
     * @param {number|string} id 
     */
    async deleteShift(id) {
        return this._request(`/workshift/api/shifts/${id}`, {
            method: 'DELETE'
        });
    }

    /**
     * Reset shifts (Day or Week scope)
     * @param {Object} payload - { scope: 'day'|'week', date: '...', start_date: '...', end_date: '...' }
     */
    async resetShifts(payload) {
        return this._request('/workshift/api/shifts/reset', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
    }

    /**
     * AI Optimization Request
     * @param {string} mode - 'current_week', 'next_week', etc.
     */
    async optimizeSchedule(mode) {
        return this._request('/workshift/api/optimize', {
            method: 'POST',
            body: JSON.stringify({ mode })
        });
    }

    /**
     * Get Team Members (Employees)
     */
    async getEmployees() {
        return this._request('/workshift/api/employees');
    }

    /**
    * Save (Create/Update) Employee
    */
    async saveEmployee(data) {
        return this._request('/workshift/api/employees/save', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * Delete Employee
     */
    async deleteEmployee(id) {
        return this._request(`/workshift/api/employees/${id}`, {
            method: 'DELETE'
        });
    }
}

// Expose to window
window.WorkShiftAPI = WorkShiftAPI;
