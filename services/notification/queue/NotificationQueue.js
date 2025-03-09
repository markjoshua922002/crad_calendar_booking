const Queue = require('bull');
const Redis = require('ioredis');

class NotificationQueue {
    constructor(config = {}) {
        this.redis = new Redis({
            host: process.env.REDIS_HOST || 'localhost',
            port: process.env.REDIS_PORT || 6379,
            password: process.env.REDIS_PASSWORD
        });

        this.reminderQueue = new Queue('meeting-reminders', {
            redis: {
                host: process.env.REDIS_HOST || 'localhost',
                port: process.env.REDIS_PORT || 6379,
                password: process.env.REDIS_PASSWORD
            }
        });

        this.setupQueueHandlers();
    }

    async addReminder(reminderData) {
        try {
            const delay = reminderData.scheduledFor.getTime() - Date.now();
            
            if (delay <= 0) {
                console.warn('Reminder scheduled for past time, sending immediately');
                return this.processReminder(reminderData);
            }

            await this.reminderQueue.add(reminderData, {
                delay,
                attempts: 3,
                backoff: {
                    type: 'exponential',
                    delay: 2000
                },
                removeOnComplete: true
            });

            return { success: true, message: 'Reminder scheduled successfully' };
        } catch (error) {
            console.error('Error scheduling reminder:', error);
            throw new Error('Failed to schedule reminder');
        }
    }

    setupQueueHandlers() {
        this.reminderQueue.process(async (job) => {
            return this.processReminder(job.data);
        });

        // Handle successful jobs
        this.reminderQueue.on('completed', (job) => {
            console.log(`Reminder job ${job.id} completed successfully`);
        });

        // Handle failed jobs
        this.reminderQueue.on('failed', (job, error) => {
            console.error(`Reminder job ${job.id} failed:`, error);
        });

        // Handle stalled jobs
        this.reminderQueue.on('stalled', (job) => {
            console.warn(`Reminder job ${job.id} stalled`);
        });
    }

    async processReminder(reminderData) {
        try {
            // Process email notification
            if (reminderData.data.email) {
                await this.sendEmail(reminderData.data.email);
            }

            // Process SMS notification if enabled
            if (reminderData.data.sms) {
                await this.sendSMS(reminderData.data.sms);
            }

            return { success: true, message: 'Reminder processed successfully' };
        } catch (error) {
            console.error('Error processing reminder:', error);
            throw error;
        }
    }

    async sendEmail({ to, subject, html }) {
        // Implementation would be similar to NotificationService.sendEmail
        // You might want to use a shared email service or implement it here
        console.log('Sending email reminder to:', to);
    }

    async sendSMS({ to, message }) {
        // Implementation would be similar to NotificationService.sendSMS
        // You might want to use a shared SMS service or implement it here
        console.log('Sending SMS reminder to:', to);
    }

    async getQueueStatus() {
        const [waiting, active, completed, failed] = await Promise.all([
            this.reminderQueue.getWaitingCount(),
            this.reminderQueue.getActiveCount(),
            this.reminderQueue.getCompletedCount(),
            this.reminderQueue.getFailedCount()
        ]);

        return {
            waiting,
            active,
            completed,
            failed
        };
    }

    async cleanQueue() {
        await this.reminderQueue.clean(7 * 24 * 3600 * 1000, 'completed'); // Clean completed jobs older than 7 days
        await this.reminderQueue.clean(7 * 24 * 3600 * 1000, 'failed'); // Clean failed jobs older than 7 days
    }

    async shutdown() {
        await this.reminderQueue.close();
        await this.redis.quit();
    }
}

module.exports = { NotificationQueue }; 