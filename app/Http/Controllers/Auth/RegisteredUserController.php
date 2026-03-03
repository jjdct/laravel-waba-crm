<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminWelcomeMail;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        // Revisamos si ya existe al menos un usuario en la tabla
        $adminExists = \App\Models\User::count() > 0;

        return Inertia::render('Auth/Register', [
            'adminExists' => $adminExists
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    /**
    * Handle an incoming registration request.
    */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 1. Creamos al usuario con el rol de admin forzado
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin', // Indispensable para que MariaDB no se enoje
        ]);

        event(new Registered($user));

        // 2. Enviamos el correo de felicitaciones por la instalación
        try {
            Mail::to($user->email)->send(new AdminWelcomeMail($user));
        } catch (\Exception $e) {
            // Si falla el correo, seguimos adelante para no trabar el login
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
