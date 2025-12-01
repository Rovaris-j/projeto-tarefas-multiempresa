<?php

/**
 * Task Model - Representa uma tarefa no sistema
 * 
 * Relacionamentos:
 * - belongsTo Company (tarefa pertence a uma empresa)
 * - belongsTo User (usuário que criou a tarefa)
 * - belongsTo assignee (usuário responsável pela tarefa)
 * 
 * Status possíveis:
 * - 'pendente': Tarefa ainda não iniciada
 * - 'em_andamento': Tarefa em execução
 * - 'concluida': Tarefa finalizada
 * 
 * Prioridades possíveis:
 * - 'baixa': Prioridade baixa
 * - 'media': Prioridade média
 * - 'alta': Prioridade alta
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /**
     * Campos que podem ser preenchidos em massa (Mass Assignment)
     * Permite criar/atualizar tarefas usando create() ou update()
     */
    protected $fillable = [
        'company_id',      // ID da empresa (multiempresa)
        'user_id',        // ID do usuário que criou a tarefa
        'assigned_to',    // ID do usuário responsável pela tarefa
        'title',          // Título da tarefa
        'description',    // Descrição detalhada
        'status',         // Status: pendente, em_andamento, concluida
        'priority',       // Prioridade: baixa, media, alta
        'due_date',       // Data de vencimento
        'progress',       // Progresso (0-100%)
        'hours_worked',   // Horas trabalhadas na tarefa
    ];

    /**
     * Relacionamentos Eloquent
     */
    
    // Tarefa pertence a uma empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    // Usuário que criou a tarefa
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Usuário responsável pela tarefa (pode ser diferente do criador)
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}