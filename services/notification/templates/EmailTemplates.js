class EmailTemplates {
    getBookingConfirmation({ userName, roomName, date, timeFrom, timeTo, attendees }) {
        return `
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Booking Confirmation</h2>
                <p>Hello ${userName},</p>
                <p>Your room booking has been confirmed:</p>
                
                <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p><strong>Room:</strong> ${roomName}</p>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>Time:</strong> ${timeFrom} - ${timeTo}</p>
                    ${attendees?.length ? `
                        <p><strong>Attendees:</strong></p>
                        <ul>
                            ${attendees.map(attendee => `<li>${attendee.name}</li>`).join('')}
                        </ul>
                    ` : ''}
                </div>

                <p>You can manage your booking through the calendar booking system.</p>
                <p>If you need to make any changes, please do so at least 1 hour before the scheduled time.</p>
                
                <div style="margin-top: 30px; font-size: 12px; color: #666;">
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        `;
    }

    getMeetingReminder({ userName, roomName, timeFrom }) {
        return `
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Meeting Reminder</h2>
                <p>Hello ${userName},</p>
                <p>This is a reminder for your upcoming meeting:</p>
                
                <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p><strong>Room:</strong> ${roomName}</p>
                    <p><strong>Time:</strong> ${timeFrom}</p>
                </div>

                <p>The room is ready for your use.</p>
                
                <div style="margin-top: 30px; font-size: 12px; color: #666;">
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        `;
    }

    getConflictNotification({ userName, roomName, date, timeFrom, timeTo, conflictReason, alternativeTimes, alternativeRooms }) {
        return `
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Booking Conflict Alert</h2>
                <p>Hello ${userName},</p>
                <p>There is a conflict with your room booking request:</p>
                
                <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p><strong>Room:</strong> ${roomName}</p>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>Requested Time:</strong> ${timeFrom} - ${timeTo}</p>
                    <p><strong>Reason:</strong> ${conflictReason}</p>
                </div>

                ${alternativeTimes?.length ? `
                    <h3>Alternative Times Available:</h3>
                    <ul style="list-style: none; padding: 0;">
                        ${alternativeTimes.map(time => `
                            <li style="margin: 10px 0; padding: 10px; background-color: #f5f5f5; border-radius: 3px;">
                                ${time.timeFrom} - ${time.timeTo}
                            </li>
                        `).join('')}
                    </ul>
                ` : ''}

                ${alternativeRooms?.length ? `
                    <h3>Alternative Rooms Available:</h3>
                    <ul style="list-style: none; padding: 0;">
                        ${alternativeRooms.map(room => `
                            <li style="margin: 10px 0; padding: 10px; background-color: #f5f5f5; border-radius: 3px;">
                                ${room.name}
                            </li>
                        `).join('')}
                    </ul>
                ` : ''}

                <p>Please visit the booking system to select an alternative or modify your request.</p>
                
                <div style="margin-top: 30px; font-size: 12px; color: #666;">
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        `;
    }

    getCancellationNotice({ userName, roomName, date, timeFrom, timeTo, cancellationReason }) {
        return `
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Booking Cancellation Notice</h2>
                <p>Hello ${userName},</p>
                <p>Your room booking has been cancelled:</p>
                
                <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p><strong>Room:</strong> ${roomName}</p>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>Time:</strong> ${timeFrom} - ${timeTo}</p>
                    ${cancellationReason ? `<p><strong>Reason:</strong> ${cancellationReason}</p>` : ''}
                </div>

                <p>You can make a new booking through the calendar booking system.</p>
                
                <div style="margin-top: 30px; font-size: 12px; color: #666;">
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        `;
    }

    getModificationNotice({ userName, roomName, date, timeFrom, timeTo, changes }) {
        return `
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Booking Modification Notice</h2>
                <p>Hello ${userName},</p>
                <p>Your room booking has been modified:</p>
                
                <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p><strong>Room:</strong> ${roomName}</p>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>New Time:</strong> ${timeFrom} - ${timeTo}</p>
                    
                    ${changes ? `
                        <div style="margin-top: 15px;">
                            <p><strong>Changes Made:</strong></p>
                            <ul>
                                ${Object.entries(changes).map(([key, value]) => `
                                    <li>${key}: ${value}</li>
                                `).join('')}
                            </ul>
                        </div>
                    ` : ''}
                </div>

                <p>If these changes don't work for you, please make adjustments through the booking system.</p>
                
                <div style="margin-top: 30px; font-size: 12px; color: #666;">
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        `;
    }
}

module.exports = { EmailTemplates }; 