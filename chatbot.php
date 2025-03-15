<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .chatbot-header {
            background: #4285f4;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chatbot-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 15px;
            margin: 5px 0;
        }

        .bot-message {
            background: #f0f0f0;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .user-message {
            background: #4285f4;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .chatbot-input {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        .chatbot-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
        }

        .chatbot-input button {
            background: #4285f4;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .chatbot-input button:hover {
            background: #3367d6;
        }

        .chatbot-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4285f4;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .chatbot-container.minimized {
            transform: translateY(calc(100% + 20px));
        }

        .options-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }

        .option-button {
            background: #e3f2fd;
            border: 1px solid #4285f4;
            color: #4285f4;
            padding: 5px 10px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }

        .option-button:hover {
            background: #4285f4;
            color: white;
        }

        .loading-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-style: italic;
        }

        .loading-indicator i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="chatbot-toggle">
        <i class="fas fa-comments"></i>
    </div>

    <div class="chatbot-container minimized">
        <div class="chatbot-header">
            <div class="chatbot-title">
                <i class="fas fa-robot"></i>
                <span>Booking Assistant</span>
            </div>
            <div>
                <i class="fas fa-minus minimize-chat" style="cursor: pointer; margin-right: 10px;"></i>
                <i class="fas fa-times close-chat" style="cursor: pointer;"></i>
            </div>
        </div>
        <div class="chatbot-messages">
            <!-- Messages will be added here dynamically -->
        </div>
        <div class="chatbot-input">
            <input type="text" placeholder="Type your message..." id="user-input">
            <button id="send-message">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        class BookingChatbot {
            constructor() {
                this.container = document.querySelector('.chatbot-container');
                this.messagesContainer = document.querySelector('.chatbot-messages');
                this.userInput = document.getElementById('user-input');
                this.sendButton = document.getElementById('send-message');
                this.toggleButton = document.querySelector('.chatbot-toggle');
                this.minimizeButton = document.querySelector('.minimize-chat');
                this.closeButton = document.querySelector('.close-chat');
                
                this.currentBooking = {
                    department: '',
                    room: '',
                    adviser: '',
                    representative: '',
                    group: '',
                    set: '',
                    date: '',
                    timeFrom: '',
                    timeTo: '',
                    agenda: '',
                    remarks: ''
                };
                
                this.bookings = [];
                this.currentStep = 'start';
                
                this.setupEventListeners();
                this.initialize();
            }

            initialize() {
                this.addMessage('bot', 'Hello! I\'m your booking assistant. I can help you schedule multiple appointments. Would you like to start booking?', [
                    { text: 'Yes, start booking', value: 'start_booking' },
                    { text: 'No, maybe later', value: 'cancel' }
                ]);
            }

            setupEventListeners() {
                this.sendButton.addEventListener('click', () => this.handleUserInput());
                this.userInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') this.handleUserInput();
                });
                this.toggleButton.addEventListener('click', () => this.toggleChat());
                this.minimizeButton.addEventListener('click', () => this.toggleChat());
                this.closeButton.addEventListener('click', () => this.closeChat());
            }

            toggleChat() {
                this.container.classList.toggle('minimized');
                this.toggleButton.style.display = this.container.classList.contains('minimized') ? 'flex' : 'none';
            }

            closeChat() {
                this.container.classList.add('minimized');
                this.toggleButton.style.display = 'flex';
            }

            addMessage(type, text, options = null) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${type}-message`;
                messageDiv.textContent = text;

                if (options) {
                    const optionsContainer = document.createElement('div');
                    optionsContainer.className = 'options-container';
                    options.forEach(option => {
                        const button = document.createElement('button');
                        button.className = 'option-button';
                        button.textContent = option.text;
                        button.addEventListener('click', () => this.handleOptionClick(option.value));
                        optionsContainer.appendChild(button);
                    });
                    messageDiv.appendChild(optionsContainer);
                }

                this.messagesContainer.appendChild(messageDiv);
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }

            handleUserInput() {
                const text = this.userInput.value.trim();
                if (!text) return;

                this.addMessage('user', text);
                this.userInput.value = '';
                this.processUserInput(text);
            }

            async processUserInput(text) {
                switch (this.currentStep) {
                    case 'department':
                        this.currentBooking.department = text;
                        this.askForRoom();
                        break;
                    case 'room':
                        this.currentBooking.room = text;
                        this.askForAdviser();
                        break;
                    case 'adviser':
                        this.currentBooking.adviser = text;
                        this.askForRepresentative();
                        break;
                    case 'representative':
                        this.currentBooking.representative = text;
                        this.askForGroup();
                        break;
                    case 'group':
                        this.currentBooking.group = text;
                        this.askForSet();
                        break;
                    case 'set':
                        this.currentBooking.set = text;
                        this.askForDate();
                        break;
                    case 'date':
                        this.currentBooking.date = text;
                        this.askForTimeFrom();
                        break;
                    case 'timeFrom':
                        this.currentBooking.timeFrom = text;
                        this.askForTimeTo();
                        break;
                    case 'timeTo':
                        this.currentBooking.timeTo = text;
                        this.askForAgenda();
                        break;
                    case 'agenda':
                        this.currentBooking.agenda = text;
                        this.askForRemarks();
                        break;
                    case 'remarks':
                        this.currentBooking.remarks = text;
                        this.confirmBooking();
                        break;
                }
            }

            handleOptionClick(value) {
                switch (value) {
                    case 'start_booking':
                        this.startBooking();
                        break;
                    case 'confirm_booking':
                        this.saveBooking();
                        break;
                    case 'add_another':
                        this.resetCurrentBooking();
                        this.startBooking();
                        break;
                    case 'finish_booking':
                        this.submitAllBookings();
                        break;
                    case 'cancel':
                        this.addMessage('bot', 'Okay, no problem! Let me know when you want to make a booking.');
                        break;
                }
            }

            startBooking() {
                this.currentStep = 'department';
                this.addMessage('bot', 'Please enter the department name:');
            }

            askForRoom() {
                this.currentStep = 'room';
                this.addMessage('bot', 'Please enter the room name:');
            }

            askForAdviser() {
                this.currentStep = 'adviser';
                this.addMessage('bot', 'Please enter the research adviser\'s name:');
            }

            askForRepresentative() {
                this.currentStep = 'representative';
                this.addMessage('bot', 'Please enter the representative\'s name:');
            }

            askForGroup() {
                this.currentStep = 'group';
                this.addMessage('bot', 'Please enter the group name:');
            }

            askForSet() {
                this.currentStep = 'set';
                this.addMessage('bot', 'Please enter the set:');
            }

            askForDate() {
                this.currentStep = 'date';
                this.addMessage('bot', 'Please enter the date (YYYY-MM-DD):');
            }

            askForTimeFrom() {
                this.currentStep = 'timeFrom';
                this.addMessage('bot', 'Please enter the start time (HH:MM AM/PM):');
            }

            askForTimeTo() {
                this.currentStep = 'timeTo';
                this.addMessage('bot', 'Please enter the end time (HH:MM AM/PM):');
            }

            askForAgenda() {
                this.currentStep = 'agenda';
                this.addMessage('bot', 'Please enter the agenda:');
            }

            askForRemarks() {
                this.currentStep = 'remarks';
                this.addMessage('bot', 'Please enter any remarks (optional):');
            }

            confirmBooking() {
                const summary = `Booking Summary:
                Department: ${this.currentBooking.department}
                Room: ${this.currentBooking.room}
                Adviser: ${this.currentBooking.adviser}
                Representative: ${this.currentBooking.representative}
                Group: ${this.currentBooking.group}
                Set: ${this.currentBooking.set}
                Date: ${this.currentBooking.date}
                Time: ${this.currentBooking.timeFrom} - ${this.currentBooking.timeTo}
                Agenda: ${this.currentBooking.agenda}
                Remarks: ${this.currentBooking.remarks}`;

                this.addMessage('bot', summary, [
                    { text: 'Confirm Booking', value: 'confirm_booking' },
                    { text: 'Cancel', value: 'cancel' }
                ]);
            }

            saveBooking() {
                this.bookings.push({...this.currentBooking});
                this.addMessage('bot', 'Booking saved! Would you like to add another booking?', [
                    { text: 'Add Another Booking', value: 'add_another' },
                    { text: 'Finish & Submit All', value: 'finish_booking' }
                ]);
            }

            resetCurrentBooking() {
                this.currentBooking = {
                    department: '',
                    room: '',
                    adviser: '',
                    representative: '',
                    group: '',
                    set: '',
                    date: '',
                    timeFrom: '',
                    timeTo: '',
                    agenda: '',
                    remarks: ''
                };
            }

            async submitAllBookings() {
                this.addMessage('bot', 'Processing all bookings...');
                
                try {
                    const response = await fetch('api/bulk_booking.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(this.bookings)
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        this.addMessage('bot', 'All bookings have been successfully submitted!');
                        this.bookings = [];
                        this.resetCurrentBooking();
                    } else {
                        this.addMessage('bot', 'There was an error processing your bookings: ' + result.message);
                    }
                } catch (error) {
                    this.addMessage('bot', 'There was an error submitting your bookings. Please try again.');
                }
            }
        }

        // Initialize the chatbot when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            window.chatbot = new BookingChatbot();
        });
    </script>
</body>
</html> 