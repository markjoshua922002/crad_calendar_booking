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
        appointment: {
            element: document.getElementById('appointmentModal'),
            close: document.getElementById('closeAppointmentModal')
        },
        view: {
            element: document.getElementById('viewModal'),
            close: document.getElementById('closeViewModal')
        },
        edit: {
            element: document.getElementById('editModal'),
            close: document.getElementById('closeEditModal')
        },
        booking: {
            element: document.getElementById('bookingModal'),
            close: document.getElementById('closeBookingModal')
        },
        department: {
            element: document.getElementById('addDepartmentModal'),
            close: document.getElementById('closeAddDepartmentModal')
        },
        room: {
            element: document.getElementById('addRoomModal'),
            close: document.getElementById('closeAddRoomModal')
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
            } else {
                console.log(`Modal type ${modalType} not found`);
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
    $('#time_from, #time_to').timepicker({
        timeFormat: 'h:i A',
        interval: 1,
        minTime: '6:00am',
        maxTime: '11:00pm',
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    $('#edit_time_from, #edit_time_to').timepicker({
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
        console.log(`Day clicked: ${day}`);
        var appointments = JSON.parse(document.getElementById('appointmentsData').textContent);
        var dayAppointments = appointments[day] || [];
        console.log(`Appointments for day ${day}:`, dayAppointments);
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
        console.log('Appointment modal displayed');
    });

    // Show appointment details in view modal
    $(document).on('click', '.view-button', function() {
        var appointment = $(this).closest('.appointment-item').data('appointment');
        var viewModalContent = $('#viewModal .modal-content');
        viewModalContent.html('<span class="close" id="closeViewModal">&times;</span>' +
                              '<h2>Appointment Details</h2>' +
                              '<strong>Name:</strong> ' + appointment.name + '<br>' +
                              '<strong>Department:</strong> ' + appointment.department_name + '<br>' +
                              '<strong>Room:</strong> ' + appointment.room_name + '<br>' +
                              '<strong>Time:</strong> ' + appointment.booking_time_from + ' to ' + appointment.booking_time_to + '<br>' +
                              '<strong>Date:</strong> ' + appointment.booking_date + '<br>' +
                              '<strong>Reason:</strong> ' + appointment.reason + '<br>' +
                              '<strong>Group Members:</strong> ' + appointment.group_members + '<br>' +
                              '<strong>Representative Name:</strong> ' + appointment.representative_name);
        $('#viewModal').show();
        console.log('View modal displayed');
    });

    // Show appointment details in edit modal
    $(document).on('click', '.edit-button', function() {
        var appointment = $(this).closest('.appointment-item').data('appointment');
        $('#appointment_id').val(appointment.id);
        $('#edit_name').val(appointment.name);
        $('#edit_id_number').val(appointment.id_number);
        $('#edit_set').val(appointment.set);
        $('#edit_date').val(appointment.booking_date);
        $('#edit_time_from').val(appointment.booking_time_from);
        $('#edit_time_to').val(appointment.booking_time_to);
        $('#edit_reason').val(appointment.reason);
        $('#edit_department').val(appointment.department_id);
        $('#edit_room').val(appointment.room_id);
        $('#edit_group_members').val(appointment.group_members);
        $('#edit_representative_name').val(appointment.representative_name);
        $('#appointmentModal').hide();
        $('#editModal').show();
        console.log('Edit modal displayed');
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
        console.log('Booking modal open button clicked');
        $('#bookingModal').show();
    });

    $('#closeBookingModal').on('click', function() {
        console.log('Booking modal close button clicked');
        $('#bookingModal').hide();
    });

    $('#closeAddDepartmentModal').on('click', function() {
        console.log('Add Department modal close button clicked');
        $('#addDepartmentModal').hide();
    });

    $('#closeAddRoomModal').on('click', function() {
        console.log('Add Room modal close button clicked');
        $('#addRoomModal').hide();
    });

    $(document).on('click', '.close', function() {
        console.log('Close button clicked');
        $(this).closest('.modal').hide();
    });

    // Show/hide buttons on hover
    $(document).on('mouseenter', '.appointment-item', function() {
        $(this).find('.appointment-buttons').show();
    });

    $(document).on('mouseleave', '.appointment-item', function() {
        $(this).find('.appointment-buttons').hide();
    });
});
