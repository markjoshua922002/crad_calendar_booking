/**
 * Conflict Resolution AI for Calendar Booking System
 * 
 * This module provides intelligent conflict detection and resolution for scheduling,
 * analyzing participant availability, and suggesting optimal meeting times.
 */

class ConflictResolver {
    constructor(appointments, rooms, departments) {
        this.appointments = appointments || [];
        this.rooms = rooms || [];
        this.departments = departments || [];
        this.conflictThreshold = 15; // Minutes threshold to consider as conflict
        this.timeSlots = this.generateTimeSlots();
        this.roomAvailability = {};
        this.departmentAvailability = {};
        
        // Initialize availability maps
        this.initializeAvailabilityMaps();
        this.setupEventListeners();
        this.debounceTimeout = null;

        // Add resize handler
        window.addEventListener('resize', () => this.handleResize());
        
        // Initial check for screen size
        this.handleResize();
    }
    
    /**
     * Generate standard time slots for a day (30-minute intervals)
     */
    generateTimeSlots() {
        const slots = [];
        const hours = [8, 9, 10, 11, 12, 1, 2, 3, 4, 5];
        const minutes = ['00', '30'];
        const ampm = ['AM', 'AM', 'AM', 'AM', 'PM', 'PM', 'PM', 'PM', 'PM', 'PM'];
        
        hours.forEach((hour, index) => {
            minutes.forEach(minute => {
                // Format hour without leading zero
                const formattedHour = hour.toString();
                slots.push(`${formattedHour}:${minute} ${ampm[index]}`);
            });
        });
        
        return slots;
    }
    
    /**
     * Initialize availability maps for rooms and departments
     */
    initializeAvailabilityMaps() {
        console.log("Initializing availability maps...");
        console.log(`Processing ${this.rooms.length} rooms and ${this.departments.length} departments`);
        
        // Initialize room availability
        this.rooms.forEach(room => {
            this.roomAvailability[room.id] = {};
        });
        
        // Initialize department availability
        this.departments.forEach(dept => {
            this.departmentAvailability[dept.id] = {};
        });
        
        // Populate with existing appointments
        console.log(`Processing ${this.appointments.length} appointments`);
        this.appointments.forEach((appointment, index) => {
            if (index < 5) {
                console.log(`Processing appointment: ${JSON.stringify(appointment)}`);
            }
            this.updateAvailabilityMaps(appointment);
        });
        
        // Log the first few entries in the availability maps for debugging
        console.log("Room availability map sample:");
        let roomSample = {};
        Object.keys(this.roomAvailability).slice(0, 2).forEach(roomId => {
            roomSample[roomId] = this.roomAvailability[roomId];
        });
        console.log(roomSample);
    }
    
    /**
     * Update availability maps with an appointment
     */
    updateAvailabilityMaps(appointment) {
        // Skip if appointment doesn't have required fields
        if (!appointment.booking_date || !appointment.booking_time_from || 
            !appointment.booking_time_to || !appointment.room_id || !appointment.department_id) {
            console.warn("Skipping appointment with missing fields:", appointment);
            return;
        }
        
        const date = appointment.booking_date;
        const timeFrom = appointment.booking_time_from; // Keep in 12-hour format
        const timeTo = appointment.booking_time_to; // Keep in 12-hour format
        
        // Update room availability
        if (!this.roomAvailability[appointment.room_id]) {
            this.roomAvailability[appointment.room_id] = {};
        }
        
        if (!this.roomAvailability[appointment.room_id][date]) {
            this.roomAvailability[appointment.room_id][date] = [];
        }
        
        this.roomAvailability[appointment.room_id][date].push({
            timeFrom,
            timeTo,
            appointmentId: appointment.id
        });
        
        // Update department availability
        if (!this.departmentAvailability[appointment.department_id]) {
            this.departmentAvailability[appointment.department_id] = {};
        }
        
        if (!this.departmentAvailability[appointment.department_id][date]) {
            this.departmentAvailability[appointment.department_id][date] = [];
        }
        
        this.departmentAvailability[appointment.department_id][date].push({
            timeFrom,
            timeTo,
            appointmentId: appointment.id
        });
    }
    
