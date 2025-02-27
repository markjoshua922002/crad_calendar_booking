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
        interval: 30,
        minTime: '12:00am',
        maxTime: '11:30pm',
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    $('#edit_time_range').timepicker({
        timeFormat: 'h:i A',
        interval: 30,
        minTime: '12:00am',
        maxTime: '11:30pm',
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    $(document).ready(function(){
        $('#time_from, #time_to').timepicker({
            timeFormat: 'h:i A',
            interval: 30,
            minTime: '6:00am',
            maxTime: '11:00pm',
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });

        // Show appointments for a specific day
        $('.day').on('click', function() {
            var day = $(this).find('.day-number').text();
            var appointments = JSON.parse(document.getElementById('appointmentsData').textContent);
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
    });

    // Add this JavaScript code to handle the click event and populate the modal
    const days = document.querySelectorAll('.calendar .day');
    const selectedDay = document.getElementById('selectedDay');
    const dayBookingsList = document.getElementById('dayBookingsList');

    days.forEach(day => {
        day.addEventListener('click', function() {
            const dayNumber = this.querySelector('.day-number').textContent;
            selectedDay.textContent = dayNumber;

            // Fetch bookings for the selected day
            const bookings = JSON.parse(document.getElementById('appointmentsData').textContent);
            const dayBookings = bookings[dayNumber] || [];

            // Populate the modal with the bookings
            dayBookingsList.innerHTML = '';
            if (dayBookings.length > 0) {
                dayBookings.forEach(booking => {
                    const bookingItem = document.createElement('div');
                    bookingItem.innerHTML = `
                        <p><strong>Name:</strong> ${booking.name}</p>
                        <p><strong>Time:</strong> ${booking.booking_time_from} - ${booking.booking_time_to}</p>
                        <p><strong>Room:</strong> ${booking.room_name}</p>
                        <p><strong>Reason:</strong> ${booking.reason}</p>
                        <hr>
                    `;
                    dayBookingsList.appendChild(bookingItem);
                });
            } else {
                dayBookingsList.innerHTML = '<p>No bookings for this day.</p>';
            }

            // Show the modal
            (document.getElementById('dayBookingsModal')).style.display = 'block';
        });
    });

    (document.getElementById('closeDayBookingsModal')).addEventListener('click', function() {
        (document.getElementById('dayBookingsModal')).style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('dayBookingsModal')) {
            (document.getElementById('dayBookingsModal')).style.display = 'none';
        }
    });

    // Get the modals
    var appointmentModal = document.getElementById('appointmentModal');
    var viewModal = document.getElementById('viewModal');
    var editModal = document.getElementById('editModal');
    var bookingModal = document.getElementById('bookingModal');
    var addDepartmentModal = document.getElementById('addDepartmentModal');
    var addRoomModal = document.getElementById('addRoomModal');
    var dayBookingsModal = document.getElementById('dayBookingsModal');

    // Get the buttons that open the modals
    var openBookingModalButton = document.getElementById('openBookingModal');

    // Get the <span> elements that close the modals
    var closeAppointmentModal = document.getElementById('closeAppointmentModal');
    var closeViewModal = document.getElementById('closeViewModal');
    var closeEditModal = document.getElementById('closeEditModal');
    var closeBookingModal = document.getElementById('closeBookingModal');
    var closeAddDepartmentModal = document.getElementById('closeAddDepartmentModal');
    var closeAddRoomModal = document.getElementById('closeAddRoomModal');
    var closeDayBookingsModal = document.getElementById('closeDayBookingsModal');

    // When the user clicks the button, open the booking modal
    openBookingModalButton.onclick = function() {
        bookingModal.style.display = 'block';
    }

    // When the user clicks on <span> (x), close the modals
    closeAppointmentModal.onclick = function() {
        appointmentModal.style.display = 'none';
    }
    closeViewModal.onclick = function() {
        viewModal.style.display = 'none';
    }
    closeEditModal.onclick = function() {
        editModal.style.display = 'none';
    }
    closeBookingModal.onclick = function() {
        bookingModal.style.display = 'none';
    }
    closeAddDepartmentModal.onclick = function() {
        addDepartmentModal.style.display = 'none';
    }
    closeAddRoomModal.onclick = function() {
        addRoomModal.style.display = 'none';
    }
    (document.getElementById('closeDayBookingsModal')).onclick = function() {
        (document.getElementById('dayBookingsModal')).style.display = 'none';
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == appointmentModal) {
            appointmentModal.style.display = 'none';
        }
        if (event.target == viewModal) {
            viewModal.style.display = 'none';
        }
        if (event.target == editModal) {
            editModal.style.display = 'none';
        }
        if (event.target == bookingModal) {
            bookingModal.style.display = 'none';
        }
        if (event.target == addDepartmentModal) {
            addDepartmentModal.style.display = 'none';
        }
        if (event.target == addRoomModal) {
            addRoomModal.style.display = 'none';
        }
        if (event.target == document.getElementById('dayBookingsModal')) {
            (document.getElementById('dayBookingsModal')).style.display = 'none';
        }
    }
});
