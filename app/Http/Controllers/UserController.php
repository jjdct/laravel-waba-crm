<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\WelcomeUserMail; // <-- 1. Importamos tu correo verde
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail; // <-- 2. Importamos el Facade de correos
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Muestra la pantalla de Gestión de Usuarios y carga la lista.
     */
    public function index(Request $request)
    {
        // 1. Bloqueamos el paso a usuarios comunes
        if ($request->user()->role !== 'admin') {
            abort(403);
        }

        // 2. Traemos todos los usuarios de la base de datos (los más nuevos primero)
        $users = User::orderBy('id', 'desc')->get();

        // 3. Renderizamos la vista de Vue y le pasamos los datos
        return Inertia::render('Admin/Users', [
            'users' => $users
        ]);
    }

    /**
     * Guarda un nuevo usuario en la base de datos.
     */
    public function store(Request $request)
    {
        // 1. Bloqueamos el paso a usuarios comunes
        if ($request->user()->role !== 'admin') {
            abort(403);
        }

        // 2. Validamos que llenen bien el formulario
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', Rules\Password::defaults()],
            'role' => 'required|in:admin,agente',
        ]);

        // 3. Creamos al usuario y lo guardamos en la variable $user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Encriptamos la contraseña
            'role' => $request->role,
        ]);

        // 4. ¡Disparamos el correo de bienvenida!
        try {
            // Le mandamos el usuario y la contraseña tal como la escribió el admin
            Mail::to($user->email)->send(new WelcomeUserMail($user, $request->password));
        } catch (\Exception $e) {
            // Si Mailpit falla o no hay internet, lo ignoramos. El usuario ya se creó.
        }

        // 5. Regresamos a la vista anterior (la lista se actualizará sola)
        return back()->with('success', 'Usuario creado exitosamente.');
    }
}