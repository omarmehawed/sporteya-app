// Sporteya Interactive UI Prototype (In-memory state)

const state = {
  sports: [
    { id: 'football', name: 'كرة قدم', icon: '⚽' },
    { id: 'padel', name: 'بادل', icon: '🎾' },
    { id: 'basketball', name: 'كرة سلة', icon: '🏀' },
    { id: 'tennis', name: 'تنس أرضي', icon: '🎾' },
    { id: 'volleyball', name: 'كرة طائرة', icon: '🏐' },
    { id: 'squash', name: 'اسكواش', icon: '🏸' },
  ],
  selectedSport: 'all',
  viewMode: 'list', // 'list' or 'map'
  searchQuery: '',
  sortBy: 'rating',
  favorites: new Set(),
  venues: [
    {
      id: 'v1',
      name: 'ملاعب النخبة الرياضية',
      city: 'القاهرة',
      address: 'التجمع الخامس، شارع التسعين',
      rating: 4.9,
      reviewsCount: 142,
      pricePerHour: 350,
      sport: 'football',
      features: ['💡 إضاءة ليلية', '🚗 موقف سيارات', '👕 غرف تبديل'],
      img: 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=600&q=80',
      mapX: 68,
      mapY: 42
    },
    {
      id: 'v2',
      name: 'أكاديمية بادل إسبانيا',
      city: 'الشيخ زايد',
      address: 'بيفرلي هيلز، مدخل 3',
      rating: 4.8,
      reviewsCount: 98,
      pricePerHour: 400,
      sport: 'padel',
      features: ['💡 ملاعب بانوراما', '☕ كافيه رياضي', '🚗 باركينج'],
      img: 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=600&q=80',
      mapX: 25,
      mapY: 55
    },
    {
      id: 'v3',
      name: 'نادي المعادي الأولمبي',
      city: 'المعادي',
      address: 'دجلة، ميدان فيكتوريا',
      rating: 4.7,
      reviewsCount: 210,
      pricePerHour: 300,
      sport: 'football',
      features: ['🌱 نجيل طبيعي', '👕 غرف ملابس', '🚿 مياه ساخنة'],
      img: 'https://images.unsplash.com/photo-1529900241484-934d40232ef1?auto=format&fit=crop&w=600&q=80',
      mapX: 48,
      mapY: 70
    },
    {
      id: 'v4',
      name: 'كورتس هيليوبوليس للبادل',
      city: 'مصر الجديدة',
      address: 'ميدان الحجاز، خلف الميريلاند',
      rating: 4.9,
      reviewsCount: 76,
      pricePerHour: 380,
      sport: 'padel',
      features: ['💡 إضاءة LED', '🎾 مضارب للإيجار', '🚗 موقف'],
      img: 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?auto=format&fit=crop&w=600&q=80',
      mapX: 55,
      mapY: 30
    }
  ],
  openMatches: [
    {
      id: 'm1',
      venueName: 'ملاعب النخبة - التجمع الخامس',
      sport: 'كرة قدم (خماسي)',
      date: 'اليوم',
      time: '08:00 م - 09:00 م',
      currentPlayers: 8,
      requiredPlayers: 10,
      pricePerPlayer: 35,
      slotWarning: true,
      avatars: ['عمر', 'أحمد', 'محمد', 'كريم']
    },
    {
      id: 'm2',
      venueName: 'بادل إسبانيا - الشيخ زايد',
      sport: 'بادل (زوجي)',
      date: 'غداً',
      time: '07:00 م - 08:30 م',
      currentPlayers: 3,
      requiredPlayers: 4,
      pricePerPlayer: 100,
      slotWarning: false,
      avatars: ['يوسف', 'علي', 'حازم']
    }
  ],
  bookings: [
    {
      id: 'BK-8942',
      venue: 'ملاعب النخبة الرياضية',
      date: 'الجمعة 17 أكتوبر',
      time: '08:00 م - 09:00 م',
      status: 'confirmed',
      amount: 350,
      qrToken: 'SPORTEYA-CONFIRMED-8942-TOKEN-SAMPLE'
    }
  ]
};

