// Initialize conflict resolver
let conflictResolver = null;

document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM fully loaded and parsed - v15");

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
        const modals = {
            bookingModal: document.getElementById('bookingModal'),
            editModal: document.getElementById('editModal'),
            viewModal: document.getElementById('viewModal'),
            addDepartmentModal: document.getElementById('addDepartmentModal'),
            addRoomModal: document.getElementById('addRoomModal'),
            dayViewModal: document.getElementById('dayViewModal'),
            appointmentModal: document.getElementById('appointmentModal'),
            searchModal: document.getElementById('searchModal')
        };
        
        console.log("Modals found:", {
            bookingModal: !!modals.bookingModal,
            editModal: !!modals.editModal,
            viewModal: !!modals.viewModal,
            addDepartmentModal: !!modals.addDepartmentModal,
            addRoomModal: !!modals.addRoomModal,
            dayViewModal: !!modals.dayViewModal,
            appointmentModal: !!modals.appointmentModal,
            searchModal: !!modals.searchModal
        });
        
        // Debug modal styles
        Object.entries(modals).forEach(([name, modal]) => {
            if (modal) {
                console.log(`Modal ${name} styles:`, {
                    display: modal.style.display,
                    position: getComputedStyle(modal).position,
                    zIndex: getComputedStyle(modal).zIndex,
                    width: getComputedStyle(modal).width,
                    height: getComputedStyle(modal).height
                });
                
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    console.log(`Modal ${name} content styles:`, {
                        width: getComputedStyle(modalContent).width,
                        maxWidth: getComputedStyle(modalContent).maxWidth,
                        margin: getComputedStyle(modalContent).margin
                    });
                } else {
                    console.error(`Modal ${name} content not found`);
                }
            }
        });
        
        // Log all close buttons for debugging
        console.log("Close buttons found:", {
            closeBookingModal: !!document.getElementById('closeBookingModal'),
            closeEditModal: !!document.getElementById('closeEditModal'),
            closeViewModal: !!document.getElementById('closeViewModal'),
            closeAddDepartmentModal: !!document.getElementById('closeAddDepartmentModal'),
            closeAddRoomModal: !!document.getElementById('closeAddRoomModal'),
            closeDayViewModal: !!document.getElementById('closeDayViewModal'),
            closeAppointmentModal: !!document.getElementById('closeAppointmentModal'),
            closeSearchModal: !!document.getElementById('closeSearchModal')
        });

        // Initialize all modals
        setupModal('bookingModal', 'openBookingModal', 'closeBookingModal');
        setupModal('editModal', null, 'closeEditModal');
        setupModal('viewModal', null, 'closeViewModal');
        setupModal('addDepartmentModal', 'openAddDepartmentModal', 'closeAddDepartmentModal');
        setupModal('addRoomModal', 'openAddRoomModal', 'closeAddRoomModal');
        setupModal('dayViewModal', null, 'closeDayViewModal');
        setupModal('appointmentModal', null, 'closeAppointmentModal');
        setupModal('searchModal', null, 'closeSearchModal');

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
                
                // Close any other open modals first
                document.querySelectorAll('.modal').forEach(m => {
                    if (m.id !== 'addDepartmentModal' && m.style.display === 'flex') {
                        hideModal(m);
                    }
                });
                
                // Show the modal
                showModal(addDepartmentModal);
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
                
                // Close any other open modals first
                document.querySelectorAll('.modal').forEach(m => {
                    if (m.id !== 'addRoomModal' && m.style.display === 'flex') {
                        hideModal(m);
                    }
                });
                
                // Show the modal
                showModal(addRoomModal);
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
                
                // Close any other open modals first
                document.querySelectorAll('.modal').forEach(m => {
                    if (m.id !== 'appointmentModal' && m.style.display === 'flex') {
                        hideModal(m);
                    }
                });
                
                // Show the modal
                showModal(appointmentModal);
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
                    hideModal(dayViewModal);
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
        
        // Setup upcoming appointment clicks
        setupUpcomingAppointmentClicks();
        
        // Setup search functionality
        setupSearch();
        
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
        
        // Ensure the modal has the correct initial styles
        modal.style.display = 'none';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        
        // Setup open button if provided
        if (openButtonId) {
            const openButton = document.getElementById(openButtonId);
            if (openButton) {
                console.log(`Adding click listener to open button: ${openButtonId}`);
                openButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling
                    
                    // Show the modal
                    showModal(modal);
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
                    hideModal(modal);
                });
            } else {
                console.error(`Close button with ID ${closeButtonId} not found`);
            }
        }
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                hideModal(modal);
            }
        });
        
        // Handle window resize to keep modal centered
        window.addEventListener('resize', function() {
            if (modal.style.display === 'flex') {
                centerModal(modal);
            }
        });
    } catch (error) {
        console.error(`Error setting up modal ${modalId}:`, error);
    }
}

