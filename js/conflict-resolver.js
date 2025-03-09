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
        const t1Start = this.timeToMinutes(start1);
        const t1End = this.timeToMinutes(end1);
        const t2Start = this.timeToMinutes(start2);
        const t2End = this.timeToMinutes(end2);
        
        const result = (t1Start < t2End && t1End > t2Start);
        console.log(`Time overlap check: ${start1}(${t1Start}) < ${end2}(${t2End}) && ${end1}(${t1End}) > ${start2}(${t2Start}) = ${result}`);
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
        
        // Convert to minutes since midnight while respecting AM/PM
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
        console.log("Analyzing booking:", { date, roomId, departmentId, timeFrom, timeTo, duration });

        // If any required field is missing or invalid, return no conflicts
        if (!date || !roomId || !timeFrom || !timeTo) {
            console.log("Missing required fields, skipping conflict check");
            return {
                hasConflicts: false,
                message: "Please fill in all required fields.",
                alternativeTimes: [],
                alternativeRooms: []
            };
        }

        // Find conflicts
        const conflicts = this.appointments.filter(appointment => {
            // Skip if not the same date or room
            if (appointment.booking_date !== date || appointment.room_id != roomId) {
                return false;
            }

            // Check for time overlap using the timeToMinutes conversion
            const hasOverlap = this.isTimeOverlap(
                timeFrom,
                timeTo,
                appointment.booking_time_from,
                appointment.booking_time_to
            );

            console.log("Checking overlap:", {
                timeFrom,
                timeTo,
                appointmentTimeFrom: appointment.booking_time_from,
                appointmentTimeTo: appointment.booking_time_to,
                hasOverlap
            });

            return hasOverlap;
        });

        console.log("Found conflicts:", conflicts);

        // If no conflicts, return early
        if (!conflicts || conflicts.length === 0) {
            return {
                hasConflicts: false,
                message: "No scheduling conflicts found.",
                alternativeTimes: [],
                alternativeRooms: []
            };
        }

        // Generate alternative times
        const alternativeTimes = this.generateAlternativeTimes(date, roomId, duration);

        // Generate alternative rooms
        const alternativeRooms = this.generateAlternativeRooms(date, timeFrom, timeTo);

        return {
            hasConflicts: true,
            message: `Found ${conflicts.length} scheduling conflict(s). Please review the suggested alternatives.`,
            conflicts: conflicts,
            alternativeTimes,
            alternativeRooms
        };
    }

    setupEventListeners() {
        console.log('Setting up conflict resolver event listeners');
        
        // Get all the form inputs we need to monitor
        const dateInput = document.getElementById('date');
        const roomInput = document.getElementById('room');
        const timeInputs = [
            'time_from_hour', 'time_from_minute', 'time_from_ampm',
            'time_to_hour', 'time_to_minute', 'time_to_ampm'
        ].map(id => document.getElementById(id));

        // Add real-time event listeners
        if (dateInput) {
            ['input', 'change'].forEach(eventType => {
                dateInput.addEventListener(eventType, () => this.debouncedCheckConflicts());
            });
            console.log('Added date input listeners');
        }

        if (roomInput) {
            ['input', 'change'].forEach(eventType => {
                roomInput.addEventListener(eventType, () => this.debouncedCheckConflicts());
            });
            console.log('Added room input listeners');
        }

        timeInputs.forEach(input => {
            if (input) {
                ['input', 'change', 'keyup', 'blur'].forEach(eventType => {
                    input.addEventListener(eventType, () => this.debouncedCheckConflicts());
                });
                console.log(`Added real-time listeners for ${input.id}`);
            }
        });

        // Add listener for the apply alternative button
        const applyBtn = document.querySelector('.apply-alternative');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => this.applySelectedAlternative());
            console.log('Added apply button listener');
        }

        // Add listener for ignore conflicts button
        const ignoreBtn = document.querySelector('.ignore-conflicts');
        if (ignoreBtn) {
            ignoreBtn.addEventListener('click', () => this.hideConflictAlert());
            console.log('Added ignore button listener');
        }

        // Initialize the debounce timer
        this.debounceTimer = null;
    }

    debouncedCheckConflicts() {
        // Clear any existing timeout
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        // Set a new timeout
        this.debounceTimer = setTimeout(() => {
            this.checkConflicts();
        }, 300); // Wait 300ms after last input before checking
    }

    checkConflicts() {
        console.log('Checking for conflicts...');

        // Get all required values
        const date = document.getElementById('date')?.value;
        const roomId = document.getElementById('room')?.value;
        
        // Get time values and format them
        const timeFrom = this.formatTime(
            document.getElementById('time_from_hour')?.value,
            document.getElementById('time_from_minute')?.value,
            document.getElementById('time_from_ampm')?.value
        );
        
        const timeTo = this.formatTime(
            document.getElementById('time_to_hour')?.value,
            document.getElementById('time_to_minute')?.value,
            document.getElementById('time_to_ampm')?.value
        );

        // Log the values we're checking
        console.log('Checking with values:', { date, roomId, timeFrom, timeTo });

        // Only proceed if we have all required values
        if (!date || !roomId || !timeFrom || !timeTo) {
            console.log('Missing required values, skipping conflict check');
            return;
        }

        // Analyze the booking
        const analysis = this.analyzeBooking(date, roomId, null, timeFrom, timeTo, this.calculateDuration(timeFrom, timeTo));
        console.log('Conflict analysis result:', analysis);

        // Update the UI based on the analysis
        this.updateConflictUI(analysis);
    }

    formatTime(hour, minute, ampm) {
        if (!hour || !minute || !ampm) {
            console.log('Missing time components:', { hour, minute, ampm });
            return null;
        }
        
        // Remove leading zeros and ensure proper format
        hour = parseInt(hour, 10).toString();
        minute = minute.toString().padStart(2, '0');
        
        return `${hour}:${minute} ${ampm}`;
    }

    calculateDuration(timeFrom, timeTo) {
        if (!timeFrom || !timeTo) return 60; // Default duration
        
        const fromMinutes = this.timeToMinutes(timeFrom);
        const toMinutes = this.timeToMinutes(timeTo);
        return toMinutes - fromMinutes;
    }

    updateConflictUI(analysis) {
        console.log('Updating conflict UI with analysis:', analysis);
        
        const container = document.getElementById('conflict-resolution-container');
        if (!container) {
            console.log('Conflict container not found');
            return;
        }

        if (!analysis.hasConflicts) {
            container.style.display = 'none';
            return;
        }

        // Show the conflict container
        container.style.display = 'block';

        // Update conflict message
        const messageEl = document.getElementById('conflict-message');
        if (messageEl) {
            messageEl.textContent = analysis.message;
        }

        // Update alternative times
        const altTimesContainer = document.getElementById('alternative-times');
        if (altTimesContainer && analysis.alternativeTimes) {
            altTimesContainer.innerHTML = analysis.alternativeTimes.map(time => `
                <div class="alternative-option" data-type="time" data-from="${time.timeFrom}" data-to="${time.timeTo}">
                    <input type="radio" name="alternative" id="time_${time.timeFrom.replace(/[:\s]/g, '_')}">
                    <label for="time_${time.timeFrom.replace(/[:\s]/g, '_')}">
                        ${time.timeFrom} - ${time.timeTo}
                        <span class="check-icon"><i class="fas fa-check"></i></span>
                    </label>
                </div>
            `).join('');
        }

        // Update alternative rooms
        const altRoomsContainer = document.getElementById('alternative-rooms');
        if (altRoomsContainer && analysis.alternativeRooms) {
            altRoomsContainer.innerHTML = analysis.alternativeRooms.map(room => `
                <div class="alternative-option" data-type="room" data-id="${room.roomId}">
                    <input type="radio" name="alternative" id="room_${room.roomId}">
                    <label for="room_${room.roomId}">
                        ${room.roomName}
                        <span class="check-icon"><i class="fas fa-check"></i></span>
                    </label>
                </div>
            `).join('');
        }

        // Add click handlers to new alternatives
        document.querySelectorAll('.alternative-option').forEach(option => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.alternative-option').forEach(opt => 
                    opt.classList.remove('selected'));
                option.classList.add('selected');
                document.querySelector('.apply-alternative').disabled = false;
            });
        });
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

    generateAlternativeTimes(date, roomId, duration) {
        const alternatives = [];
        const busyTimes = this.appointments
            .filter(app => app.booking_date === date && app.room_id == roomId)
            .map(app => ({
                from: this.timeToMinutes(app.booking_time_from),
                to: this.timeToMinutes(app.booking_time_to)
            }))
            .sort((a, b) => a.from - b.from);

        // Define working hours (8 AM to 5 PM)
        const workStart = 8 * 60; // 8:00 AM in minutes
        const workEnd = 17 * 60; // 5:00 PM in minutes

        // Find available time slots
        let currentTime = workStart;
        busyTimes.forEach(busy => {
            if (currentTime + duration <= busy.from) {
                alternatives.push({
                    timeFrom: this.minutesToTime12Hour(currentTime),
                    timeTo: this.minutesToTime12Hour(currentTime + duration),
                    score: this.calculateTimeScore(currentTime)
                });
            }
            currentTime = busy.to;
        });

        // Check for available time after last busy period
        if (currentTime + duration <= workEnd) {
            alternatives.push({
                timeFrom: this.minutesToTime12Hour(currentTime),
                timeTo: this.minutesToTime12Hour(currentTime + duration),
                score: this.calculateTimeScore(currentTime)
            });
        }

        // Sort alternatives by score
        return alternatives.sort((a, b) => b.score - a.score).slice(0, 3);
    }

    generateAlternativeRooms(date, timeFrom, timeTo) {
        return this.rooms
            .filter(room => {
                // Check if room is available during the requested time
                const conflicts = this.appointments.filter(app => 
                    app.booking_date === date &&
                    app.room_id == room.id &&
                    this.isTimeOverlap(
                        timeFrom,
                        timeTo,
                        app.booking_time_from,
                        app.booking_time_to
                    )
                );
                return conflicts.length === 0;
            })
            .map(room => ({
                roomId: room.id,
                roomName: room.name,
                score: this.calculateRoomScore(room)
            }))
            .sort((a, b) => b.score - a.score)
            .slice(0, 3);
    }

    minutesToTime12Hour(minutes) {
        let hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const period = hours >= 12 ? 'PM' : 'AM';
        
        if (hours > 12) hours -= 12;
        if (hours === 0) hours = 12;

        return `${hours}:${mins.toString().padStart(2, '0')} ${period}`;
    }

    calculateTimeScore(minutes) {
        // Prefer times closer to 9 AM (540 minutes)
        const idealTime = 540;
        const score = 100 - Math.abs(minutes - idealTime) / 10;
        return Math.round(score);
    }

    calculateRoomScore(room) {
        // Simple scoring based on room ID (can be enhanced based on room features)
        return 100 - room.id;
    }
}

// Export the ConflictResolver class
window.ConflictResolver = ConflictResolver;

// Initialize the conflict resolver when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    new ConflictResolver();
}); 