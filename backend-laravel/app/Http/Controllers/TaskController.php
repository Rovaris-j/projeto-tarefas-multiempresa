<?php

/**
 * TaskController - Gerencia operações CRUD de tarefas
 * 
 * Este controller lida com todas as operações relacionadas a tarefas:
 * - Listagem (com filtros por status e prioridade)
 * - Criação (com validação de permissões)
 * - Visualização (com verificação de acesso)
 * - Atualização (com validação de permissões)
 * - Exclusão (com verificação de acesso)
 * 
 * Regras de acesso:
 * - Admins podem ver/editar todas as tarefas da empresa
 * - Usuários comuns só podem ver/editar suas próprias tarefas
 */
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Aplica middleware de autenticação em todas as rotas
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Lista tarefas do usuário com filtros opcionais
     * 
     * @param Request $req - Pode conter 'status' e 'priority' como filtros
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $req)
    {
        $user = auth('api')->user();

        // Base query: tarefas da empresa com relacionamento assignee
        $query = Task::with(['assignee:id,name,email'])
            ->where('company_id', $user->company_id);

        // Usuários comuns só veem suas próprias tarefas
        if ($user->role !== 'admin') {
            $query->where('assigned_to', $user->id);
        }

        // Aplica filtros opcionais
        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }
        if ($req->filled('priority')) {
            $query->where('priority', $req->priority);
        }

        $tasks = $query->orderBy('due_date')->get();
        return response()->json($tasks);
    }

    /**
     * Cria nova tarefa
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $req)
    {
        $user = auth('api')->user();
        
        // Valida dados usando método centralizado
        $data = $req->validate($this->getValidationRules());

        // Define responsável (padrão: usuário atual)
        $assignedTo = $data['assigned_to'] ?? $user->id;

        // Valida permissão para atribuir a outro usuário
        if (!$this->canAssignTo($user, $assignedTo)) {
            return response()->json([
                'error' => 'Apenas administradores podem atribuir tarefas a outros usuários.'
            ], 403);
        }

        // Valida se o responsável pertence à mesma empresa
        $assignee = $this->validateAssignee($user, $assignedTo);
        if (!$assignee) {
            return response()->json(['error' => 'Responsável inválido.'], 422);
        }

        // Cria tarefa com dados validados
        $task = Task::create(array_merge($data, [
            'company_id' => $user->company_id,
            'user_id' => $user->id, // Usuário que criou
            'assigned_to' => $assignee->id, // Usuário responsável
            'progress' => $data['progress'] ?? 0,
        ]));

        return response()->json($task->load('assignee:id,name,email'), 201);
    }

    /**
     * Exibe detalhes de uma tarefa específica
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = auth('api')->user();
        $task = Task::with('assignee:id,name,email')
            ->where('company_id', $user->company_id)
            ->findOrFail($id);

        // Verifica permissão de acesso
        if (!$this->canAccessTask($user, $task)) {
            return response()->json(['error' => 'Sem permissão para visualizar esta tarefa.'], 403);
        }

        return response()->json($task);
    }

    /**
     * Atualiza tarefa existente
     * 
     * @param Request $req
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $req, $id)
    {
        $user = auth('api')->user();
        $task = Task::where('company_id', $user->company_id)->findOrFail($id);

        // Verifica permissão de acesso
        if (!$this->canAccessTask($user, $task)) {
            return response()->json(['error' => 'Sem permissão para atualizar esta tarefa.'], 403);
        }

        // Valida dados usando método centralizado
        $data = $req->validate($this->getValidationRules());

        // Se está reatribuindo a tarefa, valida permissões
        if (isset($data['assigned_to'])) {
            if (!$this->canAssignTo($user, $data['assigned_to'])) {
                return response()->json([
                    'error' => 'Apenas administradores podem reatribuir tarefas.'
                ], 403);
            }

            $assignee = $this->validateAssignee($user, $data['assigned_to']);
            if (!$assignee) {
                return response()->json(['error' => 'Responsável inválido.'], 422);
            }
            $data['assigned_to'] = $assignee->id;
        }

        $task->update($data);
        return response()->json($task->fresh('assignee:id,name,email'));
    }

    /**
     * Remove tarefa
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = auth('api')->user();
        $task = Task::where('company_id', $user->company_id)->findOrFail($id);

        // Verifica permissão de acesso
        if (!$this->canAccessTask($user, $task)) {
            return response()->json(['error' => 'Sem permissão para excluir esta tarefa.'], 403);
        }

        $task->delete();
        return response()->json(null, 204);
    }

    /**
     * Retorna regras de validação centralizadas
     * Evita duplicação de código entre store() e update()
     * 
     * @return array
     */
    protected function getValidationRules(): array
    {
        return [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'in:pendente,em_andamento,concluida',
            'priority' => 'in:baixa,media,alta',
            'due_date' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'hours_worked' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Verifica se usuário pode atribuir tarefa a outro usuário
     * Apenas admins podem atribuir a outros usuários
     * 
     * @param User $user - Usuário atual
     * @param int $assignedToId - ID do usuário que receberá a tarefa
     * @return bool
     */
    protected function canAssignTo(User $user, int $assignedToId): bool
    {
        // Se está atribuindo a si mesmo, sempre permitido
        if ($assignedToId === $user->id) {
            return true;
        }
        
        // Apenas admins podem atribuir a outros
        return $user->role === 'admin';
    }

    /**
     * Valida se o responsável pertence à mesma empresa
     * 
     * @param User $user - Usuário atual
     * @param int $assignedToId - ID do responsável
     * @return User|null - Retorna o User se válido, null caso contrário
     */
    protected function validateAssignee(User $user, int $assignedToId): ?User
    {
        return User::where('company_id', $user->company_id)
            ->find($assignedToId);
    }

    /**
     * Verifica se usuário tem permissão para acessar uma tarefa
     * 
     * @param User $user - Usuário atual
     * @param Task $task - Tarefa a ser verificada
     * @return bool
     */
    protected function canAccessTask(User $user, Task $task): bool
    {
        // Admins podem acessar todas as tarefas da empresa
        if ($user->role === 'admin') {
            return true;
        }

        // Usuários comuns só podem acessar suas próprias tarefas
        return $task->assigned_to === $user->id;
    }
}