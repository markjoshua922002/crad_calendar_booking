// Initialize conflict resolver
let conflictResolver = null;

document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM fully loaded and parsed - v14");

    // Add global error handler to catch and log JavaScript errors
    window.onerror = function(message, source, lineno, colno, error) {
        console.error("JavaScript error:", message);
        console.error("Source:", source);
        console.error("Line:", lineno, "Column:", colno);
        console.error("Error object:", error);
        return false;
    };

    try {
        // Debug element existence
        console.log("Menu button exists:", !!document.getElementById('menuButton'));
        console.log("Sidebar exists:", !!document.getElementById('sidebar'));
        console.log("Container exists:", !!document.querySelector('.container'));
        console.log("Calendar days:", document.querySelectorAll('.day').length);
        console.log("Open booking button exists:", !!document.getElementById('openBookingModal'));
        console.log("Delete button exists:", !!document.getElementById('delete_button'));
        
        // Log all modals for debugging
        console.log("Modals found:", {
            bookingModal: !!document.getElementById('bookingModal'),
            editModal: !!document.getElementById('editModal'),
            viewModal: !!document.getElementById('viewModal'),
            addDepartmentModal: !!document.getElementById('addDepartmentModal'),
            addRoomModal: !!document.getElementById('addRoomModal'),
            dayViewModal: !!document.getElementById('dayViewModal'),
            appointmentModal: !!document.getElementById('appointmentModal')
        });
        
        // Log all close buttons for debugging
        console.log("Close buttons found:", {
            closeBookingModal: !!document.getElementById('closeBookingModal'),
            closeEditModal: !!document.getElementById('closeEditModal'),
            closeViewModal: !!document.getElementById('closeViewModal'),
            closeAddDepartmentModal: !!document.getElementById('closeAddDepartmentModal'),
            closeAddRoomModal: !!document.getElementById('closeAddRoomModal'),
            closeDayViewModal: !!document.getElementById('closeDayViewModal'),
            closeAppointmentModal: !!document.getElementById('closeAppointmentModal')
        });

        // Initialize all modals
        setupModal('bookingModal', 'openBookingModal', 'closeBookingModal');
        setupModal('editModal', null, 'closeEditModal');
        setupModal('viewModal', null, 'closeViewModal');
        setupModal('addDepartmentModal', 'openAddDepartmentModal', 'closeAddDepartmentModal');
        setupModal('addRoomModal', 'openAddRoomModal', 'closeAddRoomModal');
        setupModal('dayViewModal', null, 'closeDayViewModal');
        setupModal('appointmentModal', null, 'closeAppointmentModal');

        // Initialize time pickers
        console.log("Initializing time pickers...");
        
        // Check if time picker elements exist
        console.log("Time picker elements:", {
            time_from_hour: !!document.getElementById('time_from_hour'),
            time_from_minute: !!document.getElementById('time_from_minute'),
            time_from_ampm: !!document.getElementById('time_from_ampm'),
            time_to_hour: !!document.getElementById('time_to_hour'),
            time_to_minute: !!document.getElementById('time_to_minute'),
            time_to_ampm: !!document.getElementById('time_to_ampm')
        });
        
        setupTimePicker('time_from_hour', 'time_from_minute', 'time_from_ampm');
        setupTimePicker('time_to_hour', 'time_to_minute', 'time_to_ampm');
        setupTimePicker('edit_time_from_hour', 'edit_time_from_minute', 'edit_time_from_ampm');
        setupTimePicker('edit_time_to_hour', 'edit_time_to_minute', 'edit_time_to_ampm');

        // Initialize Conflict Resolver
        initializeConflictResolver();

        // Direct event listeners for Add Department and Add Room buttons
        const openAddDepartmentBtn = document.getElementById('openAddDepartmentModal');
        const addDepartmentModal = document.getElementById('addDepartmentModal');
        if (openAddDepartmentBtn && addDepartmentModal) {
            openAddDepartmentBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log("Opening Add Department modal");
                addDepartmentModal.style.display = 'block';
            });
        } else {
            console.error("Add Department button or modal not found:", {
                button: !!openAddDepartmentBtn,
                modal: !!addDepartmentModal
            });
        }

        const openAddRoomBtn = document.getElementById('openAddRoomModal');
        const addRoomModal = document.getElementById('addRoomModal');
        if (openAddRoomBtn && addRoomModal) {
            openAddRoomBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log("Opening Add Room modal");
                addRoomModal.style.display = 'block';
            });
        } else {
            console.error("Add Room button or modal not found:", {
                button: !!openAddRoomBtn,
                modal: !!addRoomModal
            });
        }

        // Direct event listener for View All Appointments button
        const viewAllAppointmentsBtn = document.getElementById('viewAllAppointments');
        const appointmentModal = document.getElementById('appointmentModal');
        if (viewAllAppointmentsBtn && appointmentModal) {
            viewAllAppointmentsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log("Opening All Appointments modal");
                appointmentModal.style.display = 'block';
            });
        } else {
            console.error("View All Appointments button or modal not found:", {
                button: !!viewAllAppointmentsBtn,
                modal: !!appointmentModal
            });
        }

        // Setup "Add Appointment" button in day view modal
        const openBookingFromDayView = document.getElementById('openBookingFromDayView');
        if (openBookingFromDayView) {
            openBookingFromDayView.addEventListener('click', function() {
                // Close the day view modal
                const dayViewModal = document.getElementById('dayViewModal');
                if (dayViewModal) {
                    dayViewModal.style.display = 'none';
                }
                
                // Open the booking modal
                const bookingModal = document.getElementById('bookingModal');
                if (bookingModal) {
                    bookingModal.style.display = 'block';
                }
            });
        }

        // Setup sidebar toggle
        handleSidebarToggle();
        
        // Setup calendar interactions
        setupCalendarInteractions();
        
        // Setup appointment clicks
        setupAppointmentClicks();
        
        // Setup delete appointment functionality
        setupDeleteAppointment();
        
        // Setup view options
        const viewButtons = document.querySelectorAll('.view-btn');
        if (viewButtons.length > 0) {
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    viewButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    // Change calendar view
                    changeCalendarView(this.dataset.view);
                });
            });
        }
        
        // Handle mobile sidebar
        handleMobileSidebar();
        
        // Setup export calendar functionality
        setupExportCalendar();
        
        // More events functionality
        const moreEventElements = document.querySelectorAll('.more-events');
        moreEventElements.forEach(element => {
            element.addEventListener('click', function(e) {
                e.stopPropagation();
                const day = this.closest('.day');
                if (!day) {
                    console.error("Parent day element not found for more-events");
                    return;
                }
                
                const dayNumberElement = day.querySelector('.day-number');
                if (!dayNumberElement) {
                    console.error("Day number element not found");
                    return;
                }
                
                const dayNumber = dayNumberElement.textContent;
                
                // Show all events for this day
                console.log(`Show all events for day ${dayNumber}`);
                
                const appointmentsDataElement = document.getElementById('appointmentsData');
                if (!appointmentsDataElement) {
                    console.error("Appointments data element not found");
                    return;
                }
                
                try {
                    const appointmentsData = JSON.parse(appointmentsDataElement.textContent || '{}');
                    const dayAppointments = appointmentsData[dayNumber] || [];
                    showDayAppointments(dayAppointments, dayNumber);
                } catch (error) {
                    console.error("Error parsing appointments data:", error);
                }
            });
        });
        
        // Add direct click handlers to all buttons that open modals
        document.querySelectorAll('[data-modal]').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'block';
                    console.log(`Modal ${modalId} opened via data-modal attribute`);
                } else {
                    console.error(`Modal with ID ${modalId} not found`);
                }
            });
        });
        
        // Add direct click handlers to all buttons with specific IDs
        const modalOpenButtons = {
            'openBookingModal': 'bookingModal',
            'openAddDepartmentModal': 'addDepartmentModal',
            'openAddRoomModal': 'addRoomModal'
        };
        
        for (const [buttonId, modalId] of Object.entries(modalOpenButtons)) {
            const button = document.getElementById(buttonId);
            const modal = document.getElementById(modalId);
            
            if (button && modal) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.style.display = 'block';
                    console.log(`Modal ${modalId} opened via button ID ${buttonId}`);
                });
            }
        }
        
        // Add direct click handlers to all close buttons
        document.querySelectorAll('.close-button').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                    console.log(`Modal closed via close button`);
                }
            });
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                console.log(`Modal closed by clicking outside`);
            }
        });
    } catch (error) {
        console.error("Error in main script initialization:", error);
    }
});

