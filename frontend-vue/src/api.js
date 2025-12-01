/**
 * API Client - Configuração centralizada do Axios com suporte a JWT
 * 
 * Este arquivo configura o cliente HTTP para comunicação com o backend Laravel:
 * - Define URL base e timeout
 * - Adiciona token JWT automaticamente em todas as requisições via interceptor
 * - Trata erros de autenticação (401) e remove tokens inválidos
 * 
 * Sistema de autenticação JWT:
 * - Token é obtido após login/registro e armazenado no localStorage
 * - Interceptor de requisição adiciona token no header Authorization
 * - Backend valida token em cada requisição protegida
 * - Token expirado ou inválido resulta em erro 401
 * - Interceptor de resposta remove token inválido automaticamente
 */
import axios from 'axios';

// Cria instância do Axios com configurações padrão
const api = axios.create({
  baseURL: 'http://localhost:8000/api', // URL base da API Laravel
  timeout: 10000, // Timeout de 10 segundos
});

// Configura headers padrão para todas as requisições
api.defaults.headers.common['Accept'] = 'application/json';
api.defaults.headers.post['Content-Type'] = 'application/json';
api.defaults.headers.put['Content-Type'] = 'application/json';
api.defaults.headers.patch['Content-Type'] = 'application/json';

/**
 * Interceptor de Requisição - Adiciona Token JWT automaticamente
 * 
 * Este interceptor é executado ANTES de cada requisição HTTP.
 * Ele adiciona automaticamente o token JWT no header Authorization,
 * seguindo o padrão Bearer Token Authentication.
 * 
 * Fluxo:
 * 1. Interceptor é acionado antes de cada requisição
 * 2. Lê token JWT do localStorage (salvo após login/registro)
 * 3. Se token existir, adiciona no header: Authorization: "Bearer {token}"
 * 4. Backend recebe requisição e valida token usando middleware JWT
 * 
 * Vantagens:
 * - Não é necessário passar token manualmente em cada chamada
 * - Centraliza lógica de autenticação em um único lugar
 * - Funciona para todas as rotas protegidas automaticamente
 * 
 * Formato do header:
 * Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
 * 
 * @param {Object} config - Configuração da requisição Axios
 * @returns {Object} Configuração modificada com token JWT (se existir)
 */
api.interceptors.request.use(
  config => {
    /**
     * Recupera token JWT do localStorage
     * Token foi salvo pelo Vuex store após login/registro bem-sucedidos
     */
    const token = localStorage.getItem('token');
    
    /**
     * Adiciona token no header Authorization se existir
     * Formato: "Bearer {token}" - padrão OAuth 2.0 / JWT
     * Backend espera este formato para validar token via middleware JWT
     */
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  },
  error => Promise.reject(error)
);

/**
 * Interceptor de Resposta - Trata erros de autenticação JWT
 * 
 * Este interceptor é executado APÓS cada resposta HTTP.
 * Ele trata erros relacionados à autenticação JWT, especialmente
 * quando o token está expirado, inválido ou ausente.
 * 
 * Cenários tratados:
 * - 401 Unauthorized: Token inválido, expirado ou ausente
 * - Token expirado: Backend retorna 401 após validar expiração
 * - Token inválido: Backend não consegue decodificar/validar token
 * - Token ausente: Requisição a rota protegida sem token
 * 
 * Ações tomadas:
 * - Remove token inválido do localStorage
 * - Permite que erro seja propagado para componente
 * - Router guard detecta ausência de token e redireciona para login
 */
api.interceptors.response.use(
  res => res, // Retorna resposta normalmente se sucesso (não modifica)
  err => {
    /**
     * Trata erro 401 (Unauthorized) - Token JWT inválido ou expirado
     * 
     * Quando o backend retorna 401, significa que:
     * - Token JWT expirou (TTL configurado no backend)
     * - Token JWT é inválido (assinatura incorreta, formato errado)
     * - Token não foi enviado em rota protegida
     * - Usuário não existe mais ou foi desativado
     * 
     * Ação: Remove token do localStorage para evitar tentativas futuras
     * com token inválido. O router guard detectará ausência de token
     * e redirecionará automaticamente para /login.
     */
    if (err.response && err.response.status === 401) {
      /**
       * Remove token JWT inválido do localStorage
       * Isso força o usuário a fazer login novamente
       * O router guard em router/index.js verifica token e redireciona
       */
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      localStorage.removeItem('company');
      
      /**
       * Nota: O redirecionamento para /login é feito pelo router guard
       * em router/index.js quando detecta ausência de token em rota protegida
       */
    }
    
    // Propaga erro para componente que fez a requisição
    // Componente pode tratar erro específico (ex: mostrar mensagem ao usuário)
    return Promise.reject(err);
  }
);

export default api;