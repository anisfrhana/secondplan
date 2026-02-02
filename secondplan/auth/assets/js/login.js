
document.getElementById('loginForm').addEventListener('submit', async e => {
  e.preventDefault();

  const btn = document.getElementById('loginBtn');
  const errorBox = document.getElementById('errorBox');
  const roleBadge = document.getElementById('roleBadge');

  btn.classList.add('loading');
  btn.textContent = 'Signing in...';
  errorBox.style.display = 'none';
  roleBadge.style.display = 'none';

  const res = await fetch('../auth/login.php', {
    method: 'POST',
    headers: { 'Accept': 'application/json' },
    body: new FormData(e.target)
  });

  const data = await res.json();

  btn.classList.remove('loading');
  btn.textContent = 'Sign In';

  if (!data.success) {
    errorBox.textContent = data.message || 'Login failed';
    errorBox.style.display = 'block';
    return;
  }

  /* Role badge */
  const role = (data.role || 'client').toLowerCase();
  roleBadge.textContent = role === 'admin' ? 'Admin Access' : 'Client Access';
  roleBadge.className = 'role-badge ' + (role === 'admin' ? 'role-admin' : 'role-client');
  roleBadge.style.display = 'block';

  /* Redirect after short delay */
  setTimeout(() => {
    window.location.href = data.redirect || '/index.php';
  }, 900);
});

