/* ============================================================
   Tour Guidati — Admin JS (Vanilla)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  // ── Dismiss alerts ─────────────────────────────────────────
  document.querySelectorAll('.alert-close').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.closest('.alert').remove(); });
  });
  document.querySelectorAll('.alert-success').forEach(function (el) {
    setTimeout(function () { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s'; setTimeout(function () { el.remove(); }, 500); }, 5000);
  });

  // ── Delete confirmation modal ──────────────────────────────
  initDeleteConfirm();

  // ── Image upload preview ───────────────────────────────────
  initImageUpload();

  // ── Slug auto-generation ───────────────────────────────────
  initSlugGeneration();

  // ── Mobile sidebar toggle ──────────────────────────────────
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar       = document.querySelector('.admin-sidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () { sidebar.classList.toggle('open'); });
  }

  // Close sidebar on outside click (mobile)
  document.addEventListener('click', function (e) {
    if (window.innerWidth > 768) return;
    if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
      sidebar.classList.remove('open');
    }
  });
});

// ── Delete confirmation ────────────────────────────────────────
function initDeleteConfirm() {
  const modal     = document.getElementById('delete-modal');
  const modalForm = document.getElementById('delete-form');
  const cancelBtn = document.getElementById('modal-cancel');

  if (!modal) return;

  document.querySelectorAll('[data-delete-url]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const url  = btn.dataset.deleteUrl;
      const name = btn.dataset.deleteName || 'questo elemento';
      const msg  = modal.querySelector('.modal-text');
      if (msg) msg.textContent = 'Sei sicuro di voler eliminare "' + name + '"? Questa azione è irreversibile.';
      if (modalForm) modalForm.action = url;
      modal.classList.add('open');
    });
  });

  if (cancelBtn) cancelBtn.addEventListener('click', function () { modal.classList.remove('open'); });
  modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open'); });
}

// ── Image upload preview ───────────────────────────────────────
function initImageUpload() {
  const uploadInput   = document.getElementById('image-upload');
  const previewGrid   = document.getElementById('preview-grid');
  const uploadZone    = document.querySelector('.upload-zone');

  if (!uploadInput) return;

  // Click on zone triggers input
  if (uploadZone) {
    uploadZone.addEventListener('click', function () { uploadInput.click(); });
    uploadZone.addEventListener('dragover', function (e) { e.preventDefault(); uploadZone.classList.add('dragover'); });
    uploadZone.addEventListener('dragleave', function ()  { uploadZone.classList.remove('dragover'); });
    uploadZone.addEventListener('drop', function (e) {
      e.preventDefault();
      uploadZone.classList.remove('dragover');
      if (e.dataTransfer.files.length) {
        const dt = new DataTransfer();
        Array.from(uploadInput.files).forEach(f => dt.items.add(f));
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        uploadInput.files = dt.files;
        uploadInput.dispatchEvent(new Event('change'));
      }
    });
  }

  uploadInput.addEventListener('change', function () {
    if (!previewGrid) return;
    previewGrid.innerHTML = '';
    Array.from(uploadInput.files).forEach(function (file, i) {
      if (!file.type.startsWith('image/')) return;
      const reader = new FileReader();
      reader.onload = function (ev) {
        const div = document.createElement('div');
        div.className = 'image-preview-item';
        div.innerHTML =
          '<img src="' + ev.target.result + '" alt="">' +
          '<button type="button" class="image-preview-remove" data-index="' + i + '">✕</button>' +
          '<label class="cover-radio"><input type="radio" name="cover_image" value="' + i + '"' + (i === 0 ? ' checked' : '') + '> Cover</label>';
        previewGrid.appendChild(div);
      };
      reader.readAsDataURL(file);
    });
  });

  // Remove image from preview (note: cannot remove from FileList directly)
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('image-preview-remove')) {
      e.target.closest('.image-preview-item').remove();
    }
  });

  // Single image preview (guide photo)
  const singleInput   = document.getElementById('photo-upload');
  const singlePreview = document.getElementById('photo-preview');
  if (singleInput && singlePreview) {
    singleInput.addEventListener('change', function () {
      if (!singleInput.files[0]) return;
      const reader = new FileReader();
      reader.onload = function (ev) { singlePreview.src = ev.target.result; singlePreview.style.display = 'block'; };
      reader.readAsDataURL(singleInput.files[0]);
    });
  }
}

// ── Auto slug from title ───────────────────────────────────────
function initSlugGeneration() {
  const titleInput = document.getElementById('tour-title');
  const slugInput  = document.getElementById('tour-slug');
  if (!titleInput || !slugInput) return;

  let autoMode = slugInput.value === '';

  titleInput.addEventListener('input', function () {
    if (!autoMode) return;
    slugInput.value = slugify(titleInput.value);
  });

  slugInput.addEventListener('input', function () {
    autoMode = slugInput.value === '';
  });
}

function slugify(text) {
  const map = { 'à':'a','á':'a','â':'a','ã':'a','ä':'a','è':'e','é':'e','ê':'e','ë':'e','ì':'i','í':'i','î':'i','ï':'i','ò':'o','ó':'o','ô':'o','ö':'o','ù':'u','ú':'u','û':'u','ü':'u' };
  return text.toLowerCase()
    .replace(/[àáâãäèéêëìíîïòóôöùúûü]/g, m => map[m] || m)
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/[\s-]+/g, '-');
}