// Modal setup function
function setupModal(modalId, openButtonId, closeButtonId) {
    try {
        const modal = document.getElementById(modalId);
        
        if (!modal) {
            console.error(`Modal with ID ${modalId} not found`);
            return;
        }
        
        console.log(`Setting up modal: ${modalId}`);
        
        // Setup open button if provided
        if (openButtonId) {
            const openButton = document.getElementById(openButtonId);
            if (openButton) {
                console.log(`Adding click listener to open button: ${openButtonId}`);
                openButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling
                    modal.style.display = 'block';
                    console.log(`Modal ${modalId} opened`);
                    
                    // If this is the booking modal, check for conflicts
                    if (modalId === 'bookingModal') {
                        // Reset the ignore conflicts flag
                        const bookingForm = modal.querySelector('form');
                        if (bookingForm) {
                            delete bookingForm.dataset.ignoreConflicts;
                        }
                        
                        // Hide the conflict container initially
                        const conflictContainer = document.getElementById('conflict-resolution-container');
                        if (conflictContainer) {
                            conflictContainer.style.display = 'none';
                        }
                        
                        // Check for conflicts after a short delay to allow form to initialize
                        setTimeout(function() {
                            checkForConflicts();
                        }, 500);
                    }
                });
            } else {
                console.error(`Open button with ID ${openButtonId} not found`);
            }
        }
        
        // Setup close button if provided
        if (closeButtonId) {
            const closeButton = document.getElementById(closeButtonId);
            if (closeButton) {
                console.log(`Adding click listener to close button: ${closeButtonId}`);
                closeButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling
                    modal.style.display = 'none';
                    console.log(`Modal ${modalId} closed`);
                });
            } else {
                console.error(`Close button with ID ${closeButtonId} not found`);
            }
        }
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
                console.log(`Modal ${modalId} closed by clicking outside`);
            }
        });
    } catch (error) {
        console.error(`Error setting up modal ${modalId}:`, error);
    }
}

function setupCalendarInteractions() {
    console.log("Setting up calendar interactions");
    
    // Handle calendar day click to show appointments for that day
    const days = document.querySelectorAll('.day:not(.empty)');
    const appointmentsDataElement = document.getElementById('appointmentsData');
    
    if (!appointmentsDataElement) {
        console.error("Appointments data element not found");
        return;
    }
    
    const appointmentsData = JSON.parse(appointmentsDataElement.textContent || '{}');
    
    days.forEach(day => {
        const dayNumberElement = day.querySelector('.day-number');
        if (!dayNumberElement) return;
        
        day.addEventListener('click', function() {
            const dayNumber = dayNumberElement.textContent;
            console.log(`Day ${dayNumber} clicked`);
            
            const dayAppointments = appointmentsData[dayNumber] || [];
            
            if (dayAppointments.length > 0) {
                showDayAppointments(dayAppointments, dayNumber);
            } else {
                // Optionally open the booking modal pre-filled with this date
                openBookingModalWithDate(dayNumber);
            }
        });
    });
}

