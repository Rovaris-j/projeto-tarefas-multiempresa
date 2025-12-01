<template>
  <div class="page-shell auth-shell">
    <!-- Container principal com bordas arredondadas -->
    <div class="auth-container">
      <!-- Seção hero com informações da plataforma -->
      <section class="page-hero">
      <div class="auth-logo">
        <img src="/logo.svg" alt="TaskMaster" class="auth-logo-icon" />
        <div class="auth-logo-text">
          <span class="auth-logo-name">TaskMaster</span>
          <small class="auth-logo-subtitle">multiempresa</small>
        </div>
      </div>

      <span class="hero-pill">Painel multiempresa</span>
      <h1>Gerencie squads, sprints e entregas em um só lugar.</h1>
      <p>Dashboards em tempo real, automações e alertas inteligentes para manter toda a operação alinhada.</p>

      <ul class="hero-list">
        <li>Fluxos customizados por cliente</li>
        <li>Integração com calendários</li>
        <li>Monitoramento 24/7</li>
      </ul>
    </section>

    <!-- Card do formulário de login -->
    <section class="page-card form-card">
      <header class="form-header">
        <div>
          <p class="eyebrow">Acesso seguro</p>
          <h2>Entrar na plataforma</h2>
          <p class="muted">Use suas credenciais corporativas para continuar.</p>
        </div>
        <span class="mini-badge">Suporte 24/7</span>
      </header>

      <div v-if="error" class="info-banner error">{{ error }}</div>

      <!-- Formulário de login -->
      <form @submit.prevent="submit" class="page-form">
        <label for="email" class="input-group">
          <span>Email corporativo</span>
            <input 
              id="email"
              v-model="email" 
              type="email"
            placeholder="voce@empresa.com" 
              required 
            />
        </label>

        <label for="password" class="input-group">
          <span>Senha</span>
            <input 
              id="password"
              v-model="password" 
              type="password" 
              placeholder="Sua senha" 
              required 
            />
        </label>

        <label class="checkbox-field">
            <input 
              type="checkbox" 
              id="manter-conectado" 
              name="manter-conectado" 
              v-model="manterConectado"
            >
          <span>Manter-me conectado</span>
        </label>

        <button type="submit" class="primary-btn wide" :disabled="loading">
            {{ loading ? 'Entrando...' : 'Entrar' }}
          </button>
        </form>

      <footer class="form-footer">
        <p>
          Ainda sem acesso? 
          <router-link to="/register">Cadastre sua empresa</router-link>
        </p>
      </footer>
    </section>
    </div>
  </div>
</template>

<script>
import '@/css/login.css' 
import { mapActions } from 'vuex';

/**
 * Login.vue - Componente de autenticação de usuários
 * 
 * Este componente gerencia o processo de login utilizando JWT (JSON Web Tokens):
 * 
 * Fluxo de autenticação com JWT:
 * 1. Usuário preenche email e senha
 * 2. Dados são enviados para a API (/api/login)
 * 3. Backend valida credenciais e gera um token JWT
 * 4. Token JWT é retornado junto com dados do usuário e empresa
 * 5. Token é armazenado no localStorage e Vuex store
 * 6. Interceptor do Axios adiciona automaticamente o token em todas as requisições
 * 7. Token é enviado no header Authorization: "Bearer {token}"
 * 
 * Segurança:
 * - Token JWT contém informações do usuário (id, email, role) de forma assinada
 * - Token expira após um período definido no backend
 * - Token inválido ou expirado resulta em erro 401 (não autorizado)
 * - Interceptor remove token inválido automaticamente
 */
export default {
  data() {
    return {
      email: '',
      password: '',
      manterConectado: false, // Opção para manter usuário logado
      error: null,
      loading: false
    };
  },
  mounted() {
    /**
     * Recupera estado do localStorage ao montar componente
     * Restaura email e preferência de "manter conectado" se existir
     * Nota: O token JWT em si é gerenciado pelo Vuex store
     */
    if(localStorage.getItem('manterConectado') === 'true') {
      this.manterConectado = true;
      this.email = localStorage.getItem('email') || '';
    }
  },
  methods: {
    ...mapActions(['login']),
    
    /**
     * Submete o formulário de login
     * 
     * Processo:
     * 1. Chama action 'login' do Vuex que faz requisição POST /api/login
     * 2. Backend retorna token JWT, dados do usuário e empresa
     * 3. Vuex store salva token no localStorage e estado global
     * 4. Interceptor do Axios passa a incluir token em todas as requisições
     * 5. Redireciona para dashboard após sucesso
     * 
     * Tratamento de erros:
     * - Credenciais inválidas: backend retorna 401
     * - Erro de rede: capturado e exibido ao usuário
     */
    async submit() { 
      this.error = null;
      this.loading = true;
      try {
        /**
         * Chama action do Vuex que:
         * - Faz POST /api/login com email e password
         * - Backend valida credenciais e gera token JWT usando JWTAuth::attempt()
         * - Retorna { token, user, company }
         * - Vuex salva token no localStorage e estado
         */
        await this.login({email: this.email, password: this.password});
        
        /**
         * Salva ou remove dados no localStorage conforme a opção "manter conectado"
         * Nota: O token JWT em si já foi salvo pelo Vuex store
         * Aqui salvamos apenas preferências do usuário (email e checkbox)
         */
        if(this.manterConectado) {
          localStorage.setItem('manterConectado', 'true');
          localStorage.setItem('email', this.email);
        } else {
          localStorage.removeItem('manterConectado');
          localStorage.removeItem('email');
        }

        // Redireciona para dashboard após autenticação bem-sucedida
        // O token JWT será usado automaticamente em todas as requisições seguintes
        this.$router.push('/dashboard'); 
      } catch(err) {
        /**
         * Trata erros de autenticação
         * Erros comuns:
         * - 401: Credenciais inválidas (email/senha incorretos)
         * - 422: Dados inválidos (validação falhou)
         * - 500: Erro no servidor
         */
        this.error = 'Erro ao fazer login: ' + (err.response?.data?.message || err.response?.data?.error || err.message);
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>