    /**
     * Check if a proposed booking conflicts with existing bookings
     */
    checkConflicts(date, roomId, timeFrom, timeTo) {
        console.log(`Checking conflicts for date: ${date}, room: ${roomId}, time: ${timeFrom} - ${timeTo}`);
        const conflicts = [];
        
        // Check room availability
        if (this.roomAvailability[roomId] && this.roomAvailability[roomId][date]) {
            console.log(`Found ${this.roomAvailability[roomId][date].length} existing bookings for this room and date`);
            
            this.roomAvailability[roomId][date].forEach(booking => {
                console.log(`Checking against booking: ${booking.timeFrom} - ${booking.timeTo}`);
                
                if (this.isTimeOverlap(timeFrom, timeTo, booking.timeFrom, booking.timeTo)) {
                    console.log(`Conflict detected with booking ID: ${booking.appointmentId}`);
                    
                    conflicts.push({
                        type: 'room',
                        roomId,
                        appointmentId: booking.appointmentId,
                        timeFrom: booking.timeFrom,
                        timeTo: booking.timeTo
                    });
                }
            });
        } else {
            console.log(`No existing bookings found for room ${roomId} on date ${date}`);
        }
        
        console.log(`Conflict check complete. Found ${conflicts.length} conflicts.`);
        return conflicts;
    }
    
    /**
     * Check if two time ranges overlap
     */
    isTimeOverlap(start1, end1, start2, end2) {
        // Convert times to comparable format (timestamp)
        const t1Start = new Date(`2000/01/01 ${start1}`).getTime();
        const t1End = new Date(`2000/01/01 ${end1}`).getTime();
        const t2Start = new Date(`2000/01/01 ${start2}`).getTime();
        const t2End = new Date(`2000/01/01 ${end2}`).getTime();
        
        const result = (t1Start < t2End && t1End > t2Start);
        console.log(`Time overlap check: ${start1} < ${end2} && ${end1} > ${start2} = ${result}`);
        return result;
    }
    
    /**
     * Find alternative time slots for a booking
     */
    findAlternatives(date, roomId, departmentId, duration, originalTimeFrom, originalTimeTo) {
        const alternatives = [];
        const bookedSlots = this.getBookedTimeSlots(date, roomId);
        
        // Convert duration to minutes if it's a string like "1:30"
        let durationMinutes = duration;
        if (typeof duration === 'string' && duration.includes(':')) {
            const [hours, minutes] = duration.split(':').map(Number);
            durationMinutes = (hours * 60) + minutes;
        }
        
        // Check each time slot
        for (let i = 0; i < this.timeSlots.length - 1; i++) {
            const startSlot = this.timeSlots[i];
            
            // Calculate end time based on duration
            const startMinutes = this.timeToMinutes(startSlot);
            const endMinutes = startMinutes + durationMinutes;
            const endSlot = this.minutesToTime(endMinutes);
            
            // Skip if this is the original time slot
            if (startSlot === originalTimeFrom && endSlot === originalTimeTo) {
                continue;
            }
            
            // Check if this time slot is available
            let isAvailable = true;
            for (const bookedSlot of bookedSlots) {
                const bookedStart = this.timeToMinutes(bookedSlot.timeFrom);
                const bookedEnd = this.timeToMinutes(bookedSlot.timeTo);
                
                if (startMinutes < bookedEnd && endMinutes > bookedStart) {
                    isAvailable = false;
                    break;
                }
            }
            
            if (isAvailable) {
                alternatives.push({
                    timeFrom: startSlot,
                    timeTo: endSlot,
                    score: this.calculateTimeScore(startSlot, date)
                });
            }
        }
        
        // Sort alternatives by score (higher is better)
        return alternatives.sort((a, b) => b.score - a.score);
    }
    
    /**
     * Get all booked time slots for a room on a specific date
     */
    getBookedTimeSlots(date, roomId) {
        if (this.roomAvailability[roomId] && this.roomAvailability[roomId][date]) {
            return this.roomAvailability[roomId][date];
        }
        return [];
    }
    