function showDayAppointments(appointments, dayNumber) {
    try {
        console.log(`Showing appointments for day ${dayNumber}`);
        
        const appointmentList = document.getElementById('appointmentList');
        if (!appointmentList) {
            console.error("Appointment list element not found");
            return;
        }
        
        // Clear previous appointments
        appointmentList.innerHTML = '';
        
        // Update the day title
        const dayTitle = document.getElementById('dayTitle');
        if (dayTitle) {
            const monthYearElement = document.querySelector('.month-year');
            const currentMonth = monthYearElement ? monthYearElement.textContent : '';
            dayTitle.textContent = `Appointments for ${currentMonth} ${dayNumber}`;
        }
        
        // Display appointments
        if (appointments.length > 0) {
            appointments.forEach(appointment => {
                try {
                    // Format times for display
                    const timeFrom = new Date(`2000-01-01T${appointment.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const timeTo = new Date(`2000-01-01T${appointment.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    // Create appointment element
                    const appointmentItem = document.createElement('div');
                    appointmentItem.classList.add('appointment-item');
                    appointmentItem.style.borderLeft = `4px solid ${appointment.color || '#4285f4'}`;
                    appointmentItem.dataset.id = appointment.id;
                    
                    appointmentItem.innerHTML = `
                        <div class="appointment-header">
                            <h3>${appointment.name || 'Unnamed Appointment'}</h3>
                            <span class="appointment-time">${timeFrom} - ${timeTo}</span>
                        </div>
                        <div class="appointment-details">
                            <p><strong>Department:</strong> ${appointment.department_name || 'N/A'}</p>
                            <p><strong>Room:</strong> ${appointment.room_name || 'N/A'}</p>
                            <p><strong>Agenda:</strong> ${appointment.reason || 'N/A'}</p>
                        </div>
                        <div class="appointment-actions">
                            <button class="view-appointment" data-id="${appointment.id}">View</button>
                            <button class="edit-appointment" data-id="${appointment.id}">Edit</button>
                        </div>
                    `;
                    
                    appointmentList.appendChild(appointmentItem);
                } catch (error) {
                    console.error("Error creating appointment item:", error, appointment);
                }
            });
            
            // Add event listeners to the view and edit buttons
            document.querySelectorAll('.view-appointment').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const appointmentId = this.dataset.id;
                    const appointment = appointments.find(a => a.id == appointmentId);
                    if (appointment) {
                        showAppointmentDetails(appointment);
                    } else {
                        console.error(`Appointment with ID ${appointmentId} not found`);
                    }
                });
            });
            
            document.querySelectorAll('.edit-appointment').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const appointmentId = this.dataset.id;
                    const appointment = appointments.find(a => a.id == appointmentId);
                    if (appointment) {
                        fillEditForm(appointment);
                    } else {
                        console.error(`Appointment with ID ${appointmentId} not found`);
                    }
                });
            });
        } else {
            appointmentList.innerHTML = '<div class="no-appointments">No appointments for this day</div>';
        }
        
        // Show the day view modal
        const dayViewModal = document.getElementById('dayViewModal');
        if (dayViewModal) {
            dayViewModal.style.display = 'block';
        } else {
            console.error("Day view modal not found");
        }
    } catch (error) {
        console.error("Error in showDayAppointments:", error);
    }
}

