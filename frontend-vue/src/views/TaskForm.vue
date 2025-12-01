<template>
  <!-- Formulário de criação/edição de tarefas -->
  <div class="page-shell form-shell">
    <!-- Seção hero com informações contextuais -->
    <section class="page-hero">
      <span class="hero-pill">{{ isEdit ? 'Atualizar sprint' : 'Nova entrega' }}</span>
      <h1>{{ isEdit ? 'Refine sua tarefa' : 'Crie uma nova tarefa para a equipe' }}</h1>
      <p>Defina prioridade, status e prazos enquanto acompanha o progresso em tempo real.</p>
    </section>

    <!-- Card do formulário -->
    <section class="page-card form-card">
      <header class="form-header">
        <div>
          <p class="eyebrow">{{ isEdit ? 'Editar tarefa' : 'Cadastro rápido' }}</p>
          <h2>{{ isEdit ? 'Atualizar tarefa existente' : 'Cadastrar nova tarefa' }}</h2>
          <p class="muted">Campos essenciais para manter todo o time sincronizado.</p>
        </div>
      </header>

      <div v-if="error" class="info-banner error">{{ error }}</div>

      <!-- Mensagem de erro de validação -->
      <div v-if="validationError" class="info-banner error validation-error">
        <strong>⚠️ {{ validationError }}</strong>
      </div>

      <!-- Formulário principal -->
      <form @submit.prevent="save" class="page-form form-grid">
        <label class="input-group wide">
          <span>Título<span class="required-asterisk">*</span></span>
          <input v-model="title" placeholder="Ex: Apresentação para cliente X" required />
        </label>

        <label class="input-group wide">
          <span>Descrição</span>
          <textarea v-model="description" rows="4" placeholder="Notas adicionais, links úteis, anexos, etc."></textarea>
        </label>

        <label class="input-group">
          <span>Status</span>
          <select v-model="status" @change="onStatusChange">
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </label>

        <label class="input-group">
          <span>Prioridade</span>
          <select v-model="priority">
            <option value="baixa">Baixa</option>
            <option value="media">Média</option>
            <option value="alta">Alta</option>
          </select>
        </label>

        <!-- Campo de responsável (apenas para administradores) -->
        <label class="input-group" v-if="isAdmin">
          <span>Responsável</span>
          <select v-model="assigned_to">
            <option v-for="member in companyUsers" :key="member.id" :value="member.id">
              {{ member.name }}
            </option>
          </select>
        </label>

        <label class="input-group">
          <span>Prazo</span>
          <input type="date" v-model="due_date" />
        </label>

        <label class="input-group">
          <span>Progresso (%)</span>
          <input 
            type="number" 
            min="0" 
            max="100" 
            v-model.number="progress" 
            @input="onProgressChange"
          />
        </label>

        <label class="input-group" :class="{ 'has-error': showHoursError }">
          <span>Horas trabalhadas<span v-if="status === 'concluida'" class="required-asterisk">*</span></span>
          <input 
            type="number" 
            min="0" 
            step="0.5" 
            v-model.number="hours_worked" 
            placeholder="0.0"
            :class="{ 'input-error': showHoursError }"
          />
          <small v-if="showHoursError" class="field-error">Informe as horas trabalhadas</small>
        </label>

        <div class="form-actions">
          <button type="button" class="ghost-btn" @click="$router.back()" :disabled="loading">Cancelar</button>
          <button type="submit" class="primary-btn" :disabled="loading">
            {{ loading ? 'Salvando...' : (isEdit ? 'Atualizar tarefa' : 'Criar tarefa') }}
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<script>
import '@/css/login.css';
import api from '@/api';
import { mapState, mapActions } from 'vuex';

