import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import '@fullcalendar/core/main.css';
import '@fullcalendar/daygrid/main.css';

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    if (!calendarEl) {
        console.error("L'élément #calendar est introuvable !");
        return;
    }

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin],
        timeZone: 'local', // Affichage en heure locale
        events: function (fetchInfo, successCallback, failureCallback) {
            fetch('/events.json')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(events => {
                    formattedEvents = events.map(event => ({
                        id: event.id,
                        title: event.title,
                        start: event.start, // Add start date
                        end: event.end,     // Add end date if applicable
                        description: event.extendedProps?.description,
                        location: event.extendedProps?.lieu,
                    }));
                    successCallback(formattedEvents);
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des événements:', error);
                    failureCallback(error);
                });
        },
        eventClick: function (info) {
            const event = info.event;
            document.getElementById('eventId').value = event.id;
            document.getElementById('eventTitle').value = event.title;
            document.getElementById('eventDate').value = event.startStr;
            document.getElementById('description').value = event.description;
            document.getElementById('location').value = event.location;
            document.getElementById('eventModal').style.display = 'block';
        }
    });

    calendar.render();

    // Ajouter un événement
    document.getElementById('addEventBtn').addEventListener('click', function () {
        // Collect all form values
        const eventData = {
            titre: document.getElementById('eventTitle').value,
            description: document.getElementById('description').value,
            lieu: document.getElementById('location').value,
            type: document.getElementById('type').value,
            img: document.getElementById('img').value,
            budget: document.getElementById('budget').value,
            date: document.getElementById('eventDate').value, // Ensure date is included
        };
    
        fetch('/addEvent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData),
        })
        .then(response => response.json())
        .then(data => {
            calendar.refetchEvents(); // Reload events
        });
    });

    // Mettre à jour un événement
    document.getElementById('updateEventBtn').addEventListener('click', function () {
        const eventId = document.getElementById('eventId').value;
        const newTitle = document.getElementById('eventTitle').value;
     
        if (!eventId || !newTitle ) {
            alert("Tous les champs sont obligatoires !");
            return;
        }

        fetch(`/events/${eventId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                titre: document.getElementById('eventTitle').value,
                description: document.getElementById('description').value,
                lieu: document.getElementById('location').value,
                type: document.getElementById('type').value,
                img: document.getElementById('img').value,
                budget: document.getElementById('budget').value,
                date: document.getElementById('eventDate').value,
            }),
        })
            .then(response => response.json())
            .then(data => {
                alert('Événement mis à jour');
                calendar.refetchEvents();
            })
            .catch(error => console.error('Erreur mise à jour:', error));
    });

    // Fermer la modale
    document.getElementById('closeModalBtn').addEventListener('click', function () {
        document.getElementById('eventModal').style.display = 'none';
    });
});
