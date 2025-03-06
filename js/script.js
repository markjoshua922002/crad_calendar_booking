document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM fully loaded and parsed - v13");

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

        // Sidebar toggle
        const menuButton = document.getElementById('menuButton');
        const sidebar = document.getElementById('sidebar');
        
        if (menuButton && sidebar) {
            menuButton.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.classList.toggle('expanded');
                }
            });
        } else {
            console.error("Menu button or sidebar not found!");
        }
        
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
        
        // Setup time pickers
        setupTimePicker('time_from_hour', 'time_from_minute', 'time_from_ampm', 'time_from');
        setupTimePicker('time_to_hour', 'time_to_minute', 'time_to_ampm', 'time_to');
        setupTimePicker('edit_time_from_hour', 'edit_time_from_minute', 'edit_time_from_ampm', 'edit_time_from');
        setupTimePicker('edit_time_to_hour', 'edit_time_to_minute', 'edit_time_to_ampm', 'edit_time_to');
        
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

function setupTimePicker(hourSelectId, minuteSelectId, ampmSelectId, timeInputId) {
    const hourSelect = document.getElementById(hourSelectId);
    const minuteSelect = document.getElementById(minuteSelectId);
    const ampmSelect = document.getElementById(ampmSelectId);
    const timeInput = document.getElementById(timeInputId);
    
    if (!hourSelect || !minuteSelect || !ampmSelect) {
        return;
    }
    
    const updateTimeInput = () => {
        if (hourSelect.value && minuteSelect.value && ampmSelect.value) {
            let hour = parseInt(hourSelect.value);
            const minute = minuteSelect.value;
            const ampm = ampmSelect.value;
            
            // Convert to 24-hour format
            if (ampm === 'PM' && hour < 12) {
                hour += 12;
            } else if (ampm === 'AM' && hour === 12) {
                hour = 0;
            }
            
            // Format as HH:MM:SS
            const time = `${hour.toString().padStart(2, '0')}:${minute}:00`;
            
            if (timeInput) {
                timeInput.value = time;
            }
        }
    };
    
    // Add event listeners to update the hidden time input
    hourSelect.addEventListener('change', updateTimeInput);
    minuteSelect.addEventListener('change', updateTimeInput);
    ampmSelect.addEventListener('change', updateTimeInput);
}

function handleMobileSidebar() {
    const menuButton = document.getElementById('menuButton');
    const sidebar = document.getElementById('sidebar');
    
    if (menuButton && sidebar) {
        menuButton.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isMobile = window.innerWidth < 768;
            if (isMobile && !sidebar.contains(event.target) && event.target !== menuButton) {
                sidebar.classList.remove('active');
            }
        });
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