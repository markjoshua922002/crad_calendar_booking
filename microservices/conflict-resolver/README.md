# Conflict Resolution Microservice

This microservice provides intelligent conflict detection and resolution for the Smart Scheduling System, offering alternative time slots and rooms when scheduling conflicts occur.

## Features

- **Conflict Detection**: Identifies scheduling conflicts for rooms and time slots
- **Alternative Time Suggestions**: Suggests alternative time slots based on availability and preferences
- **Alternative Room Suggestions**: Recommends available rooms for the requested time slot
- **Intelligent Scoring**: Ranks alternatives based on various factors like time of day and room suitability
- **RESTful API**: Simple JSON-based API for easy integration

## API Endpoints

### 1. Check Conflicts

**Endpoint**: `index.php?endpoint=check`  
**Method**: POST  
**Description**: Checks if a proposed booking conflicts with existing bookings

**Request Body**:
```json
{
  "date": "2023-05-15",
  "room_id": "1",
  "time_from": "10:00 AM",
  "time_to": "11:00 AM"
}
```

**Response**:
```json
{
  "has_conflicts": true,
  "conflicts": [
    {
      "id": "123",
      "room_id": "1",
      "room_name": "Conference Room A",
      "department_id": "2",
      "department_name": "Marketing",
      "time_from": "9:30 AM",
      "time_to": "10:30 AM",
      "date": "2023-05-15"
    }
  ]
}
```

### 2. Find Alternatives

**Endpoint**: `index.php?endpoint=alternatives`  
**Method**: POST  
**Description**: Finds alternative time slots and rooms for a booking

**Request Body**:
```json
{
  "date": "2023-05-15",
  "room_id": "1",
  "department_id": "2",
  "duration": 60,
  "time_from": "10:00 AM",
  "time_to": "11:00 AM"
}
```

**Response**:
```json
{
  "alternative_times": [
    {
      "time_from": "11:00 AM",
      "time_to": "12:00 PM",
      "score": 85
    },
    {
      "time_from": "1:00 PM",
      "time_to": "2:00 PM",
      "score": 75
    }
  ],
  "alternative_rooms": [
    {
      "id": "2",
      "name": "Conference Room B",
      "score": 80
    },
    {
      "id": "3",
      "name": "Meeting Room 101",
      "score": 65
    }
  ]
}
```

### 3. Analyze Booking

**Endpoint**: `index.php?endpoint=analyze`  
**Method**: POST  
**Description**: Analyzes a booking for conflicts and suggests alternatives

**Request Body**:
```json
{
  "date": "2023-05-15",
  "room_id": "1",
  "department_id": "2",
  "time_from": "10:00 AM",
  "time_to": "11:00 AM",
  "duration": 60
}
```

**Response**:
```json
{
  "has_conflicts": true,
  "conflicts": [...],
  "message": "There are scheduling conflicts with your requested time. Please review the suggestions below.",
  "alternative_times": [...],
  "alternative_rooms": [...]
}
```

## Configuration

The microservice can be configured by editing the `config.php` file:

- **Database Settings**: Connection parameters for the database
- **Time Settings**: Business hours, time slot intervals
- **Conflict Resolution Settings**: Threshold for conflicts, maximum alternatives to suggest
- **Logging**: Enable/disable logging and set log file location

## Integration

To integrate with the microservice from JavaScript:

1. Include the `conflict-service.js` client in your HTML:
   ```html
   <script src="js/conflict-service.js"></script>
   ```

2. Initialize the client:
   ```javascript
   const conflictService = new ConflictService();
   ```

3. Use the client to check for conflicts:
   ```javascript
   conflictService.analyzeBooking(date, roomId, departmentId, timeFrom, timeTo, duration)
     .then(analysis => {
       // Handle the analysis result
       if (analysis.has_conflicts) {
         // Show alternatives
       }
     })
     .catch(error => {
       console.error("Error analyzing booking:", error);
     });
   ```

## Logging

The microservice logs all operations to `logs/conflict_resolver.log` by default. This can be configured in `config.php`. 