function openBookingModalWithDate(day) {
    console.log(`Opening booking modal with date: ${day}`);
    
    const dateInput = document.getElementById('date');
    if (dateInput) {
        // Get current month and year
        const monthYear = document.querySelector('.month-year').textContent;
        const [month, year] = monthYear.split(' ');
        
        // Create date string in YYYY-MM-DD format
        const monthIndex = new Date(`${month} 1, 2000`).getMonth() + 1;
        const formattedDate = `${year}-${monthIndex.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
        
        dateInput.value = formattedDate;
    }
    
    // Open the booking modal
    const bookingModal = document.getElementById('bookingModal');
    if (bookingModal) {
        bookingModal.style.display = 'block';
    }
}

function setupAppointmentClicks() {
    console.log("Setting up appointment clicks");
    
    // Add click event to all appointment items in the calendar
    document.querySelectorAll('.appointment').forEach(appointment => {
        appointment.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the day click event
            
            const appointmentId = this.dataset.id;
            console.log(`Appointment clicked: ${appointmentId}`);
            
            // Get appointment data from the hidden JSON element
            const appointmentsData = JSON.parse(document.getElementById('appointmentsData')?.textContent || '{}');
            
            // Find the appointment in the data
            let foundAppointment = null;
            Object.values(appointmentsData).forEach(dayAppointments => {
                dayAppointments.forEach(appointment => {
                    if (appointment.id == appointmentId) {
                        foundAppointment = appointment;
                    }
                });
            });
            
            if (foundAppointment) {
                showAppointmentDetails(foundAppointment);
            }
        });
    });
}

function showAppointmentDetails(appointment) {
    try {
        console.log(`Showing details for appointment: ${appointment.id}`);
        
        const viewContainer = document.getElementById('viewContainer');
        if (!viewContainer) {
            console.error("View container not found");
            return;
        }
        
        // Format times for display
        let timeFrom = '';
        let timeTo = '';
        
        try {
            timeFrom = new Date(`2000-01-01T${appointment.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            timeTo = new Date(`2000-01-01T${appointment.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } catch (error) {
            console.error("Error formatting time:", error);
            timeFrom = appointment.booking_time_from || 'N/A';
            timeTo = appointment.booking_time_to || 'N/A';
        }
        
        viewContainer.innerHTML = `
            <div class="appointment-details">
                <p><strong>Research Adviser's Name:</strong> ${appointment.name || 'N/A'}</p>
                <p><strong>Group Number:</strong> ${appointment.id_number || 'N/A'}</p>
                <p><strong>Set:</strong> ${appointment.set || "N/A"}</p>
                <p><strong>Department:</strong> ${appointment.department_name || 'N/A'}</p>
                <p><strong>Room:</strong> ${appointment.room_name || 'N/A'}</p>
                <p><strong>Date:</strong> ${appointment.booking_date || 'N/A'}</p>
                <p><strong>Time:</strong> ${timeFrom} - ${timeTo}</p>
                <p><strong>Agenda:</strong> ${appointment.reason || 'N/A'}</p>
                <p><strong>Representative:</strong> ${appointment.representative_name || "N/A"}</p>
                <p><strong>Remarks:</strong> ${appointment.group_members || "None"}</p>
            </div>
            <div class="form-actions-right" style="margin-top: 20px;">
                <button type="button" class="edit-appointment-btn" data-id="${appointment.id}">Edit Appointment</button>
            </div>
        `;
        
        // Close any other open modals
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.id !== 'viewModal') {
                modal.style.display = 'none';
            }
        });
        
        // Show the view modal
        const viewModal = document.getElementById('viewModal');
        if (viewModal) {
            viewModal.style.display = 'block';
        } else {
            console.error("View modal not found");
            return;
        }
        
        // Add event listener to the edit button
        const editButton = document.querySelector('.edit-appointment-btn');
        if (editButton) {
            editButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                fillEditForm(appointment);
                
                // Close the view modal and open the edit modal
                viewModal.style.display = 'none';
                
                const editModal = document.getElementById('editModal');
                if (editModal) {
                    editModal.style.display = 'block';
                } else {
                    console.error("Edit modal not found");
                }
            });
        } else {
            console.error("Edit button not found in view modal");
        }
    } catch (error) {
        console.error("Error in showAppointmentDetails:", error);
    }
}

function fillEditForm(appointment) {
    try {
        console.log(`Filling edit form for appointment: ${appointment.id}`);
        
        // Set the appointment ID in the hidden field
        const appointmentIdField = document.getElementById('appointment_id');
        if (appointmentIdField) {
            appointmentIdField.value = appointment.id;
        } else {
            console.error("Appointment ID field not found");
        }
        
        // Fill in the form fields
        const fields = {
            'edit_department': appointment.department_id,
            'edit_name': appointment.name,
            'edit_id_number': appointment.id_number,
            'edit_set': appointment.set,
            'edit_date': appointment.booking_date,
            'edit_reason': appointment.reason,
            'edit_room': appointment.room_id,
            'edit_representative_name': appointment.representative_name,
            'edit_group_members': appointment.group_members
        };
        
        for (const [fieldId, value] of Object.entries(fields)) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = value || '';
            } else {
                console.warn(`Field with ID ${fieldId} not found`);
            }
        }
        
        // Time handling - parse the time into components
        try {
            const timeFrom = new Date(`2000-01-01T${appointment.booking_time_from}`);
            const timeTo = new Date(`2000-01-01T${appointment.booking_time_to}`);
            
            const fromHour = timeFrom.getHours() % 12 || 12;
            const fromMinute = timeFrom.getMinutes();
            const fromAMPM = timeFrom.getHours() < 12 ? 'AM' : 'PM';
            
            const toHour = timeTo.getHours() % 12 || 12;
            const toMinute = timeTo.getMinutes();
            const toAMPM = timeTo.getHours() < 12 ? 'AM' : 'PM';
            
            // Set time fields
            const timeFields = {
                'edit_time_from_hour': fromHour,
                'edit_time_from_minute': fromMinute.toString().padStart(2, '0'),
                'edit_time_from_ampm': fromAMPM,
                'edit_time_to_hour': toHour,
                'edit_time_to_minute': toMinute.toString().padStart(2, '0'),
                'edit_time_to_ampm': toAMPM
            };
            
            for (const [fieldId, value] of Object.entries(timeFields)) {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = value;
                } else {
                    console.warn(`Time field with ID ${fieldId} not found`);
                }
            }
        } catch (error) {
            console.error("Error parsing appointment times:", error);
        }
        
        // Close any other open modals
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.id !== 'editModal') {
                modal.style.display = 'none';
            }
        });
        
        // Show the edit modal
        const editModal = document.getElementById('editModal');
        if (editModal) {
            editModal.style.display = 'block';
        } else {
            console.error("Edit modal not found");
        }
    } catch (error) {
        console.error("Error in fillEditForm:", error);
    }
}

function setupExportCalendar() {
    const exportButton = document.getElementById('exportCalendar');
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            const appointmentsData = JSON.parse(document.getElementById('appointmentsData')?.textContent || '{}');
            exportToCSV(appointmentsData);
        });
    }
}

function exportToCSV(appointmentsData) {
    // Flatten the appointments data
    const appointments = [];
    Object.values(appointmentsData).forEach(dayAppointments => {
        dayAppointments.forEach(appointment => {
            appointments.push(appointment);
        });
    });
    
    if (appointments.length === 0) {
        alert('No appointments to export');
        return;
    }
    
    // Define CSV headers
    const headers = [
        'Name',
        'Group Number',
        'Set',
        'Department',
        'Room',
        'Date',
        'Time From',
        'Time To',
        'Agenda',
        'Representative',
        'Remarks'
    ];
    
    // Format appointments for CSV
    const csvData = appointments.map(appointment => {
        const timeFrom = new Date(`2000-01-01T${appointment.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        const timeTo = new Date(`2000-01-01T${appointment.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        return [
            appointment.name,
            appointment.id_number,
            appointment.set || '',
            appointment.department_name,
            appointment.room_name,
            appointment.booking_date,
            timeFrom,
            timeTo,
            appointment.reason,
            appointment.representative_name || '',
            appointment.group_members || ''
        ];
    });
    
    // Helper function to escape commas in CSV
    const escapeComma = (text) => `"${(text || '').replace(/"/g, '""')}"`;
    
    // Create CSV content
    let csvContent = headers.map(escapeComma).join(',') + '\n';
    csvContent += csvData.map(row => row.map(escapeComma).join(',')).join('\n');
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', 'calendar_appointments.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function setupDeleteAppointment() {
    try {
        console.log("Setting up delete appointment functionality");
        
        const deleteButton = document.getElementById('delete_button');
        if (deleteButton) {
            console.log("Delete button found, adding event listener");
            
            deleteButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const appointmentIdField = document.getElementById('appointment_id');
                if (!appointmentIdField) {
                    console.error("Appointment ID field not found");
                    alert("Error: Could not find appointment ID");
                    return;
                }
                
                const appointmentId = appointmentIdField.value;
                console.log(`Delete button clicked for appointment ID: ${appointmentId}`);
                
                if (!appointmentId) {
                    console.error("No appointment ID found");
                    alert("Error: No appointment ID found");
                    return;
                }
                
                if (confirm('Are you sure you want to delete this appointment?')) {
                    console.log(`Deleting appointment ID: ${appointmentId}`);
                    
                    // Create a form to submit the delete request
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `api/delete_appointment.php`;
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = appointmentId;
                    
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    
                    form.submit();
                } else {
                    console.log("Delete cancelled");
                }
            });
        } else {
            console.error("Delete button not found");
        }
    } catch (error) {
        console.error("Error in setupDeleteAppointment:", error);
    }
}

function setupTimePicker(hourInputId, minuteInputId, ampmSelectId) {
    const hourInput = document.getElementById(hourInputId);
    const minuteInput = document.getElementById(minuteInputId);
    const ampmSelect = document.getElementById(ampmSelectId);
    
    if (!hourInput || !minuteInput || !ampmSelect) {
        console.error(`Time picker elements not found: ${hourInputId}, ${minuteInputId}, ${ampmSelectId}`);
        return;
    }
    
    // Setup dropdown functionality
    setupTimeDropdown(hourInputId);
    setupTimeDropdown(minuteInputId);
    
    const updateTimeInput = () => {
        if (hourInput.value && minuteInput.value && ampmSelect.value) {
            let hour = parseInt(hourInput.value);
            const minute = parseInt(minuteInput.value);
            const ampm = ampmSelect.value;
            
            // Validate input ranges
            if (hour < 1) hour = 1;
            if (hour > 12) hour = 12;
            hourInput.value = hour;
            
            let validMinute = minute;
            if (validMinute < 0) validMinute = 0;
            if (validMinute > 59) validMinute = 59;
            minuteInput.value = validMinute;
        }
    };
    
    // Add event listeners to update the hidden time input
    hourInput.addEventListener('input', updateTimeInput);
    minuteInput.addEventListener('input', updateTimeInput);
    ampmSelect.addEventListener('change', updateTimeInput);
    
    // Set initial values if needed
    hourInput.value = hourInput.value || "9";
    minuteInput.value = minuteInput.value || "00";
}

// Function to setup the time dropdown functionality
function setupTimeDropdown(inputId) {
    console.log(`Setting up time dropdown for: ${inputId}`);
    
    const input = document.getElementById(inputId);
    if (!input) {
        console.error(`Input element not found: ${inputId}`);
        return;
    }
    
    const dropdownId = `${inputId}_dropdown`;
    const dropdown = document.getElementById(dropdownId);
    if (!dropdown) {
        console.error(`Dropdown element not found: ${dropdownId}`);
        return;
    }
    
    const toggleBtn = document.querySelector(`[data-target="${dropdownId}"]`);
    if (!toggleBtn) {
        console.error(`Toggle button not found for: ${dropdownId}`);
        return;
    }
    
    console.log(`Found all elements for ${inputId} dropdown`);
    
    // Toggle dropdown when button is clicked
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log(`Toggle button clicked for: ${inputId}`);
        
        // Close all other dropdowns first
        document.querySelectorAll('.time-input-container').forEach(container => {
            if (container !== input.parentElement) {
                container.classList.remove('show-dropdown');
            }
        });
        
        // Toggle this dropdown
        input.parentElement.classList.toggle('show-dropdown');
        console.log(`Dropdown toggled: ${input.parentElement.classList.contains('show-dropdown')}`);
    });
    
    // Handle dropdown item selection
    dropdown.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            console.log(`Dropdown item clicked: ${item.dataset.value}`);
            input.value = item.dataset.value;
            input.parentElement.classList.remove('show-dropdown');
            
            // Trigger input event to update any dependent values
            const event = new Event('input', { bubbles: true });
            input.dispatchEvent(event);
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.parentElement.contains(e.target)) {
            input.parentElement.classList.remove('show-dropdown');
        }
    });
}

