<?php

/**
 * Company Model - Representa uma empresa no sistema
 * 
 * Sistema multiempresa:
 * - Cada empresa é isolada (usuários e tarefas pertencem a uma empresa)
 * - Empresas são identificadas por 'slug' (nome normalizado para URL)
 * - Cada empresa pode ter apenas um administrador
 * 
 * Relacionamentos:
 * - hasMany User (usuários da empresa)
 * - hasMany Task (tarefas da empresa)
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    /**
     * Campos que podem ser preenchidos em massa
     */
    protected $fillable = [
        'name',  // Nome da empresa
        'slug',  // Nome normalizado para URL (ex: "Minha Empresa" -> "minha-empresa")
    ];

    /**
     * Relacionamentos Eloquent
     */
    
    // Usuários da empresa
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    // Tarefas da empresa
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
