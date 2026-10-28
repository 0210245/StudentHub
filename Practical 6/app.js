/**
 * Practical 6: Rendering External JSON Data using Fetch API, Search, Filter, Sort & Pagination
 * Author: D26CE153 - ITUE203 Web Development Frameworks
 * Features:
 *   1. Fetch API with async/await, try/catch/finally error handling.
 *   2. Array pipeline: filter(), map(), sort(), slice() for pagination.
 *   3. Dependent dropdown filter (Intermediate extension).
 *   4. LocalStorage response caching for offline resilience (Advanced extension).
 *   5. Modularity across Events, Students, and FAQs datasets.
 */

// Application State
const state = {
  currentTab: 'events', // 'events' | 'students' | 'faqs'
  rawData: [],
  filteredData: [],
  searchTerm: '',
  selectedCategory: 'all',
  selectedSubFilter: 'all',
  sortBy: 'default',
  currentPage: 1,
  pageSize: 6,
  isCached: false,
  isLoading: false
};

// Data endpoints mapping
const DATA_CONFIG = {
  events: {
    url: 'events.json',
    storageKey: 'sh_p6_events_cache',
    label: 'Campus Events',
    searchPlaceholder: 'Search events by title, description or venue...',
    primaryFilterLabel: 'Filter by Category',
    subFilterLabel: 'Filter by Department'
  },
  students: {
    url: 'students.json',
    storageKey: 'sh_p6_students_cache',
    label: 'Student Directory',
    searchPlaceholder: 'Search by student name, enrollment or email...',
    primaryFilterLabel: 'Filter by Department',
    subFilterLabel: 'Filter by Course'
  },
  faqs: {
    url: 'faqs.json',
    storageKey: 'sh_p6_faqs_cache',
    label: 'Frequently Asked Questions',
    searchPlaceholder: 'Search question or keywords...',
    primaryFilterLabel: 'Filter by Topic',
    subFilterLabel: null
  }
};

// DOM Elements
const contentContainer = document.getElementById('contentContainer');
const searchInput = document.getElementById('searchInput');
const primaryFilter = document.getElementById('primaryFilter');
const subFilter = document.getElementById('subFilter');
const subFilterGroup = document.getElementById('subFilterGroup');
const primaryFilterLabel = document.getElementById('primaryFilterLabel');
const subFilterLabel = document.getElementById('subFilterLabel');
const sortSelect = document.getElementById('sortSelect');
const paginationContainer = document.getElementById('paginationContainer');
const resultCountEl = document.getElementById('resultCount');
const cacheStatusEl = document.getElementById('cacheStatus');
const tabButtons = document.querySelectorAll('.tab-btn');
const refreshBtn = document.getElementById('refreshBtn');

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
  initEventListeners();
  loadData(state.currentTab);
});

// Event Listeners
function initEventListeners() {
  // Tab switching
  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetTab = btn.getAttribute('data-tab');
      if (targetTab === state.currentTab) return;

      tabButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      state.currentTab = targetTab;
      state.searchTerm = '';
      state.selectedCategory = 'all';
      state.selectedSubFilter = 'all';
      state.sortBy = 'default';
      state.currentPage = 1;
      searchInput.value = '';

      loadData(targetTab);
    });
  });

  // Search input with instant filtering
  searchInput.addEventListener('input', (e) => {
    state.searchTerm = e.target.value.trim().toLowerCase();
    state.currentPage = 1;
    applyFilters();
  });

  // Primary category dropdown
  primaryFilter.addEventListener('change', (e) => {
    state.selectedCategory = e.target.value;
    state.selectedSubFilter = 'all';
    state.currentPage = 1;
    updateDependentDropdown();
    applyFilters();
  });

  // Dependent secondary dropdown (Intermediate extension)
  subFilter.addEventListener('change', (e) => {
    state.selectedSubFilter = e.target.value;
    state.currentPage = 1;
    applyFilters();
  });

  // Sort dropdown
  sortSelect.addEventListener('change', (e) => {
    state.sortBy = e.target.value;
    state.currentPage = 1;
    applyFilters();
  });

  // Force refetch & bust cache
  refreshBtn.addEventListener('click', () => {
    const config = DATA_CONFIG[state.currentTab];
    localStorage.removeItem(config.storageKey);
    loadData(state.currentTab, true);
  });
}

/**
 * 1. Fetch JSON Data with LocalStorage Caching & Error Handling
 */
