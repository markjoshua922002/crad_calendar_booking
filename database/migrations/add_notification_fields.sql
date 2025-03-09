ALTER TABLE bookings
ADD COLUMN user_email VARCHAR(255) NOT NULL AFTER booking_time_to,
ADD COLUMN user_phone VARCHAR(50) NULL AFTER user_email,
ADD COLUMN notification_preferences JSON NULL AFTER user_phone,
ADD INDEX idx_user_email (user_email),
ADD INDEX idx_user_phone (user_phone); 