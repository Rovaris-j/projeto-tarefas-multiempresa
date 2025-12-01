<template>
  <div class="profile-shell">
    <!-- Cabeçalho do perfil -->
    <header class="profile-header">
      <div>
        <p class="eyebrow">Meu perfil</p>
        <h1>{{ currentUserName }}</h1>
        <p class="muted">Visualize suas estatísticas e gerencie suas informações.</p>
      </div>
    </header>

    <section class="profile-main">
      <!-- Sidebar com informações do usuário -->
      <aside class="profile-sidebar">
        <!-- Card de perfil com avatar -->
        <article class="profile-card">
          <div class="profile-avatar-wrapper">
            <label for="avatar-upload" class="avatar-upload-trigger">
              <img :src="userAvatar" :alt="currentUserName" class="profile-avatar" />
              <span class="avatar-edit-icon">✎</span>
            </label>
            <input
              id="avatar-upload"
              type="file"
              accept="image/*"
              @change="handleAvatarUpload"
              style="display: none;"
            />
          </div>
          <div class="profile-info">
            <h2>{{ currentUserName }}</h2>
            <p class="profile-company" v-if="company?.name">{{ company.name }}</p>
            <p class="profile-role">{{ user?.role === 'admin' ? 'Administrador' : 'Membro' }}</p>
            <p class="profile-email">{{ user?.email }}</p>
          </div>
        </article>

        <!-- Card de estatísticas rápidas -->
        <article class="profile-card">
          <h3>Estatísticas rápidas</h3>
          <ul class="stats-list">
            <li>
              <span>Tarefas concluídas</span>
              <strong>{{ completedCount }}</strong>
            </li>
            <li>
              <span>Tempo médio</span>
              <strong>{{ averageTime }}</strong>
            </li>
            <li>
              <span>Taxa de conclusão</span>
              <strong>{{ completionRate }}%</strong>
            </li>
          </ul>
        </article>
      </aside>

      <!-- Conteúdo principal do perfil -->
      <div class="profile-content">
        <!-- Lista de tarefas concluídas -->
        <article class="profile-card">
          <div class="card-header">
            <h3>Tarefas concluídas</h3>
            <span>{{ completedTasks.length }} tarefas</span>
          </div>
          <div class="completed-list">
            <div
              v-for="task in completedTasks"
              :key="task.id"
              class="completed-item"
            >
              <div>
                <strong>{{ task.title }}</strong>
                <small>{{ formatDate(task.updated_at) }}</small>
              </div>
              <span class="chip" :class="task.priority">{{ task.priority }}</span>
            </div>
            <p v-if="!completedTasks.length" class="empty-state">
              Nenhuma tarefa concluída ainda.
            </p>
          </div>
        </article>

        <!-- Gráfico de conclusão com barras separadas lado a lado -->
        <article class="profile-card">
          <div class="card-header">
            <h3>Gráfico de conclusão</h3>
          </div>
          <div class="chart-container">
            <!-- Legenda do gráfico -->
            <div class="chart-legend">
              <div class="legend-item">
                <span class="legend-color completed"></span>
                <span>Concluídas</span>
              </div>
              <div class="legend-item">
                <span class="legend-color pending"></span>
                <span>Pendentes</span>
              </div>
              <div class="legend-item">
                <span class="legend-color overdue"></span>
                <span>Atrasadas</span>
              </div>
            </div>
            <!-- Barras separadas lado a lado (não empilhadas) -->
            <div class="chart-bars-separated">
              <div
                v-for="(item, index) in chartData"
                :key="index"
                class="chart-month-group"
              >
                <!-- Container para as 3 barras lado a lado -->
                <div class="chart-bars-group">
                  <!-- Barra de tarefas concluídas (roxo) -->
                  <div class="chart-bar-separated-wrapper">
                    <div 
                      class="chart-bar-separated completed-bar" 
                      :style="{ height: item.completedHeight + '%' }"
                    >
                      <span v-if="item.completed > 0" class="chart-value">{{ item.completed }}</span>
                    </div>
                  </div>
                  <!-- Barra de tarefas pendentes (cinza) -->
                  <div class="chart-bar-separated-wrapper">
                    <div 
                      class="chart-bar-separated pending-bar" 
                      :style="{ height: item.pendingHeight + '%' }"
                    >
                      <span v-if="item.pending > 0" class="chart-value">{{ item.pending }}</span>
                    </div>
                  </div>
                  <!-- Barra de tarefas atrasadas (vermelho) -->
                  <div class="chart-bar-separated-wrapper">
                    <div 
                      class="chart-bar-separated overdue-bar" 
                      :style="{ height: item.overdueHeight + '%' }"
                    >
                      <span v-if="item.overdue > 0" class="chart-value">{{ item.overdue }}</span>
                    </div>
                  </div>
                </div>
                <small>{{ item.label }}</small>
              </div>
            </div>
          </div>
        </article>

        <!-- Card de performance -->
        <article class="profile-card">
          <div class="card-header">
            <h3>Performance</h3>
          </div>
          <div class="performance-grid">
            <div class="performance-item">
              <span class="performance-label">Tempo médio por tarefa</span>
              <strong class="performance-value">{{ averageTime }}</strong>
            </div>
            <div class="performance-item">
              <span class="performance-label">Tarefas este mês</span>
              <strong class="performance-value">{{ thisMonthCount }}</strong>
            </div>
            <div class="performance-item">
              <span class="performance-label">Taxa de sucesso</span>
              <strong class="performance-value">{{ completionRate }}%</strong>
            </div>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script>