// DOM Functions
function renderSports() {
  const container = document.getElementById('sports-filter-container');
  if (!container) return;

  let html = `<button class="sport-pill ${state.selectedSport === 'all' ? 'active' : ''}" onclick="filterBySport('all')" aria-label="عرض جميع الرياضات">الكل ⚡</button>`;
  state.sports.forEach(s => {
    html += `<button class="sport-pill ${state.selectedSport === s.id ? 'active' : ''}" onclick="filterBySport('${s.id}')" aria-label="تصفية حسب ${s.name}">${s.name} ${s.icon}</button>`;
  });
  container.innerHTML = html;
}

function filterBySport(sportId) {
  state.selectedSport = sportId;
  renderSports();
  renderVenues();
  renderMap();
}

function handleLiveSearch(query) {
  state.searchQuery = query.trim().toLowerCase();
  renderVenues();
  renderMap();
}

function changeSort(sortBy) {
  state.sortBy = sortBy;
  renderVenues();
}

function switchViewMode(mode) {
  state.viewMode = mode;
  const listBtn = document.getElementById('view-list-btn');
  const mapBtn = document.getElementById('view-map-btn');
  const mapContainer = document.getElementById('interactive-map-container');
  const gridContainer = document.getElementById('venues-grid-container');

  if (listBtn) listBtn.classList.toggle('active', mode === 'list');
  if (mapBtn) mapBtn.classList.toggle('active', mode === 'map');

  if (mapContainer) {
    mapContainer.classList.toggle('show', mode === 'map');
  }
  if (gridContainer) {
    gridContainer.style.display = mode === 'map' ? 'none' : 'grid';
  }

  if (mode === 'map') {
    renderMap();
  }
}

function toggleFavorite(venueId, event) {
  if (event) event.stopPropagation();
  if (state.favorites.has(venueId)) {
    state.favorites.delete(venueId);
    showToast('تمت إزالة الملعب من المفضلة');
  } else {
    state.favorites.add(venueId);
    showToast('❤️ تمت إضافة الملعب إلى المفضلة!');
  }
  renderVenues();
}

function getFilteredVenues() {
  let list = state.venues;

  if (state.selectedSport !== 'all') {
    list = list.filter(v => v.sport === state.selectedSport);
  }

  if (state.searchQuery) {
    list = list.filter(v =>
      v.name.toLowerCase().includes(state.searchQuery) ||
      v.city.toLowerCase().includes(state.searchQuery) ||
      v.address.toLowerCase().includes(state.searchQuery)
    );
  }

  if (state.sortBy === 'price_asc') {
    list = [...list].sort((a, b) => a.pricePerHour - b.pricePerHour);
  } else if (state.sortBy === 'rating') {
    list = [...list].sort((a, b) => b.rating - a.rating);
  }

  return list;
}

