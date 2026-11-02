<?php

namespace App\Http\Controllers;

use App\Http\Middleware\KeepSignedIn;
use App\Models\AuditLog;
use App\Repositories\AuthRepo;
use App\Support\AuditTrail;
use App\Support\PasswordHasher;
use App\Support\SessionAuth;
use App\Support\SessionKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/** Port dari Controllers/AuthController.cs. */
class AuthController extends Controller
{
    private const INVALID_LOGIN_MESSAGE = 'Username atau password salah.';

    public function __construct(private AuthRepo $authRepo)
    {
    }

    public function login(Request $request)
    {
        if (SessionAuth::isLoggedIn()) {
            return redirect('/');
        }

        return view('auth.login', [
            'returnUrl' => $request->query('returnUrl', ''),
        ]);
    }

    public function validateLogin(Request $request)
    {
        try {
            $username = trim((string) $request->input('username', ''));
            $password = (string) $request->input('password', '');

            if ($username === '' || $password === '') {
                return response()->json(['status' => false, 'message' => 'Username dan password wajib diisi.']);
            }

            $user = $this->authRepo->getUserByUsername($username);
            if ($user === null || !$user->IsActive) {
                Log::warning("Login failed for {$username}: user not found or inactive");
                AuditTrail::log(AuditLog::ActionLoginFailed, 'Login', null, $username, 'Login gagal: user tidak ditemukan atau nonaktif', username: $username);

                return response()->json(['status' => false, 'message' => self::INVALID_LOGIN_MESSAGE]);
            }

            // Active Directory belum didukung di fase ini - hanya akun lokal.
            if ($user->IsAdUser) {
                return response()->json(['status' => false, 'message' => 'Login Active Directory belum tersedia.']);
            }

            if (!PasswordHasher::verify($password, $user->PasswordHash)) {
                Log::warning("Login failed for {$username}: invalid password");
                AuditTrail::log(AuditLog::ActionLoginFailed, 'Login', (string) $user->UserId, $username, 'Login gagal: password salah', username: $username);

                return response()->json(['status' => false, 'message' => self::INVALID_LOGIN_MESSAGE]);
            }

            $menus = $this->authRepo->getMenuByRole((int) $user->RoleId);
            $permissions = $this->authRepo->getPermissionByRole((int) $user->RoleId);
            SessionAuth::signIn($user, $menus, $permissions);
            $this->authRepo->updateLastLogin((int) $user->UserId, $user->Username);
            AuditTrail::log(AuditLog::ActionLogin, 'Login', (string) $user->UserId, $user->Username, 'Login');

            $returnUrl = (string) $request->input('returnUrl', '');
            $redirectUrl = $returnUrl !== '' && str_starts_with($returnUrl, '/') ? $returnUrl : '/';

            // "Keep me signed in": session bertahan KEEP_MINUTES (lihat KeepSignedIn), selain itu SESSION_LIFETIME.
            if ($request->boolean('keepSignedIn')) {
                config(['session.lifetime' => KeepSignedIn::KEEP_MINUTES]);
                Cookie::queue(KeepSignedIn::KEEP_COOKIE, '1', KeepSignedIn::KEEP_MINUTES);
            } else {
                Cookie::queue(Cookie::forget(KeepSignedIn::KEEP_COOKIE));
            }

            return response()->json(['status' => true, 'message' => 'Login berhasil!', 'data' => $redirectUrl]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            if (session(SessionKeys::IsAdUser) === '1') {
                return response()->json(['status' => false, 'message' => 'User Active Directory mengganti password melalui Windows.']);
            }

            $newPassword = (string) $request->input('newPassword', '');
            if (strlen($newPassword) < 8) {
                return response()->json(['status' => false, 'message' => 'Password baru minimal 8 karakter.']);
            }

            $user = $this->authRepo->getUserByUsername($this->loginUsername());
            if ($user === null || !PasswordHasher::verify((string) $request->input('currentPassword', ''), $user->PasswordHash)) {
                return response()->json(['status' => false, 'message' => 'Password saat ini salah.']);
            }

            $result = $this->authRepo->changePassword((int) $user->UserId, PasswordHasher::hash($newPassword), $this->loginUsername());

            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }

    public function logout()
    {
        if (session()->has(SessionKeys::Username)) {
            AuditTrail::log(AuditLog::ActionLogout, 'Login', (string) session(SessionKeys::UserId), $this->loginUsername(), 'Logout');
        }
        session()->flush();
        Cookie::queue(Cookie::forget(KeepSignedIn::KEEP_COOKIE));

        return redirect()->route('auth.login');
    }
}