    /**
     * Convert time string to minutes since midnight
     */
    timeToMinutes(timeStr) {
        if (!timeStr) return 0;
        
        const [time, modifier] = timeStr.split(' ');
        let [hours, minutes] = time.split(':').map(Number);
        
        // Convert to 24-hour format for calculation
        if (hours === 12) {
            hours = modifier.toUpperCase() === 'AM' ? 0 : 12;
        } else if (modifier.toUpperCase() === 'PM' && hours !== 12) {
            hours += 12;
        }
        
        return (hours * 60) + parseInt(minutes || 0, 10);
    }
    
    /**
     * Convert minutes since midnight to time string
     */
    minutesToTime(minutes) {
        if (!minutes && minutes !== 0) return '';
        
        let hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const modifier = hours >= 12 ? 'PM' : 'AM';
        
        // Convert to 12-hour format
        if (hours > 12) {
            hours -= 12;
        } else if (hours === 0) {
            hours = 12;
        }
        
        // Format without leading zeros on hours
        return `${hours}:${mins.toString().padStart(2, '0')} ${modifier}`;
    }
    
    /**
     * Calculate a score for a time slot based on various factors
     * Higher score means better time slot
     */
    calculateTimeScore(timeSlot, date) {
        let score = 50; // Base score
        
        // Parse the time slot
        const [time, modifier] = timeSlot.split(' ');
        let hour = parseInt(time.split(':')[0], 10);
        
        // Convert to 24-hour for scoring
        if (modifier === 'PM' && hour !== 12) {
            hour += 12;
        } else if (modifier === 'AM' && hour === 12) {
            hour = 0;
        }
        
        // Prefer business hours (9 AM - 4 PM)
        if (hour >= 9 && hour <= 16) {
            score += 20;
        }
        
        // Avoid lunch time (12 PM - 1 PM)
        if (hour === 12) {
            score -= 15;
        }
        
        // Prefer morning over afternoon
        if (hour < 12) {
            score += 10;
        }
        
        // Prefer time slots on the hour or half hour
        const minute = parseInt(timeSlot.split(':')[1], 10);
        if (minute === 0) {
            score += 15;
        } else if (minute === 30) {
            score += 10;
        }
        
        return score;
    }
    
    /**
     * Suggest alternative rooms for a specific time
     */
    suggestAlternativeRooms(date, timeFrom, timeTo, originalRoomId) {
        const alternatives = [];
        
        this.rooms.forEach(room => {
            // Skip the original room
            if (room.id === originalRoomId) {
                return;
            }
            
            // Check if this room is available at the requested time
            const conflicts = this.checkConflicts(date, room.id, timeFrom, timeTo);
            
            if (conflicts.length === 0) {
                alternatives.push({
                    roomId: room.id,
                    roomName: room.name,
                    score: this.calculateRoomScore(room)
                });
            }
        });
        
        // Sort alternatives by score (higher is better)
        return alternatives.sort((a, b) => b.score - a.score);
    }
    
    /**
     * Calculate a score for a room based on various factors
     */
    calculateRoomScore(room) {
        // This could be expanded with more factors like room size, equipment, etc.
        return 50; // Base score for now
    }
    
    /**
     * Analyze a proposed booking and provide recommendations
     */
    analyzeBooking(date, roomId, departmentId, timeFrom, timeTo, duration) {
        const conflicts = this.checkConflicts(date, roomId, timeFrom, timeTo);
        
        // If no conflicts, return success
        if (conflicts.length === 0) {
            return {
                hasConflicts: false,
                message: "No conflicts detected. This booking can be scheduled as requested."
            };
        }
        
        // Find alternative times
        const alternativeTimes = this.findAlternatives(date, roomId, departmentId, duration, timeFrom, timeTo);
        
        // Find alternative rooms
        const alternativeRooms = this.suggestAlternativeRooms(date, timeFrom, timeTo, roomId);
        
        return {
            hasConflicts: true,
            conflicts,
            alternativeTimes: alternativeTimes.slice(0, 5), // Top 5 alternative times
            alternativeRooms: alternativeRooms.slice(0, 3), // Top 3 alternative rooms
            message: "Conflicts detected. Please consider the suggested alternatives."
        };
    }