export default {
  data(){
    return {
      title:'',
      description:'',
      status:'pendente',
      priority:'media',
      assigned_to:null,
      due_date:null,
      progress:0,
      hours_worked:0, // Horas trabalhadas na tarefa
      error:null,
      validationError: null, // Erro de validação específico
      loading:false,
      originalStatus: null, // Status original ao carregar (para validação)
      originalProgress: 0, // Progresso original ao carregar
      statusOptions: [
        { value:'pendente', label:'Pendente' },
        { value:'em_andamento', label:'Em andamento' },
        { value:'concluida', label:'Concluída' }
      ]
    };
  },
  computed: {
    ...mapState(['companyUsers','user']),
    // Verifica se está editando uma tarefa existente
    isEdit(){ return !!this.$route.params.id; },
    // Verifica se o usuário é administrador
    isAdmin(){ return this.user?.role === 'admin'; },
    // Label da prioridade para exibição
    priorityLabel(){
      return { baixa:'Baixa', media:'Moderada', alta:'Crítica' }[this.priority] || 'Moderada';
    },
    
    // Verifica se deve mostrar erro de horas trabalhadas
    showHoursError() {
      return this.status === 'concluida' && (!this.hours_worked || this.hours_worked <= 0);
    }
  },
  async mounted(){
    // Se estiver editando, carrega os dados da tarefa
    if(this.isEdit){
      try {
        const res = await api.get(`/tasks/${this.$route.params.id}`);
        Object.assign(this.$data, {
          title: res.data.title,
          description: res.data.description,
          status: res.data.status,
          priority: res.data.priority || 'media',
          due_date: res.data.due_date || null,
          progress: res.data.progress || 0,
          hours_worked: res.data.hours_worked || 0,
          assigned_to: res.data.assigned_to || this.user?.id,
          originalStatus: res.data.status, // Salva status original
          originalProgress: res.data.progress || 0 // Salva progresso original
        });
      } catch(err) {
        this.error = 'Erro ao carregar tarefa: ' + (err.response?.data?.message || err.message);
      }
    } else {
      // Ao criar nova tarefa, atribui ao usuário atual
      this.assigned_to = this.user?.id;
      if(this.$route.query.status){
        this.status = this.$route.query.status;
      }
    }

    // Se for admin, carrega lista de usuários da empresa
    if(this.isAdmin){
      await this.ensureUsersLoaded();
    }
  },
  methods:{
    ...mapActions(['fetchCompanyUsers']),
    
    /**
     * Valida se a tarefa pode ser concluída
     * Regras:
     * - Deve estar em "em_andamento" antes de concluir (se estiver editando)
     * - Deve ter horas trabalhadas > 0 (OBRIGATÓRIO)
     * - Deve ter progresso > 0 (porcentagem determinada)
     */
    validateTaskCompletion() {
      this.validationError = null;
      
      // Se não está tentando concluir, não precisa validar
      if (this.status !== 'concluida') {
        return true;
      }
      
      // Validação 1: Se está editando, deve estar em "em_andamento" antes de concluir
      if (this.isEdit && this.originalStatus && 
          this.originalStatus !== 'em_andamento' && 
          this.originalStatus !== 'concluida') {
        this.validationError = 'A tarefa deve estar "Em andamento" antes de ser concluída.';
        return false;
      }
      
      // Validação 2: Deve ter horas trabalhadas > 0 (OBRIGATÓRIO - não pode ser 0)
      if (!this.hours_worked || this.hours_worked <= 0) {
        this.validationError = 'É obrigatório informar as horas trabalhadas (maior que 0) para concluir a tarefa.';
        return false;
      }
      
      // Validação 3: Deve ter progresso > 0 (porcentagem determinada)
      if (!this.progress || this.progress <= 0) {
        this.validationError = 'É necessário definir o progresso da tarefa (porcentagem maior que 0) antes de concluí-la.';
        return false;
      }
      
      return true;
    },
    
    /**
     * Monitora mudanças no progresso
     * Se chegar a 100%, atualiza status automaticamente para "concluida"
     * Mas só se tiver horas trabalhadas
     */
    onProgressChange() {
      // Se progresso chegou a 100%, tenta atualizar status automaticamente
      if (this.progress >= 100) {
        // Só atualiza para concluída se tiver horas trabalhadas
        if (this.hours_worked && this.hours_worked > 0) {
          this.status = 'concluida';
        } else {
          // Se não tem horas trabalhadas, mantém status atual e mostra aviso
          // Não força mudança de status sem horas trabalhadas
        }
      }
      // Limpa erro de validação quando progresso muda
      if (this.validationError) {
        this.validationError = null;
      }
    },
    
    /**
     * Monitora mudanças no status
     * Quando status muda para "concluida", atualiza progresso para 100% automaticamente
     * Valida quando usuário tenta concluir a tarefa
     */
    onStatusChange() {
      // Se está tentando concluir
      if (this.status === 'concluida') {
        // Atualiza progresso para 100% automaticamente
        if (this.progress < 100) {
          this.progress = 100;
        }
        // Valida imediatamente se pode concluir (verifica horas trabalhadas)
        if (!this.validateTaskCompletion()) {
          // Se validação falhar, reverte o status para o anterior
          this.status = this.originalStatus || 'em_andamento';
        }
      } else {
        // Se mudou para outro status, limpa erro
        this.validationError = null;
      }
    },
    
    // Salva ou atualiza a tarefa
    async save(){
      this.error = null;
      this.validationError = null;
      
      // Validação obrigatória: não permite concluir sem horas trabalhadas
      if (this.status === 'concluida' && (!this.hours_worked || this.hours_worked <= 0)) {
        this.validationError = 'É obrigatório informar as horas trabalhadas (maior que 0) para concluir a tarefa.';
        return; // Impede salvamento
      }
      
      // Valida antes de salvar
      if (!this.validateTaskCompletion()) {
        return; // Impede salvamento se validação falhar
      }
      
      this.loading = true;
      try {
        const payload = { 
          title:this.title,
          description:this.description,
          status:this.status,
          priority:this.priority,
          assigned_to:this.assigned_to || this.user?.id,
          due_date:this.due_date,
          progress:this.progress,
          hours_worked:this.hours_worked || 0
        };
        
        // Validação final antes de enviar: não permite concluir sem horas trabalhadas
        if (payload.status === 'concluida' && (!payload.hours_worked || payload.hours_worked <= 0)) {
          this.validationError = 'É obrigatório informar as horas trabalhadas (maior que 0) para concluir a tarefa.';
          this.loading = false;
          return;
        }
        
        if(this.isEdit) await this.$store.dispatch('updateTask',{id:this.$route.params.id, payload});
        else await this.$store.dispatch('createTask', payload);
        this.$router.push('/tasks');
      } catch(err) {
        this.error = 'Erro ao salvar tarefa: ' + (err.response?.data?.message || err.message);
      } finally {
        this.loading = false;
      }
    },
    
    // Garante que a lista de usuários está carregada (para admins)
    async ensureUsersLoaded(){
      if(!this.companyUsers.length){
        try {
          await this.fetchCompanyUsers();
        } catch(err) {
          console.error(err);
        }
      }
      if(!this.assigned_to){
        this.assigned_to = this.user?.id;
      }
    }
  }
}
</script>
