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
    }
    
    /**
     * Generate standard time slots for a day (30-minute intervals)
     */
    generateTimeSlots() {
        const slots = [];
        const hours = ['08', '09', '10', '11', '12', '13', '14', '15', '16', '17'];
        const minutes = ['00', '30'];
        
        hours.forEach(hour => {
            minutes.forEach(minute => {
                slots.push(`${hour}:${minute}`);
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
        let timeFrom, timeTo;
        
        try {
            timeFrom = this.convertTo24Hour(appointment.booking_time_from);
            timeTo = this.convertTo24Hour(appointment.booking_time_to);
        } catch (error) {
            console.error("Error converting appointment times:", error);
            console.error("Problematic appointment:", appointment);
            return;
        }
        
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
     * Convert time string to 24-hour format
     */
    convertTo24Hour(timeStr) {
        console.log(`Converting time to 24-hour format: ${timeStr}`);
        
        // Handle SQL time format (HH:MM:SS)
        if (timeStr.includes(':') && !timeStr.includes(' ')) {
            // Check if it's already in 24-hour format
            const parts = timeStr.split(':');
            if (parts.length >= 2) {
                // It's already in 24-hour format, just return the HH:MM part
                return `${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}`;
            }
        }
        
        // Handle 12-hour format with AM/PM
        try {
            const [timePart, modifier] = timeStr.split(' ');
            if (!timePart || !modifier) {
                throw new Error(`Invalid time format: ${timeStr}`);
            }
            
            let [hours, minutes] = timePart.split(':');
            hours = parseInt(hours, 10);
            minutes = parseInt(minutes, 10);
            
            if (isNaN(hours) || isNaN(minutes)) {
                throw new Error(`Invalid time components: hours=${hours}, minutes=${minutes}`);
            }
            
            // Convert to 24-hour format
            if (hours === 12) {
                hours = modifier.toUpperCase() === 'AM' ? 0 : 12;
            } else if (modifier.toUpperCase() === 'PM') {
                hours += 12;
            }
            
            const result = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
            console.log(`Converted time: ${timeStr} -> ${result}`);
            return result;
        } catch (error) {
            console.error(`Error converting time: ${timeStr}`, error);
            throw new Error(`Failed to convert time: ${timeStr}`);
        }
    }
    
    /**
     * Convert 24-hour format to 12-hour format
     */
    convertTo12Hour(timeStr) {
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours, 10);
        
        if (hour === 0) {
            return `12:${minutes} AM`;
        } else if (hour < 12) {
            return `${hour}:${minutes} AM`;
        } else if (hour === 12) {
            return `12:${minutes} PM`;
        } else {
            return `${hour - 12}:${minutes} PM`;
        }
    }
    
    /**
     * Check if a proposed booking conflicts with existing bookings
     */
    checkConflicts(date, roomId, timeFrom, timeTo) {
        console.log(`Checking conflicts for date: ${date}, room: ${roomId}, time: ${timeFrom} - ${timeTo}`);
        const conflicts = [];
        
        // Convert times to 24-hour format if they aren't already
        const startTime = timeFrom.includes(' ') ? this.convertTo24Hour(timeFrom) : timeFrom;
        const endTime = timeTo.includes(' ') ? this.convertTo24Hour(timeTo) : timeTo;
        
        console.log(`Converted times for conflict check: ${startTime} - ${endTime}`);
        
        // Check room availability
        if (this.roomAvailability[roomId] && this.roomAvailability[roomId][date]) {
            console.log(`Found ${this.roomAvailability[roomId][date].length} existing bookings for this room and date`);
            
            this.roomAvailability[roomId][date].forEach(booking => {
                console.log(`Checking against booking: ${booking.timeFrom} - ${booking.timeTo}`);
                
                if (this.isTimeOverlap(startTime, endTime, booking.timeFrom, booking.timeTo)) {
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
        const result = (start1 < end2 && end1 > start2);
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
                    timeFrom: this.convertTo12Hour(startSlot),
                    timeTo: this.convertTo12Hour(endSlot),
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
        const [hours, minutes] = timeStr.split(':').map(Number);
        return (hours * 60) + minutes;
    }
    
    /**
     * Convert minutes since midnight to time string
     */
    minutesToTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    }
    
    /**
     * Calculate a score for a time slot based on various factors
     * Higher score means better time slot
     */
    calculateTimeScore(timeSlot, date) {
        let score = 50; // Base score
        
        // Prefer business hours (9 AM - 4 PM)
        const hour = parseInt(timeSlot.split(':')[0], 10);
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
                        time_to
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
        return `${hour}:${minute} ${ampm}`;
    }

    showConflictAlert(data) {
        const container = document.getElementById('conflict-resolution-container');
        if (!container) return;

        container.style.display = 'block';
        
        // Update conflict message
        const conflictMsg = document.getElementById('conflict-message');
        if (conflictMsg) {
            const conflict = data.conflicts[0];
            conflictMsg.innerHTML = `This room is already booked by ${conflict.department} from ${conflict.time_from} to ${conflict.time_to}`;
        }

        // Update alternative times
        const altTimesContainer = document.getElementById('alternative-times');
        if (altTimesContainer) {
            altTimesContainer.innerHTML = data.alternative_times.map(time => `
                <div class="alternative-option" data-type="time" data-from="${time.time_from}" data-to="${time.time_to}">
                    <input type="radio" name="alternative" id="time_${time.time_from}">
                    <label for="time_${time.time_from}">
                        ${time.time_from} - ${time.time_to}
                        <span class="check-icon"><i class="fas fa-check"></i></span>
                    </label>
                </div>
            `).join('');
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
    }

    hideConflictAlert() {
        const container = document.getElementById('conflict-resolution-container');
        if (container) {
            container.style.display = 'none';
        }
    }

    applySelectedAlternative() {
        const selected = document.querySelector('.alternative-option.selected');
        if (!selected) return;

        if (selected.dataset.type === 'time') {
            // Apply alternative time
            const [fromHour, fromMinute, fromAMPM] = selected.dataset.from.split(/[:\s]/);
            const [toHour, toMinute, toAMPM] = selected.dataset.to.split(/[:\s]/);
            
            document.getElementById('time_from_hour').value = parseInt(fromHour);
            document.getElementById('time_from_minute').value = fromMinute;
            document.getElementById('time_from_ampm').value = fromAMPM;
            
            document.getElementById('time_to_hour').value = parseInt(toHour);
            document.getElementById('time_to_minute').value = toMinute;
            document.getElementById('time_to_ampm').value = toAMPM;
        } else if (selected.dataset.type === 'room') {
            // Apply alternative room
            document.getElementById('room').value = selected.dataset.id;
        }

        this.hideConflictAlert();
        this.checkConflicts(); // Recheck with new values
    }
}

// Export the ConflictResolver class
window.ConflictResolver = ConflictResolver;

// Initialize the conflict resolver when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    new ConflictResolver();
}); 