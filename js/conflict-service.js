/**
 * Conflict Resolution Service Client
 * 
 * This module provides a client interface to the Conflict Resolution Microservice,
 * handling API calls and response processing.
 */

class ConflictService {
    constructor(baseUrl = '/microservices/conflict-resolver/') {
        this.baseUrl = baseUrl;
        this.endpoints = {
            check: 'index.php?endpoint=check',
            alternatives: 'index.php?endpoint=alternatives',
            analyze: 'index.php?endpoint=analyze'
        };
        
        console.log('ConflictService initialized with base URL:', this.baseUrl);
    }
    
    /**
     * Check for conflicts with a proposed booking
     */
    async checkConflicts(date, roomId, timeFrom, timeTo) {
        console.log('Checking conflicts:', { date, roomId, timeFrom, timeTo });
        
        try {
            const response = await this.makeRequest(this.endpoints.check, {
                date,
                room_id: roomId,
                time_from: timeFrom,
                time_to: timeTo
            });
            
            return response;
        } catch (error) {
            console.error('Error checking conflicts:', error);
            throw error;
        }
    }
    
    /**
     * Find alternative times and rooms for a booking
     */
    async findAlternatives(date, roomId, departmentId, duration, timeFrom, timeTo) {
        console.log('Finding alternatives:', { date, roomId, departmentId, duration, timeFrom, timeTo });
        
        try {
            const response = await this.makeRequest(this.endpoints.alternatives, {
                date,
                room_id: roomId,
                department_id: departmentId,
                duration,
                time_from: timeFrom,
                time_to: timeTo
            });
            
            return response;
        } catch (error) {
            console.error('Error finding alternatives:', error);
            throw error;
        }
    }
    
    /**
     * Analyze a booking for conflicts and get suggestions
     */
    async analyzeBooking(date, roomId, departmentId, timeFrom, timeTo, duration) {
        console.log('Analyzing booking:', { date, roomId, departmentId, timeFrom, timeTo, duration });
        
        try {
            const response = await this.makeRequest(this.endpoints.analyze, {
                date,
                room_id: roomId,
                department_id: departmentId,
                time_from: timeFrom,
                time_to: timeTo,
                duration
            });
            
            return response;
        } catch (error) {
            console.error('Error analyzing booking:', error);
            throw error;
        }
    }
    
    /**
     * Make a request to the microservice
     */
    async makeRequest(endpoint, data) {
        const url = this.baseUrl + endpoint;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error ${response.status}: ${errorText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }
}

// Export the ConflictService class
window.ConflictService = ConflictService; 