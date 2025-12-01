/**
 * Vuex Store - Gerenciamento de estado global da aplicação
 * 
 * Este arquivo centraliza todo o estado da aplicação, incluindo:
 * - Autenticação (token, usuário, empresa)
 * - Tarefas (do usuário e do admin)
 * - Dados administrativos
 * - Busca de tarefas
 */
import Vue from 'vue'
import Vuex from 'vuex'
import api from '@/api'

Vue.use(Vuex)

export default new Vuex.Store({
  /**
   * Estado global da aplicação
   * Todos os dados compartilhados entre componentes ficam aqui
   */
  state: {
    /**
     * Autenticação: dados do usuário logado
     * 
     * token: Token JWT retornado pelo backend após login/registro
     *        - Armazenado no localStorage para persistir entre sessões
     *        - Usado automaticamente pelo interceptor do Axios em todas as requisições
     *        - Formato: string JWT assinada contendo informações do usuário
     *        - Expira após período definido no backend (configurado em config/jwt.php)
     *        - Quando expirado ou inválido, backend retorna 401 e token é removido
     * 
     * user: Dados do usuário autenticado (id, name, email, role, company_id)
     *       - Armazenado no localStorage para evitar requisições desnecessárias
     *       - Atualizado após login/registro bem-sucedidos
     * 
     * company: Dados da empresa do usuário (id, name, slug)
     *          - Armazenado no localStorage junto com dados do usuário
     *          - Usado para identificar contexto multiempresa
     */
    token: localStorage.getItem('token') || null, // Token JWT para autenticação
    user: JSON.parse(localStorage.getItem('user') || 'null'), // Dados do usuário
    company: JSON.parse(localStorage.getItem('company') || 'null'), // Dados da empresa
    
    // Tarefas: lista de tarefas do usuário atual
    tasks: [],
    
    // Dados administrativos: apenas para usuários com role 'admin'
    adminTasks: [],      // Todas as tarefas da empresa
    adminStats: null,    // Estatísticas do dashboard admin
    companyUsers: [],    // Lista de usuários da empresa
    
    // Busca: termo de pesquisa global para tarefas
    taskSearch: ''
  },
  
  /**
   * Mutations - Modificam o estado de forma síncrona
   * IMPORTANTE: Mutations devem ser chamadas apenas via commit()
   */
  mutations: {
    /**
     * Define dados de autenticação e salva no localStorage
     * 
     * Esta mutation é chamada após login ou registro bem-sucedidos.
     * O token JWT recebido do backend é armazenado e será usado
     * automaticamente pelo interceptor do Axios em todas as requisições.
     * 
     * Parâmetros:
     * @param {Object} state - Estado atual do Vuex
     * @param {Object} payload - { token, user, company }
     *   - token: Token JWT retornado pelo backend (string)
     *   - user: Objeto com dados do usuário (id, name, email, role, company_id)
     *   - company: Objeto com dados da empresa (id, name, slug)
     */
    setAuth(state, { token, user, company }) {
      // Atualiza estado do Vuex
      state.token = token
      state.user = user
      state.company = company
      
      /**
       * Persiste dados no localStorage para manter sessão após refresh da página
       * 
       * Importante:
       * - Token JWT é armazenado como string simples
       * - User e company são serializados como JSON
       * - Interceptor do Axios lê token do localStorage automaticamente
       * - Router guard verifica token para proteger rotas
       */
      localStorage.setItem('token', token)
      localStorage.setItem('user', JSON.stringify(user))
      localStorage.setItem('company', JSON.stringify(company))
    },
    
    /**
     * Limpa todos os dados de autenticação e estado
     * Usado no logout
     * 
     * Nota sobre avatares:
     * - Avatares são salvos com chave específica por usuário: user_avatar_{userId}
     * - Não é necessário limpar avatar no logout, pois cada usuário tem sua própria chave
     * - Quando outro usuário fizer login, seu avatar será carregado automaticamente
     */
    logout(state) {
      state.token = null
      state.user = null
      state.company = null
      state.tasks = []
      state.adminTasks = []
      state.adminStats = null
      state.companyUsers = []
      state.taskSearch = ''
      // Remove dados do localStorage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('company')
      // Nota: Avatar não é removido pois está associado ao ID do usuário
      // Cada usuário terá seu próprio avatar quando fizer login
    },
    
    // Mutations simples para atualizar listas
    setTasks(state, tasks) {
      state.tasks = tasks
    },
    setAdminTasks(state, tasks) {
      state.adminTasks = tasks
    },
    setAdminStats(state, payload) {
      state.adminStats = payload
    },
    setCompanyUsers(state, users) {
      state.companyUsers = users
    },
    setTaskSearch(state, term = '') {
      state.taskSearch = term
    }
  },
  
  /**
   * Actions - Operações assíncronas e chamadas à API
   * Actions podem chamar mutations via commit() e outras actions via dispatch()
   */
  actions: {
    /**
     * Autenticação: Login do usuário com JWT
     * 
     * Esta action realiza o processo de autenticação via JWT:
     * 
     * Fluxo:
     * 1. Envia credenciais (email, password) para POST /api/login
     * 2. Backend valida credenciais usando JWTAuth::attempt()
     * 3. Backend gera token JWT assinado contendo dados do usuário
     * 4. Backend retorna { token, user, company }
     * 5. Token JWT é salvo no localStorage e estado Vuex
     * 6. Interceptor do Axios passa a incluir token em todas as requisições
     * 
     * Token JWT retornado:
     * - Contém informações do usuário (id, email, role, company_id)
     * - Assinado digitalmente pelo backend (não pode ser modificado)
     * - Expira após período configurado no backend (ex: 60 minutos)
     * - Usado para autenticar todas as requisições subsequentes
     * 
     * Parâmetros:
     * @param {Object} context - Contexto do Vuex (commit, dispatch, state)
     * @param {Object} credentials - { email, password }
     * 
     * Retorna:
     * @returns {Promise} Promise que resolve quando login é bem-sucedido
     * 
     * Lança:
     * @throws {Error} Erro se credenciais forem inválidas (401) ou erro de rede
     */
    async login({ commit }, credentials) {
      /**
       * Faz requisição POST para /api/login
       * Backend valida credenciais e retorna token JWT
       * 
       * Resposta esperada:
       * {
       *   token: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", // Token JWT
       *   user: { id, name, email, role, company_id },
       *   company: { id, name, slug }
       * }
       */
      const res = await api.post('/login', credentials)
      
      /**
       * Salva token JWT e dados no estado Vuex e localStorage
       * Após isso, interceptor do Axios adiciona token automaticamente
       * em todas as requisições via header Authorization: Bearer {token}
       */
      commit('setAuth', { 
        token: res.data.token,  // Token JWT para autenticação
        user: res.data.user,    // Dados do usuário autenticado
        company: res.data.company // Dados da empresa do usuário
      })
    },
    
    /**
     * Autenticação: Registro de nova empresa e usuário admin com JWT
     * 
     * Esta action realiza o processo de registro via JWT:
     * 
     * Fluxo:
     * 1. Envia dados do formulário para POST /api/register
     * 2. Backend cria empresa (se não existir) e usuário com role 'admin'
     * 3. Backend gera token JWT automaticamente usando JWTAuth::fromUser()
     * 4. Backend retorna { token, user, company }
     * 5. Token JWT é salvo no localStorage e estado Vuex
     * 6. Usuário é autenticado automaticamente após registro
     * 7. Interceptor do Axios passa a incluir token em todas as requisições
     * 
     * Token JWT retornado:
     * - Gerado imediatamente após criação do usuário
     * - Contém informações do usuário (id, email, role='admin', company_id)
     * - Assinado digitalmente pelo backend
     * - Expira após período configurado no backend
     * - Permite acesso imediato sem necessidade de fazer login
     * 
     * Parâmetros:
     * @param {Object} context - Contexto do Vuex (commit, dispatch, state)
     * @param {Object} payload - { name, email, password, company_name }
     * 
     * Retorna:
     * @returns {Promise} Promise que resolve quando registro é bem-sucedido
     * 
     * Lança:
     * @throws {Error} Erro se email já existir (422) ou erro de rede
     */
    async register({ commit }, payload) {
      /**
       * Faz requisição POST para /api/register
       * Backend cria empresa e usuário, depois gera token JWT
       * 
       * Resposta esperada:
       * {
       *   token: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", // Token JWT gerado
       *   user: { id, name, email, role: 'admin', company_id },
       *   company: { id, name, slug }
       * }
       */
      const res = await api.post('/register', payload)
      
      /**
       * Salva token JWT e dados no estado Vuex e localStorage
       * O token permite que o usuário seja autenticado imediatamente
       * após o registro, sem necessidade de fazer login separadamente
       * 
       * Interceptor do Axios adiciona token automaticamente em todas
       * as requisições via header Authorization: Bearer {token}
       */
      commit('setAuth', { 
        token: res.data.token,  // Token JWT para autenticação imediata
        user: res.data.user,    // Dados do usuário recém-criado
        company: res.data.company // Dados da empresa recém-criada
      })
    },
    
    /**
     * Tarefas: Busca lista de tarefas do usuário
     * @param {Object} filters - Filtros opcionais (status, priority)
     */
    async fetchTasks({ commit }, filters = {}) {
      const res = await api.get('/tasks', { params: filters })
      commit('setTasks', res.data)
    },
    
    /**
     * Tarefas: Cria nova tarefa e atualiza a lista
     */
    async createTask({ dispatch }, payload) {
      await api.post('/tasks', payload)
      dispatch('fetchTasks') // Atualiza lista após criar
    },
    
    /**
     * Tarefas: Atualiza tarefa existente e atualiza a lista
     */
    async updateTask({ dispatch }, { id, payload }) {
      await api.put(`/tasks/${id}`, payload)
      dispatch('fetchTasks') // Atualiza lista após editar
    },
    
    /**
     * Tarefas: Remove tarefa e atualiza a lista
     */
    async deleteTask({ dispatch }, id) {
      await api.delete(`/tasks/${id}`)
      dispatch('fetchTasks') // Atualiza lista após deletar
    },
    
    /**
     * Admin: Busca estatísticas do dashboard administrativo
     * Apenas para usuários com role 'admin'
     */
    async fetchAdminOverview({ commit }) {
      const res = await api.get('/admin/dashboard')
      commit('setAdminStats', res.data)
    },
    
    /**
     * Admin: Busca todas as tarefas da empresa
     */
    async fetchAdminTasks({ commit }) {
      const res = await api.get('/admin/tasks')
      commit('setAdminTasks', res.data)
    },
    
    /**
     * Admin: Busca lista de usuários da empresa
     */
    async fetchCompanyUsers({ commit }) {
      const res = await api.get('/admin/users')
      commit('setCompanyUsers', res.data)
    },
    
    /**
     * Admin: Cria novo usuário na empresa
     */
    async createCompanyUser({ dispatch }, payload) {
      await api.post('/admin/users', payload)
      await dispatch('fetchCompanyUsers') // Atualiza lista após criar
    }
  }
})