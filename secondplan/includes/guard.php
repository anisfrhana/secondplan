<?php
function require_auth(): void {
if (empty($_SESSION['user_id'])) {
header('Location: /auth/login.php');
exit;
}
}
function require_admin(): void {
require_auth();
if (($_SESSION['role'] ?? '') !== 'admin') {
http_response_code(403);
echo 'Forbidden: Admins only.';
exit;
}
}
function require_member(): void {
require_auth();
if (($_SESSION['role'] ?? '') !== 'member') {
http_response_code(403);
echo 'Forbidden: Members only.';
exit;
}
}