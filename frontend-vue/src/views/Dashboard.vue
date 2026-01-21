<template>
  <!-- Dashboard principal com visão geral das tarefas -->
  <div class="dashboard-shell">
    <!-- Cabeçalho do dashboard -->
    <header class="dashboard-header">
      <div>
        <p class="eyebrow">
          <span v-if="isAdmin">Visão geral da empresa<span v-if="company?.name">, {{ company.name }}</span></span>
          <span v-else>Visão geral de suas tarefas<span v-if="company?.name">, para a empresa {{ company.name }}</span></span>
        </p>
        <h1>Olá, {{ currentUserName }}</h1>
      </div>
      <button class="primary-btn" @click="$router.push('/tasks/create')">+ Nova tarefa</button>
    </header>

    <!-- Grid de resumo por status -->
    <section class="summary-grid">
      <article class="summary-card" v-for="card in statusSummary" :key="card.label">
        <p>{{ card.label }}</p>
        <strong>{{ card.value }}</strong>
        <span :class="['status-chip', card.key]">{{ card.caption }}</span>
      </article>
    </section>

    <!-- Área principal do dashboard -->
    <section class="dashboard-main">
      <!-- Card de prioridades -->
      <article class="dashboard-card priority-card">
        <div class="card-header">
          <h3>Prioridades</h3>
        </div>
        <ul>
          <li v-for="item in prioritySummary" :key="item.label">
            <span class="chip" :class="item.key">{{ item.label }}</span>
            <strong>{{ item.value }}</strong>
          </li>
        </ul>
      </article>

      <!-- Card de próximas entregas -->
      <article class="dashboard-card upcoming-card">
        <div class="card-header">
          <h3>Próximas entregas</h3>
        </div>
        <ul>
          <li v-for="task in upcomingTasks" :key="task.id">
            <div>
              <p>{{ task.title }}</p>
              <small>{{ formatDate(task.due_date) }}</small>
            </div>
            <span class="chip" :class="task.priority">{{ task.priority }}</span>
          </li>
          <li v-if="!upcomingTasks.length" class="empty-state">Nenhuma tarefa agendada.</li>
        </ul>
      </article>

      <!-- Card do calendário mensal -->
      <article class="dashboard-card calendar-card">
        <div class="card-header">
          <h3>Agenda mensal</h3>
          <!-- Controles de navegação do mês -->
          <div class="calendar-controls">
            <button @click="changeMonth(-1)">‹</button>
            <span>{{ monthLabel }}</span>
            <button @click="changeMonth(1)">›</button>
          </div>
        </div>
        <!-- Grid do calendário -->
        <div class="calendar-grid">
          <span v-for="day in weekDays" :key="day" class="calendar-weekday">{{ day }}</span>
          <button
            v-for="cell in calendarDays"
            :key="cell.key"
            :class="[
              'calendar-day',
              { 'is-current': cell.isCurrentMonth, 'is-today': cell.isToday, 'has-task': cell.tasks.length }
            ]"
            @click="selectDay(cell)"
          >
            <span>{{ cell.label }}</span>
            <!-- Indicadores de tarefas por prioridade -->
            <div v-if="cell.tasks.length" class="calendar-dots">
              <span
                v-for="(task, idx) in cell.tasks.slice(0, 3)"
                :key="idx"
                class="priority-dot"
                :class="task.priority"
              ></span>
            </div>
          </button>
        </div>

        <!-- Detalhes das tarefas do dia selecionado -->
        <div class="calendar-detail" v-if="selectedDayTasks.length">
          <h4>Tarefas em {{ selectedDayLabel }}</h4>
          <ul>
            <li v-for="task in selectedDayTasks" :key="task.id">
              {{ task.title }} — <span>{{ task.priority }}</span>
            </li>
          </ul>
        </div>
      </article>
    </section>
  </div>
</template>

<script>
import '@/css/userprincipal.css';
import { mapState, mapActions } from 'vuex';

