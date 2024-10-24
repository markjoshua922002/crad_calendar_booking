document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const closeModal = document.getElementsByClassName("close")[0];
    const editForm = document.getElementById("editForm");
    const deleteButton = document.getElementById("delete_button");

    // Add Department Modal
    const addDepartmentModal = document.getElementById("addDepartmentModal");
    const addDepartmentButton = document.getElementById("add_department_button");
    const closeAddDepartmentModal = document.getElementById("closeAddDepartmentModal");

    // Add Room Modal
    const addRoomModal = document.getElementById("addRoomModal");
    const addRoomButton = document.getElementById("add_room_button");
    const closeAddRoomModal = document.getElementById("closeAddRoomModal");

    // Show Add Department modal
    addDepartmentButton.onclick = function () {
        addDepartmentModal.style.display = "block"; 
    };

    // Show Add Room modal
    addRoomButton.onclick = function () {
        addRoomModal.style.display = "block"; 
    };

    // Close Add Department modal
    closeAddDepartmentModal.onclick = function () {
        addDepartmentModal.style.display = "none"; 
    };

    // Close Add Room modal
    closeAddRoomModal.onclick = function () {
        addRoomModal.style.display = "none"; 
    };

    // Close modals when clicking outside
    window.onclick = function (event) {
        if (event.target === addDepartmentModal) {
            addDepartmentModal.style.display = "none"; 
        }
        if (event.target === addRoomModal) {
            addRoomModal.style.display = "none"; 
        }
    };

    // Existing appointment modal functionality
    document.querySelectorAll(".appointment").forEach(item => {
        item.addEventListener("click", event => {
            const appointmentId = item.getAttribute("data-id");
            fetch(`api/get_appointment.php?id=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("appointment_id").value = data.id;
                    document.getElementById("edit_name").value = data.name;
                    document.getElementById("edit_id_number").value = data.id_number;
                    document.getElementById("edit_date").value = data.booking_date;
                    document.getElementById("edit_time").value = data.booking_time;
                    document.getElementById("edit_reason").value = data.reason;
                    document.getElementById("edit_department").value = data.department_id;
                    document.getElementById("edit_room").value = data.room_id;
                });
            modal.style.display = "block"; // Show modal
        });
    });

    // Close existing appointment modal
    closeModal.onclick = function () {
        modal.style.display = "none"; 
    };

    // Handle save changes for existing appointments
    editForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch('api/update_appointment.php', {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                alert('Appointment updated successfully');
                location.reload(); // Reload the page to see the changes
            } else {
                alert('Failed to update appointment');
            }
        }).catch(error => {
            console.error('Error updating appointment:', error);
            alert('An error occurred while updating the appointment.');
        });
    });

    // Handle delete appointment
    deleteButton.addEventListener("click", function () {
        const appointmentId = document.getElementById("appointment_id").value;
        if (confirm('Are you sure you want to delete this appointment?')) {
            fetch(`api/delete_appointment.php?id=${appointmentId}`, {
                method: 'DELETE'
            }).then(response => {
                if (response.ok) {
                    alert('Appointment deleted successfully');
                    location.reload(); // Reload the page to see the changes
                } else {
                    alert('Failed to delete appointment');
                }
            }).catch(error => {
                console.error('Error deleting appointment:', error);
                alert('An error occurred while deleting the appointment.');
            });
        }
    });

    // Toggle submenu visibility
    function toggleSubmenu(submenuId) {
        const submenu = document.getElementById(submenuId);
        submenu.style.display = submenu.style.display === "block" ? "none" : "block";
    }

    // Toggle sidebar collapse
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
    }
});
