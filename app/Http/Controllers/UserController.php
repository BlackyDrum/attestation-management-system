<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $search = $request->input('search') ?? "";

        $user = User::query()->where('name', 'ILIKE', '%' . $search . '%')->orderByRaw("SPLIT_PART(name,' ', -1)")->paginate(20);

        if (!empty($request->input('response')) && $request->input('response'))
        {
            return response()->json($user);
        }

        return Inertia::render('User', [
            'users' => $user,
            'search' => $search
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'userid' => 'required',
        ]);

        $userid = $request->input('userid');

        $count = User::query()->where('id','=',$userid)->where('admin','=','false')->delete();

        if ($count === 0)
        {
            return response()->json(['success' => false, 'message' => 'User ID '.$userid.' not found']);
        }

        return response()->json(['success' => true,'userid' => $userid]);
    }
}
