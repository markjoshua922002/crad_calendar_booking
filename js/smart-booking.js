class SmartBookingManager {
    constructor() {
        this.availabilityCache = new Map();
        this.initializeEventListeners();
    }

    /**
     * Initialize event listeners for the booking modal
     */
    initializeEventListeners() {
        // Listen for modal open
        document.querySelector('#bookingModal').addEventListener('show.bs.modal', (e) => {
            this.onModalOpen(e);
        });

        // Listen for date/room changes
        document.querySelector('#booking_date').addEventListener('change', (e) => {
            this.updateAvailability();
        });

        document.querySelector('#room_id').addEventListener('change', (e) => {
            this.updateAvailability();
        });

        // Listen for time selection
        document.querySelector('#time_from').addEventListener('change', (e) => {
            this.checkDoubleBooking();
        });

        document.querySelector('#time_to').addEventListener('change', (e) => {
            this.checkDoubleBooking();
        });
    }

    /**
     * Handle modal open event
     */
    async onModalOpen(event) {
        const modal = event.target;
        const date = document.querySelector('#booking_date').value;
        const roomId = document.querySelector('#room_id').value;

        // Show loading state
        this.showLoadingState();

        try {
            // Get available slots
            await this.updateAvailability();
            
            // Initialize time picker with optimal slots
            this.initializeTimePicker();
            
            // Check for potential conflicts
            await this.checkDoubleBooking();
        } catch (error) {
            console.error('Error initializing smart booking:', error);
            this.showError('Failed to initialize smart booking features');
        } finally {
            this.hideLoadingState();
        }
    }

    /**
     * Update availability display
     */
    async updateAvailability() {
        const date = document.querySelector('#booking_date').value;
        const roomId = document.querySelector('#room_id').value;
        const duration = this.calculateDuration();

        // Check cache first
        const cacheKey = `${date}-${roomId}-${duration}`;
        if (this.availabilityCache.has(cacheKey)) {
            this.updateAvailabilityDisplay(this.availabilityCache.get(cacheKey));
            return;
        }

        try {
            const response = await fetch(`/microservices/conflict-resolver/smart_booking_api.php?endpoint=get_available_slots&date=${date}&room_id=${roomId}&duration=${duration}`);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to fetch availability');
            }

            // Cache the results
            this.availabilityCache.set(cacheKey, data);
            
            // Update the display
            this.updateAvailabilityDisplay(data);
        } catch (error) {
            console.error('Error fetching availability:', error);
            this.showError('Failed to fetch available time slots');
        }
    }

    /**
     * Update the availability display in the modal
     */
    updateAvailabilityDisplay(data) {
        const availabilityContainer = document.querySelector('#availability-display');
        const optimalWindows = data.optimal_windows;

        // Clear existing content
        availabilityContainer.innerHTML = '';

        // Create availability timeline
        const timeline = document.createElement('div');
        timeline.className = 'availability-timeline';

        // Add optimal windows
        optimalWindows.forEach(window => {
            const windowElement = this.createTimeWindowElement(window);
            timeline.appendChild(windowElement);
        });

        // Add availability summary
        const summary = document.createElement('div');
        summary.className = 'availability-summary mt-3';
        summary.innerHTML = `
            <h6>Recommended Time Slots:</h6>
            <ul class="list-unstyled">
                ${optimalWindows.map(window => `
                    <li class="mb-2">
                        <span class="badge bg-success me-2">
                            ${this.formatTime(window.start_time)} - ${this.formatTime(window.end_time)}
                        </span>
                        <small class="text-muted">
                            Confidence: ${Math.round(window.score * 100)}%
                        </small>
                    </li>
                `).join('')}
            </ul>
        `;

        availabilityContainer.appendChild(timeline);
        availabilityContainer.appendChild(summary);
    }

    /**
     * Create a visual element for a time window
     */
    createTimeWindowElement(window) {
        const element = document.createElement('div');
        element.className = 'time-window';
        element.style.cssText = `
            position: relative;
            padding: 10px;
            margin: 5px 0;
            background: ${this.getGradientByScore(window.score)};
            border-radius: 4px;
            cursor: pointer;
        `;

        element.innerHTML = `
            <div class="window-time">${this.formatTime(window.start_time)} - ${this.formatTime(window.end_time)}</div>
            <div class="window-score">Optimal Score: ${Math.round(window.score * 100)}%</div>
        `;

        // Add click handler to set time
        element.addEventListener('click', () => {
            document.querySelector('#time_from').value = window.start_time;
            document.querySelector('#time_to').value = window.end_time;
            this.checkDoubleBooking();
        });

        return element;
    }

    /**
     * Check for double bookings
     */
    async checkDoubleBooking() {
        const timeFrom = document.querySelector('#time_from').value;
        const timeTo = document.querySelector('#time_to').value;
        const date = document.querySelector('#booking_date').value;
        const userId = document.querySelector('#user_id').value;
        const departmentId = document.querySelector('#department_id').value;

        if (!timeFrom || !timeTo || !date || !userId || !departmentId) {
            return;
        }

        try {
            const response = await fetch('/microservices/conflict-resolver/smart_booking_api.php?endpoint=check_double_booking', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    department_id: departmentId,
                    start_time: timeFrom,
                    end_time: timeTo,
                    date: date
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to check double booking');
            }

            this.updateDoubleBookingWarning(data);
        } catch (error) {
            console.error('Error checking double booking:', error);
            this.showError('Failed to check for scheduling conflicts');
        }
    }

    /**
     * Update double booking warning display
     */
    updateDoubleBookingWarning(data) {
        const warningContainer = document.querySelector('#double-booking-warning');
        
        if (data.has_double_booking) {
            warningContainer.innerHTML = `
                <div class="alert alert-danger">
                    <h6>⚠️ Scheduling Conflict Detected</h6>
                    <p>You have existing bookings that conflict with this time:</p>
                    <ul>
                        ${data.conflicts.map(conflict => `
                            <li>
                                ${this.formatTime(conflict.time_from)} - ${this.formatTime(conflict.time_to)}
                                ${conflict.room_name ? `(${conflict.room_name})` : ''}
                            </li>
                        `).join('')}
                    </ul>
                    <h6>Recommendations:</h6>
                    <ul>
                        ${data.recommendations.map(rec => `
                            <li>
                                <a href="#" class="recommendation-link" data-start="${rec.suggestions[0].time}">
                                    Try ${this.formatTime(rec.suggestions[0].time)}
                                    (${Math.round(rec.suggestions[0].confidence * 100)}% optimal)
                                </a>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;

            // Add click handlers for recommendations
            warningContainer.querySelectorAll('.recommendation-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const startTime = e.target.dataset.start;
                    document.querySelector('#time_from').value = startTime;
                    // Calculate and set end time based on duration
                    const duration = this.calculateDuration();
                    const endTime = new Date(new Date(`2000-01-01 ${startTime}`).getTime() + duration * 60000);
                    document.querySelector('#time_to').value = endTime.toTimeString().slice(0, 8);
                    this.checkDoubleBooking();
                });
            });
        } else if (data.risk_level !== 'low') {
            warningContainer.innerHTML = `
                <div class="alert alert-warning">
                    <h6>⚠️ Potential Scheduling Risk</h6>
                    <p>Risk Level: ${data.risk_level}</p>
                    ${data.recommendations.length > 0 ? `
                        <p>Consider these alternative times:</p>
                        <ul>
                            ${data.recommendations.map(rec => `
                                <li>
                                    <a href="#" class="recommendation-link" data-start="${rec.suggestions[0].time}">
                                        ${this.formatTime(rec.suggestions[0].time)}
                                        (${Math.round(rec.suggestions[0].confidence * 100)}% optimal)
                                    </a>
                                </li>
                            `).join('')}
                        </ul>
                    ` : ''}
                </div>
            `;
        } else {
            warningContainer.innerHTML = `
                <div class="alert alert-success">
                    <h6>✅ No Scheduling Conflicts</h6>
                    <p>This time slot is available and optimal for your schedule.</p>
                </div>
            `;
        }
    }

    /**
     * Calculate duration between selected times
     */
    calculateDuration() {
        const timeFrom = document.querySelector('#time_from').value;
        const timeTo = document.querySelector('#time_to').value;

        if (!timeFrom || !timeTo) {
            return 60; // Default duration
        }

        const start = new Date(`2000-01-01 ${timeFrom}`);
        const end = new Date(`2000-01-01 ${timeTo}`);
        return Math.round((end - start) / 60000); // Convert to minutes
    }

    /**
     * Format time for display
     */
    formatTime(time) {
        return time.split(':').slice(0, 2).join(':');
    }

    /**
     * Get gradient background based on score
     */
    getGradientByScore(score) {
        const hue = Math.round(score * 120); // 0 = red, 120 = green
        return `linear-gradient(to right, hsl(${hue}, 70%, 90%), hsl(${hue}, 70%, 80%))`;
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        const loadingIndicator = document.createElement('div');
        loadingIndicator.id = 'smart-booking-loading';
        loadingIndicator.className = 'text-center my-3';
        loadingIndicator.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Analyzing availability and scheduling patterns...</p>
        `;
        
        document.querySelector('#availability-display').appendChild(loadingIndicator);
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        const loadingIndicator = document.querySelector('#smart-booking-loading');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger mt-3';
        errorAlert.textContent = message;
        
        document.querySelector('#availability-display').appendChild(errorAlert);
        
        // Remove after 5 seconds
        setTimeout(() => {
            errorAlert.remove();
        }, 5000);
    }
}

// Initialize the smart booking manager when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.smartBookingManager = new SmartBookingManager();
}); 