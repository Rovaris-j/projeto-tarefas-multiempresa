<template>
  <div class="admin-shell">
    <header class="dashboard-header">
      <div>
        <p class="eyebrow">Painel do administrador</p>
        <h1>Visão geral da empresa</h1>
        <p class="muted">Monitore todas as tarefas e gerencie os usuários da sua organização.</p>
      </div>
      <button class="primary-btn" @click="$router.push('/tasks/create')">+ Nova tarefa</button>
    </header>

    <section class="summary-grid" v-if="adminStats">
      <article class="summary-card" v-for="card in adminStatusCards" :key="card.label">
        <p>{{ card.label }}</p>
        <strong>{{ card.value }}</strong>
        <span class="status-chip">{{ card.caption }}</span>
      </article>
    </section>

    <section class="admin-main">
      <article class="dashboard-card wide">
        <div class="card-header">
          <h3>Tarefas da empresa</h3>
          <span>{{ filteredAdminTasks.length }} tarefas</span>
        </div>
        <div class="table-head compact">
          <span>Tarefa</span>
          <span>Status</span>
          <span>Responsável</span>
          <span>Prazo</span>
          <span>Progresso</span>
        </div>
        <div v-for="task in filteredAdminTasks" :key="task.id" class="table-row compact">
          <div class="task-title">
            <strong>{{ task.title }}</strong>
            <small>{{ task.description || 'Sem descrição' }}</small>
          </div>
          <span class="status-pill" :class="task.status">{{ statusLabels[task.status] || task.status }}</span>
          <span>{{ task.assignee?.name || '—' }}</span>
          <span>{{ formatDate(task.due_date) }}</span>
          <div class="progress-inline">
            <div class="progress-track">
              <div class="progress-thumb" :style="{ width: (task.progress || 0) + '%' }"></div>
            </div>
            <small>{{ task.progress || 0 }}%</small>
          </div>
        </div>
        <p v-if="!filteredAdminTasks.length" class="empty-state">Nenhuma tarefa cadastrada.</p>
      </article>

      <aside class="admin-sidebar">
        <article class="dashboard-card">
          <div class="card-header">
            <h3>Equipe</h3>
          </div>
          <ul class="team-list">
            <li v-for="member in teamProgress" :key="member.user?.id">
              <div>
                <strong>{{ member.user?.name || 'Sem responsável' }}</strong>
                <small>{{ member.completed }}/{{ member.total }} concluídas</small>
              </div>
              <span>{{ member.completion_rate }}%</span>
            </li>
            <li v-if="!teamProgress.length" class="empty-state">Sem dados suficientes.</li>
          </ul>
        </article>

        <article class="dashboard-card">
          <div class="card-header">
            <h3>Novo usuário</h3>
          </div>
          <form @submit.prevent="createUser" class="page-form">
            <label class="input-group">
              <span>Nome</span>
              <input v-model="form.name" required />
            </label>
            <label class="input-group">
              <span>Email</span>
              <input type="email" v-model="form.email" required />
            </label>
            <label class="input-group">
              <span>Senha temporária</span>
              <input type="password" v-model="form.password" required minlength="6" />
            </label>
            <div v-if="formError" class="info-banner error">{{ formError }}</div>
            <button class="primary-btn" :disabled="submitting">
              {{ submitting ? 'Criando...' : 'Criar usuário' }}
            </button>
          </form>
          <div class="user-list">
            <h4>Equipe cadastrada</h4>
            <ul>
              <li v-for="member in companyUsers" :key="member.id">
                <div>
                  <strong>{{ member.name }}</strong>
                  <small>{{ member.email }}</small>
                </div>
                <span class="chip">{{ member.role }}</span>
              </li>
              <li v-if="!companyUsers.length" class="empty-state">Nenhum usuário cadastrado.</li>
            </ul>
          </div>
        </article>
      </aside>
    </section>
  </div>
</template>

<script>
import '@/css/userprincipal.css';
import { mapState, mapActions } from 'vuex';

export default {
  data(){
    return {
      form: { name:'', email:'', password:'' },
      formError: null,
      submitting: false,
    };
  },
  computed: {
    ...mapState(['adminStats','adminTasks','companyUsers', 'taskSearch']),
    filteredAdminTasks() {
      const term = (this.taskSearch || '').trim().toLowerCase();
      if (!term) return this.adminTasks;
      return this.adminTasks.filter(task => {
        const haystack = `${task.title || ''} ${task.description || ''}`.toLowerCase();
        return haystack.includes(term);
      });
    },
    adminStatusCards(){
      if(!this.adminStats) return [];
      const filtered = this.filteredAdminTasks;
      const byStatus = filtered.reduce((acc, task) => {
        const status = task.status === 'concluido' ? 'concluida' : task.status;
        acc[status] = (acc[status] || 0) + 1;
        return acc;
      }, {});
      return [
        { label:'Pendentes', value: byStatus.pendente || 0, caption:'À iniciar' },
        { label:'Em andamento', value: byStatus.em_andamento || 0, caption:'Em execução' },
        { label:'Concluídas', value: byStatus.concluida || 0, caption:'Finalizadas' }
      ];
    },
    teamProgress(){
      return this.adminStats?.team || [];
    },
    statusLabels(){
      return {
        pendente: 'Pendente',
        em_andamento: 'Em andamento',
        concluida: 'Concluída'
      };
    }
  },
  methods: {
    ...mapActions(['fetchAdminOverview','fetchAdminTasks','createCompanyUser','fetchCompanyUsers']),
    async bootstrap(){
      try {
        await Promise.all([
          this.fetchAdminOverview(),
          this.fetchAdminTasks(),
          this.fetchCompanyUsers()
        ]);
      } catch (err) {
        console.error(err);
      }
    },
    async createUser(){
      this.formError = null;
      this.submitting = true;
      try {
        await this.createCompanyUser(this.form);
        this.form = { name:'', email:'', password:'' };
        await Promise.all([
          this.fetchCompanyUsers(),
          this.fetchAdminOverview(),
          this.fetchAdminTasks()
        ]);
      } catch(err) {
        this.formError = err.response?.data?.error || err.response?.data?.message || err.message;
      } finally {
        this.submitting = false;
      }
    },
    formatDate(value){
      if(!value) return 'Sem data';
      return new Date(value).toLocaleDateString();
    }
  },
  mounted(){
    this.bootstrap();
  }
}
</script>

