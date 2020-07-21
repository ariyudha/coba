<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use Illuminate\Http\Request;
use App\User;
use DB;
use File;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['masjid'])->orderBy('created_at', 'DESC')->operator();
        if (request()->q != '') {
            $users = $users->where('name', 'LIKE', '%' . request()->q . '%');
        }
        $users = $users->paginate(10);
        return new UserCollection($users);
    }
    public function store(Request $request)
    {
        //VALIDASI
        $this->validate($request, [
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|string',
            'masjid_id' => 'required|exists:masjids,id',
            'photo' => 'required|image'
        ]);

        DB::beginTransaction();
        try {
            $name = NULL;
            //APABILA ADA FILE YANG DIKIRIMKAN
            if ($request->hasFile('photo')) {
                //MAKA FILE TERSEBUT AKAN DISIMPAN KE STORAGE/APP/PUBLIC/operatorS
                $file = $request->file('photo');
                $name = $request->email . '-' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/operators', $name);
            }
            //BUAT DATA BARUNYA KE DATABASE
            User::create([ //MODIFIKASI BAGIAN INI DENGAN MEMASUKKANYA KE DALAM VARIABLE $USER
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => $request->role,
                'photo' => $name,
                'masjid_id' => $request->masjid_id,
                'role' => 3
            ]);
            $user->assignRole('operator'); //TAMBAHKAN BAGIAN UNTUK MENAMBAHKAN ROLE COURIER
            DB::commit();
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'data' => $e->getMessage()], 200);
        }
    }
    public function edit($id)
    {
        $user = User::find($id); //MENGAMBIL DATA BERDASARKAN ID
        return response()->json(['status' => 'success', 'data' => $user], 200);
    }

    public function update(Request $request, $id)
    {
        //VALIDASI DATA
        $this->validate($request, [
            'name' => 'required|string|max:150',
            'email' => 'required|email',
            'password' => 'nullable|min:6|string',
            'masjid_id' => 'required|exists:masjids,id',
            'photo' => 'nullable|image'
        ]);

        try {
            $user = User::find($id); //MENGAMBIL DATA YANG AKAN DI UBAH
            //JIKA FORM PASSWORD TIDAK DI KOSONGKAN, MAKA PASSWORD AKAN DIPERBAHARUI
            $password = $request->password != '' ? bcrypt($request->password):$user->password;
            $filename = $user->photo; //NAMA FILE FOTO SEBELUMNYA

            //JIKA ADA FILE BARU YANG DIKIRIMKAN
            if ($request->hasFile('photo')) {
                //MAKA FOTO YANG LAMA AKAN DIGANTI
                $file = $request->file('photo');
                //DAN FILE FOTO YANG LAMA AKAN DIHAPUS
                File::delete(storage_path('app/public/operators/' . $filename));
                $filename = $request->email . '-' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/operators', $filename);
            }
            
            //PERBAHARUI DATA YANG ADA DI DATABASE
            $user->update([
                'name' => $request->name,
                'password' => $password,
                'photo' => $filename,
                'masjid_id' => $request->masjid_id
            ]);
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'data' => $e->getMessage()], 200);
        }
    }
    public function destroy($id)
    {
        $user = User::find($id); //MENGAMBIL DATA YANG AKAN DIHAPUS
        File::delete(storage_path('app/public/operators/' . $user->photo)); //MENGHAPUS FILE FOTO
        $user->delete(); //MENGHAPUS DATANYA
        return response()->json(['status' => 'success']);
    }

    public function userLists()
    {
        $user = User::where('role', '!=', 3)->get();
        return new UserCollection($user);
    }

    public function getUserLogin()
    {
        $user = request()->user(); //MENGAMBIL USER YANG SEDANG LOGIN
        $permissions = [];
        foreach (Permission::all() as $permission) {
            //JIKA USER YANG SEDANG LOGIN PUNYA PERMISSION TERKAIT
            if (request()->user()->can($permission->name)) {
                $permissions[] = $permission->name; //MAKA PERMISSION TERSEBUT DITAMBAHKAN
            }
        }
        $user['permission'] = $permissions; //PERMISSION YANG DIMILIKI DIMASUKKAN KE DALAM DATA USER.
        return response()->json(['status' => 'success', 'data' => $user]);
    }
}