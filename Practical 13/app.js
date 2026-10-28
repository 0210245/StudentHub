/**
 * Practical 13: Fetch-Based Asynchronous CRUD Application
 * Features:
 *   1. Full CRUD without page reloads using JavaScript Fetch API.
 *   2. Intermediate Extension: Search input with 300ms Debounce.
 *   3. Advanced Extension: Status code handling and Centralized error toast.
 */

const API_URL = 'api/events.php';
const eventsTableBody = document.getElementById('eventsTableBody');
const searchInput = document.getElementById('apiSearchInput');
const loadingSpinner = document.getElementById('loadingSpinner');
const toastBox = document.getElementById('toastBox');
const eventModal = document.getElementById('apiEventModal');
const eventForm = document.getElementById('apiEventForm');

let debounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
  fetchEvents();

  // Intermediate Extension: Search with 300ms debounce
  searchInput.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      fetchEvents(e.target.value.trim());
    }, 300);
  });

  // Modal form submit
  eventForm.addEventListener('submit', handleFormSubmit);
});

// Centralized Toast Notifications
function showToast(message, type = 'success') {
  toastBox.textContent = message;
  toastBox.className = `toast toast-${type} active`;
  setTimeout(() => {
    toastBox.classList.remove('active');
  }, 4000);
}

// 1. Fetch Events (GET)
async function fetchEvents(query = '') {
  loadingSpinner.style.display = 'block';
  eventsTableBody.innerHTML = '';

  try {
    const url = query ? `${API_URL}?search=${encodeURIComponent(query)}` : API_URL;
    const res = await fetch(url);
    const json = await res.json();

    if (!res.ok) {
      throw new Error(json.message || `HTTP ${res.status}`);
    }

    renderTable(json.data || []);
  } catch (err) {
    showToast(`Fetch failed: ${err.message}`, 'error');
  } finally {
    loadingSpinner.style.display = 'none';
  }
}

function renderTable(events) {
  if (events.length === 0) {
    eventsTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:#64748b;">No events found.</td></tr>`;
    return;
  }

  eventsTableBody.innerHTML = events.map(e => `
    <tr>
      <td><code>${escapeHtml(e.event_id)}</code></td>
      <td><strong>${escapeHtml(e.event_name)}</strong></td>
      <td>${e.event_date}</td>
      <td><span class="badge" style="background:#e0e7ff; color:#3730a3;">${escapeHtml(e.event_type)}</span></td>
      <td>${escapeHtml(e.event_venue || 'N/A')}</td>
      <td>
        <button class="action-btn" onclick='openEditModal(${JSON.stringify(e)})'>✏️ Edit</button>
        <button class="action-btn" style="color:#dc2626;" onclick="deleteEvent('${e.event_id}')">🗑 Delete</button>
      </td>
    </tr>
  `).join('');
}

// 2. Handle Add / Edit Submission (POST / PUT)
async function handleFormSubmit(e) {
  e.preventDefault();
  const formData = new FormData(eventForm);
  const isEdit = document.getElementById('isEditMode').value === '1';

  const payload = {
    event_id: formData.get('event_id'),
    event_name: formData.get('event_name'),
    event_date: formData.get('event_date'),
    event_venue: formData.get('event_venue'),
    event_type: formData.get('event_type'),
    coordinator: formData.get('coordinator'),
    description: formData.get('description'),
    _method: isEdit ? 'PUT' : 'POST'
  };

  try {
    const res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const json = await res.json();

    if (!res.ok) {
      throw new Error(json.message || `Error ${res.status}`);
    }

    showToast(json.message, 'success');
    closeModal();
    fetchEvents(searchInput.value.trim());
  } catch (err) {
    showToast(`Operation failed: ${err.message}`, 'error');
  }
}

// 3. Delete Event (DELETE without page reload)
async function deleteEvent(id) {
  if (!confirm(`Are you sure you want to delete event ${id} asynchronously?`)) return;

  try {
    const res = await fetch(`${API_URL}?id=${encodeURIComponent(id)}`, {
      method: 'DELETE'
    });

    const json = await res.json();

    if (!res.ok) {
      throw new Error(json.message || `Error ${res.status}`);
    }

    showToast(json.message, 'success');
    fetchEvents(searchInput.value.trim());
  } catch (err) {
    showToast(`Delete failed: ${err.message}`, 'error');
  }
}

// Modal controls
window.openAddModal = function() {
  document.getElementById('modalTitle').textContent = 'Add Event (AJAX)';
  document.getElementById('isEditMode').value = '0';
  document.getElementById('m_event_id').readOnly = false;
  eventForm.reset();
  eventModal.classList.add('active');
};

window.openEditModal = function(ev) {
  document.getElementById('modalTitle').textContent = 'Edit Event (AJAX): ' + ev.event_id;
  document.getElementById('isEditMode').value = '1';
  document.getElementById('m_event_id').value = ev.event_id;
  document.getElementById('m_event_id').readOnly = true;
  document.getElementById('m_event_name').value = ev.event_name;
  document.getElementById('m_event_date').value = ev.event_date;
  document.getElementById('m_event_type').value = ev.event_type;
  document.getElementById('m_event_venue').value = ev.event_venue || '';
  document.getElementById('m_coordinator').value = ev.coordinator || '';
  document.getElementById('m_description').value = ev.description || '';
  eventModal.classList.add('active');
};

window.closeModal = function() {
  eventModal.classList.remove('active');
};

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
