<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        //VALIDASI EMAIL DAN PASSWORD YANG DIKIRIMKAN
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
            'password' => 'required'
        ]);

        //ADA 3 VALUE YANG DIKIRIMKAN, YAKNI: EMAIL, PASSWORD DAN REMEMBER_ME
        //AMBIL SEMUA REQUEST TERSEBUT KECUALI REMEMBER ME KARENA YANG DIBUTUHKAN 
        //UNTUK OTENTIKASI ADALAH EMAIL DAN PASSWORD
        $auth = $request->except(['remember_me']);
        
        //MELAKUKAN PROSES OTENTIKASI
        if (auth()->attempt($auth, $request->remember_me)) {
            //APABILA BERHASIL, GENERATE API_TOKEN MENGGUNAKAN STRING RANDOM
            auth()->user()->update(['api_token' => Str::random(40)]);
            //KEMUDIAN KIRIM RESPONSENYA KE CLIENT UNTUK DIPROSES LEBIH LANJUT
            return response()->json(['status' => 'success', 'data' => auth()->user()->api_token], 200);
        }
        //APABILA GAGAL, KIRIM RESPONSE LAGI KE BAHWA PERMINTAAN GAGAL
        return response()->json(['status' => 'failed']);
    }
}