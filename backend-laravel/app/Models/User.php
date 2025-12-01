<?php

/**
 * User Model - Representa um usuário do sistema
 * 
 * Relacionamentos:
 * - belongsTo Company (usuário pertence a uma empresa)
 * - hasMany Task (tarefas criadas pelo usuário)
 * - hasMany assignedTasks (tarefas atribuídas ao usuário)
 * 
 * Roles:
 * - 'admin': Administrador da empresa (pode ver todas as tarefas, criar usuários)
 * - 'member': Membro comum (só vê suas próprias tarefas)
 */
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    
    /**
     * Métodos necessários para JWT Authentication
     * getJWTIdentifier: retorna a chave primária do usuário
     * getJWTCustomClaims: retorna claims customizados (vazio por padrão)
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Relacionamentos Eloquent
     */
    
    // Usuário pertence a uma empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    // Tarefas criadas por este usuário
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    
    // Tarefas atribuídas a este usuário (como responsável)
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Campos que podem ser preenchidos em massa (Mass Assignment)
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role', // 'admin' ou 'member'
    ];

    /**
     * Campos que devem ser ocultos na serialização (não aparecem em JSON)
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