import '@/css/userprincipal.css';
import { mapState, mapActions } from 'vuex';
import api from '@/api';

export default {
  data() {
    return {
      userAvatar: null
    };
  },
  computed: {
    ...mapState(['tasks', 'user', 'company']),
    
    // Nome do usuário atual (primeiro nome)
    currentUserName() {
      return this.user?.name || 'Usuário';
    },
    
    // Lista de tarefas concluídas ordenadas por data (apenas as 3 mais recentes)
    completedTasks() {
      return [...this.tasks]
        .filter(task => {
          const status = task.status === 'concluido' ? 'concluida' : task.status;
          return status === 'concluida';
        })
        .sort((a, b) => new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at))
        .slice(0, 3); // Apenas as 3 mais recentes
    },
    
    // Contagem total de tarefas concluídas
    completedCount() {
      return this.tasks.filter(task => {
        const status = task.status === 'concluido' ? 'concluida' : task.status;
        return status === 'concluida';
      }).length;
    },
    
    // Taxa de conclusão em porcentagem
    completionRate() {
      const total = this.tasks.length;
      if (!total) return 0;
      return Math.round((this.completedCount / total) * 100);
    },
    
    // Tempo médio de conclusão de tarefas
    averageTime() {
      const completed = this.tasks.filter(task => {
        const status = task.status === 'concluido' ? 'concluida' : task.status;
        return status === 'concluida' && task.created_at && task.updated_at;
      });
      if (!completed.length) return '—';
      
      // Calcula a diferença média entre criação e conclusão
      const totalDays = completed.reduce((sum, task) => {
        const created = new Date(task.created_at);
        const updated = new Date(task.updated_at);
        const diff = (updated - created) / (1000 * 60 * 60 * 24);
        return sum + diff;
      }, 0);
      
      const avg = totalDays / completed.length;
      if (avg < 1) return `${Math.round(avg * 24)}h`;
      return `${Math.round(avg)} dias`;
    },
    
    // Contagem de tarefas criadas no mês atual
    thisMonthCount() {
      const now = new Date();
      return this.tasks.filter(task => {
        const date = new Date(task.created_at);
        return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();
      }).length;
    },
    
    // Dados do gráfico com barras separadas (últimos 6 meses)
    chartData() {
      const last6Months = [];
      const now = new Date();
      let maxTotal = 0;
      
      // Primeiro passo: coletar dados de cada mês
      for (let i = 5; i >= 0; i--) {
        const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        const nextMonth = new Date(date.getFullYear(), date.getMonth() + 1, 1);
        
        // Filtra tarefas criadas neste mês
        const monthTasks = this.tasks.filter(task => {
          const taskDate = new Date(task.created_at);
          return taskDate >= date && taskDate < nextMonth;
        });
        
        // Separa por status e verifica atrasos
        const completed = monthTasks.filter(task => {
          const status = task.status === 'concluido' ? 'concluida' : task.status;
          return status === 'concluida';
        }).length;
        
        const pending = monthTasks.filter(task => {
          const status = task.status === 'concluido' ? 'concluida' : task.status;
          return status !== 'concluida' && !this.isOverdue(task);
        }).length;
        
        const overdue = monthTasks.filter(task => this.isOverdue(task)).length;
        
        const total = Math.max(completed, pending, overdue);
        maxTotal = Math.max(maxTotal, total);
        
        last6Months.push({
          label: date.toLocaleDateString('pt-BR', { month: 'short' }),
          completed,
          pending,
          overdue,
          total,
          date
        });
      }
      
      // Segundo passo: calcular alturas relativas (máximo 100%)
      return last6Months.map(item => {
        const max = Math.max(maxTotal, 1);
        const completedHeight = Math.round((item.completed / max) * 100);
        const pendingHeight = Math.round((item.pending / max) * 100);
        const overdueHeight = Math.round((item.overdue / max) * 100);
        
        return {
          ...item,
          completedHeight,
          pendingHeight,
          overdueHeight
        };
      });
    }
  },
  methods: {
    ...mapActions(['fetchTasks']),
    
    // Verifica se uma tarefa está atrasada (prazo vencido e não concluída)
    isOverdue(task) {
      if (!task.due_date) return false;
      const status = task.status === 'concluido' ? 'concluida' : task.status;
      if (status === 'concluida') return false;
      
      const dueDate = new Date(task.due_date);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      dueDate.setHours(0, 0, 0, 0);
      
      return dueDate < today;
    },
    
    // Formata data para exibição
    formatDate(value) {
      if (!value) return 'Sem data';
      return new Date(value).toLocaleDateString('pt-BR');
    },
    
    /**
     * Manipula upload de avatar
     * Salva avatar associado ao ID do usuário para evitar conflito entre contas
     */
    handleAvatarUpload(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      // Valida tamanho do arquivo (máximo 2MB)
      if (file.size > 2 * 1024 * 1024) {
        alert('Imagem muito grande. Use arquivos menores que 2MB.');
        return;
      }
      
      // Converte para base64 e salva no localStorage
      // IMPORTANTE: Salva com chave associada ao ID do usuário para evitar conflito entre contas
      const reader = new FileReader();
      reader.onload = (e) => {
        this.userAvatar = e.target.result;
        // Salva avatar com chave específica do usuário: user_avatar_{userId}
        const userId = this.user?.id;
        if (userId) {
          localStorage.setItem(`user_avatar_${userId}`, e.target.result);
        }
        // TODO: Implementar upload para backend se necessário
      };
      reader.readAsDataURL(file);
    }
  },
  mounted() {
    /**
     * Carrega avatar salvo do usuário atual ou gera um novo
     * Busca avatar usando ID do usuário para garantir que cada conta tenha seu próprio avatar
     */
    const userId = this.user?.id;
    if (userId) {
      // Busca avatar específico deste usuário
      const savedAvatar = localStorage.getItem(`user_avatar_${userId}`);
      if (savedAvatar) {
        this.userAvatar = savedAvatar;
      } else {
        // Gera avatar automático se não houver avatar salvo
        this.userAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(this.currentUserName)}&background=7f56d9&color=fff&size=200`;
      }
    } else {
      // Fallback: gera avatar automático se não houver ID do usuário
      this.userAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(this.currentUserName)}&background=7f56d9&color=fff&size=200`;
    }
    
    // Carrega tarefas se ainda não foram carregadas
    if (!this.tasks.length) {
      this.fetchTasks();
    }
  }
};
</script>