function handleMobileSidebar() {
    try {
        console.log("Setting up mobile sidebar");
        
        const menuButton = document.getElementById('menuButton');
        const sidebar = document.getElementById('sidebar');
        
        if (!menuButton || !sidebar) {
            console.error("Menu button or sidebar not found for mobile handling");
            return;
        }
        
        // Create overlay for mobile sidebar
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
        
        // Check if we're on mobile
        const isMobile = () => window.innerWidth < 768;
        
        // Handle resize events
        window.addEventListener('resize', function() {
            if (!isMobile()) {
                // Remove mobile-specific classes when returning to desktop
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
        
        // Handle menu button click on mobile
        menuButton.addEventListener('click', function(e) {
            if (isMobile()) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log("Menu button clicked on mobile");
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                
                // Prevent the regular sidebar toggle from running
                e.stopImmediatePropagation();
            }
        });
        
        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        // Add CSS for overlay if not already in stylesheet
        if (!document.getElementById('mobile-sidebar-styles')) {
            const style = document.createElement('style');
            style.id = 'mobile-sidebar-styles';
            style.textContent = `
                .sidebar-overlay {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    z-index: 99;
                    opacity: 0;
                    transition: opacity 0.3s;
                }
                
                .sidebar-overlay.active {
                    display: block;
                    opacity: 1;
                }
                
                @media (max-width: 768px) {
                    .sidebar {
                        transform: translateX(-100%);
                        transition: transform 0.3s ease;
                        z-index: 100;
                    }
                    
                    .sidebar.active {
                        transform: translateX(0);
                        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
                    }
                }
            `;
            document.head.appendChild(style);
        }
    } catch (error) {
        console.error("Error in handleMobileSidebar:", error);
    }
}

function changeCalendarView(view) {
    console.log(`Changing to ${view} view`);
    
    // In a real implementation, you would hide/show or rebuild
    // the calendar based on the selected view
    
    switch(view) {
        case 'month':
            // Show month view (default)
            break;
        case 'week':
            alert('Week view will be implemented in the next version!');
            break;
        case 'day':
            alert('Day view will be implemented in the next version!');
            break;
    }
}

// Sidebar toggle
function handleSidebarToggle() {
    try {
        console.log("Setting up sidebar toggle");
        
        const menuButton = document.getElementById('menuButton');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (!menuButton) {
            console.error("Menu button not found");
            return;
        }
        
        if (!sidebar) {
            console.error("Sidebar not found");
            return;
        }
        
        menuButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log("Menu button clicked");
            sidebar.classList.toggle('collapsed');
            
            if (mainContent) {
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.style.marginLeft = '70px';
                    mainContent.style.width = 'calc(100% - 70px)';
                } else {
                    mainContent.style.marginLeft = '250px';
                    mainContent.style.width = 'calc(100% - 250px)';
                }
            }
        });
    } catch (error) {
        console.error("Error in handleSidebarToggle:", error);
    }
}

