<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserPageController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::ASISTENTE])
            ->orderBy('name')
            ->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', [
            'userModel' => new User(['role' => UserRole::ASISTENTE, 'is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        User::create($data);

        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $user)
    {
        return view('users.form', [
            'userModel' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        if ($data['password'] === null) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:4'],
            'role' => ['required', Rule::in([UserRole::ADMIN->value, UserRole::ASISTENTE->value])],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if (($data['password'] ?? '') === '') {
            $data['password'] = null;
        }

        return $data;
    }
}
