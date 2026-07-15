<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\LoginThrottle;

final class AuthController
{
    public function show(): string { if (auth()) return redirect('/admin')->send(); return view('auth.login'); }
    public function login(Request $request): Response
    {
        verify_csrf();
        $email = strtolower(trim((string) $request->get('email')));
        $throttle = new LoginThrottle();
        if ($throttle->blocked($email)) { flash('error','Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.'); return redirect('/login'); }
        $stmt = db()->prepare("SELECT u.*,r.name role FROM users u JOIN roles r ON r.id=u.role_id WHERE email=? AND u.status='active' LIMIT 1");
        $stmt->execute([$email]); $user=$stmt->fetch();
        if (!$user || !password_verify((string)$request->get('password'),$user['password_hash'])) { $throttle->failed($email); flash('error','Email atau password salah.'); return redirect('/login'); }
        $throttle->clear($email);
        session_regenerate_id(true); unset($user['password_hash']); $_SESSION['user']=$user;
        db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$user['id']]); return redirect('/admin');
    }
    public function logout(): Response { verify_csrf(); $_SESSION=[]; session_regenerate_id(true); return redirect('/login'); }
}