// Initialize the Conflict Resolver
function initializeConflictResolver() {
    try {
        console.log("Initializing Conflict Resolver...");
        
        // Get data from JSON elements
        const appointmentsDataElement = document.getElementById('appointmentsData');
        const roomsDataElement = document.getElementById('roomsData');
        const departmentsDataElement = document.getElementById('departmentsData');
        
        if (!appointmentsDataElement || !roomsDataElement || !departmentsDataElement) {
            console.error("Missing data elements for Conflict Resolver");
            return;
        }
        
        // Parse the JSON data
        const appointments = JSON.parse(appointmentsDataElement.textContent || '{}');
        const rooms = JSON.parse(roomsDataElement.textContent || '[]');
        const departments = JSON.parse(departmentsDataElement.textContent || '[]');
        
        // Flatten appointments into an array
        const flatAppointments = [];
        for (const day in appointments) {
            if (appointments.hasOwnProperty(day)) {
                appointments[day].forEach(appointment => {
                    flatAppointments.push(appointment);
                });
            }
        }
        
        // Create the ConflictResolver instance
        conflictResolver = new ConflictResolver(flatAppointments, rooms, departments);
        console.log("Conflict Resolver initialized successfully");
        
        // Setup form submission handling for conflict detection
        setupConflictDetection();
    } catch (error) {
        console.error("Error initializing Conflict Resolver:", error);
    }
}