async function loadData(tabKey, forceNetwork = false) {
  const config = DATA_CONFIG[tabKey];
  state.isLoading = true;
  renderLoadingState();
  updateToolbarLabels(config);

  // Check LocalStorage cache first (Advanced Extension)
  if (!forceNetwork) {
    const cachedString = localStorage.getItem(config.storageKey);
    if (cachedString) {
      try {
        const cached = JSON.parse(cachedString);
        state.rawData = cached;
        state.isCached = true;
        state.isLoading = false;
        setupDropdownOptions();
        applyFilters();
        updateCacheIndicator(true);
        return;
      } catch (err) {
        console.warn('Invalid cache, refetching fresh JSON...', err);
        localStorage.removeItem(config.storageKey);
      }
    }
  }

  // Fetch over network
  try {
    const response = await fetch(config.url);
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status} (${response.statusText})`);
    }

    const data = await response.json();
    state.rawData = Array.isArray(data) ? data : (data.events || data.students || data.faqs || []);
    state.isCached = false;

    // Cache to localStorage
    try {
      localStorage.setItem(config.storageKey, JSON.stringify(state.rawData));
    } catch (storageError) {
      console.warn('LocalStorage quota exceeded or disabled:', storageError);
    }

    setupDropdownOptions();
    applyFilters();
    updateCacheIndicator(false);
  } catch (error) {
    console.error('Fetch error:', error);
    renderErrorState(error.message, config.url);
  } finally {
    state.isLoading = false;
  }
}

/**
 * Update UI labels and placeholders per active dataset
 */
function updateToolbarLabels(config) {
  searchInput.placeholder = config.searchPlaceholder;
  primaryFilterLabel.textContent = config.primaryFilterLabel;

  if (config.subFilterLabel) {
    subFilterGroup.style.display = 'flex';
    subFilterLabel.textContent = config.subFilterLabel;
  } else {
    subFilterGroup.style.display = 'none';
  }
}

/**
 * Populate Primary and Dependent Filter dropdowns dynamically
 */
function setupDropdownOptions() {
  const tab = state.currentTab;

  // Populate primary dropdown
  primaryFilter.innerHTML = '<option value="all">All Categories</option>';
  let primaryValues = [];

  if (tab === 'events') {
    primaryValues = [...new Set(state.rawData.map(e => e.category))].filter(Boolean);
  } else if (tab === 'students') {
    primaryValues = [...new Set(state.rawData.map(s => s.department))].filter(Boolean);
  } else if (tab === 'faqs') {
    primaryValues = [...new Set(state.rawData.map(f => f.category))].filter(Boolean);
  }

  primaryValues.sort().forEach(val => {
    const opt = document.createElement('option');
    opt.value = val;
    opt.textContent = val;
    primaryFilter.appendChild(opt);
  });
  primaryFilter.value = state.selectedCategory;

  // Setup dependent dropdown
  updateDependentDropdown();
}

/**
 * Intermediate Extension: Dependent Dropdown (e.g. Category -> Dept OR Dept -> Course)
 */
function updateDependentDropdown() {
  const tab = state.currentTab;
  if (!DATA_CONFIG[tab].subFilterLabel) return;

  subFilter.innerHTML = '<option value="all">All Sub-Categories</option>';

  let relevantData = state.rawData;
  if (state.selectedCategory !== 'all') {
    if (tab === 'events') {
      relevantData = state.rawData.filter(e => e.category === state.selectedCategory);
    } else if (tab === 'students') {
      relevantData = state.rawData.filter(s => s.department === state.selectedCategory);
    }
  }

  let subValues = [];
  if (tab === 'events') {
    subValues = [...new Set(relevantData.map(e => e.department))].filter(Boolean);
  } else if (tab === 'students') {
    subValues = [...new Set(relevantData.map(s => s.course))].filter(Boolean);
  }

  subValues.sort().forEach(val => {
    const opt = document.createElement('option');
    opt.value = val;
    opt.textContent = val;
    subFilter.appendChild(opt);
  });
  subFilter.value = state.selectedSubFilter;
}

/**
 * 2. Array Pipeline: Filter, Search, Sort & Paginate
 */
function applyFilters() {
  let list = [...state.rawData];
  const tab = state.currentTab;
  const q = state.searchTerm;

  // 1. Primary Filter
  if (state.selectedCategory !== 'all') {
    if (tab === 'events' || tab === 'faqs') {
      list = list.filter(item => item.category === state.selectedCategory);
    } else if (tab === 'students') {
      list = list.filter(item => item.department === state.selectedCategory);
    }
  }

  // 2. Secondary Dependent Filter
  if (state.selectedSubFilter !== 'all') {
    if (tab === 'events') {
      list = list.filter(item => item.department === state.selectedSubFilter);
    } else if (tab === 'students') {
      list = list.filter(item => item.course === state.selectedSubFilter);
    }
  }

  // 3. Search Filter (map/filter logic)
  if (q !== '') {
    list = list.filter(item => {
      if (tab === 'events') {
        return (
          item.title?.toLowerCase().includes(q) ||
          item.description?.toLowerCase().includes(q) ||
          item.venue?.toLowerCase().includes(q) ||
          item.organizer?.toLowerCase().includes(q)
        );
      } else if (tab === 'students') {
        return (
          item.name?.toLowerCase().includes(q) ||
          item.enrollment_no?.toLowerCase().includes(q) ||
          item.email?.toLowerCase().includes(q) ||
          (item.skills && item.skills.some(skill => skill.toLowerCase().includes(q)))
        );
      } else if (tab === 'faqs') {
        return (
          item.question?.toLowerCase().includes(q) ||
          item.answer?.toLowerCase().includes(q)
        );
      }
      return false;
    });
  }

  // 4. Sorting logic
  if (state.sortBy !== 'default') {
    list.sort((a, b) => {
      if (state.sortBy === 'name_asc') {
        const nameA = (a.title || a.name || a.question || '').toLowerCase();
        const nameB = (b.title || b.name || b.question || '').toLowerCase();
        return nameA.localeCompare(nameB);
      } else if (state.sortBy === 'name_desc') {
        const nameA = (a.title || a.name || a.question || '').toLowerCase();
        const nameB = (b.title || b.name || b.question || '').toLowerCase();
        return nameB.localeCompare(nameA);
      } else if (state.sortBy === 'date_asc') {
        return new Date(a.date || 0) - new Date(b.date || 0);
      } else if (state.sortBy === 'date_desc') {
        return new Date(b.date || 0) - new Date(a.date || 0);
      } else if (state.sortBy === 'gpa_desc') {
        return (b.gpa || 0) - (a.gpa || 0);
      }
      return 0;
    });
  }

  state.filteredData = list;
  resultCountEl.textContent = `Showing ${list.length} record${list.length === 1 ? '' : 's'}`;

  // 5. Paginate and Render
  renderPaginatedView();
}

/**
 * 3. Render Paginated View & Pagination Buttons
 */
function renderPaginatedView() {
  const total = state.filteredData.length;
  const totalPages = Math.ceil(total / state.pageSize) || 1;

  if (state.currentPage > totalPages) {
    state.currentPage = totalPages;
  }

  const startIdx = (state.currentPage - 1) * state.pageSize;
  const endIdx = startIdx + state.pageSize;
  const pageItems = state.filteredData.slice(startIdx, endIdx);

  if (total === 0) {
    renderEmptyState();
    paginationContainer.innerHTML = '';
    return;
  }

  if (state.currentTab === 'events') {
    renderEvents(pageItems);
  } else if (state.currentTab === 'students') {
    renderStudents(pageItems);
  } else if (state.currentTab === 'faqs') {
    renderFaqs(pageItems);
  }

  renderPaginationControls(totalPages);
}

/**
 * Dynamic Card Renderers
 */
function renderEvents(items) {
  contentContainer.className = 'cards-grid';
  contentContainer.innerHTML = items.map(evt => {
    const badgeClass = getBadgeClass(evt.category);
    const feeText = evt.fee === 0 ? 'Free Entry' : `₹${evt.fee}`;
    return `
      <article class="item-card">
        <div class="card-header">
          <h3>${escapeHtml(evt.title)}</h3>
          <span class="pill-badge ${badgeClass}">${escapeHtml(evt.category)}</span>
        </div>
        <div class="card-meta">
          <span>📅 ${evt.date} &bull; 📍 ${escapeHtml(evt.venue)}</span>
          <span>🏛️ ${escapeHtml(evt.department)}</span>
          <span>👥 Capacity: ${evt.capacity} students</span>
        </div>
        <p class="card-desc">${escapeHtml(evt.description)}</p>
        <div class="card-footer">
          <span style="font-weight:600; color:var(--primary);">💰 ${feeText}</span>
          <span style="font-size:12px; color: ${evt.status === 'Completed' ? 'var(--text-muted)' : 'var(--accent)'}; font-weight:600;">
            ● ${evt.status || 'Open'}
          </span>
        </div>
      </article>
    `;
  }).join('');
}

function renderStudents(items) {
  contentContainer.className = 'cards-grid';
  contentContainer.innerHTML = items.map(stu => {
    const skillsHtml = (stu.skills || [])
      .map(s => `<span class="pill-badge pill-tech" style="font-size:10px;">${escapeHtml(s)}</span>`)
      .join(' ');

    return `
      <article class="item-card">
        <div class="card-header">
          <div>
            <h3>${escapeHtml(stu.name)}</h3>
            <span style="font-size:12px; font-weight:700; color:var(--primary);">${escapeHtml(stu.enrollment_no)}</span>
          </div>
          <span class="pill-badge pill-seminar">${escapeHtml(stu.course)} - Year ${stu.year}</span>
        </div>
        <div class="card-meta">
          <span>🏫 ${escapeHtml(stu.department)}</span>
          <span>✉️ <a href="mailto:${stu.email}" style="color:var(--text-muted); text-decoration:none;">${escapeHtml(stu.email)}</a></span>
        </div>
        <div style="margin: 10px 0;">
          <small style="font-weight:600; color:var(--text-muted);">Skills:</small>
          <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:4px;">${skillsHtml}</div>
        </div>
        <div class="card-footer">
          <span>CGPA: <strong>${stu.gpa}</strong></span>
          <span style="color:var(--accent); font-weight:600; font-size:12px;">● ${stu.status}</span>
        </div>
      </article>
    `;
  }).join('');
}

function renderFaqs(items) {
  contentContainer.className = 'faq-list';
  contentContainer.innerHTML = items.map(faq => `
    <div class="faq-card" id="faq-${faq.id}">
      <button class="faq-question" onclick="toggleFaq(${faq.id})">
        <span>${escapeHtml(faq.question)}</span>
        <span class="faq-icon">▼</span>
      </button>
      <div class="faq-answer">
        <span class="pill-badge pill-social" style="margin-bottom:8px; display:inline-block;">${escapeHtml(faq.category)}</span>
        <p>${escapeHtml(faq.answer)}</p>
      </div>
    </div>
  `).join('');
}

window.toggleFaq = function(id) {
  const card = document.getElementById(`faq-${id}`);
  if (card) {
    card.classList.toggle('open');
  }
};

/**
 * Pagination Controls UI
 */
function renderPaginationControls(totalPages) {
  if (totalPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }

  let html = `
    <button class="page-btn" ${state.currentPage === 1 ? 'disabled' : ''} onclick="goToPage(${state.currentPage - 1})">
      &laquo; Previous
    </button>
  `;

  for (let i = 1; i <= totalPages; i++) {
    html += `
      <button class="page-btn ${state.currentPage === i ? 'active' : ''}" onclick="goToPage(${i})">
        ${i}
      </button>
    `;
  }

  html += `
    <button class="page-btn" ${state.currentPage === totalPages ? 'disabled' : ''} onclick="goToPage(${state.currentPage + 1})">
      Next &raquo;
    </button>
  `;

  paginationContainer.innerHTML = html;
}

window.goToPage = function(pageNum) {
  state.currentPage = pageNum;
  renderPaginatedView();
  contentContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

/**
 * Cache & Loading UI States
 */
function updateCacheIndicator(isFromCache) {
  if (isFromCache) {
    cacheStatusEl.innerHTML = `
      <span class="cache-pill">
        ⚡ Loaded from LocalStorage Cache (Offline Ready)
      </span>
    `;
  } else {
    cacheStatusEl.innerHTML = `
      <span class="cache-pill" style="background:#e0e7ff; color:#3730a3;">
        🌐 Fetched Live via Fetch API
      </span>
    `;
  }
}

function renderLoadingState() {
  contentContainer.className = '';
  contentContainer.innerHTML = `
    <div class="state-box">
      <div class="spinner"></div>
      <p style="color:var(--text-muted); font-size:15px;">Fetching JSON records via Fetch API...</p>
    </div>
  `;
}

function renderEmptyState() {
  contentContainer.className = '';
  contentContainer.innerHTML = `
    <div class="state-box">
      <p style="font-size:24px; margin-bottom:10px;">🔍</p>
      <h3>No matching records found</h3>
      <p style="color:var(--text-muted); font-size:14px; margin-top:5px;">
        Try refining your search keyword or resetting dropdown filters.
      </p>
    </div>
  `;
}

function renderErrorState(errorMsg, endpoint) {
  contentContainer.className = '';
  contentContainer.innerHTML = `
    <div class="state-box">
      <p style="font-size:30px; margin-bottom:8px;">⚠️</p>
      <h3 class="error-text">Failed to fetch data</h3>
      <p style="color:var(--text-muted); font-size:14px; margin: 8px 0 16px;">
        Could not load <code>${endpoint}</code>. Details: ${escapeHtml(errorMsg)}
      </p>
      <button class="btn-secondary" onclick="loadData('${state.currentTab}', true)">
        🔄 Try Again
      </button>
    </div>
  `;
}

// Helpers
function getBadgeClass(cat) {
  const c = (cat || '').toLowerCase();
  if (c.includes('tech')) return 'pill-tech';
  if (c.includes('work')) return 'pill-workshop';
  if (c.includes('cult')) return 'pill-cultural';
  if (c.includes('sport')) return 'pill-sports';
  if (c.includes('sem')) return 'pill-seminar';
  return 'pill-social';
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
