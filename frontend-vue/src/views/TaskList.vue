<template>
  <!-- Lista detalhada de tarefas -->
  <div class="tasks-shell">
    <!-- Cabeçalho com ações -->
    <header class="tasks-header">
      <div>
        <p class="eyebrow">Minhas tarefas</p>
        <h1>Detalhes das entregas</h1>
      </div>
      <div class="tasks-actions">
        <button class="ghost-btn" @click="load" :disabled="loading">
          {{ loading ? 'Atualizando...' : 'Atualizar' }}
        </button>
        <button class="primary-btn" @click="createTask()">+ Nova tarefa</button>
      </div>
    </header>

    <div v-if="error" class="info-banner error">{{ error }}</div>

    <!-- Resumo por prioridade -->
    <section class="priority-summary">
      <article v-for="item in prioritySummary" :key="item.key">
        <p>{{ item.label }}</p>
        <strong>{{ item.value }}</strong>
      </article>
    </section>

    <!-- Tabela de tarefas -->
    <section class="tasks-table">
      <div class="table-head">
        <span>Tarefa</span>
        <span>Status</span>
        <span>Prioridade</span>
        <span>Prazo</span>
        <span>Horas</span>
        <span>Progresso</span>
        <span>Ações</span>
      </div>

      <!-- Linhas da tabela com dados das tarefas -->
      <div
        v-for="task in visibleTasks"
        :key="task.id"
        class="table-row"
        :class="{ 'task-overdue': isOverdue(task) }"
      >
        <div class="task-title">
          <strong>
            {{ task.title }}
            <!-- Badge de urgente para tarefas atrasadas -->
            <span v-if="isOverdue(task)" class="urgent-badge">URGENTE</span>
          </strong>
          <small>{{ task.description || 'Sem descrição' }}</small>
        </div>
        <!-- Status com destaque para atrasadas -->
        <span class="status-pill" :class="[task.status, { overdue: isOverdue(task) }]">
          {{ isOverdue(task) ? 'Atrasada' : (statusLabels[task.status] || task.status) }}
        </span>
        <!-- Prioridade marcada como urgente se atrasada -->
        <span class="task-priority" :class="[task.priority, { urgent: isOverdue(task) }]">
          {{ isOverdue(task) ? 'Urgente' : task.priority }}
        </span>
        <!-- Data com destaque se atrasada -->
        <span :class="{ 'overdue-date': isOverdue(task) }">{{ formatDate(task.due_date) }}</span>
        <!-- Horas trabalhadas -->
        <span>{{ task.hours_worked || 0 }}h</span>
        <!-- Barra de progresso -->
        <div class="progress-inline">
          <div class="progress-track">
            <div class="progress-thumb" :style="{ width: (task.progress || 0) + '%' }"></div>
          </div>
          <small>{{ task.progress || 0 }}%</small>
        </div>
        <!-- Ações: editar e excluir -->
        <div class="table-actions">
          <router-link :to="`/tasks/${task.id}/edit`">Editar</router-link>
          <button @click="del(task.id)">Excluir</button>
        </div>
      </div>

      <p v-if="!visibleTasks.length" class="empty-state">Nenhuma tarefa encontrada.</p>
    </section>
  </div>
</template>

<script>
import '@/css/userprincipal.css';
import { mapState, mapActions } from 'vuex';

export default {
  data(){ return { error:null, loading:false }; },
  computed: {
    ...mapState(['tasks','taskSearch']),
    
    // Filtra tarefas: últimas 7 dias, atrasadas, ou inconcluídas desde o começo
    visibleTasks(){
      const now = new Date();
      const sevenDaysAgo = new Date(now);
      sevenDaysAgo.setDate(now.getDate() - 7);
      sevenDaysAgo.setHours(0, 0, 0, 0);
      
      // Filtra tarefas que devem aparecer
      let filtered = this.tasks.filter(task => {
        const status = task.status === 'concluido' ? 'concluida' : task.status;
        const taskDate = new Date(task.created_at || task.updated_at);
        taskDate.setHours(0, 0, 0, 0);
        
        // Inclui se:
        // 1. Está atrasada (não concluída e prazo vencido)
        if (this.isOverdue(task)) return true;
        
        // 2. Foi criada/atualizada nos últimos 7 dias
        if (taskDate >= sevenDaysAgo) return true;
        
        // 3. Está inconcluída desde o começo (não concluída e criada há mais de 7 dias)
        if (status !== 'concluida' && taskDate < sevenDaysAgo) return true;
        
        return false;
      });
      
      // Aplica busca se houver termo de pesquisa
      const term = (this.taskSearch || '').trim().toLowerCase();
      if(term) {
        filtered = filtered.filter(task => {
          const haystack = `${task.title || ''} ${task.description || ''}`.toLowerCase();
          return haystack.includes(term);
        });
      }
      
      return filtered;
    },
    
    // Resumo de tarefas por prioridade
    prioritySummary(){
      const base = [
        { key:'alta', label:'Alta prioridade' },
        { key:'media', label:'Prioridade média' },
        { key:'baixa', label:'Prioridade baixa' }
      ];
      return base.map(item => ({
        ...item,
        value: this.tasks.filter(task => task.priority === item.key).length
      }));
    },
    
    // Labels de status para exibição
    statusLabels(){
      return {
        pendente: 'Pendente',
        em_andamento: 'Em andamento',
        concluida: 'Concluída'
      };
    }
  },
  methods: {
    ...mapActions(['fetchTasks','deleteTask']),
    
    // Recarrega a lista de tarefas
    async load(){ 
      this.error = null;
      this.loading = true;
      try {
        await this.fetchTasks(); 
      } catch(err) {
        this.error = 'Erro ao carregar tarefas: ' + (err.response?.data?.message || err.message);
      } finally {
        this.loading = false;
      }
    },
    
    // Exclui uma tarefa após confirmação
    async del(id){ 
      if(confirm('Excluir esta tarefa?')) {
        try {
          await this.deleteTask(id);
          this.load();
        } catch(err) {
          this.error = 'Erro ao excluir tarefa: ' + (err.response?.data?.message || err.message);
        }
      }
    },
    
    // Navega para a página de criação de tarefa
    createTask(){
      this.$router.push({ path: '/tasks/create' });
    },
    
    // Verifica se uma tarefa está atrasada (prazo vencido e não concluída)
    isOverdue(task){
      if(!task.due_date) return false;
      const status = task.status === 'concluido' ? 'concluida' : task.status;
      if(status === 'concluida') return false;
      
      const dueDate = new Date(task.due_date);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      dueDate.setHours(0, 0, 0, 0);
      
      return dueDate < today;
    },
    
    // Formata data para exibição
    formatDate(dateStr){
      if(!dateStr) return 'Sem data';
      const d = new Date(dateStr);
      return d.toLocaleDateString();
    }
  },
  mounted(){ this.load(); }
}
</script>