// Function to show a modal
function showModal(modal) {
    if (!modal) {
        console.error("Cannot show modal: modal is null or undefined");
        return;
    }
    
    console.log(`Showing modal: ${modal.id}`);
    
    try {
        // Close any other open modals first
        document.querySelectorAll('.modal').forEach(m => {
            if (m !== modal && m.style.display === 'flex') {
                console.log(`Closing other modal: ${m.id}`);
                hideModal(m);
            }
        });
        
        // Get the current scroll position
        const scrollY = window.scrollY;
        
        // Add class to body to prevent scrolling
        document.body.classList.add('modal-open');
        
        // Show modal with flex display
        modal.style.display = 'flex';
        modal.classList.add('show');
        
        // Ensure the modal covers the entire viewport
        modal.style.height = '100vh';
        
        // Prevent the background from shifting
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        
        // Store the scroll position for later
        document.body.dataset.scrollY = scrollY;
        
        // Ensure the modal content is visible and centered
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.cssText = `
                opacity: 1;
                transform: translateY(0);
                margin: 20px auto;
                max-height: 85vh;
                width: 90%;
                position: relative;
            `;
            
            // Log modal dimensions for debugging
            console.log(`Modal content dimensions: ${modalContent.offsetWidth}x${modalContent.offsetHeight}`);
        }
        
        // Special handling for booking modal
        if (modal.id === 'bookingModal') {
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
    } catch (error) {
        console.error(`Error showing modal ${modal.id}:`, error);
    }
}

// Function to hide a modal
function hideModal(modal) {
    if (!modal) return;
    
    console.log(`Hiding modal: ${modal.id}`);
    
    try {
        // Hide the modal
        modal.style.display = 'none';
        modal.classList.remove('show');
        
        // Check if there are any other visible modals
        const visibleModals = document.querySelectorAll('.modal[style*="display: flex"]');
        if (visibleModals.length === 0) {
            // Remove body class only if no other modals are visible
            document.body.classList.remove('modal-open');
            
            // Restore the page position
            const scrollY = parseInt(document.body.dataset.scrollY || '0');
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            window.scrollTo(0, scrollY);
        }
        
        // Special handling for booking modal
        if (modal.id === 'bookingModal') {
            // Reset the conflict container
            const conflictContainer = document.getElementById('conflict-resolution-container');
            if (conflictContainer) {
                conflictContainer.style.display = 'none';
            }
        }
    } catch (error) {
        console.error(`Error hiding modal ${modal.id}:`, error);
    }
}

