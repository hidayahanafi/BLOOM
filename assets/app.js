import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import '@fullcalendar/core/main.css';
import '@fullcalendar/daygrid/main.css';

// Wait until the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const calendar = new Calendar(document.getElementById('calendar'), {
        plugins: [dayGridPlugin],  // Add the plugin for the grid view
        events: '/events.json', // Your API endpoint for events
        eventClick: function(info) {
            const event = info.event; // Get the clicked event object
            const inputElement = document.getElementById('event-id-input'); // Your form input field
            
            // Check if the input element exists before setting its value
            if (inputElement) {
                inputElement.value = event.id;  // Set the input field value to the event ID
            } else {
                console.error('Input element not found.');
            }

            // Set the event details (this is where you can display the event data in a modal)
            document.getElementById('eventId').value = info.event.id;
            document.getElementById('eventStart').value = info.event.start.toISOString();
            document.getElementById('eventEnd').value = info.event.end ? info.event.end.toISOString() : '';
            document.getElementById('eventModal').style.display = 'block'; // Open the modal
        }
    });

    calendar.render();

    // Update Event
    document.getElementById('updateEventBtn').addEventListener('click', function() {
        var eventId = document.getElementById('eventId').value;
        var event = calendar.getEventById(eventId);

        // Ask for a new start and end time
        var newStart = prompt("Enter new start time:", event.start.toISOString());
        var newEnd = prompt("Enter new end time:", event.end ? event.end.toISOString() : null);

        if (newStart && newEnd) {
            event.setDates(newStart, newEnd);

            // Send updated data to the server (backend)
            fetch(`/events/${eventId}`, {  // Adjusted to use PUT
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    start: newStart,
                    end: newEnd,
                }),
            })
            .then(response => response.json())
            .then(data => {
                console.log('Event updated:', data);
            })
            .catch((error) => {
                console.error('Error updating event:', error);
            });

            // Hide the modal
            document.getElementById('eventModal').style.display = 'none';
        }
    });
});
