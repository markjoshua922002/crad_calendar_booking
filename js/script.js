document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM fully loaded and parsed - v11");

    // Debug element existence
    console.log("Menu button exists:", !!document.getElementById('menuButton'));
    console.log("Sidebar exists:", !!document.getElementById('sidebar'));
    console.log("Container exists:", !!document.querySelector('.container'));
    console.log("Calendar days:", document.querySelectorAll('.day').length);
    console.log("Open booking button exists:", !!document.getElementById('openBookingModal'));
    console.log("Delete button exists:", !!document.getElementById('delete_button'));

    // Sidebar toggle
    const menuButton = document.getElementById('menuButton');
    if (menuButton) {
        console.log("Menu button found");
        menuButton.addEventListener('click', function() {
            console.log("Menu button clicked");
            const sidebar = document.getElementById('sidebar');
            const container = document.querySelector('.container');
            
            if (sidebar) {
                sidebar.classList.toggle('open');
                console.log("Sidebar toggled:", sidebar.classList.contains('open'));
            }
            
            if (container) {
                container.classList.toggle('shifted');
            }
        });
    } else {
        console.error("Menu button not found!");
    }
    
    // Open booking modal on button click
    const openBookingBtn = document.getElementById('openBookingModal');
    if (openBookingBtn) {
        openBookingBtn.addEventListener('click', function() {
            console.log("Open booking button clicked");
            const bookingModal = document.getElementById('bookingModal');
            if (bookingModal) {
                bookingModal.style.display = 'block';
            }
        });
    }
    
    // FIX: Delete appointment button - Make sure this is properly attached
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'delete_button') {
            console.log("Delete button clicked");
            e.preventDefault(); // Prevent any default button behavior
            
            const appointmentId = document.getElementById('appointment_id').value;
            console.log("Deleting appointment ID:", appointmentId);
            
            if (appointmentId && confirm('Are you sure you want to delete this appointment?')) {
                console.log("Delete confirmed for appointment ID:", appointmentId);
                // Redirect to the delete API endpoint
                window.location.href = `api/delete_appointment.php?id=${appointmentId}`;
            } else {
                console.log("Delete cancelled or no appointment ID found");
            }
        }
    });
    
    // Calendar day click to show appointments
    const days = document.querySelectorAll('.day');
    console.log(`Found ${days.length} calendar days`);
    
    days.forEach(day => {
        if (day.querySelector('.day-number')) {  // Only attach to days that have a number
            day.addEventListener('click', function() {
                console.log("Calendar day clicked");
                const dayNumber = this.querySelector('.day-number').textContent;
                console.log(`Day ${dayNumber} clicked`);
                
                try {
                    // Get appointments data for the clicked day
                    const appointmentsDataElement = document.getElementById('appointmentsData');
                    if (!appointmentsDataElement) {
                        console.error("Appointments data element not found");
                        return;
                    }
                    
                    const appointmentsData = JSON.parse(appointmentsDataElement.textContent || '{}');
                    console.log("Appointments data:", appointmentsData);
                    
                    // Show appointments for the clicked day
                    const appointmentList = document.getElementById('appointmentList');
                    if (!appointmentList) {
                        console.error("Appointment list element not found");
                        return;
                    }
                    
                    appointmentList.innerHTML = ''; // Clear previous appointments
                    
                    // Display appointments for this day
                    const dayAppointments = appointmentsData[dayNumber] || [];
                    console.log(`Found ${dayAppointments.length} appointments for day ${dayNumber}`);
                    
                    if (dayAppointments.length > 0) {
                        dayAppointments.forEach(appointment => {
                            // Format times for display
                            const timeFrom = new Date(`2000-01-01T${appointment.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            const timeTo = new Date(`2000-01-01T${appointment.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            
                            // Create appointment element with a better structure
                            const appointmentItem = document.createElement('div');
                            appointmentItem.classList.add('appointment-item');
                            appointmentItem.style.backgroundColor = appointment.color;
                            
                            // Use this improved HTML structure with text-container
                            appointmentItem.innerHTML = `
                                <div class="appointment-content">
                                    <div class="appointment-text-container">
                                        <h3 class="appointment-title">${appointment.representative_name}</h3>
                                        <div class="appointment-details">
                                            <p><strong>Department:</strong> ${appointment.department_name}</p>
                                            <p><strong>Room:</strong> ${appointment.room_name}</p>
                                            <p><strong>Time:</strong> ${timeFrom} - ${timeTo}</p>
                                        </div>
                                    </div>
                                    <div class="appointment-actions">
                                        <button class="view-details" data-id="${appointment.id}" data-appointment='${JSON.stringify(appointment)}'>View Details</button>
                                        <button class="edit-appointment" data-id="${appointment.id}" data-appointment='${JSON.stringify(appointment)}'>Edit</button>
                                    </div>
                                </div>
                            `;
                            
                            appointmentList.appendChild(appointmentItem);
                        });
                        
                        // NOW ADD EVENT LISTENERS TO THE NEWLY CREATED BUTTONS
                        addEventListenersToAppointmentButtons();
                    } else {
                        appointmentList.innerHTML = '<p>No appointments for this day.</p>';
                    }
                    
                    // Show the appointments modal
                    const appointmentModal = document.getElementById('appointmentModal');
                    if (appointmentModal) {
                        appointmentModal.style.display = 'block';
                    }
                } catch (error) {
                    console.error("Error handling day click:", error);
                }
            });
        }
    });
    
    // Function to add event listeners to appointment buttons (view and edit)
    function addEventListenersToAppointmentButtons() {
        console.log("Adding event listeners to appointment buttons");
        
        // View details buttons
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                console.log("View details button clicked");
                try {
                    const appointmentData = JSON.parse(this.getAttribute('data-appointment'));
                    console.log("Viewing appointment:", appointmentData);
                    
                    // Display appointment details in the view modal
                    const viewContainer = document.getElementById('viewContainer');
                    const viewModal = document.getElementById('viewModal');
                    
                    if (viewContainer && viewModal) {
                        const timeFrom = new Date(`2000-01-01T${appointmentData.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        const timeTo = new Date(`2000-01-01T${appointmentData.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        viewContainer.innerHTML = `
                            <div class="appointment-details">
                                <p><strong>Research Adviser's Name:</strong> ${appointmentData.name}</p>
                                <p><strong>Group Number:</strong> ${appointmentData.id_number}</p>
                                <p><strong>Set:</strong> ${appointmentData.set}</p>
                                <p><strong>Department:</strong> ${appointmentData.department_name}</p>
                                <p><strong>Room:</strong> ${appointmentData.room_name}</p>
                                <p><strong>Date:</strong> ${appointmentData.booking_date}</p>
                                <p><strong>Time:</strong> ${timeFrom} - ${timeTo}</p>
                                <p><strong>Agenda:</strong> ${appointmentData.reason}</p>
                                <p><strong>Representative:</strong> ${appointmentData.representative_name}</p>
                                <p><strong>Remarks:</strong> ${appointmentData.group_members || "None"}</p>
                            </div>
                        `;
                        
                        // Close the appointment modal and open the view modal
                        document.getElementById('appointmentModal').style.display = 'none';
                        viewModal.style.display = 'block';
                    }
                } catch (error) {
                    console.error("Error displaying appointment details:", error);
                }
            });
        });
        
        // Edit appointment buttons
        document.querySelectorAll('.edit-appointment').forEach(button => {
            button.addEventListener('click', function() {
                console.log("Edit appointment button clicked");
                try {
                    const appointmentData = JSON.parse(this.getAttribute('data-appointment'));
                    console.log("Editing appointment:", appointmentData);
                    
                    // Fill the edit form with the appointment data
                    document.getElementById('appointment_id').value = appointmentData.id;
                    document.getElementById('edit_department').value = appointmentData.department_id;
                    document.getElementById('edit_name').value = appointmentData.name;
                    document.getElementById('edit_id_number').value = appointmentData.id_number;
                    document.getElementById('edit_set').value = appointmentData.set;
                    document.getElementById('edit_date').value = appointmentData.booking_date;
                    document.getElementById('edit_reason').value = appointmentData.reason;
                    document.getElementById('edit_room').value = appointmentData.room_id;
                    document.getElementById('edit_representative_name').value = appointmentData.representative_name;
                    document.getElementById('edit_group_members').value = appointmentData.group_members;
                    
                    // Time handling - parse the time into components
                    const timeFrom = new Date(`2000-01-01T${appointmentData.booking_time_from}`);
                    const timeTo = new Date(`2000-01-01T${appointmentData.booking_time_to}`);
                    
                    const fromHour = timeFrom.getHours() % 12 || 12;
                    const fromMinute = timeFrom.getMinutes();
                    const fromAMPM = timeFrom.getHours() < 12 ? 'AM' : 'PM';
                    
                    const toHour = timeTo.getHours() % 12 || 12;
                    const toMinute = timeTo.getMinutes();
                    const toAMPM = timeTo.getHours() < 12 ? 'AM' : 'PM';
                    
                    document.getElementById('edit_time_from_hour').value = fromHour;
                    document.getElementById('edit_time_from_minute').value = fromMinute.toString().padStart(2, '0');
                    document.getElementById('edit_time_from_ampm').value = fromAMPM;
                    
                    document.getElementById('edit_time_to_hour').value = toHour;
                    document.getElementById('edit_time_to_minute').value = toMinute.toString().padStart(2, '0');
                    document.getElementById('edit_time_to_ampm').value = toAMPM;
                    
                    // Close the appointment modal and open the edit modal
                    document.getElementById('appointmentModal').style.display = 'none';
                    document.getElementById('editModal').style.display = 'block';
                } catch (error) {
                    console.error("Error preparing edit form:", error);
                }
            });
        });
    }
    
    // Close modal buttons
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            console.log("Close button clicked");
            // Find the parent modal and close it
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            console.log("Clicked outside modal content");
            event.target.style.display = 'none';
        }
    });
    
    // Appointment items in the reminder list
    document.querySelectorAll('#reminderList .appointment-item').forEach(item => {
        item.addEventListener('click', function() {
            try {
                const appointmentData = JSON.parse(this.getAttribute('data-appointment'));
                console.log("Clicked reminder appointment:", appointmentData);
                
                // Display appointment details in the view modal
                const viewContainer = document.getElementById('viewContainer');
                if (viewContainer) {
                    const timeFrom = new Date(`2000-01-01T${appointmentData.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const timeTo = new Date(`2000-01-01T${appointmentData.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    viewContainer.innerHTML = `
                        <div class="appointment-details">
                            <p><strong>Research Adviser's Name:</strong> ${appointmentData.name}</p>
                            <p><strong>Group Number:</strong> ${appointmentData.id_number}</p>
                            <p><strong>Set:</strong> ${appointmentData.set}</p>
                            <p><strong>Department:</strong> ${appointmentData.department_name}</p>
                            <p><strong>Room:</strong> ${appointmentData.room_name}</p>
                            <p><strong>Date:</strong> ${appointmentData.booking_date}</p>
                            <p><strong>Time:</strong> ${timeFrom} - ${timeTo}</p>
                            <p><strong>Agenda:</strong> ${appointmentData.reason}</p>
                            <p><strong>Representative:</strong> ${appointmentData.representative_name}</p>
                            <p><strong>Remarks:</strong> ${appointmentData.group_members || "None"}</p>
                        </div>
                    `;
                    
                    // Show the view modal
                    const viewModal = document.getElementById('viewModal');
                    if (viewModal) {
                        viewModal.style.display = 'block';
                    }
                }
            } catch (error) {
                console.error("Error displaying appointment details:", error);
            }
        });
    });

    // Modal Handling
    const modals = {
        edit: {
            element: document.getElementById('editModal'),
            close: document.getElementById('closeEditModal')
        },
        department: {
            element: document.getElementById('addDepartmentModal'),
            close: document.getElementById('closeAddDepartmentModal')
        },
        room: {
            element: document.getElementById('addRoomModal'),
            close: document.getElementById('closeAddRoomModal')
        },
        appointment: {
            element: document.getElementById('appointmentModal'),
            close: document.getElementById('closeAppointmentModal')
        },
        view: {
            element: document.getElementById('viewModal'),
            close: document.getElementById('closeViewModal')
        }
    };

    // Show modals based on data attributes
    document.querySelectorAll('[data-modal]').forEach(button => {
        button.addEventListener('click', (e) => {
            const modalType = e.target.dataset.modal;
            console.log(`Button clicked for modal type: ${modalType}`);
            if (modals[modalType]) {
                modals[modalType].element.style.display = 'block';
                console.log(`${modalType} modal displayed`);
            }
        });
    });

    // Close modals
    Object.keys(modals).forEach(modalKey => {
        const modal = modals[modalKey];
        if (modal.close) {
            modal.close.addEventListener('click', () => {
                modal.element.style.display = 'none';
                console.log(`${modalKey} modal closed`);
            });
        }
    });

    // Close modals on outside click
    window.addEventListener('click', (e) => {
        Object.keys(modals).forEach(modalKey => {
            if (e.target === modals[modalKey].element) {
                modals[modalKey].element.style.display = 'none';
                console.log(`${modalKey} modal closed on outside click`);
            }
        });
    });

    // Appointment Click Handling
    document.querySelectorAll('.appointment').forEach(appointment => {
        appointment.addEventListener('click', async (e) => {
            const appointmentId = appointment.dataset.id;
            console.log(`Appointment clicked with ID: ${appointmentId}`);
            try {
                const response = await fetch(`api/get_appointment.php?id=${appointmentId}`);
                const data = await response.json();
                
                // Populate form fields
                Object.keys(data).forEach(key => {
                    const field = document.getElementById(`edit_${key}`);
                    if (field) field.value = data[key];
                });
                
                modals.edit.element.style.display = 'block';
                console.log(`Edit modal displayed for appointment ID: ${appointmentId}`);
            } catch (error) {
                console.error('Error loading appointment:', error);
            }
        });
    });

    // Form Submission
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editForm);

            try {
                const response = await fetch('api/update_appointment.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Failed to update appointment');
                }
            } catch (error) {
                console.error('Update error:', error);
            }
        });
    }

    // Delete Handling
    const deleteButton = document.getElementById('delete_button');
    if (deleteButton) {
        deleteButton.addEventListener('click', async () => {
            if (confirm('Are you sure you want to delete this appointment?')) {
                const appointmentId = document.getElementById('appointment_id').value;

                try {
                    const response = await fetch(`api/delete_appointment.php?id=${appointmentId}`, {
                        method: 'DELETE'
                    });

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Failed to delete appointment');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                }
            }
        });
    }

    // Timepicker Initialization
    $('#time_range').timepicker({
        timeFormat: 'h:i A',
        interval: 1,
        minTime: '12:00am',
        maxTime: '11:30pm',
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    $('#edit_time_range').timepicker({
        timeFormat: 'h:i A',
        interval: 1,
        minTime: '12:00am',
        maxTime: '11:30pm',
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    $(document).ready(function(){
        $('#time_from, #time_to').timepicker({
            timeFormat: 'h:i A',
            interval: 1,
            minTime: '6:00am',
            maxTime: '11:00pm',
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });

        // Show appointments for a specific day
        $('.day').on('click', function() {
            var day = $(this).find('.day-number').text();
            var appointmentsDataElement = document.getElementById('appointmentsData');
            if (!appointmentsDataElement) {
                console.error('appointmentsData element not found');
                return;
            }
            var appointments = JSON.parse(appointmentsDataElement.textContent);
            var dayAppointments = appointments[day] || [];
            var appointmentList = $('#appointmentList');
            appointmentList.empty();
            dayAppointments.forEach(function(appointment) {
                var appointmentItem = $('<div class="appointment-item"></div>');
                appointmentItem.css('background-color', appointment.color);
                appointmentItem.html('<div class="appointment-container"><strong>' + appointment.name + '</strong><br>' + appointment.department_name + '<br>' + appointment.room_name + '<br>' + appointment.booking_time_from + ' to ' + appointment.booking_time_to + '</div>');
                appointmentItem.data('appointment', appointment);
                appointmentItem.append('<div class="appointment-buttons"><button class="view-button">View</button><button class="edit-button">Edit</button></div>');
                appointmentList.append(appointmentItem);
            });
            $('#appointmentModal').show();
        });


        // Show appointment details in edit modal
        $(document).on('click', '.edit-button', function() {
            var appointment = $(this).closest('.appointment-item').data('appointment');
            $('#appointment_id').val(appointment.id);
            $('#edit_name').val(appointment.name);
            $('#edit_id_number').val(appointment.id_number);
            $('#edit_set').val(appointment.set);
            $('#edit_date').val(appointment.booking_date);
            $('#edit_time_from_hour').val(new Date('1970-01-01T' + appointment.booking_time_from + 'Z').getHours() % 12 || 12);
            $('#edit_time_from_minute').val(new Date('1970-01-01T' + appointment.booking_time_from + 'Z').getMinutes());
            $('#edit_time_from_ampm').val(new Date('1970-01-01T' + appointment.booking_time_from + 'Z').getHours() >= 12 ? 'PM' : 'AM');
            $('#edit_time_to_hour').val(new Date('1970-01-01T' + appointment.booking_time_to + 'Z').getHours() % 12 || 12);
            $('#edit_time_to_minute').val(new Date('1970-01-01T' + appointment.booking_time_to + 'Z').getMinutes());
            $('#edit_time_to_ampm').val(new Date('1970-01-01T' + appointment.booking_time_to + 'Z').getHours() >= 12 ? 'PM' : 'AM');
            $('#edit_reason').val(appointment.reason);
            $('#edit_department').val(appointment.department_id);
            $('#edit_room').val(appointment.room_id);
            $('#edit_representative_name').val(appointment.representative_name);
            $('#edit_group_members').val(appointment.group_members);
            $('#editModal').show();
        });

        // Handle delete button click event
        $('#delete_button').on('click', function() {
            var appointmentId = $('#appointment_id').val();
            if (confirm('Are you sure you want to delete this appointment?')) {
                $.ajax({
                    url: 'api/delete_appointment.php',
                    type: 'POST',
                    data: { id: appointmentId },
                    success: function(response) {
                        alert(response);
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Error deleting appointment: ' + xhr.responseText);
                    }
                });
            }
        });

        // Open and close modals
        $('#openBookingModal').on('click', function() {
            $('#bookingModal').show();
        });

        $('#closeBookingModal').on('click', function() {
            $('#bookingModal').hide();
        });

        $('#closeAddDepartmentModal').on('click', function() {
            $('#addDepartmentModal').hide();
        });

        $('#closeAddRoomModal').on('click', function() {
            $('#addRoomModal').hide();
        });

        $(document).on('click', '.close', function() {
            $(this).closest('.modal').hide();
        });

        // Show/hide buttons on hover
        $(document).on('mouseenter', '.appointment-item', function() {
            $(this).find('.appointment-buttons').show();
        });

        $(document).on('mouseleave', '.appointment-item', function() {
            $(this).find('.appointment-buttons').hide();
        });

        // Show appointment details in view modal
        $(document).on('click', '.appointment-item', function() {
            var appointment = $(this).data('appointment');
            var viewModalContent = $('#viewModal .modal-content');

            // Format the time to hour:minute AM/PM
            var timeFrom = new Date('1970-01-01T' + appointment.booking_time_from + 'Z').toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            var timeTo = new Date('1970-01-01T' + appointment.booking_time_to + 'Z').toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

            viewModalContent.html('<span class="close" id="closeViewModal">&times;</span>' +
                                  '<h2>Appointment Details</h2>' +
                                  '<strong>Representative Name:</strong> ' + appointment.representative_name + '<br>' +
                                  '<strong>Department:</strong> ' + appointment.department_name + '<br>' +
                                  '<strong>Room:</strong> ' + appointment.room_name + '<br>' +
                                  '<strong>Set:</strong> ' + appointment.set + '<br>' +
                                  '<strong>Time:</strong> ' + timeFrom + ' to ' + timeTo + '<br>' +
                                  '<strong>Date:</strong> ' + appointment.booking_date + '<br>' +
                                  '<strong>Reason:</strong> ' + appointment.reason + '<br>' +
                                  '<strong>Group Members:</strong> ' + appointment.group_members + '<br>' +
                                  '<strong>Research Adviser:</strong> ' + appointment.name);
            $('#viewModal').show();
        });

        // Close the view modal
        $(document).on('click', '#closeViewModal', function() {
            $('#viewModal').hide();
        });
    });

    function getTime(hourSelect, minuteSelect, ampmSelect) {
        const hour = hourSelect.value;
        const minute = minuteSelect.value;
        const ampm = ampmSelect.value;

        if (hour && minute && ampm) {
            return `${hour}:${minute} ${ampm}`;
        }
        return '';
    }

    function setupTimePicker(hourSelectId, minuteSelectId, ampmSelectId, timeInputId) {
        const hourSelect = document.getElementById(hourSelectId);
        const minuteSelect = document.getElementById(minuteSelectId);
        const ampmSelect = document.getElementById(ampmSelectId);
        const timeInput = document.getElementById(timeInputId);

        [hourSelect, minuteSelect, ampmSelect].forEach(select => {
            select.addEventListener('change', function() {
                timeInput.value = getTime(hourSelect, minuteSelect, ampmSelect);
            });
        });
    }

    // Setup time pickers for booking modal
    setupTimePicker('time_from_hour', 'time_from_minute', 'time_from_ampm', 'time_from');
    setupTimePicker('time_to_hour', 'time_to_minute', 'time_to_ampm', 'time_to');

    // Setup time pickers for edit modal
    setupTimePicker('edit_time_from_hour', 'edit_time_from_minute', 'edit_time_from_ampm', 'edit_time_from');
    setupTimePicker('edit_time_to_hour', 'edit_time_to_minute', 'edit_time_to_ampm', 'edit_time_to');
    
    // Date input debugging
    // Check all forms with date inputs
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        console.log(`Found date input ${input.name} with value: ${input.value}`);
        
        input.addEventListener('change', function() {
            console.log(`Date input ${this.name} changed to: ${this.value}`);
        });
    });
    
    // Log form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (this.querySelector('input[type="date"]')) {
                const dateInput = this.querySelector('input[type="date"]');
                console.log(`Form submitting with date value: ${dateInput.value}`);
            }
        });
    });
});