/* ============================================================
   Tour Guidati — Main Frontend JS (Vanilla)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  // ── Mobile nav toggle ──────────────────────────────────────
  const navToggle = document.querySelector('.nav-toggle');
  const mainNav   = document.querySelector('.main-nav');
  const navActions= document.querySelector('.nav-actions');
  if (navToggle) {
    navToggle.addEventListener('click', function () {
      mainNav  && mainNav.classList.toggle('open');
      navActions && navActions.classList.toggle('open');
    });
  }

  // ── Dismiss flash alerts ───────────────────────────────────
  document.querySelectorAll('.alert-close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.alert').style.display = 'none';
    });
  });

  // Auto-dismiss success alerts after 5 s
  document.querySelectorAll('.alert-success').forEach(function (el) {
    setTimeout(function () { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s'; setTimeout(function () { el.style.display = 'none'; }, 500); }, 5000);
  });

  // ── Tour image gallery ─────────────────────────────────────
  initGallery();

  // ── Booking participant counter ────────────────────────────
  initBookingCounter();

  // ── Slot selection ─────────────────────────────────────────
  initSlotSelection();

  // ── My bookings accordion ─────────────────────────────────
  initBookingAccordion();

  // ── Star rating input ─────────────────────────────────────
  initStarRating();
});

// ── Gallery ────────────────────────────────────────────────────
function initGallery() {
  const mainImg     = document.getElementById('gallery-main-img');
  const thumbItems  = document.querySelectorAll('.gallery-thumb');
  const lightbox    = document.getElementById('gallery-lightbox');
  const lightboxImg = document.getElementById('lightbox-img');
  const lightboxClose = document.getElementById('lightbox-close');
  const prevBtn     = document.getElementById('gallery-prev');
  const nextBtn     = document.getElementById('gallery-next');

  if (!mainImg) return;

  let currentIdx = 0;
  const images = Array.from(thumbItems).map(t => ({
    src: t.dataset.full || t.querySelector('img').src,
    alt: t.dataset.alt  || '',
  }));
  if (!images.length) return;

  function showImage(idx) {
    currentIdx = (idx + images.length) % images.length;
    mainImg.src = images[currentIdx].src;
    mainImg.alt = images[currentIdx].alt;
    thumbItems.forEach((t, i) => t.classList.toggle('active', i === currentIdx));
    if (lightboxImg) lightboxImg.src = images[currentIdx].src;
  }

  thumbItems.forEach(function (thumb, i) {
    thumb.addEventListener('click', function () { showImage(i); });
  });

  if (prevBtn) prevBtn.addEventListener('click', function () { showImage(currentIdx - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { showImage(currentIdx + 1); });

  // Keyboard navigation
  document.addEventListener('keydown', function (e) {
    if (!lightbox || !lightbox.classList.contains('open')) return;
    if (e.key === 'ArrowLeft')  showImage(currentIdx - 1);
    if (e.key === 'ArrowRight') showImage(currentIdx + 1);
    if (e.key === 'Escape') closeLightbox();
  });

  if (mainImg) {
    mainImg.style.cursor = 'zoom-in';
    mainImg.addEventListener('click', function () { openLightbox(); });
  }

  function openLightbox() {
    if (!lightbox) return;
    if (lightboxImg) lightboxImg.src = images[currentIdx].src;
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (lightbox) lightbox.addEventListener('click', function (e) { if (e.target === lightbox) closeLightbox(); });
  if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
}

// ── Booking counter & price update ────────────────────────────
function initBookingCounter() {
  const decreaseBtn   = document.getElementById('decrease-participants');
  const increaseBtn   = document.getElementById('increase-participants');
  const countEl       = document.getElementById('participant-count');
  const countInput    = document.getElementById('num-participants-input');
  const pricePerPerson= parseFloat(document.getElementById('price-per-person') ? document.getElementById('price-per-person').dataset.price : 0);
  const maxSeats      = parseInt(document.getElementById('max-seats') ? document.getElementById('max-seats').dataset.max : 10);
  const totalPriceEl  = document.getElementById('total-price');
  const participantsContainer = document.getElementById('participants-forms');

  if (!countEl) return;

  let count = 1;

  function updateDisplay() {
    countEl.textContent = count;
    if (countInput) countInput.value = count;
    if (totalPriceEl) {
      const total = (pricePerPerson * count).toFixed(2);
      totalPriceEl.textContent = '€ ' + total.replace('.', ',');
    }
    if (decreaseBtn) decreaseBtn.disabled = count <= 1;
    if (increaseBtn) increaseBtn.disabled = count >= maxSeats;
    updateParticipantForms(count);
  }

  function updateParticipantForms(n) {
    if (!participantsContainer) return;
    const existing = participantsContainer.querySelectorAll('.participant-form');
    const diff = n - existing.length;

    if (diff > 0) {
      for (let i = existing.length; i < n; i++) {
        const form = createParticipantForm(i);
        participantsContainer.appendChild(form);
      }
    } else if (diff < 0) {
      for (let i = existing.length - 1; i >= n; i--) {
        participantsContainer.removeChild(existing[i]);
      }
    }
  }

  function createParticipantForm(index) {
    const div = document.createElement('div');
    div.className = 'participant-form';
    const label = index === 0 ? ' <span class="badge badge-info">Contatto principale</span>' : '';
    div.innerHTML =
      '<div class="participant-header">Partecipante ' + (index + 1) + label + '</div>' +
      '<div class="form-row">' +
      '  <div class="form-group">' +
      '    <label class="form-label">Nome <span class="req">*</span></label>' +
      '    <input type="text" name="participants[' + index + '][first_name]" class="form-control" required>' +
      '  </div>' +
      '  <div class="form-group">' +
      '    <label class="form-label">Cognome <span class="req">*</span></label>' +
      '    <input type="text" name="participants[' + index + '][last_name]" class="form-control" required>' +
      '  </div>' +
      '</div>';
    return div;
  }

  if (decreaseBtn) decreaseBtn.addEventListener('click', function () { if (count > 1) { count--; updateDisplay(); } });
  if (increaseBtn) increaseBtn.addEventListener('click', function () { if (count < maxSeats) { count++; updateDisplay(); } });

  updateDisplay();
}

// ── Slot selection ─────────────────────────────────────────────
function initSlotSelection() {
  const slotItems    = document.querySelectorAll('.slot-item[data-slot-id]');
  const hiddenInput  = document.getElementById('selected-slot-id');
  const bookingBtn   = document.getElementById('btn-book-slot');

  slotItems.forEach(function (item) {
    if (item.classList.contains('full')) return;
    item.addEventListener('click', function () {
      slotItems.forEach(s => s.classList.remove('selected'));
      item.classList.add('selected');
      if (hiddenInput) hiddenInput.value = item.dataset.slotId;
      if (bookingBtn) {
        bookingBtn.disabled = false;
        bookingBtn.href = bookingBtn.dataset.base + '?slot=' + item.dataset.slotId;
      }
    });
  });
}

// ── Accordion for my bookings ──────────────────────────────────
function initBookingAccordion() {
  document.querySelectorAll('.booking-item-header').forEach(function (header) {
    header.addEventListener('click', function () {
      const body = header.nextElementSibling;
      if (body && body.classList.contains('booking-item-body')) {
        body.classList.toggle('open');
        const arrow = header.querySelector('.accordion-arrow');
        if (arrow) arrow.textContent = body.classList.contains('open') ? '▲' : '▼';
      }
    });
  });
}

// ── Star rating input (review form) ───────────────────────────
function initStarRating() {
  const container = document.querySelector('.star-rating-input');
  if (!container) return;

  const stars = container.querySelectorAll('.star-btn');
  const input = document.getElementById('rating-input');
  let selected = 0;

  stars.forEach(function (star, i) {
    star.addEventListener('mouseover', function () { highlight(i + 1); });
    star.addEventListener('mouseleave', function () { highlight(selected); });
    star.addEventListener('click', function () {
      selected = i + 1;
      if (input) input.value = selected;
      highlight(selected);
    });
  });

  function highlight(n) {
    stars.forEach(function (s, i) {
      s.textContent = i < n ? '★' : '☆';
      s.style.color  = i < n ? '#DAA520' : '#D8C9B5';
    });
  }
}
