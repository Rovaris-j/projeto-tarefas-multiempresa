/**
 * Vue Router - Configuração de rotas da aplicação
 * 
 * Define todas as rotas e protege rotas que requerem autenticação ou permissões de admin
 */
import Vue from 'vue';
import Router from 'vue-router';
import store from '@/store';
import Login from '@/views/Login.vue';
import Register from '@/views/Register.vue';
import Dashboard from '@/views/Dashboard.vue';
import TaskList from '@/views/TaskList.vue';
import TaskForm from '@/views/TaskForm.vue';
import AdminDashboard from '@/views/AdminDashboard.vue';
import Profile from '@/views/Profile.vue';

Vue.use(Router);

/**
 * Configuração de rotas
 * - mode: 'history' remove o # da URL
 * - meta.requiresAuth: rota requer usuário autenticado
 * - meta.requiresAdmin: rota requer role 'admin'
 */
const router = new Router({
  mode: 'history', // Remove # da URL (requer configuração no servidor)
  routes: [
    // Rotas públicas (sem autenticação)
    { path: '/login', component: Login },
    { path: '/register', component: Register },
    
    // Redirecionamento da raiz para dashboard
    { path: '/', redirect: '/dashboard' },
    
    // Rotas protegidas (requerem autenticação)
    { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true } },
    { path: '/tasks', component: TaskList, meta: { requiresAuth: true } },
    { path: '/tasks/create', component: TaskForm, meta: { requiresAuth: true } },
    { path: '/tasks/:id/edit', component: TaskForm, meta: { requiresAuth: true }, props: true },
    { path: '/profile', component: Profile, meta: { requiresAuth: true } },
    
    // Rota administrativa (requer autenticação E role admin)
    { path: '/admin', component: AdminDashboard, meta: { requiresAuth: true, requiresAdmin: true } },
  ]
});

/**
 * Navigation Guard - Proteção de rotas com verificação de JWT
 * 
 * Este guard é executado ANTES de cada navegação para verificar:
 * - Se o usuário possui token JWT válido (para rotas protegidas)
 * - Se o usuário possui permissões de administrador (para rotas admin)
 * 
 * Sistema de proteção com JWT:
 * - Verifica presença de token JWT no estado Vuex (vindo do localStorage)
 * - Token JWT é validado pelo backend em cada requisição (não aqui)
 * - Se token expirar ou for inválido, backend retorna 401
 * - Interceptor do Axios remove token inválido e router redireciona
 * 
 * Fluxo de verificação:
 * 1. Usuário tenta acessar rota protegida
 * 2. Guard verifica se token existe no store (não valida token aqui)
 * 3. Se não houver token, redireciona para /login
 * 4. Se houver token, permite acesso (backend valida token na requisição)
 * 5. Se rota requer admin, verifica role do usuário no token/estado
 * 
 * Nota: Validação real do token JWT é feita pelo backend via middleware.
 * Este guard apenas verifica se token existe localmente.
 */
router.beforeEach((to, from, next) => {
  /**
   * Verifica se a rota requer autenticação (token JWT)
   * 
   * Verifica apenas se token existe no estado Vuex (vindo do localStorage).
   * A validação real do token (expiração, assinatura) é feita pelo backend
   * quando a requisição é feita. Se token for inválido, backend retorna 401
   * e interceptor remove token, forçando novo login.
   * 
   * Se rota requer autenticação e não há token:
   * - Redireciona para /login
   * - Usuário precisará fazer login para obter novo token JWT
   */
  if (to.meta.requiresAuth && !store.state.token) {
    return next('/login'); // Redireciona para login se não autenticado
  }
  
  /**
   * Verifica se a rota requer permissões de administrador
   * 
   * Role do usuário vem do token JWT decodificado pelo backend
   * e armazenado no estado Vuex após login/registro.
   * 
   * Se usuário não for admin:
   * - Redireciona para /dashboard
   * - Impede acesso a rotas administrativas
   */
  if (to.meta.requiresAdmin) {
    const role = store.state.user?.role; // Role vem do token JWT decodificado
    if (role !== 'admin') {
      return next('/dashboard'); // Redireciona para dashboard se não for admin
    }
  }
  
  /**
   * Permite navegação se todas as verificações passaram
   * Token JWT será validado pelo backend quando componente fizer requisições
   */
  next();
});

export default router;