    setupEventListeners() {
        // Listen for changes in date and time inputs
        document.getElementById('date')?.addEventListener('change', () => this.checkConflicts());
        document.getElementById('room')?.addEventListener('change', () => this.checkConflicts());
        
        // Time inputs
        const timeInputs = [
            'time_from_hour', 'time_from_minute', 'time_from_ampm',
            'time_to_hour', 'time_to_minute', 'time_to_ampm'
        ];
        
        timeInputs.forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => this.checkConflicts());
        });

        // Notification inputs
        document.getElementById('user_email')?.addEventListener('change', () => this.checkConflicts());
        document.getElementById('user_phone')?.addEventListener('change', () => this.checkConflicts());
        document.getElementById('notify_by_sms')?.addEventListener('change', () => {
            const phoneInput = document.getElementById('user_phone');
            if (phoneInput) {
                phoneInput.required = this.checked;
            }
            this.checkConflicts();
        });

        // Handle alternative selection
        document.querySelector('.apply-alternative')?.addEventListener('click', () => this.applySelectedAlternative());
    }

    async checkConflicts() {
        // Clear any existing timeout
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }

        // Debounce the check to prevent too many requests
        this.debounceTimeout = setTimeout(async () => {
            const date = document.getElementById('date')?.value;
            const room_id = document.getElementById('room')?.value;
            const userEmail = document.getElementById('user_email')?.value;
            const userPhone = document.getElementById('user_phone')?.value;
            const notifyBySMS = document.getElementById('notify_by_sms')?.checked;
            
            // Get time values
            const time_from = this.formatTime(
                document.getElementById('time_from_hour')?.value,
                document.getElementById('time_from_minute')?.value,
                document.getElementById('time_from_ampm')?.value
            );
            
            const time_to = this.formatTime(
                document.getElementById('time_to_hour')?.value,
                document.getElementById('time_to_minute')?.value,
                document.getElementById('time_to_ampm')?.value
            );

            // Check if we have all required values
            if (!date || !room_id || !time_from || !time_to) {
                return;
            }

            try {
                const response = await fetch('api/check_conflicts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        date,
                        room_id,
                        time_from,
                        time_to,
                        user_email: userEmail,
                        user_phone: userPhone,
                        notification_preferences: {
                            email: !!userEmail,
                            sms: notifyBySMS && !!userPhone,
                            reminderTiming: 15 // 15 minutes before by default
                        }
                    })
                });

                const data = await response.json();
                
                if (data.has_conflicts) {
                    this.showConflictAlert(data);
                } else {
                    this.hideConflictAlert();
                }
            } catch (error) {
                console.error('Error checking conflicts:', error);
            }
        }, 500); // Wait 500ms after last change before checking
    }

    formatTime(hour, minute, ampm) {
        if (!hour || !minute || !ampm) return null;
        
        // Convert hour to number and handle 12-hour format
        hour = parseInt(hour, 10);
        minute = minute.toString().padStart(2, '0');
        
        // Remove leading zero from hour if present
        hour = hour.toString().replace(/^0/, '');
        
        // Format in 12-hour format
        return `${hour}:${minute} ${ampm}`;
    }

    showConflictAlert(data) {
        const conflictModal = document.getElementById('conflictModal');
        if (!conflictModal) return;

        // Show the conflict modal
        conflictModal.style.display = 'block';
        
        // Update conflict message
        const conflictMsg = document.getElementById('conflict-message');
        if (conflictMsg) {
            const conflict = data.conflicts[0];
            conflictMsg.innerHTML = `This room is already booked by ${conflict.department} from ${conflict.time_from} to ${conflict.time_to}`;
        }

        // Update alternative times
        const altTimesContainer = document.getElementById('alternative-times');
        if (altTimesContainer) {
            altTimesContainer.innerHTML = data.alternative_times.map(time => {
                const timeId = time.time_from.replace(/[:\s]/g, '_');
                return `
                    <div class="alternative-option" data-type="time" data-from="${time.time_from}" data-to="${time.time_to}">
                        <input type="radio" name="alternative" id="time_${timeId}">
                        <label for="time_${timeId}">
                            ${time.time_from} - ${time.time_to}
                            <span class="check-icon"><i class="fas fa-check"></i></span>
                        </label>
                    </div>
                `;
            }).join('');
        }

        // Update alternative rooms
        const altRoomsContainer = document.getElementById('alternative-rooms');
        if (altRoomsContainer) {
            altRoomsContainer.innerHTML = data.alternative_rooms.map(room => `
                <div class="alternative-option" data-type="room" data-id="${room.id}">
                    <input type="radio" name="alternative" id="room_${room.id}">
                    <label for="room_${room.id}">
                        ${room.name}
                        <span class="check-icon"><i class="fas fa-check"></i></span>
                    </label>
                </div>
            `).join('');
        }

        // Enable/disable apply button based on selection
        const applyBtn = document.querySelector('.apply-alternative');
        if (applyBtn) {
            applyBtn.disabled = true;
        }

        // Add click handlers to alternatives
        document.querySelectorAll('.alternative-option').forEach(option => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.alternative-option').forEach(opt => 
                    opt.classList.remove('selected'));
                option.classList.add('selected');
                if (applyBtn) applyBtn.disabled = false;
            });
        });

        // Add click handler to ignore conflicts button
        document.querySelector('.ignore-conflicts')?.addEventListener('click', () => {
            this.hideConflictAlert();
        });
    }

    hideConflictAlert() {
        const conflictModal = document.getElementById('conflictModal');
        if (conflictModal) {
            conflictModal.style.display = 'none';
        }
    }

    applySelectedAlternative() {
        const selected = document.querySelector('.alternative-option.selected');
        if (!selected) return;

        if (selected.dataset.type === 'time') {
            // Parse the time strings (e.g., "9:00 AM" format)
            const [fromTime, fromAMPM] = selected.dataset.from.split(' ');
            const [fromHour, fromMinute] = fromTime.split(':');
            const [toTime, toAMPM] = selected.dataset.to.split(' ');
            const [toHour, toMinute] = toTime.split(':');
            
            // Update the form fields with 12-hour format values
            // Note: parseInt removes leading zeros automatically
            document.getElementById('time_from_hour').value = parseInt(fromHour, 10);
            document.getElementById('time_from_minute').value = fromMinute;
            document.getElementById('time_from_ampm').value = fromAMPM;
            
            document.getElementById('time_to_hour').value = parseInt(toHour, 10);
            document.getElementById('time_to_minute').value = toMinute;
            document.getElementById('time_to_ampm').value = toAMPM;
        } else if (selected.dataset.type === 'room') {
            document.getElementById('room').value = selected.dataset.id;
        }

        this.hideConflictAlert();
        this.checkConflicts(); // Recheck with new values
    }

    // Add this new method to handle window resize
    handleResize() {
        const bookingModal = document.getElementById('bookingModal');
        const conflictModal = document.getElementById('conflictModal');
        
        if (bookingModal && conflictModal && window.innerWidth <= 1200) {
            // On smaller screens, position the conflict modal at the bottom
            conflictModal.querySelector('.modal-content').style.top = 'auto';
            conflictModal.querySelector('.modal-content').style.bottom = '20px';
        }
    }

    // Add this method to validate notification inputs
    validateNotificationInputs() {
        const emailInput = document.getElementById('user_email');
        const phoneInput = document.getElementById('user_phone');
        const smsCheckbox = document.getElementById('notify_by_sms');
        
        let isValid = true;
        
        if (emailInput && !emailInput.value) {
            emailInput.classList.add('is-invalid');
            isValid = false;
        } else if (emailInput) {
            emailInput.classList.remove('is-invalid');
        }
        
        if (smsCheckbox?.checked && phoneInput && !phoneInput.value) {
            phoneInput.classList.add('is-invalid');
            isValid = false;
        } else if (phoneInput) {
            phoneInput.classList.remove('is-invalid');
        }
        
        return isValid;
    }
}

// Export the ConflictResolver class
window.ConflictResolver = ConflictResolver;

// Initialize the conflict resolver when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    new ConflictResolver();
}); 