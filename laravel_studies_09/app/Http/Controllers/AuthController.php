<?php

namespace App\Http\Controllers;

use App\Mail\NewUserConfirmation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;


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
                ->orWhere('blocked_until', '<=', Carbon::now());
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

        $user->last_login_at = Carbon::now();
        $user->blocked_until = null;
        $user->save();

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

    public function register(){
        return view('auth.register');
    }

    public function  store_user(Request $request){
        // dd($request);

        // Form Validation
        $request->validate(
            [
                'username' => 'required|min:4|max:30|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'password_confirmation' => 'required|same:password'
                
            ],
            [

                'username.required' => 'O usuário é obrigatório',
                'username.min' => 'O usuário deve ter no mínimo :min caracteres',
                'username.max' => 'O usuário deve ter no máximo :max caracteres',

                'email.required' => 'O email é obrigatório',
                'email.email' => 'O email não é válido',

                'password.required' => 'A senha é obrigatória',
                'password.min' => 'A senha deve conter no mínimo :min caracteres',
                'password.max' => 'A senha deve conter no máximo :max caracteres',
                'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',
                
                'password_confirmation.required' => 'A confirmação da senha é obrigatória',
                'password_confirmation.same' => 'A confirmação da senha não é igual à senha',
                
            ]
        );

        // Criando um novo usuário definindo um token de verificação de email
        $user = new User();

        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        
        // Criando token
        $user->token = Str::random(64);
        
        // Verificando dados do user
        // dd($user);

        // Gerar link
        $link_confirm = route('new_user_confirmation', ['token' => $user->token]);
        
        // Enviar Email
        $result = Mail::to($user->email)->send(new NewUserConfirmation($user->username, $link_confirm));

        // Verificar se o envio foi com sucesso
        if(!$result){
            return back()->withInput()->with(
                [
                    'server_error' => 'Ocorreu um erro ao enviar o email de confirmação!'
                    
                ]
                );
        }

        // Salvar User
        $user->save();

        // Apresentar view de sucesso

        return view('auth.email_sent', ['email' => $user->email]);

        
        
    }

    public function home(){
        return view('home');
    }


    public function new_user_confirmation($token){
        
        // Verificar se o token é válido
        $user = User::where('token', $token)->first();
        if(!$user){
            return redirect()->route('login');
        }

        // Confirmar o registro do usuário
        $user->email_verified_at = Carbon::now();
        $user->token = null;
        $user->active = true;
        $user->last_login_at = Carbon::now();
        $user->save();

        // Autenticação automática do usuário confirmado
        Auth::login($user);

        

        // Apresenta uma mensagem de sucesso

        return view('auth.new_user_confirmation');
    }

    public function profile(){
        return view('auth.profile');
    }

    public function change_password(Request $request){
        
        $request->validate(
            [
                // 'username' => 'required|min:4|max:30|unique:users,username',
                // 'email' => 'required|email|unique:users,email',
                'current_password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',

                'new_password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|different:current_password',
                
                'new_password_confirmation' => 'required|same:new_password'
                // 'new_password_confirmation'=> 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                
                
                
            ],
            [           

                'current_password.required' => 'A senha atual é obrigatória',
                'current_password.required' => 'A senha atual é obrigatória',
                'current_password.required' => 'A senha atual é obrigatória',
                'current_password.required' => 'A senha atual é obrigatória',
               
                'new_password.required' => 'A senha nova é obrigatória',
                'new_password.different' => 'A senha nova deve ser diferente da atual',
                'new_password.min' => 'A senha deve conter no mínimo :min caracteres',
                'new_password.max' => 'A senha deve conter no máximo :max caracteres',
                'new_password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',
                
                'new_password_confirmation.required' => 'A confirmação da senha é obrigatória',
                'new_password_confirmation.same' => 'A confirmação da senha não é igual à senha',
                
            ]
        );

        // dd($request);

        // Validar a senha atual
        if(!password_verify($request->current_password, Auth::user()->password)){
            return back()->with([
                'server_error' => 'A senha atual não está correta'
            ]);
        }

        // dd('Senha OK');


        // Atualizando a senha

        $user = Auth::user();

        $user->password = bcrypt($request->new_password);

        $user->save();

        // Atualizar a senha da sessão
        Auth::user()->password = $request->new_password;

        return redirect()->route('profile')->with([
            'success' => 'A senha foi alterada com sucesso!'
        ]);



    }
}
