<?php

namespace App\Http\Controllers;

use App\Repositories\SchoolRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Profil sekolah (satu form) + logo. */
class SchoolController extends Controller
{
    public function __construct(private SchoolRepo $schoolRepo)
    {
    }

    public function index()
    {
        return view('school.index', [
            'school' => $this->schoolRepo->get(),
            'edit' => $this->hasPermission(PermissionCodes::SchoolEdit),
        ]);
    }

    public function save(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::SchoolEdit)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->schoolRepo->save($request->all(), $this->loginUsername()));
    }

    public function uploadLogo(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::SchoolEdit)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->schoolRepo->saveLogo($request->file('file'), $this->loginUsername()));
    }

    /** Logo untuk ditampilkan di halaman (cukup login). */
    public function logo()
    {
        $file = SchoolRepo::logoFile();

        return $file === null ? response()->file(public_path('images/logo.svg')) : response()->file($file);
    }
}
