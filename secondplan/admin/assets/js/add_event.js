
  const form = document.getElementById('eventForm');
  const preview = document.getElementById('preview');

  const map = {
    title: 'p-title',
    date: 'p-date',
    location: 'p-location',
    capacity: 'p-capacity',
    description: 'p-desc'
  };

  form.addEventListener('input', () => {
    preview.style.display = 'block';

    Object.keys(map).forEach(id => {
      const el = document.getElementById(id);
      const target = document.getElementById(map[id]);

      if (!el || !target) return;

      if (el.value) {
        target.textContent = el.value;
        target.parentElement.style.display = 'block';
      } else {
        target.parentElement.style.display = 'none';
      }
    });
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    alert('Event created (UI only)');
  });
