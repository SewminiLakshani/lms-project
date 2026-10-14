
// Confirm before delete actions
document.addEventListener('click', function (e) {
  const target = e.target.closest('.confirm-delete');
  if (target) {
    const msg = target.getAttribute('data-msg') || 'Are you sure you want to delete this?';
    if (!confirm(msg)) {
      e.preventDefault();
    }
  }
});

// Auto-hide alerts after 4 seconds
document.addEventListener('DOMContentLoaded', function () {
  const alerts = document.querySelectorAll('.auto-alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity 0.5s ease';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 4000);
  });
});

// Quiz countdown timer
// Usage: startQuizTimer(minutes, 'timer-display-id', 'quiz-form-id')
function startQuizTimer(minutes, displayId, formId) {
  let totalSeconds = minutes * 60;
  const display = document.getElementById(displayId);
  if (!display) return;

  const interval = setInterval(function () {
    const m = Math.floor(totalSeconds / 60);
    const s = totalSeconds % 60;
    display.textContent = `${m}:${s < 10 ? '0' + s : s}`;

    if (totalSeconds <= 0) {
      clearInterval(interval);
      const form = document.getElementById(formId);
      if (form) {
        alert('Time is up! Submitting your quiz automatically.');
        form.submit();
      }
    }
    totalSeconds--;
  }, 1000);
}

// Live search filter for course listing (client side)
function filterCourses(inputId, cardClass) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.addEventListener('keyup', function () {
    const query = input.value.toLowerCase();
    document.querySelectorAll('.' + cardClass).forEach(function (card) {
      const text = card.innerText.toLowerCase();
      card.style.display = text.includes(query) ? '' : 'none';
    });
  });
}

// Preview profile image before upload
function previewImage(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (!input || !preview) return;
  input.addEventListener('change', function () {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => (preview.src = e.target.result);
      reader.readAsDataURL(input.files[0]);
    }
  });
}