// Function to center a modal in the viewport
function centerModal(modal) {
    try {
        if (!modal) return;
        
        console.log("Centering modal:", modal.id);
        
        // Get the modal content
        const modalContent = modal.querySelector('.modal-content');
        if (!modalContent) {
            console.error("Modal content not found");
            return;
        }
        
        // Reset positioning styles
        modalContent.style.margin = 'auto';
        
        // Calculate viewport dimensions
        const viewportHeight = window.innerHeight;
        const contentHeight = modalContent.scrollHeight;
        
        console.log(`Modal ${modal.id} - Content height: ${contentHeight}px, Viewport height: ${viewportHeight}px`);
        
        // Adjust max-height if content is too tall
        if (contentHeight > viewportHeight * 0.9) {
            modalContent.style.maxHeight = `${viewportHeight * 0.9}px`;
            modalContent.style.overflowY = 'auto';
            console.log(`Modal ${modal.id} - Content exceeds 90% of viewport, enabling scrolling`);
        } else {
            modalContent.style.maxHeight = '';
            modalContent.style.overflowY = '';
            console.log(`Modal ${modal.id} - Content fits within viewport`);
        }
    } catch (error) {
        console.error('Error centering modal:', error);
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
        console.log(`Showing appointments for day ${dayNumber}:`, appointments);
        
        const appointmentList = document.getElementById('appointmentList');
        if (!appointmentList) {
            console.error("Appointment list element not found");
            return;
        }
        
        // Clear previous appointments
        appointmentList.innerHTML = '';
        
        // Set the day title
        const dayTitle = document.getElementById('dayTitle');
        if (dayTitle) {
            const monthYearElement = document.querySelector('.month-year');
            const currentMonth = monthYearElement ? monthYearElement.textContent : '';
            dayTitle.textContent = `Appointments for ${currentMonth} ${dayNumber}`;
        }
        
        // Add appointments to the list
        if (appointments.length === 0) {
            appointmentList.innerHTML = '<p class="no-appointments">No appointments for this day.</p>';
        } else {
            appointments.forEach(appointment => {
                try {
                    // Format times for display
                    const timeFrom = new Date(`2000-01-01T${appointment.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const timeTo = new Date(`2000-01-01T${appointment.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    const appointmentItem = document.createElement('div');
                    appointmentItem.className = 'appointment-item';
                    appointmentItem.style.borderLeft = `4px solid ${appointment.color || '#4285f4'}`;
                    appointmentItem.dataset.id = appointment.id;
                    
                    appointmentItem.innerHTML = `
                        <div class="appointment-content">
                            <div class="appointment-info">
                                <p>
                                    <strong>Research Adviser</strong>
                                    ${appointment.name || 'N/A'}
                                </p>
                                <p>
                                    <strong>Time</strong>
                                    ${timeFrom} - ${timeTo}
                                </p>
                                <p>
                                    <strong>Department</strong>
                                    ${appointment.department_name || 'N/A'}
                                </p>
                                <p>
                                    <strong>Room</strong>
                                    ${appointment.room_name || 'N/A'}
                                </p>
                                <p>
                                    <strong>Representative</strong>
                                    ${appointment.representative_name || 'N/A'}
                                </p>
                                <p>
                                    <strong>Agenda</strong>
                                    ${appointment.reason || 'N/A'}
                                </p>
                            </div>
                            <div class="appointment-actions">
                                <button class="view-appointment" data-id="${appointment.id}">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="edit-appointment" data-id="${appointment.id}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                    `;
                    
                    appointmentList.appendChild(appointmentItem);
                    
                    // Add event listeners directly to the buttons
                    const viewButton = appointmentItem.querySelector('.view-appointment');
                    const editButton = appointmentItem.querySelector('.edit-appointment');
                    
                    if (viewButton) {
                        viewButton.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            showAppointmentDetails(appointment);
                        });
                    }
                    
                    if (editButton) {
                        editButton.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            fillEditForm(appointment);
                        });
                    }
                } catch (error) {
                    console.error("Error creating appointment item:", error, appointment);
                }
            });
        }
        
        // Show the day view modal
        const dayViewModal = document.getElementById('dayViewModal');
        if (dayViewModal) {
            showModal(dayViewModal);
        }
    } catch (error) {
        console.error("Error in showDayAppointments:", error);
    }
}

