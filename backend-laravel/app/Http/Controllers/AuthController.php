<?php

/**
 * AuthController - Gerencia autenticação e registro de usuários
 * 
 * Este controller lida com:
 * - Registro de nova empresa e primeiro usuário (admin)
 * - Login de usuários existentes
 * - Obtenção de dados do usuário autenticado
 * 
 * Sistema de multiempresa:
 * - Cada empresa pode ter apenas um administrador
 * - O primeiro usuário registrado para uma empresa se torna admin
 * - Empresas são identificadas por slug (nome abreviado)
 */
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Registra nova empresa e cria primeiro usuário (admin)
     * 
     * Fluxo:
     * 1. Valida dados do formulário
     * 2. Cria ou encontra empresa pelo slug
     * 3. Verifica se empresa já tem admin (evita múltiplos admins)
     * 4. Cria usuário com role 'admin'
     * 5. Retorna token JWT, dados do usuário e empresa
     * 
     * @param Request $request - { name, email, password, company_name }
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Valida dados do formulário
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'company_name' => 'required'
        ]);

        // Cria slug da empresa (nome normalizado para URL)
        $slug = Str::slug($data['company_name']);
        $company = Company::where('slug', $slug)->first();

        // Verifica se empresa já existe e tem administrador
        if ($company && $company->users()->where('role', 'admin')->exists()) {
            return response()->json([
                'error' => 'Esta empresa já possui um administrador. Solicite acesso ao responsável.'
            ], 422);
        }

        // Cria empresa se não existir
        if (!$company) {
            $company = Company::create([
                'name' => $data['company_name'],
                'slug' => $slug,
            ]);
        }

        // Cria primeiro usuário como administrador
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'company_id' => $company->id,
            'role' => 'admin', // Primeiro usuário sempre é admin
        ]);

        // Gera token JWT para autenticação automática após registro
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' => $token,
            'user' => $user,
            'company' => $company
        ]);
    }

    /**
     * Autentica usuário existente
     * 
     * @param Request $request - { email, password }
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        
        // Tenta autenticar e gerar token JWT
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        // Retorna token, dados do usuário e empresa
        $user = JWTAuth::user();
        return response()->json([
            'token' => $token,
            'user' => $user,
            'company' => $user->company
        ]);
    }

    /**
     * Retorna dados do usuário autenticado
     * Útil para verificar se token ainda é válido
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json($user);
    }
}