// Setup conflict detection on form submission
function setupConflictDetection() {
    const bookingForm = document.querySelector('#bookingModal form');
    if (!bookingForm) {
        console.error("Booking form not found");
        return;
    }
    
    // Get form elements
    const dateInput = document.getElementById('date');
    const roomSelect = document.getElementById('room');
    const departmentSelect = document.getElementById('department');
    const timeFromHour = document.getElementById('time_from_hour');
    const timeFromMinute = document.getElementById('time_from_minute');
    const timeFromAmpm = document.getElementById('time_from_ampm');
    const timeToHour = document.getElementById('time_to_hour');
    const timeToMinute = document.getElementById('time_to_minute');
    const timeToAmpm = document.getElementById('time_to_ampm');
    
    // Create an array of all form elements that trigger conflict checking
    const formElements = [dateInput, roomSelect, timeFromHour, timeFromMinute, 
                         timeFromAmpm, timeToHour, timeToMinute, timeToAmpm];
    
    // Add event listeners to check for conflicts when any relevant field changes
    formElements.forEach(element => {
        if (element) {
            // Use both change and input events to catch all changes
            element.addEventListener('change', immediateConflictCheck);
            element.addEventListener('input', immediateConflictCheck);
            
            // For dropdowns, check when an item is selected
            if (element.id.includes('_dropdown')) {
                const dropdownItems = element.querySelectorAll('.dropdown-item');
                dropdownItems.forEach(item => {
                    item.addEventListener('click', immediateConflictCheck);
                });
            }
        }
    });
    
    // Function to check conflicts immediately
    function immediateConflictCheck() {
        // Only check if all required fields have values
        if (dateInput?.value && roomSelect?.value && 
            timeFromHour?.value && timeFromMinute?.value && timeFromAmpm?.value &&
            timeToHour?.value && timeToMinute?.value && timeToAmpm?.value) {
            
            console.log("Checking for conflicts immediately...");
            const hasConflicts = checkForConflicts();
            
            // If conflicts are found, show the conflict resolution UI
            if (hasConflicts) {
                const conflictContainer = document.getElementById('conflict-resolution-container');
                if (conflictContainer) {
                    conflictContainer.style.display = 'block';
                    conflictContainer.scrollIntoView({ behavior: 'smooth' });
                }
            }
        }
    }
    
    // Add form submission handler
    bookingForm.addEventListener('submit', function(e) {
        // Only check if we have a conflict resolver
        if (!conflictResolver) return;
        
        // Force a conflict check before submission
        const hasConflicts = checkForConflicts();
        
        // If there are conflicts and the user hasn't explicitly chosen to ignore them,
        // prevent form submission
        if (hasConflicts && !bookingForm.dataset.ignoreConflicts) {
            e.preventDefault();
            
            // Show the conflict resolution container
            const conflictContainer = document.getElementById('conflict-resolution-container');
            if (conflictContainer) {
                conflictContainer.style.display = 'block';
                
                // Scroll to the conflict container
                conflictContainer.scrollIntoView({ behavior: 'smooth' });
                
                // Show an alert to make it more obvious
                alert("Scheduling conflict detected! Please review the suggested alternatives or click 'Keep Original Time' to proceed anyway.");
            }
        }
    });
    
    // Handle "Keep Original Time" button
    const ignoreConflictsBtn = document.querySelector('.ignore-conflicts');
    if (ignoreConflictsBtn) {
        ignoreConflictsBtn.addEventListener('click', function() {
            // Mark the form to ignore conflicts
            bookingForm.dataset.ignoreConflicts = 'true';
            
            // Hide the conflict container
            const conflictContainer = document.getElementById('conflict-resolution-container');
            if (conflictContainer) {
                conflictContainer.style.display = 'none';
            }
        });
    }
    
    // Handle "Apply Selected Alternative" button
    const applyAlternativeBtn = document.querySelector('.apply-alternative');
    if (applyAlternativeBtn) {
        applyAlternativeBtn.addEventListener('click', function() {
            // Get the selected alternative
            const selectedTimeCard = document.querySelector('#alternative-times .alternative-card.selected');
            const selectedRoomCard = document.querySelector('#alternative-rooms .alternative-card.selected');
            
            // Apply the selected time if any
            if (selectedTimeCard) {
                const timeFrom = selectedTimeCard.dataset.timeFrom;
                const timeTo = selectedTimeCard.dataset.timeTo;
                
                // Parse the time values
                const [fromHour, fromMinute, fromAmpm] = parseTimeString(timeFrom);
                const [toHour, toMinute, toAmpm] = parseTimeString(timeTo);
                
                // Update the form fields
                timeFromHour.value = fromHour;
                timeFromMinute.value = fromMinute;
                timeFromAmpm.value = fromAmpm;
                timeToHour.value = toHour;
                timeToMinute.value = toMinute;
                timeToAmpm.value = toAmpm;
            }
            
            // Apply the selected room if any
            if (selectedRoomCard) {
                roomSelect.value = selectedRoomCard.dataset.roomId;
            }
            
            // Hide the conflict container
            const conflictContainer = document.getElementById('conflict-resolution-container');
            if (conflictContainer) {
                conflictContainer.style.display = 'none';
            }
            
            // Mark the form to ignore conflicts (since we've resolved them)
            bookingForm.dataset.ignoreConflicts = 'true';
        });
    }
    
    // Check for conflicts immediately if the form is pre-filled
    setTimeout(function() {
        if (dateInput?.value && roomSelect?.value && 
            timeFromHour?.value && timeFromMinute?.value && timeFromAmpm?.value &&
            timeToHour?.value && timeToMinute?.value && timeToAmpm?.value) {
            checkForConflicts();
        }
    }, 1000);
}

// Parse a time string like "9:00 AM" into [hour, minute, ampm]
function parseTimeString(timeStr) {
    const [time, ampm] = timeStr.split(' ');
    const [hour, minute] = time.split(':');
    return [parseInt(hour), minute, ampm];
}

