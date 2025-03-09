class SMSTemplates {
    getBookingConfirmation({ roomName, date, timeFrom }) {
        return `Booking Confirmed: ${roomName} on ${date} at ${timeFrom}. Check your email for details.`;
    }

    getMeetingReminder({ roomName, timeFrom }) {
        return `Reminder: Your meeting in ${roomName} starts at ${timeFrom}.`;
    }

    getCancellationNotice({ roomName, date }) {
        return `Your booking for ${roomName} on ${date} has been cancelled. Check your email for details.`;
    }

    getModificationNotice({ roomName, date, timeFrom }) {
        return `Your booking for ${roomName} on ${date} at ${timeFrom} has been modified. Check your email for details.`;
    }
}

module.exports = { SMSTemplates }; 