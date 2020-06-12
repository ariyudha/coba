<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\MasjidCollection;
use App\Masjid;

class MasjidController extends Controller
{
    public function index()
    {
        $masjids = Masjid::orderBy('created_at', 'DESC');
        if (request()->q != '') {
            $masjids = $masjids->where('name', 'LIKE', '%' . request()->q . '%');
        }
        return new MasjidCollection($masjids->paginate(10));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|unique:masjids,code',
            'name' => 'required|string|max:100',
            'address' => 'required|string',
            'phone' => 'required|max:13'
        ]);

        Masjid::create($request->all());
        return response()->json(['status' => 'success'], 200);
    }

    public function edit($id)
    {
        $masjid = Masjid::whereCode($id)->first();
        return response()->json(['status' => 'success', 'data' => $masjid], 200);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'code' => 'required|exists:masjids,code',
            'name' => 'required|string|max:100',
            'address' => 'required|string',
            'phone' => 'required|max:13'
        ]);

        $masjid = Masjid::whereCode($id)->first();
        $masjid->update($request->except('code'));
        return response()->json(['status' => 'success'], 200);
    }

    public function destroy($id)
    {
        $masjid = Masjid::find($id);
        $masjid->delete();
        return response()->json(['status' => 'success'], 200);
    }
}
