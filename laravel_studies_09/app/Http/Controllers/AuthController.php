<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(){
        return view('auth.login');
    }

    public function authenticate(Request $request){
        // Validação do formulário
        $credentials = $request->validate(
            [
                'username' => 'required|min:3|max:30',
                'password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                
            ],
            [
                'username.required' => 'O usuário é obrigatório',
                'username.min' => 'O usuário deve ter no mínimo :min caracteres',
                'username.max' => 'O usuário deve ter no máximo :max caracteres',
                
                'password.required' => 'A senha é obrigatória',
                'password.min' => 'A senha deve conter no mínimo :min caracteres',
                'password.max' => 'A senha deve conter no máximo :max caracteres',
                'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número'

            ]
        );
        // Verificar se o user existe
        
        $user = User::where('username', $credentials['username'])->
            where('active', true)-> 
            where(function($query){
                $query->whereNull('blocked_until')
                ->orWhere('blocked_until', '<=', now());
            })->whereNotNull('email_verified_at')->
            whereNull('deleted_at')->first();


        // Verifica se o user existe
        if(!$user){
            return back()->withInput()->with([
                'invalid_login' => 'Login Inválido'
            ]);
        }
        
        // Verificar se a senha é valida

        if(!password_verify($credentials['password'], $user->password)){
            return back()->withInput()->with([
                'invalid_login' => 'Login Inválido'
            ]);
        };


        // Atualizar o last_login

        $user->last_login_at = now();
        $user->blocked_until = null;
        $user->save;

        // Logar
        $request->session()->regenerate();
        Auth::login($user);
        
        // Redirecionar
        return redirect()->intended(route('home'));
    }


    public function logout(){
        Auth::logout();
        return redirect()->route('login');
    }

    public function home(){
        return view('home');
    }
}
