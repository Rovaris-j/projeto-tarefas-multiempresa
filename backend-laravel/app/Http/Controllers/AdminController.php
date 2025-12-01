<?php

/**
 * AdminController - Funcionalidades exclusivas para administradores
 * 
 * Este controller fornece endpoints para:
 * - Dashboard administrativo com estatísticas da empresa
 * - Visualização de todas as tarefas da empresa
 * - Gerenciamento de usuários da empresa
 * 
 * IMPORTANTE: Todas as rotas requerem autenticação E role 'admin'
 */
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Aplica middleware de autenticação em todas as rotas
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Dashboard administrativo - Estatísticas e visão geral da empresa
     * 
     * Retorna:
     * - Total de tarefas
     * - Tarefas agrupadas por prioridade e status
     * - Estatísticas de cada membro da equipe (completion rate, progresso médio)
     * - Próximas 5 tarefas com prazo definido
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard()
    {
        $user = auth('api')->user();
        $this->ensureAdmin($user);

        // Busca todas as tarefas da empresa com responsável
        $tasks = Task::with('assignee:id,name')
            ->where('company_id', $user->company_id)
            ->get();

        // Busca todos os usuários da empresa
        $users = User::where('company_id', $user->company_id)
            ->select('id', 'name')
            ->get();

        // Agrupa tarefas por prioridade e status para estatísticas
        $byPriority = $tasks->groupBy('priority')->map->count();
        $byStatus = $tasks->groupBy('status')->map->count();

        // Calcula estatísticas por membro da equipe
        $taskGroups = $tasks->groupBy('assigned_to');
        $team = $users->map(function ($member) use ($taskGroups) {
            $group = $taskGroups->get($member->id, collect());
            $total = $group->count();
            $completed = $group->where('status', 'concluida')->count();
            
            return [
                'user' => $member->only(['id', 'name']),
                'total' => $total,
                'completed' => $completed,
                'completion_rate' => $total ? round(($completed / $total) * 100) : 0,
                'avg_progress' => $total ? round($group->avg('progress')) : 0,
            ];
        })->values();

        // Próximas 5 tarefas com prazo definido (ordenadas por data)
        $upcoming = $tasks->filter(fn($task) => $task->due_date)
            ->sortBy('due_date')
            ->take(5)
            ->values();

        return response()->json([
            'total_tasks' => $tasks->count(),
            'by_priority' => $byPriority,
            'by_status' => $byStatus,
            'team' => $team,
            'upcoming' => $upcoming,
        ]);
    }

    /**
     * Lista todas as tarefas da empresa
     * Inclui informações do responsável e do criador
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function tasks()
    {
        $user = auth('api')->user();
        $this->ensureAdmin($user);

        $tasks = Task::with([
            'assignee:id,name,email',  // Responsável pela tarefa
            'user:id,name,email'        // Criador da tarefa
        ])
            ->where('company_id', $user->company_id)
            ->orderBy('due_date')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Lista todos os usuários da empresa
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function users()
    {
        $user = auth('api')->user();
        $this->ensureAdmin($user);

        $users = User::where('company_id', $user->company_id)
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * Cria novo usuário na empresa
     * Apenas administradores podem criar novos usuários
     * 
     * @param Request $request - { name, email, password, role? }
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeUser(Request $request)
    {
        $user = auth('api')->user();
        $this->ensureAdmin($user);

        // Valida dados do formulário
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'nullable|in:admin,member', // Role opcional, padrão: 'member'
        ]);

        // Cria novo usuário na mesma empresa
        $newUser = User::create([
            'company_id' => $user->company_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'] ?? 'member', // Padrão: membro comum
        ]);

        return response()->json($newUser, 201);
    }

    /**
     * Verifica se usuário tem permissões de administrador
     * Aborta requisição com erro 403 se não for admin
     * 
     * @param User $user
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function ensureAdmin($user): void
    {
        abort_if($user->role !== 'admin', 403, 'Acesso restrito a administradores.');
    }
}

