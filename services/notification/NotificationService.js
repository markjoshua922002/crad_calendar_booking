const nodemailer = require('nodemailer');
const twilio = require('twilio');
const { EmailTemplates } = require('./templates/EmailTemplates');
const { SMSTemplates } = require('./templates/SMSTemplates');
const { NotificationQueue } = require('./queue/NotificationQueue');

class NotificationService {
    constructor(config) {
        this.emailTransporter = nodemailer.createTransport({
            host: config.email.host,
            port: config.email.port,
            secure: config.email.secure,
            auth: {
                user: config.email.user,
                pass: config.email.password
            }
        });

        this.smsClient = twilio(
            config.twilio.accountSid,
            config.twilio.authToken
        );

        this.emailTemplates = new EmailTemplates();
        this.smsTemplates = new SMSTemplates();
        this.notificationQueue = new NotificationQueue();

        // Initialize notification preferences (default settings)
        this.defaultPreferences = {
            email: true,
            sms: false,
            reminderTiming: 15 // minutes before event
        };
    }

    async sendBookingConfirmation(booking) {
        try {
            const emailContent = this.emailTemplates.getBookingConfirmation({
                userName: booking.userName,
                roomName: booking.roomName,
                date: booking.date,
                timeFrom: booking.timeFrom,
                timeTo: booking.timeTo,
                attendees: booking.attendees
            });

            await this.sendEmail({
                to: booking.userEmail,
                subject: 'Booking Confirmation',
                html: emailContent
            });

            // If SMS notifications are enabled
            if (booking.notificationPreferences?.sms) {
                const smsContent = this.smsTemplates.getBookingConfirmation({
                    roomName: booking.roomName,
                    date: booking.date,
                    timeFrom: booking.timeFrom
                });

                await this.sendSMS({
                    to: booking.userPhone,
                    message: smsContent
                });
            }

            return { success: true, message: 'Confirmation notifications sent successfully' };
        } catch (error) {
            console.error('Error sending booking confirmation:', error);
            throw new Error('Failed to send booking confirmation');
        }
    }

    async sendMeetingReminder(booking) {
        try {
            const reminderTime = this.calculateReminderTime(
                booking.date,
                booking.timeFrom,
                booking.notificationPreferences?.reminderTiming || this.defaultPreferences.reminderTiming
            );

            // Queue the reminder notifications
            await this.notificationQueue.addReminder({
                type: 'reminder',
                scheduledFor: reminderTime,
                data: {
                    email: {
                        to: booking.userEmail,
                        subject: 'Meeting Reminder',
                        html: this.emailTemplates.getMeetingReminder({
                            userName: booking.userName,
                            roomName: booking.roomName,
                            timeFrom: booking.timeFrom
                        })
                    },
                    sms: booking.notificationPreferences?.sms ? {
                        to: booking.userPhone,
                        message: this.smsTemplates.getMeetingReminder({
                            roomName: booking.roomName,
                            timeFrom: booking.timeFrom
                        })
                    } : null
                }
            });

            return { success: true, message: 'Reminder scheduled successfully' };
        } catch (error) {
            console.error('Error scheduling reminder:', error);
            throw new Error('Failed to schedule reminder');
        }
    }

    async sendConflictNotification(booking, conflict) {
        try {
            const emailContent = this.emailTemplates.getConflictNotification({
                userName: booking.userName,
                roomName: booking.roomName,
                date: booking.date,
                timeFrom: booking.timeFrom,
                timeTo: booking.timeTo,
                conflictReason: conflict.reason,
                alternativeTimes: conflict.alternativeTimes,
                alternativeRooms: conflict.alternativeRooms
            });

            await this.sendEmail({
                to: booking.userEmail,
                subject: 'Booking Conflict Alert',
                html: emailContent
            });

            return { success: true, message: 'Conflict notification sent successfully' };
        } catch (error) {
            console.error('Error sending conflict notification:', error);
            throw new Error('Failed to send conflict notification');
        }
    }

    async sendCancellationNotice(booking, reason) {
        try {
            const emailContent = this.emailTemplates.getCancellationNotice({
                userName: booking.userName,
                roomName: booking.roomName,
                date: booking.date,
                timeFrom: booking.timeFrom,
                timeTo: booking.timeTo,
                cancellationReason: reason
            });

            await this.sendEmail({
                to: booking.userEmail,
                subject: 'Booking Cancellation Notice',
                html: emailContent
            });

            if (booking.notificationPreferences?.sms) {
                const smsContent = this.smsTemplates.getCancellationNotice({
                    roomName: booking.roomName,
                    date: booking.date
                });

                await this.sendSMS({
                    to: booking.userPhone,
                    message: smsContent
                });
            }

            return { success: true, message: 'Cancellation notice sent successfully' };
        } catch (error) {
            console.error('Error sending cancellation notice:', error);
            throw new Error('Failed to send cancellation notice');
        }
    }

    async sendModificationNotice(booking, changes) {
        try {
            const emailContent = this.emailTemplates.getModificationNotice({
                userName: booking.userName,
                roomName: booking.roomName,
                date: booking.date,
                timeFrom: booking.timeFrom,
                timeTo: booking.timeTo,
                changes: changes
            });

            await this.sendEmail({
                to: booking.userEmail,
                subject: 'Booking Modification Notice',
                html: emailContent
            });

            return { success: true, message: 'Modification notice sent successfully' };
        } catch (error) {
            console.error('Error sending modification notice:', error);
            throw new Error('Failed to send modification notice');
        }
    }

    // Private helper methods
    async sendEmail({ to, subject, html }) {
        try {
            await this.emailTransporter.sendMail({
                from: process.env.EMAIL_FROM,
                to,
                subject,
                html
            });
        } catch (error) {
            console.error('Email sending failed:', error);
            throw new Error('Failed to send email');
        }
    }

    async sendSMS({ to, message }) {
        try {
            await this.smsClient.messages.create({
                body: message,
                from: process.env.TWILIO_PHONE_NUMBER,
                to
            });
        } catch (error) {
            console.error('SMS sending failed:', error);
            throw new Error('Failed to send SMS');
        }
    }

    calculateReminderTime(date, time, minutesBefore) {
        const meetingTime = new Date(`${date} ${time}`);
        return new Date(meetingTime.getTime() - (minutesBefore * 60000));
    }
}

module.exports = { NotificationService }; 