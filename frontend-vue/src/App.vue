<template>
  <div class="app-container">
    <!-- NAVBAR SUPERIOR -->
    <!-- Exibida apenas em rotas autenticadas (oculta em /login e /register) -->
    <nav v-if="showNavbar" class="navbar">
      <!-- Logo e marca -->
      <div class="navbar-left">
        <div class="brand">
          <img src="/logo.svg" class="brand-icon" />
          <div>
            <span class="brand-text">TaskMaster</span>
            <small class="brand-subtitle">multiempresa</small>
          </div>
        </div>
        <span class="nav-chip">Workspace Pro</span>
      </div>

      <!-- Links de navegação e busca -->
      <div class="navbar-center">
        <ul class="nav-links">
          <li><router-link to="/dashboard">Dashboard</router-link></li>
          <li><router-link to="/tasks">Tarefas</router-link></li>
          <!-- Link Admin só aparece para usuários com role 'admin' -->
          <li v-if="isAdmin"><router-link to="/admin">Admin</router-link></li>
        </ul>

        <!-- Busca global de tarefas (sincronizada com Vuex) -->
        <div class="navbar-search">
          <span class="search-icon" aria-hidden="true"></span>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Buscar tarefas, squads ou projetos"
          />
        </div>
      </div>

      <!-- Ações rápidas e perfil do usuário -->
      <div class="navbar-right">
        <button class="navbar-quick" @click="goToNewTask">+ Nova tarefa</button>
        
        <!-- Trigger do dropdown do usuário -->
        <div @click.stop="toggleDropdown" class="user-trigger">
          <img :src="userAvatar" :alt="currentUserName" class="avatar" />
          <div class="user-info">
            <strong>{{ currentUserName }}</strong>
            <small>{{ userRole }}</small>
          </div>
          <span class="caret">▾</span>
        </div>

        <!-- Dropdown do usuário (fecha ao clicar fora ou em um item) -->
        <ul v-if="dropdownOpen" v-click-outside="closeDropdown" class="dropdown">
          <li><router-link to="/profile" @click.native="closeDropdown">Perfil</router-link></li>
          <li><button @click="logout">Sair</button></li>
        </ul>
      </div>
    </nav>

    <!-- CONTEÚDO PRINCIPAL -->
    <!-- Router-view renderiza o componente da rota atual -->
    <!-- content-area--flush remove padding para páginas full-bleed (login, dashboard, etc) -->
    <div
      :class="['content-area', { 'content-area--flush': isFullBleedRoute }]"
      :style="isFullBleedRoute ? fullBleedStyle : null"
    >
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
/**
 * App.vue - Componente raiz da aplicação
 * 
 * Responsabilidades:
 * - Renderiza navbar (condicionalmente)
 * - Gerencia dropdown do usuário
 * - Sincroniza busca global com Vuex
 * - Define rotas full-bleed (sem padding)
 * - Exibe avatar do usuário (custom ou gerado)
 */
export default {
  name: 'App',
  data() {
    return {
      dropdownOpen: false // Controla visibilidade do dropdown do usuário
    }
  },
  watch: {
    /**
     * Observa mudanças no estado do usuário no Vuex
     * Força atualização do componente quando o usuário muda
     * Isso garante que o avatar seja atualizado quando alterado no perfil
     */
    '$store.state.user': {
      handler() {
        this.$forceUpdate();
      },
      deep: true
    }
  },
  computed: {
    /**
     * Determina se navbar deve ser exibida
     * Ocultada em rotas públicas (login, register)
     */
    showNavbar() {
      const hideRoutes = ['/login', '/register'];
      return !hideRoutes.includes(this.$route.path);
    },
    
    /**
     * Determina se a rota atual deve ocupar toda a tela (full-bleed)
     * Rotas full-bleed não têm padding e ocupam 100vh
     */
    isFullBleedRoute() {
      const path = this.$route.path || '';
      const flushRoutes = [
        '/login', '/register', '/dashboard', '/tasks', 
        '/tasks/create', '/admin', '/profile'
      ];
      return flushRoutes.includes(path) || path.startsWith('/tasks/');
    },
    
    /**
     * Busca global de tarefas (two-way binding com Vuex)
     * Quando o usuário digita, atualiza o estado global
     */
    searchTerm: {
      get() {
        return this.$store.state.taskSearch;
      },
      set(value) {
        console.log('Setting searchTerm:', value);
        this.$store.commit('setTaskSearch', value);
      }
    },
    
    /**
     * Calcula altura mínima para rotas full-bleed
     * Considera altura da navbar se estiver visível
     */
    fullBleedStyle() {
      const navHeight = this.showNavbar ? 80 : 0;
      return { minHeight: `calc(100vh - ${navHeight}px)` };
    },
    
    /**
     * Nome do usuário atual (fallback para email ou 'Usuário')
     */
    currentUserName() {
      const user = this.$store.state.user;
      return user?.name || user?.email || 'Usuário';
    },
    
    /**
     * Verifica se usuário é administrador
     */
    isAdmin() {
      return this.$store.state.user?.role === 'admin';
    },
    
    /**
     * Label do role do usuário (para exibição)
     */
    userRole() {
      return this.$store.state.user?.role === 'admin' ? 'Administrador' : 'Membro';
    },
    
    /**
     * Avatar do usuário
     * Prioridade: 1) Imagem salva no localStorage (associada ao ID do usuário), 2) Avatar gerado automaticamente
     */
    userAvatar() {
      const user = this.$store.state.user;
      const userId = user?.id;
      
      // Busca avatar específico deste usuário usando ID
      if (userId) {
        const saved = localStorage.getItem(`user_avatar_${userId}`);
        if (saved) return saved;
      }
      
      // Gera avatar automático usando API externa se não houver avatar salvo
      const name = this.currentUserName;
      return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=7f56d9&color=fff&size=128`;
    }
  },
  methods: {
    /**
     * Faz logout do usuário e redireciona para login
     */
    logout() {
      this.$store.dispatch('logout');
      this.$router.push('/login');
    },
    
    /**
     * Navega para página de criação de nova tarefa
     */
    goToNewTask() {
      this.$router.push('/tasks/create');
    },
    
    /**
     * Abre/fecha dropdown do usuário
     */
    toggleDropdown() {
      this.dropdownOpen = !this.dropdownOpen;
    },
    
    /**
     * Fecha dropdown do usuário
     * Chamado ao clicar fora ou em um item do dropdown
     */
    closeDropdown() {
      this.dropdownOpen = false;
    }
  }
}
</script>

<style src="@/css/base.css"></style>
<style src="@/css/navbar.css"></style>
<style src="@/css/userprincipal.css"></style>
