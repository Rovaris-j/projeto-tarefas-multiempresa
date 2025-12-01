<template>
  <div class="page-shell auth-shell">
    <!-- Container principal com bordas arredondadas -->
    <div class="auth-container">
      <section class="page-hero">
      <!-- Logo e nome da marca no topo do hero -->
      <div class="auth-logo">
        <img src="/logo.svg" alt="TaskMaster" class="auth-logo-icon" />
        <div class="auth-logo-text">
          <span class="auth-logo-name">TaskMaster</span>
          <small class="auth-logo-subtitle">multiempresa</small>
        </div>
      </div>

      <span class="hero-pill">Onboarding guiado</span>
      <h1>Crie o espaço da sua empresa em minutos.</h1>
      <p>Convide a equipe, defina fluxos e acompanhe indicadores desde o primeiro dia.</p>

      <ol class="hero-steps">
        <li>Informações do administrador</li>
        <li>Dados da empresa</li>
        <li>Configurações de squads</li>
      </ol>
    </section>

    <section class="page-card form-card">
      <header class="form-header">
        <div>
          <p class="eyebrow">Cadastro corporativo</p>
          <h2>Registrar empresa</h2>
          <p class="muted">Esses dados serão usados para criar o primeiro workspace.</p>
        </div>
        <router-link class="mini-link" to="/login">Já tenho conta</router-link>
      </header>

      <div v-if="error" class="info-banner error">{{ error }}</div>

      <form @submit.prevent="submit" class="page-form form-grid">
        <label class="input-group">
          <span>Nome do administrador</span>
          <input v-model="name" placeholder="Seu nome completo" required />
        </label>

        <label class="input-group">
          <span>Email corporativo</span>
          <input v-model="email" placeholder="voce@empresa.com" type="email" required />
        </label>

        <label class="input-group">
          <span>Senha</span>
          <input v-model="password" placeholder="Crie uma senha forte" type="password" required />
        </label>

        <label class="input-group">
          <span>Nome da empresa</span>
          <input v-model="company_name" placeholder="Razão social ou fantasia" required />
        </label>

        <button type="submit" class="primary-btn wide" :disabled="loading">
          {{ loading ? 'Registrando...' : 'Registrar e acessar' }}
        </button>
      </form>
    </section>
    </div>
  </div>
</template>

<script>
import '@/css/login.css';
import { mapActions } from 'vuex';

/**
 * Register.vue - Componente de registro de nova empresa e usuário administrador
 */
export default {
  data(){ 
    return { 
      name: '',           // Nome completo do administrador
      email: '',          // Email corporativo
      password: '',       // Senha do usuário
      company_name: '',   // Nome da empresa
      error: null,        // Mensagem de erro (se houver)
      loading: false      // Estado de carregamento durante requisição
    };
  },
  methods: {
    ...mapActions(['register']),
    
    /**
     * Submete o formulário de registro
     */
    async submit(){
      this.error = null;
      this.loading = true;
      try{
        /**
         * Chama action do Vuex que:
         * - Faz POST /api/register com dados do formulário
         * - Backend valida dados e cria empresa/usuário
         * - Backend gera token JWT automaticamente após criar usuário
         * - Retorna { token, user, company }
         * - Vuex salva token no localStorage e estado
         * 
         * O token JWT gerado permite que o usuário seja autenticado
         * imediatamente após o registro, sem necessidade de fazer login
         */
        await this.register({ 
          name: this.name, 
          email: this.email, 
          password: this.password, 
          company_name: this.company_name 
        });
        
        /**
         * Redireciona para dashboard após registro bem-sucedido
         * O token JWT já foi salvo e será usado automaticamente
         * em todas as requisições seguintes via interceptor do Axios
         */
        this.$router.push('/dashboard');
      } catch(err){
        /**
         * Trata erros de registro
         * Erros comuns:
         * - 422: Email já cadastrado ou empresa já possui admin
         * - 422: Dados inválidos (validação falhou)
         * - 500: Erro no servidor
         */
        this.error = 'Erro ao registrar: ' + (err.response?.data?.message || err.response?.data?.error || err.message);
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>
