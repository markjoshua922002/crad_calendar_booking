<?php
require_once '../db_connect.php';
require_once '../services/notification/NotificationService.php';

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['date']) || !isset($data['room_id']) || 
    !isset($data['time_from']) || !isset($data['time_to']) || 
    !isset($data['user_email'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Initialize the notification service
    $notificationService = new NotificationService([
        'email' => [
            'host' => getenv('EMAIL_HOST'),
            'port' => getenv('EMAIL_PORT'),
            'secure' => true,
            'user' => getenv('EMAIL_USER'),
            'password' => getenv('EMAIL_PASSWORD')
        ],
        'twilio' => [
            'accountSid' => getenv('TWILIO_ACCOUNT_SID'),
            'authToken' => getenv('TWILIO_AUTH_TOKEN')
        ]
    ]);

    // Start transaction
    $conn->begin_transaction();

    // Insert the booking
    $stmt = $conn->prepare("INSERT INTO bookings (booking_date, room_id, booking_time_from, booking_time_to, 
                           user_email, user_phone, notification_preferences, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    $notificationPrefs = json_encode([
        'email' => true,
        'sms' => isset($data['notification_preferences']['sms']) ? $data['notification_preferences']['sms'] : false,
        'reminderTiming' => isset($data['notification_preferences']['reminderTiming']) ? 
                           $data['notification_preferences']['reminderTiming'] : 15
    ]);

    $stmt->bind_param("sisssss", 
        $data['date'],
        $data['room_id'],
        $data['time_from'],
        $data['time_to'],
        $data['user_email'],
        $data['user_phone'],
        $notificationPrefs
    );

    if (!$stmt->execute()) {
        throw new Exception("Error saving booking: " . $stmt->error);
    }

    $bookingId = $conn->insert_id;

    // Get room details for notification
    $stmt = $conn->prepare("SELECT name FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $data['room_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();

    // Commit transaction
    $conn->commit();

    // Send notifications
    try {
        // Send booking confirmation
        $notificationService->sendBookingConfirmation([
            'id' => $bookingId,
            'userName' => $data['user_name'] ?? 'User',
            'userEmail' => $data['user_email'],
            'userPhone' => $data['user_phone'],
            'roomName' => $room['name'],
            'date' => $data['date'],
            'timeFrom' => $data['time_from'],
            'timeTo' => $data['time_to'],
            'notificationPreferences' => json_decode($notificationPrefs, true)
        ]);

        // Schedule meeting reminder
        $notificationService->sendMeetingReminder([
            'id' => $bookingId,
            'userName' => $data['user_name'] ?? 'User',
            'userEmail' => $data['user_email'],
            'userPhone' => $data['user_phone'],
            'roomName' => $room['name'],
            'date' => $data['date'],
            'timeFrom' => $data['time_from'],
            'notificationPreferences' => json_decode($notificationPrefs, true)
        ]);

    } catch (Exception $e) {
        // Log notification error but don't fail the booking
        error_log("Error sending notifications: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Booking saved successfully',
        'booking_id' => $bookingId
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

$conn->close(); 