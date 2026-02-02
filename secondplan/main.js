// =========================
// MOBILE NAV TOGGLE
// =========================
const navToggle = document.getElementById("navToggle");
const navMenu = document.getElementById("navMenu");

navToggle.addEventListener("click", () => {
  navMenu.classList.toggle("active");
});

// =========================
// FAKE LOADING (DEMO)
// =========================
document.addEventListener("DOMContentLoaded", () => {
  const eventsGrid = document.getElementById("eventsGrid");
  const merchGrid = document.getElementById("merchGrid");

  setTimeout(() => {
    if (eventsGrid) {
      eventsGrid.innerHTML = `
        <div class="feature-card">
          <h3>Live Band Night</h3>
          <p>📍 Kuala Lumpur</p>
          <p>📅 15 March 2026</p>
        </div>
        <div class="feature-card">
          <h3>Acoustic Session</h3>
          <p>📍 Shah Alam</p>
          <p>📅 2 April 2026</p>
        </div>
      `;
    }

    if (merchGrid) {
      merchGrid.innerHTML = `
        <div class="feature-card">
          <h3>Band T-Shirt</h3>
          <p>RM 59.00</p>
        </div>
        <div class="feature-card">
          <h3>Cap Edition</h3>
          <p>RM 39.00</p>
        </div>
      `;
    }
  }, 1200);
});
