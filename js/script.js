document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM fully loaded and parsed");

    // Toggle sidebar functionality
    const menuButton = document.getElementById('menuButton');
    const sidebar = document.getElementById('sidebar');
    const container = document.querySelector('.container');

    menuButton.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        container.classList.toggle('shifted');
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
});