function renderVenues() {
  const container = document.getElementById('venues-grid-container');
  if (!container) return;

  const filtered = getFilteredVenues();

  if (filtered.length === 0) {
    container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted); font-size: 1rem;">لا توجد ملاعب مطابقة لبحثك حالياً.</div>`;
    return;
  }

  container.innerHTML = filtered.map(v => {
    const isFav = state.favorites.has(v.id);
    return `
      <div class="venue-card" id="venue-card-${v.id}">
        <div class="venue-img-box">
          <img src="${v.img}" alt="${v.name}" class="venue-img">
          <span class="badge-float">${v.city}</span>
          <button class="heart-btn ${isFav ? 'liked' : ''}" onclick="toggleFavorite('${v.id}', event)" aria-label="إضافة للمفضلة">
            ${isFav ? '❤️' : '🤍'}
          </button>
        </div>
        <div class="venue-body">
          <div class="venue-title-row">
            <h3 class="venue-name">${v.name}</h3>
            <span class="venue-rating">⭐ ${v.rating}</span>
          </div>
          <p class="venue-address">📍 ${v.address}</p>
          <div class="venue-features">
            ${v.features.map(f => `<span class="feature-tag">${f}</span>`).join('')}
          </div>
          <div class="venue-footer">
            <div class="venue-price">${v.pricePerHour} ج.م <span>/ ساعة</span></div>
            <button class="btn btn-primary btn-sm" onclick="openBookingModal('${v.id}')" aria-label="حجز موعد في ${v.name}">احجز الآن</button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function renderMap() {
  const mapSvg = document.getElementById('map-svg-pins');
  if (!mapSvg) return;

  const filtered = getFilteredVenues();

  mapSvg.innerHTML = filtered.map(v => `
    <g class="map-pin" transform="translate(${v.mapX * 8}, ${v.mapY * 3.5})" onclick="openBookingModal('${v.id}')">
      <circle r="18" fill="rgba(16,185,129,0.25)" />
      <circle r="10" fill="#10B981" stroke="#FFFFFF" stroke-width="2.5" />
      <text y="-16" text-anchor="middle" fill="#FFFFFF" font-size="11" font-weight="700" font-family="Alexandria">
        ${v.name.slice(0, 15)}.. (${v.pricePerHour}ج)
      </text>
    </g>
  `).join('');
}

function renderMatches() {
  const container = document.getElementById('open-matches-container');
  if (!container) return;

  container.innerHTML = state.openMatches.map(m => `
    <div class="venue-card" style="margin-bottom: 1rem; border-right: 4px solid var(--primary);">
      <div class="venue-body">
        <div class="venue-title-row">
          <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary);">${m.sport}</span>
          <span style="font-size: 0.85rem; font-weight: 800; color: var(--accent);">${m.pricePerPlayer} ج.م / لاعب</span>
        </div>
        <h4 style="font-size: 1.1rem; margin: 0.3rem 0;">${m.venueName}</h4>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">📅 ${m.date} (${m.time})</p>
        
        ${m.slotWarning ? `
          <div style="background: #FEF3C7; border: 1px solid #F59E0B; padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.75rem; color: #92400E; margin-bottom: 0.75rem; font-weight: 700;">
            ⚠️ في ماتش تاني على نفس الوقت ده. الأول يكمل الدفع هياخد الملعب.
          </div>
        ` : ''}

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 0.75rem;">
          <div style="font-size: 0.85rem; font-weight: 700;">
            👥 باقي ${m.requiredPlayers - m.currentPlayers} لاعبين
          </div>
          <button class="btn btn-primary btn-sm" onclick="openJoinModal('${m.id}')" aria-label="انضم للماتش في ${m.venueName}">انضم للماتش</button>
        </div>
      </div>
    </div>
  `).join('');
}

// Modal & Flow Handlers
let currentBookingVenue = null;
let currentBookingStage = 1;

function openBookingModal(venueId) {
  currentBookingVenue = state.venues.find(v => v.id === venueId);
  currentBookingStage = 1;
  renderBookingModalStage();
  document.getElementById('booking-modal').classList.add('open');
}

function closeBookingModal() {
  document.getElementById('booking-modal').classList.remove('open');
}

function nextBookingStage() {
  currentBookingStage++;
  renderBookingModalStage();
}

function prevBookingStage() {
  currentBookingStage--;
  renderBookingModalStage();
}

function renderBookingModalStage() {
  const container = document.getElementById('booking-modal-content');
  if (!container || !currentBookingVenue) return;

  if (currentBookingStage === 1) {
    container.innerHTML = `
      <div class="modal-header">
        <h3>حجز موعد - المرحلة 1/3 (تفاصيل الملعب)</h3>
        <button class="close-btn" onclick="closeBookingModal()">✕</button>
      </div>
      <div>
        <h4 style="font-size: 1.25rem; margin-bottom: 0.5rem;">${currentBookingVenue.name}</h4>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">📍 ${currentBookingVenue.address}</p>
        <div style="background: var(--surface-muted); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
          <div><strong>سعر الساعة:</strong> ${currentBookingVenue.pricePerHour} ج.م</div>
          <div><strong>التقييم العام:</strong> ⭐ ${currentBookingVenue.rating} (${currentBookingVenue.reviewsCount} تقييم)</div>
        </div>
        <button class="btn btn-primary" style="width: 100%;" onclick="nextBookingStage()">متابعة لاختيار التاريخ والوقت ←</button>
      </div>
    `;
  } else if (currentBookingStage === 2) {
    container.innerHTML = `
      <div class="modal-header">
        <h3>حجز موعد - المرحلة 2/3 (الميعاد)</h3>
        <button class="close-btn" onclick="closeBookingModal()">✕</button>
      </div>
      <div>
        <div class="form-group">
          <label class="form-label">تاريخ الحجز</label>
          <input type="date" class="form-input" value="${new Date().toISOString().slice(0,10)}" id="booking-date-input">
        </div>
        <div class="form-group">
          <label class="form-label">الفترة الزمنية المتاحة</label>
          <select class="form-input" id="booking-slot-input">
            <option value="18:00 - 19:00">06:00 م - 07:00 م (${currentBookingVenue.pricePerHour} ج.م)</option>
            <option value="19:00 - 20:00">07:00 م - 08:00 م (${currentBookingVenue.pricePerHour} ج.م)</option>
            <option value="20:00 - 21:00">08:00 م - 09:00 م (${currentBookingVenue.pricePerHour} ج.م)</option>
            <option value="21:00 - 22:00">09:00 م - 10:00 م (${currentBookingVenue.pricePerHour} ج.م)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">طريقة اللعب</label>
          <select class="form-input">
            <option>ماتش عام (مفتوح للجميع لتقسيم التكلفة)</option>
            <option>ماتش مختلط (بالموافقة المسبقة)</option>
            <option>حجز كامل مع أصحابي (ماتش خاص)</option>
          </select>
        </div>
        <div style="display: flex; gap: 0.75rem;">
          <button class="btn btn-secondary" style="flex: 1;" onclick="prevBookingStage()">→ السابق</button>
          <button class="btn btn-primary" style="flex: 2;" onclick="nextBookingStage()">تأكيد الحجز المبدئي ←</button>
        </div>
      </div>
    `;
  } else if (currentBookingStage === 3) {
    container.innerHTML = `
      <div class="modal-header">
        <h3>تأكيد الحجز - المرحلة 3/3</h3>
        <button class="close-btn" onclick="closeBookingModal()">✕</button>
      </div>
      <div style="text-align: center;">
        <span class="preview-badge" style="margin-bottom: 1rem; display: inline-block;">معاينة فقط — حجز تجريبي</span>
        <div style="font-size: 3rem; margin: 1rem 0;">🎉</div>
        <h4 style="font-size: 1.25rem; margin-bottom: 0.5rem;">تم إنشاء حجزك بنجاح!</h4>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
          تم تسجيل حجزك في <strong>${currentBookingVenue.name}</strong>. يمكنك الآن دعوة اللاعبين والبدء في سداد الحصص.
        </p>
        <button class="btn btn-primary" style="width: 100%;" onclick="closeBookingModal(); showToast('تم تأكيد الحجز التجريبي بنجاح!');">إغلاق والعودة للرئيسية</button>
      </div>
    `;
  }
}

// Join Modal
function openJoinModal(matchId) {
  const match = state.openMatches.find(m => m.id === matchId);
  if (!match) return;

  const modal = document.getElementById('join-modal');
  const content = document.getElementById('join-modal-content');
  if (!modal || !content) return;

  content.innerHTML = `
    <div class="modal-header">
      <h3>الانضمام للماتش</h3>
      <button class="close-btn" onclick="document.getElementById('join-modal').classList.remove('open')">✕</button>
    </div>
    <div>
      <h4 style="font-size: 1.15rem; margin-bottom: 0.25rem;">${match.venueName}</h4>
      <p style="color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">${match.sport}</p>
      <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">📅 ${match.date} (${match.time})</p>

      ${match.slotWarning ? `
        <div style="background: #FEF3C7; border: 1px solid #F59E0B; padding: 0.75rem; border-radius: 8px; font-size: 0.8rem; color: #92400E; margin-bottom: 1.25rem; font-weight: 700;">
          ⚠️ في ماتش تاني على نفس الوقت ده. الأول يكمل الدفع هياخد الملعب.
        </div>
      ` : ''}

      <div style="background: var(--surface-muted); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <div><strong>نصيب اللاعب في الحجز:</strong> ${match.pricePerPlayer} ج.م</div>
        <div><strong>اللاعبين الحاليين:</strong> ${match.currentPlayers} / ${match.requiredPlayers}</div>
      </div>

      <button class="btn btn-primary" style="width: 100%;" onclick="
        document.getElementById('join-modal').classList.remove('open');
        showToast('تم الانضمام للماتش بنجاح! (معاينة فقط)');
      ">تأكيد الانضمام ودفع ${match.pricePerPlayer} ج.م</button>
    </div>
  `;
  modal.classList.add('open');
}

// Toast
function showToast(msg) {
  const toast = document.getElementById('toast-msg');
  if (!toast) return;
  toast.innerText = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3500);
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  renderSports();
  renderVenues();
  renderMatches();

  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => handleLiveSearch(e.target.value));
  }
});
