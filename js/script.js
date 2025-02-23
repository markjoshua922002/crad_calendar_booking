document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM fully loaded and parsed");

    // Toggle sidebar functionality
    document.getElementById('menuButton').onclick = function() {
        var sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    };

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
});