// Check for conflicts and update the UI
function checkForConflicts() {
    if (!conflictResolver) {
        console.error("Conflict resolver not initialized");
        return false;
    }
    
    console.log("Running conflict check...");
    
    // Get form values
    const dateInput = document.getElementById('date');
    const roomSelect = document.getElementById('room');
    const departmentSelect = document.getElementById('department');
    const timeFromHour = document.getElementById('time_from_hour');
    const timeFromMinute = document.getElementById('time_from_minute');
    const timeFromAmpm = document.getElementById('time_from_ampm');
    const timeToHour = document.getElementById('time_to_hour');
    const timeToMinute = document.getElementById('time_to_minute');
    const timeToAmpm = document.getElementById('time_to_ampm');
    
    // Ensure all required fields have values
    if (!dateInput?.value || !roomSelect?.value || !departmentSelect?.value ||
        !timeFromHour?.value || !timeFromMinute?.value || !timeFromAmpm?.value ||
        !timeToHour?.value || !timeToMinute?.value || !timeToAmpm?.value) {
        console.log("Missing required fields for conflict check");
        return false;
    }
    
    console.log("Form values for conflict check:", {
        date: dateInput.value,
        roomId: roomSelect.value,
        departmentId: departmentSelect.value,
        timeFromHour: timeFromHour.value,
        timeFromMinute: timeFromMinute.value,
        timeFromAmpm: timeFromAmpm.value,
        timeToHour: timeToHour.value,
        timeToMinute: timeToMinute.value,
        timeToAmpm: timeToAmpm.value
    });
    
    // Format the time values
    const timeFrom = `${timeFromHour.value}:${timeFromMinute.value.padStart(2, '0')} ${timeFromAmpm.value}`;
    const timeTo = `${timeToHour.value}:${timeToMinute.value.padStart(2, '0')} ${timeToAmpm.value}`;
    
    console.log("Formatted times:", { timeFrom, timeTo });
    
    // Calculate duration in minutes
    const fromMinutes = (parseInt(timeFromHour.value) % 12) * 60 + parseInt(timeFromMinute.value);
    const toMinutes = (parseInt(timeToHour.value) % 12) * 60 + parseInt(timeToMinute.value);
    let durationMinutes = toMinutes - fromMinutes;
    
    // Adjust for AM/PM
    if (timeFromAmpm.value === 'AM' && timeToAmpm.value === 'PM') {
        durationMinutes += 12 * 60;
    } else if (timeFromAmpm.value === 'PM' && timeToAmpm.value === 'AM') {
        durationMinutes += 24 * 60;
    } else if (timeFromAmpm.value === timeToAmpm.value && toMinutes < fromMinutes) {
        // Same AM/PM but end time is earlier than start time (next day)
        durationMinutes += 12 * 60;
    }
    
    // Handle negative duration (crossing midnight)
    if (durationMinutes <= 0) {
        durationMinutes += 24 * 60;
    }
    
    console.log("Calculated duration:", { durationMinutes });
    
    try {
        // Analyze the booking for conflicts
        const analysis = conflictResolver.analyzeBooking(
            dateInput.value,
            roomSelect.value,
            departmentSelect.value,
            timeFrom,
            timeTo,
            durationMinutes
        );
        
        console.log("Conflict analysis result:", analysis);
        
        // Update the UI based on the analysis
        updateConflictUI(analysis);
        
        return analysis.hasConflicts;
    } catch (error) {
        console.error("Error during conflict analysis:", error);
        return false;
    }
}

// Update the conflict resolution UI
function updateConflictUI(analysis) {
    const conflictContainer = document.getElementById('conflict-resolution-container');
    const conflictMessage = document.getElementById('conflict-message');
    const alternativeTimesContainer = document.getElementById('alternative-times');
    const alternativeRoomsContainer = document.getElementById('alternative-rooms');
    const applyAlternativeBtn = document.querySelector('.apply-alternative');
    
    if (!conflictContainer || !conflictMessage || !alternativeTimesContainer || 
        !alternativeRoomsContainer || !applyAlternativeBtn) {
        console.error("Conflict UI elements not found");
        return;
    }
    
    // If no conflicts, hide the container and return
    if (!analysis.hasConflicts) {
        conflictContainer.style.display = 'none';
        return;
    }
    
    // Show the container
    conflictContainer.style.display = 'block';
    
    // Update the message
    conflictMessage.textContent = analysis.message;
    
    // Clear previous alternatives
    alternativeTimesContainer.innerHTML = '';
    alternativeRoomsContainer.innerHTML = '';
    
    // Add alternative times
    if (analysis.alternativeTimes && analysis.alternativeTimes.length > 0) {
        analysis.alternativeTimes.forEach(alt => {
            const card = document.createElement('div');
            card.className = 'alternative-card';
            card.dataset.timeFrom = alt.timeFrom;
            card.dataset.timeTo = alt.timeTo;
            
            card.innerHTML = `
                <h6><i class="fas fa-clock"></i> Alternative Time <span class="score">${alt.score}</span></h6>
                <p>${alt.timeFrom} - ${alt.timeTo}</p>
            `;
            
            // Add click handler to select this alternative
            card.addEventListener('click', function() {
                // Remove selected class from all time cards
                document.querySelectorAll('#alternative-times .alternative-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to this card
                card.classList.add('selected');
                
                // Enable the apply button
                applyAlternativeBtn.disabled = false;
            });
            
            alternativeTimesContainer.appendChild(card);
        });
    } else {
        alternativeTimesContainer.innerHTML = '<p>No alternative times available.</p>';
    }
    
    // Add alternative rooms
    if (analysis.alternativeRooms && analysis.alternativeRooms.length > 0) {
        analysis.alternativeRooms.forEach(alt => {
            const card = document.createElement('div');
            card.className = 'alternative-card';
            card.dataset.roomId = alt.roomId;
            
            card.innerHTML = `
                <h6><i class="fas fa-door-open"></i> Alternative Room <span class="score">${alt.score}</span></h6>
                <p>${alt.roomName}</p>
            `;
            
            // Add click handler to select this alternative
            card.addEventListener('click', function() {
                // Remove selected class from all room cards
                document.querySelectorAll('#alternative-rooms .alternative-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to this card
                card.classList.add('selected');
                
                // Enable the apply button
                applyAlternativeBtn.disabled = false;
            });
            
            alternativeRoomsContainer.appendChild(card);
        });
    } else {
        alternativeRoomsContainer.innerHTML = '<p>No alternative rooms available.</p>';
    }
    
    // Disable the apply button initially
    applyAlternativeBtn.disabled = true;
}