function openBookingModalWithDate(day) {
    try {
        console.log(`Opening booking modal with date: ${day}`);
        
        // Get the current month and year from the UI
        const monthYearElement = document.querySelector('.month-year');
        if (!monthYearElement) {
            console.error("Month/year element not found");
            return;
        }
        
        const monthYear = monthYearElement.textContent;
        const [month, year] = monthYear.split(' ');
        
        // Convert month name to month number
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const monthNumber = monthNames.indexOf(month) + 1;
        
        if (monthNumber === 0) {
            console.error(`Invalid month name: ${month}`);
            return;
        }
        
        // Format the date as YYYY-MM-DD
        const formattedDate = `${year}-${monthNumber.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
        console.log(`Formatted date: ${formattedDate}`);
        
        // Set the date in the booking form
        const dateInput = document.getElementById('date');
        if (dateInput) {
            dateInput.value = formattedDate;
        } else {
            console.error("Date input not found");
        }
        
        // Show the booking modal
        const bookingModal = document.getElementById('bookingModal');
        if (bookingModal) {
            // Close any other open modals first
            document.querySelectorAll('.modal').forEach(m => {
                if (m.id !== 'bookingModal' && m.style.display === 'flex') {
                    hideModal(m);
                }
            });
            
            // Show the modal
            showModal(bookingModal);
        } else {
            console.error("Booking modal not found");
        }
    } catch (error) {
        console.error("Error in openBookingModalWithDate:", error);
    }
}

function setupAppointmentClicks() {
    console.log("Setting up appointment clicks");
    
    try {
        // Add click event to all appointment items in the calendar
        const dayEvents = document.querySelectorAll('.day-event');
        console.log(`Found ${dayEvents.length} day events`);
        
        dayEvents.forEach(appointment => {
            appointment.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent triggering the day click event
                
                const appointmentId = this.dataset.id;
                console.log(`Day event clicked: ${appointmentId}`);
                
                // Get appointment data from the hidden JSON element
                const appointmentsDataElement = document.getElementById('appointmentsData');
                if (!appointmentsDataElement) {
                    console.error("Appointments data element not found");
                    return;
                }
                
                try {
                    const appointmentsData = JSON.parse(appointmentsDataElement.textContent || '{}');
                    console.log("Parsed appointments data:", appointmentsData);
                    
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
                        console.log("Found appointment:", foundAppointment);
                        showAppointmentDetails(foundAppointment);
                    } else {
                        console.error(`Appointment with ID ${appointmentId} not found`);
                        console.log("Available appointments:", appointmentsData);
                    }
                } catch (error) {
                    console.error("Error parsing appointments data:", error);
                }
            });
        });
        
        // Add click event to view appointment buttons
        const viewButtons = document.querySelectorAll('.view-appointment');
        console.log(`Found ${viewButtons.length} view buttons`);
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const appointmentId = this.dataset.id;
                console.log(`View button clicked: ${appointmentId}`);
                
                const appointmentsDataElement = document.getElementById('appointmentsData');
                if (!appointmentsDataElement) {
                    console.error("Appointments data element not found");
                    return;
                }
                
                try {
                    const appointmentsData = JSON.parse(appointmentsDataElement.textContent || '{}');
                    let foundAppointment = null;
                    
                    Object.values(appointmentsData).forEach(dayAppointments => {
                        dayAppointments.forEach(appointment => {
                            if (appointment.id == appointmentId) {
                                foundAppointment = appointment;
                            }
                        });
                    });
                    
                    if (foundAppointment) {
                        console.log("Found appointment:", foundAppointment);
                        showAppointmentDetails(foundAppointment);
                    } else {
                        console.error(`Appointment with ID ${appointmentId} not found`);
                    }
                } catch (error) {
                    console.error("Error parsing appointments data:", error);
                }
            });
        });
    } catch (error) {
        console.error("Error in setupAppointmentClicks:", error);
    }
}

// Add formatTime function
function formatTime(timeString) {
    try {
        const time = new Date(`2000-01-01T${timeString}`);
        return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch (error) {
        console.error("Error formatting time:", error);
        return timeString;
    }
}

function showAppointmentDetails(appointment) {
    try {
        console.log("Showing appointment details:", appointment);
        const viewContainer = document.getElementById('viewContainer');
        if (!viewContainer) {
            console.error("View container not found");
            return;
        }

        const timeFrom = formatTime(appointment.booking_time_from);
        const timeTo = formatTime(appointment.booking_time_to);
        const date = new Date(appointment.booking_date).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        viewContainer.innerHTML = `
            <div class="appointment-details">
                <div class="detail-group">
                    <label>Research Adviser:</label>
                    <span>${appointment.name || 'N/A'}</span>
                </div>
                <div class="detail-group">
                    <label>Representative:</label>
                    <span>${appointment.representative_name || 'N/A'}</span>
                </div>
                <div class="detail-group">
                    <label>Set:</label>
                    <span>${appointment.set_name || 'N/A'}</span>
                </div>
                <div class="detail-group">
                    <label>Department:</label>
                    <span>${appointment.department_name || 'N/A'}</span>
                </div>
                <div class="detail-group">
                    <label>Room:</label>
                    <span>${appointment.room_name || 'N/A'}</span>
                </div>
                <div class="detail-group">
                    <label>Date:</label>
                    <span>${date}</span>
                </div>
                <div class="detail-group">
                    <label>Time:</label>
                    <span>${timeFrom} - ${timeTo}</span>
                </div>
                <div class="detail-group">
                    <label>Agenda:</label>
                    <span>${appointment.reason || 'N/A'}</span>
                </div>
                <div class="detail-group">
                    <label>Remarks:</label>
                    <span>${appointment.group_members || 'N/A'}</span>
                </div>
            </div>
            <div class="appointment-actions">
                <button onclick="fillEditForm(${JSON.stringify(appointment).replace(/"/g, '&quot;')})" class="edit-button">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
        `;

        // Show the view modal
        const viewModal = document.getElementById('viewModal');
        if (viewModal) {
            showModal(viewModal);
        } else {
            console.error("View modal not found");
        }
    } catch (error) {
        console.error("Error in showAppointmentDetails:", error);
    }
}

function fillEditForm(appointment) {
    console.log("Filling edit form with appointment:", appointment);
    
    try {
        // Hide view modal and show edit modal
        hideModal(document.getElementById('viewModal'));
        showModal(document.getElementById('editModal'));

        // Set form values
        document.getElementById('appointment_id').value = appointment.id;
        document.getElementById('edit_name').value = appointment.name;
        document.getElementById('edit_representative_name').value = appointment.representative_name;
        document.getElementById('edit_id_number').value = appointment.id_number;
        document.getElementById('edit_set').value = appointment.set_name;
        document.getElementById('edit_department').value = appointment.department_id;
        document.getElementById('edit_room').value = appointment.room_id;
        
        // Format date properly for the date input
        const bookingDate = new Date(appointment.booking_date);
        const formattedDate = bookingDate.toISOString().split('T')[0];
        document.getElementById('edit_date').value = formattedDate;
        
        document.getElementById('edit_reason').value = appointment.reason;
        document.getElementById('edit_group_members').value = appointment.group_members || '';

        // Parse and set time values
        const timeFrom = new Date(`2000-01-01 ${appointment.booking_time_from}`);
        const timeTo = new Date(`2000-01-01 ${appointment.booking_time_to}`);

        // Set hours (12-hour format)
        document.getElementById('edit_time_from_hour').value = timeFrom.getHours() > 12 ? 
            timeFrom.getHours() - 12 : (timeFrom.getHours() === 0 ? 12 : timeFrom.getHours());
        document.getElementById('edit_time_to_hour').value = timeTo.getHours() > 12 ? 
            timeTo.getHours() - 12 : (timeTo.getHours() === 0 ? 12 : timeTo.getHours());

        // Set minutes (pad with leading zero if needed)
        document.getElementById('edit_time_from_minute').value = timeFrom.getMinutes().toString().padStart(2, '0');
        document.getElementById('edit_time_to_minute').value = timeTo.getMinutes().toString().padStart(2, '0');

        // Set AM/PM
        document.getElementById('edit_time_from_ampm').value = timeFrom.getHours() >= 12 ? 'PM' : 'AM';
        document.getElementById('edit_time_to_ampm').value = timeTo.getHours() >= 12 ? 'PM' : 'AM';
    } catch (error) {
        console.error("Error filling edit form:", error);
        alert("Error loading appointment details. Please try again.");
    }
}

// Add form submission handler
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    try {
        // Validate required fields
        const requiredFields = [
            'edit_name',
            'edit_representative_name',
            'edit_department',
            'edit_room',
            'edit_date',
            'edit_time_from_hour',
            'edit_time_from_minute',
            'edit_time_to_hour',
            'edit_time_to_minute'
        ];

        for (const fieldId of requiredFields) {
            const field = document.getElementById(fieldId);
            if (!field.value) {
                alert(`Please fill in all required fields (${fieldId.replace('edit_', '')}).`);
                field.focus();
                return;
            }
        }

        // Validate date
        const dateField = document.getElementById('edit_date');
        const selectedDate = new Date(dateField.value);
        if (isNaN(selectedDate.getTime())) {
            alert('Please enter a valid date.');
            dateField.focus();
            return;
        }

        // Submit the form
        this.submit();
    } catch (error) {
        console.error("Error submitting edit form:", error);
        alert("Error updating appointment. Please try again.");
    }
});

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
                    
                    // Use AJAX to delete the appointment
                    fetch('api/delete_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${appointmentId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Delete result:", data);
                        
                        // Close the edit modal
                        const editModal = document.getElementById('editModal');
                        if (editModal) {
                            hideModal(editModal);
                        }
                        
                        // Show message based on response
                        const alertDiv = document.createElement('div');
                        alertDiv.className = `alert alert-${data.success ? 'success' : 'danger'}`;
                        alertDiv.innerHTML = `
                            <i class="fas fa-${data.success ? 'check' : 'exclamation'}-circle"></i>
                            ${data.message}
                        `;
                        
                        // Insert the alert at the top of the main content
                        const mainContent = document.querySelector('.main-content');
                        if (mainContent) {
                            mainContent.insertBefore(alertDiv, mainContent.firstChild);
                            
                            // Only proceed if deletion was successful
                            if (data.success) {
                                // Update the appointments data in the hidden element
                                const appointmentsDataElement = document.getElementById('appointmentsData');
                                if (appointmentsDataElement) {
                                    const appointmentsData = JSON.parse(appointmentsDataElement.textContent || '{}');
                                    
                                    // Remove the deleted appointment from the data
                                    Object.keys(appointmentsData).forEach(day => {
                                        appointmentsData[day] = appointmentsData[day].filter(
                                            appointment => appointment.id != appointmentId
                                        );
                                        // Remove the day if it has no appointments
                                        if (appointmentsData[day].length === 0) {
                                            delete appointmentsData[day];
                                        }
                                    });
                                    
                                    // Update the hidden element
                                    appointmentsDataElement.textContent = JSON.stringify(appointmentsData);
                                    
                                    // Reset conflict resolver
                                    if (conflictResolver) {
                                        conflictResolver = null;
                                    }
                                    
                                    // Hide any existing conflict containers
                                    const conflictContainer = document.getElementById('conflict-resolution-container');
                                    if (conflictContainer) {
                                        conflictContainer.style.display = 'none';
                                    }
                                    
                                    // Reinitialize conflict resolver with new data
                                    initializeConflictResolver();
                                    
                                    // Remove the alert after 3 seconds and reload
                                    setTimeout(() => {
                                        alertDiv.remove();
                                        window.location.reload(); // Reload to refresh calendar view
                                    }, 3000);
                                }
                            } else {
                                // Remove the alert after 3 seconds
                                setTimeout(() => {
                                    alertDiv.remove();
                                }, 3000);
                            }
                        }
                    })
                    .catch(error => {
                        console.error("Error deleting appointment:", error);
                        
                        // Show error message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger';
                        alertDiv.innerHTML = `
                            <i class="fas fa-exclamation-circle"></i>
                            Error deleting appointment. Please try again.
                        `;
                        
                        // Insert the alert at the top of the main content
                        const mainContent = document.querySelector('.main-content');
                        if (mainContent) {
                            mainContent.insertBefore(alertDiv, mainContent.firstChild);
                            
                            // Remove the alert after 3 seconds
                            setTimeout(() => {
                                alertDiv.remove();
                            }, 3000);
                        }
                    });
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
    console.log('Initializing conflict resolver...');
    
    // Get the appointments data from the hidden element
    const appointmentsData = document.getElementById('appointmentsData');
    const roomsData = document.getElementById('roomsData');
    const departmentsData = document.getElementById('departmentsData');
    
    if (!appointmentsData || !roomsData || !departmentsData) {
        console.error('Missing required data elements');
            return;
        }
        
    try {
        // Parse the JSON data
        const appointments = JSON.parse(appointmentsData.textContent);
        const rooms = JSON.parse(roomsData.textContent);
        const departments = JSON.parse(departmentsData.textContent);

        // Convert appointments object to array and flatten it
        const flatAppointments = [];
        Object.keys(appointments).forEach(day => {
                appointments[day].forEach(appointment => {
                if (appointment && appointment.booking_date) {
                    flatAppointments.push(appointment);
                }
            });
        });

        console.log('Appointments loaded:', flatAppointments.length);
        console.log('Rooms loaded:', rooms.length);
        console.log('Departments loaded:', departments.length);

        // Create new instance of ConflictResolver
        conflictResolver = new ConflictResolver(flatAppointments, rooms, departments);
        
        // Set up the event listeners for real-time checking
        setupConflictDetection();
        
        console.log('Conflict resolver initialized successfully');
    } catch (error) {
        console.error('Error initializing conflict resolver:', error);
    }
}

function setupConflictDetection() {
    console.log('Setting up conflict detection...');
    
    const bookingForm = document.querySelector('#bookingModal form');
    if (!bookingForm) {
        console.error('Booking form not found');
        return;
    }
    
    const inputs = [
        { id: 'date', events: ['input', 'change'] },
        { id: 'room', events: ['input', 'change'] },
        { id: 'time_from_hour', events: ['input', 'change', 'keyup'] },
        { id: 'time_from_minute', events: ['input', 'change', 'keyup'] },
        { id: 'time_from_ampm', events: ['input', 'change'] },
        { id: 'time_to_hour', events: ['input', 'change', 'keyup'] },
        { id: 'time_to_minute', events: ['input', 'change', 'keyup'] },
        { id: 'time_to_ampm', events: ['input', 'change'] }
    ];

    inputs.forEach(({ id, events }) => {
        const element = document.getElementById(id);
        if (element) {
            events.forEach(eventType => {
                element.addEventListener(eventType, () => {
                    console.log(`Input changed: ${id}`);
                    if (conflictResolver) {
                        conflictResolver.checkConflictsRealTime();
                    }
                });
            });
            console.log(`Added listeners for ${id}`);
        }
    });

    // Add immediate check when opening the booking modal
    const openBookingButton = document.getElementById('openBookingModal');
    if (openBookingButton) {
        openBookingButton.addEventListener('click', () => {
            console.log('Booking modal opened');
            // Short delay to ensure modal is fully opened
            setTimeout(() => {
                if (conflictResolver) {
                    conflictResolver.checkConflictsRealTime();
                }
            }, 100);
        });
    }

    // Add check when selecting a date from calendar
    document.querySelectorAll('.day').forEach(day => {
        day.addEventListener('click', () => {
            setTimeout(() => {
                if (conflictResolver) {
                    conflictResolver.checkConflictsRealTime();
                }
            }, 100);
        });
    });

    // Add form submission handler
    bookingForm.addEventListener('submit', (e) => {
        if (conflictResolver) {
            const date = document.getElementById('date')?.value;
            const roomId = document.getElementById('room')?.value;
            const timeFrom = conflictResolver.formatTime(
                document.getElementById('time_from_hour')?.value,
                document.getElementById('time_from_minute')?.value,
                document.getElementById('time_from_ampm')?.value
            );
            const timeTo = conflictResolver.formatTime(
                document.getElementById('time_to_hour')?.value,
                document.getElementById('time_to_minute')?.value,
                document.getElementById('time_to_ampm')?.value
            );

            const conflicts = conflictResolver.checkConflicts(date, roomId, timeFrom, timeTo);
            if (conflicts.length > 0 && !bookingForm.dataset.ignoreConflicts) {
                e.preventDefault();
                conflictResolver.showConflictAlert({
                    hasConflicts: true,
                    conflicts: conflicts,
                    message: 'This time slot is already booked. Please choose another time or room.',
                    alternativeTimes: conflictResolver.generateAlternativeTimes(date, roomId),
                    alternativeRooms: conflictResolver.generateAlternativeRooms(date, timeFrom, timeTo)
                });
            }
        }
    });
}

function setupUpcomingAppointmentClicks() {
    console.log("Setting up upcoming appointment clicks");
    
    // Add click event to all upcoming appointment items in the sidebar
    document.querySelectorAll('.event-item.upcoming-appointment').forEach(appointment => {
        appointment.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const appointmentId = this.dataset.id;
            console.log(`Upcoming appointment clicked: ${appointmentId}`);
            
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
            } else {
                console.error(`Appointment with ID ${appointmentId} not found in data`);
                console.log("Available appointment data:", appointmentsData);
            }
        });
    });
}

function setupSearch() {
    console.log("Setting up search functionality");
    
    const searchInput = document.getElementById('search_name');
    const searchButton = document.getElementById('search_button');
    const searchResults = document.getElementById('searchResults');
    const searchModal = document.getElementById('searchModal');
    
    if (!searchInput || !searchButton || !searchResults || !searchModal) {
        console.error("Search elements not found:", {
            searchInput: !!searchInput,
            searchButton: !!searchButton,
            searchResults: !!searchResults,
            searchModal: !!searchModal
        });
        return;
    }
    
    // Add event listener to search button
    searchButton.addEventListener('click', function() {
        performSearch();
    });
    
    // Add event listener to search input for Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    function performSearch() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        if (searchTerm.length < 2) {
            alert("Please enter at least 2 characters to search");
            return;
        }
        
        console.log(`Performing search for: ${searchTerm}`);
        
        // Get appointment data from the hidden JSON element
        const appointmentsData = JSON.parse(document.getElementById('appointmentsData')?.textContent || '{}');
        
        // Find matching appointments
        const matchingAppointments = [];
        const exactMatchesByRepresentative = [];
        const partialMatchesByRepresentative = [];
        const otherMatches = [];
        
        Object.values(appointmentsData).forEach(dayAppointments => {
            dayAppointments.forEach(appointment => {
                // Check for representative name match first (prioritized)
                if (appointment.representative_name && appointment.representative_name.toLowerCase() === searchTerm) {
                    // Exact match on representative name (highest priority)
                    exactMatchesByRepresentative.push(appointment);
                } else if (appointment.representative_name && appointment.representative_name.toLowerCase().includes(searchTerm)) {
                    // Partial match on representative name (medium priority)
                    partialMatchesByRepresentative.push(appointment);
                } else if (
                    (appointment.name && appointment.name.toLowerCase().includes(searchTerm)) ||
                    (appointment.reason && appointment.reason.toLowerCase().includes(searchTerm))
                ) {
                    // Match on other fields (lowest priority)
                    otherMatches.push(appointment);
                }
            });
        });
        
        // Combine results in priority order
        matchingAppointments.push(...exactMatchesByRepresentative);
        matchingAppointments.push(...partialMatchesByRepresentative);
        matchingAppointments.push(...otherMatches);
        
        console.log(`Found ${matchingAppointments.length} matching appointments (${exactMatchesByRepresentative.length} exact representative matches, ${partialMatchesByRepresentative.length} partial representative matches, ${otherMatches.length} other matches)`);
        
        // Display search results
        if (matchingAppointments.length === 0) {
            searchResults.innerHTML = '<p>No appointments found matching your search.</p>';
        } else {
            searchResults.innerHTML = `<p>Found ${matchingAppointments.length} appointments matching your search:</p>`;
            
            const resultsContainer = document.createElement('div');
            resultsContainer.className = 'search-results-container';
            
            matchingAppointments.forEach(appointment => {
                try {
                    // Format times for display
                    const timeFrom = new Date(`2000-01-01T${appointment.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const timeTo = new Date(`2000-01-01T${appointment.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    const appointmentItem = document.createElement('div');
                    appointmentItem.className = 'appointment-item';
                    appointmentItem.style.borderLeft = `4px solid ${appointment.color || '#4285f4'}`;
                    appointmentItem.dataset.id = appointment.id;
                    
                    // Highlight if this is a representative name match
                    const isRepresentativeMatch = 
                        exactMatchesByRepresentative.includes(appointment) || 
                        partialMatchesByRepresentative.includes(appointment);
                    
                    appointmentItem.innerHTML = `
                        <div class="appointment-header">
                            <h3>${appointment.name || 'Unnamed Appointment'}</h3>
                            <span class="appointment-time">${appointment.booking_date} · ${timeFrom} - ${timeTo}</span>
                        </div>
                        <div class="appointment-details">
                            <p><strong>Department:</strong> ${appointment.department_name || 'N/A'}</p>
                            <p><strong>Room:</strong> ${appointment.room_name || 'N/A'}</p>
                            <p><strong>Representative:</strong> <span class="${isRepresentativeMatch ? 'highlight-match' : ''}">${appointment.representative_name || 'N/A'}</span></p>
                            <p><strong>Agenda:</strong> ${appointment.reason || 'N/A'}</p>
                        </div>
                        <div class="appointment-actions">
                            <button class="view-appointment" data-id="${appointment.id}">View</button>
                            <button class="edit-appointment" data-id="${appointment.id}">Edit</button>
                        </div>
                    `;
                    
                    resultsContainer.appendChild(appointmentItem);
                } catch (error) {
                    console.error("Error creating search result item:", error, appointment);
                }
            });
            
            searchResults.appendChild(resultsContainer);
            
            // Add event listeners to the view and edit buttons
            setupAppointmentClicks();
        }
        
        // Show the search modal
        const searchModal = document.getElementById('searchModal');
        if (searchModal) {
            showModal(searchModal);
        } else {
            console.error("Search modal not found");
        }
    }
}

// Modal handling functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Close any other open modals first
        document.querySelectorAll('.modal').forEach(m => {
            if (m.id !== modalId && m.style.display === 'flex') {
                m.style.display = 'none';
                m.classList.remove('show');
            }
        });
        
        // Get the current scroll position
        const scrollY = window.scrollY;
        
        // Show the modal
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
        modal.classList.add('show');
        
        // Ensure the modal covers the entire viewport
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        
        // Prevent the background from shifting
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        
        // Store the scroll position for later
        document.body.dataset.scrollY = scrollY;
        
        // Focus first input if exists
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        modal.classList.remove('show');
        
        // Restore the page position
        const scrollY = parseInt(document.body.dataset.scrollY || '0');
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        window.scrollTo(0, scrollY);
    }
}

// Setup modal handlers
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside of the modal content
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            const modalId = event.target.id;
            closeModal(modalId);
        }
    });
    
    // Close modals with escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // Setup close buttons for all modals
    document.querySelectorAll('.modal .close-button').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });

    // Setup modal triggers
    const modalTriggers = {
        'openBookingModal': 'bookingModal',
        'openAddDepartmentModal': 'addDepartmentModal',
        'openAddRoomModal': 'addRoomModal',
        'viewAllAppointments': 'appointmentModal'
    };

    Object.entries(modalTriggers).forEach(([triggerId, modalId]) => {
        const trigger = document.getElementById(triggerId);
        if (trigger) {
            trigger.addEventListener('click', () => openModal(modalId));
        }
    });
});

function openBookingModal(date) {
    const modal = document.getElementById('bookingModal');
    if (modal) {
        modal.style.display = 'block';
        document.getElementById('date').value = date;
        
        // Initialize conflict resolver when modal opens
        initializeConflictResolver();
        
        // Clear any existing conflict messages
        const conflictModal = document.getElementById('conflictModal');
        if (conflictModal) {
            conflictModal.style.display = 'none';
        }
    }
}