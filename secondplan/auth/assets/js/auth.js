function togglePasswordField(id) {
    var input = id ? document.getElementById(id) : document.getElementById('password');
    if (!input) return;
    var btn = input.parentElement.querySelector('.toggle-password');
    var eyeOpen = btn ? btn.querySelector('.eye-open') : null;
    var eyeClosed = btn ? btn.querySelector('.eye-closed') : null;
    if (input.type === 'password') {
        input.type = 'text';
        if (eyeOpen) eyeOpen.style.display = 'none';
        if (eyeClosed) eyeClosed.style.display = 'block';
    } else {
        input.type = 'password';
        if (eyeOpen) eyeOpen.style.display = 'block';
        if (eyeClosed) eyeClosed.style.display = 'none';
    }
}