export default {
  data() {
    const today = new Date();
    return {
      // Mês atual sendo exibido no calendário
      currentMonth: new Date(today.getFullYear(), today.getMonth(), 1),
      // Data selecionada no calendário
      selectedDate: today.toISOString().split('T')[0],
      // Dias da semana abreviados
      weekDays: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']
    };
  },
  computed: {
    ...mapState(['tasks', 'user', 'company', 'taskSearch']),
    
    // Tarefas filtradas pela busca global
    filteredTasks() {
      const term = (this.$store.state.taskSearch || '').trim().toLowerCase();
      console.log('Dashboard filteredTasks term:', term, 'tasks:', this.tasks.length);
      if (!term) return this.tasks;
      const filtered = this.tasks.filter(task => {
        const haystack = `${task.title || ''} ${task.description || ''}`.toLowerCase();
        return haystack.includes(term);
      });
      console.log('Filtered to:', filtered.length);
      return filtered;
    },
    
    // Nome do usuário atual (primeiro nome)
    currentUserName() {
      return this.user?.name?.split(' ')[0] || 'Equipe';
    },
    
    // Verifica se o usuário é administrador
    isAdmin() {
      return this.user?.role === 'admin';
    },
    
    // Resumo de tarefas por status
    statusSummary() {
      const base = [
        { key: 'pendente', label: 'Pendentes', caption: 'À iniciar' },
        { key: 'em_andamento', label: 'Em andamento', caption: 'Em progresso' },
        { key: 'concluida', label: 'Concluídas', caption: 'Finalizadas' }
      ];
      return base.map(item => ({
        ...item,
        value: this.filteredTasks.filter(task => {
          // Normaliza status para compatibilidade (concluido -> concluida)
          const status = task.status === 'concluido' ? 'concluida' : task.status;
          return status === item.key;
        }).length
      }));
    },
    
    // Resumo de tarefas por prioridade
    prioritySummary() {
      const base = [
        { key: 'alta', label: 'Alta prioridade' },
        { key: 'media', label: 'Prioridade média' },
        { key: 'baixa', label: 'Prioridade baixa' }
      ];
      return base.map(item => ({
        ...item,
        value: this.filteredTasks.filter(task => task.priority === item.key).length
      }));
    },
    
    // Lista de próximas tarefas ordenadas por prazo
    upcomingTasks() {
      return [...this.filteredTasks]
        .filter(task => !!task.due_date)
        .sort((a, b) => new Date(a.due_date) - new Date(b.due_date))
        .slice(0, 4);
    },
    
    // Label do mês atual formatado
    monthLabel() {
      return this.currentMonth.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    },
    
    // Gera os dias do calendário 
    calendarDays() {
      const start = new Date(this.currentMonth);
      const startDay = start.getDay();
      const firstVisible = new Date(start);
      firstVisible.setDate(1 - startDay);

      const cells = [];
      for (let i = 0; i < 42; i++) {
        const cellDate = new Date(firstVisible);
        cellDate.setDate(firstVisible.getDate() + i);
        const iso = cellDate.toISOString().split('T')[0];
        
        // Filtra tarefas que têm prazo neste dia
        const tasks = this.filteredTasks.filter(task => {
          if (!task.due_date) return false;
          const taskDate = new Date(task.due_date).toISOString().split('T')[0];
          return taskDate === iso;
        });
        
        cells.push({
          key: iso + i,
          date: iso,
          label: cellDate.getDate(),
          isCurrentMonth: cellDate.getMonth() === this.currentMonth.getMonth(),
          isToday: iso === new Date().toISOString().split('T')[0],
          tasks
        });
      }
      return cells;
    },
    
    // Tarefas do dia selecionado no calendário
    selectedDayTasks() {
      if (!this.selectedDate) return [];
      return this.filteredTasks.filter(task => task.due_date === this.selectedDate);
    },
    
    // Label formatado do dia selecionado
    selectedDayLabel() {
      if (!this.selectedDate) return '';
      const d = new Date(this.selectedDate);
      return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
    }
  },
  methods: {
    ...mapActions(['fetchTasks']),
    
    // Navega entre os meses do calendário
    changeMonth(step) {
      const next = new Date(this.currentMonth);
      next.setMonth(this.currentMonth.getMonth() + step);
      this.currentMonth = next;
    },
    
    // Seleciona um dia no calendário
    selectDay(cell) {
      if (cell && cell.date) {
        this.selectedDate = cell.date;
      }
    },
    
    // Formata data para exibição
    formatDate(value) {
      if (!value) return 'Sem data';
      return new Date(value).toLocaleDateString();
    }
  },
  mounted() {
    // Carrega tarefas se ainda não foram carregadas
    if (!this.tasks.length) {
      this.fetchTasks();
    }
  }
};
</script>
