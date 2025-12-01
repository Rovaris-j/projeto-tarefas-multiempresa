# 🔐 Como Funciona o Token JWT no Sistema

## 📋 Índice
1. [O que é JWT?](#o-que-é-jwt)
2. [Fluxo Completo](#fluxo-completo)
3. [Geração do Token (Backend)](#geração-do-token-backend)
4. [Armazenamento (Frontend)](#armazenamento-frontend)
5. [Uso Automático (Interceptor)](#uso-automático-interceptor)
6. [Validação (Backend)](#validação-backend)
7. [Tratamento de Erros](#tratamento-de-erros)

---

## 🎯 O que é JWT?

**JWT (JSON Web Token)** é um padrão de autenticação que permite transmitir informações de forma segura entre frontend e backend.

### Estrutura do Token:
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIiwibmFtZSI6IkpvaG8ifQ.assinatura
     └─ HEADER ─┘                    └─── PAYLOAD ───┘              └─ SIGNATURE ─┘
```

- **Header**: Tipo do token e algoritmo de assinatura
- **Payload**: Dados do usuário (id, email, role, etc.)
- **Signature**: Assinatura digital que garante integridade

---

## 🔄 Fluxo Completo

```
┌─────────────┐
│   LOGIN     │
│  (Frontend) │
└──────┬──────┘
       │ 1. Envia email + senha
       ▼
┌─────────────────┐
│  POST /api/login │
│   (Backend)      │
└──────┬───────────┘
       │ 2. Valida credenciais
       │ 3. Gera token JWT
       ▼
┌─────────────────┐
│  Retorna Token  │
│  {token, user}  │
└──────┬───────────┘
       │ 4. Salva no localStorage
       ▼
┌─────────────────┐
│  Vuex Store     │
│  localStorage   │
└──────┬───────────┘
       │ 5. Interceptor adiciona automaticamente
       ▼
┌─────────────────┐
│  Requisições    │
│  Authorization: │
│  Bearer {token} │
└──────┬───────────┘
       │ 6. Backend valida token
       ▼
┌─────────────────┐
│  Resposta       │
│  (200 ou 401)   │
└─────────────────┘
```

---

## 🔨 Geração do Token (Backend)

### Localização: `backend-laravel/app/Http/Controllers/AuthController.php`

### 1. **Login** (linha 93-108)
```php
public function login(Request $request)
{
    $credentials = $request->only('email', 'password');
    
    // JWTAuth::attempt() valida credenciais E gera token
    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => 'Credenciais inválidas'], 401);
    }

    // Retorna token + dados do usuário
    $user = JWTAuth::user();
    return response()->json([
        'token' => $token,      // ← Token JWT gerado
        'user' => $user,
        'company' => $user->company
    ]);
}
```

**O que acontece:**
- `JWTAuth::attempt()` verifica email/senha no banco
- Se válido, cria token JWT com dados do usuário
- Token contém: id, email, role, company_id, etc.

### 2. **Registro** (linha 77-78)
```php
// Após criar usuário
$token = JWTAuth::fromUser($user);
```

**O que acontece:**
- Gera token imediatamente após criar usuário
- Usuário já fica autenticado sem precisar fazer login

---

## 💾 Armazenamento (Frontend)

### Localização: `frontend-vue/src/store/index.js`

### 1. **Action de Login** (linha 173-191)
```javascript
async login({ commit }, credentials) {
    // Faz POST /api/login
    const res = await api.post('/login', credentials)
    
    // Salva token no Vuex e localStorage
    commit('setAuth', { 
        token: res.data.token,  // ← Token JWT
        user: res.data.user,
        company: res.data.company
    })
}
```

### 2. **Mutation setAuth** (linha 74-91)
```javascript
setAuth(state, { token, user, company }) {
    state.token = token
    state.user = user
    state.company = company
    
    // Persiste no localStorage
    localStorage.setItem('token', token)      // ← Salva token
    localStorage.setItem('user', JSON.stringify(user))
    localStorage.setItem('company', JSON.stringify(company))
}
```

**Onde fica armazenado:**
- **Vuex Store**: Estado global da aplicação (memória)
- **localStorage**: Persistência entre sessões (navegador)

---

## 🚀 Uso Automático (Interceptor)

### Localização: `frontend-vue/src/api.js` (linha 54-74)

### Interceptor de Requisição
```javascript
api.interceptors.request.use(
  config => {
    // 1. Lê token do localStorage
    const token = localStorage.getItem('token');
    
    // 2. Adiciona no header se existir
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  }
);
```

**O que acontece:**
- **ANTES** de cada requisição HTTP
- Lê token do localStorage automaticamente
- Adiciona no header: `Authorization: Bearer {token}`
- Não precisa passar token manualmente em cada chamada

**Exemplo de requisição:**
```javascript
// Você faz:
api.get('/tasks')

// Interceptor transforma em:
GET /api/tasks
Headers:
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

---

## ✅ Validação (Backend)

### Localização: `backend-laravel/app/Http/Kernel.php` + Middleware

### 1. **Middleware `auth:api`** (Kernel.php linha 57)
```php
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
];
```

### 2. **Uso nas Rotas** (`routes/api.php`)
```php
Route::middleware('auth:api')->group(function () {
    Route::apiResource('tasks', TaskController::class);
});
```

### 3. **Uso nos Controllers** (`TaskController.php` linha 30)
```php
public function __construct()
{
    $this->middleware('auth:api');  // ← Protege todas as rotas
}
```

### 4. **Processo de Validação:**
```
1. Requisição chega com header: Authorization: Bearer {token}
2. Middleware auth:api intercepta
3. Laravel usa guard 'api' (configurado com JWT)
4. JWTAuth valida:
   ✓ Token existe?
   ✓ Assinatura válida?
   ✓ Token não expirou?
   ✓ Usuário existe no banco?
5. Se válido: permite acesso
6. Se inválido: retorna 401 Unauthorized
```

### 5. **Configuração do Guard** (`config/auth.php` linha 44-49)
```php
'guards' => [
    'api' => [
        'driver' => 'jwt',  // ← Usa JWT para autenticação
        'provider' => 'users',
    ],
],
```

---

## ⚠️ Tratamento de Erros

### Localização: `frontend-vue/src/api.js` (linha 94-130)

### Interceptor de Resposta
```javascript
api.interceptors.response.use(
  res => res,  // Sucesso: passa adiante
  err => {
    // Erro 401: Token inválido ou expirado
    if (err.response && err.response.status === 401) {
      // Remove token inválido
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      localStorage.removeItem('company');
    }
    
    return Promise.reject(err);
  }
);
```

**Cenários tratados:**
- **Token expirado**: Backend retorna 401 → Remove token → Força novo login
- **Token inválido**: Backend retorna 401 → Remove token → Força novo login
- **Token ausente**: Backend retorna 401 → Remove token → Força novo login

### Router Guard (`router/index.js` linha 87-88)
```javascript
if (to.meta.requiresAuth && !store.state.token) {
    return next('/login');  // Redireciona se não houver token
}
```

---

## 📍 Resumo dos Arquivos

| Arquivo | Função |
|---------|--------|
| `AuthController.php` | Gera token JWT no login/registro |
| `config/auth.php` | Configura guard JWT |
| `Kernel.php` | Registra middleware `auth` |
| `routes/api.php` | Aplica middleware nas rotas |
| `store/index.js` | Salva token no Vuex/localStorage |
| `api.js` | Interceptor adiciona token automaticamente |
| `router/index.js` | Verifica token antes de navegar |

---

## 🔍 Exemplo Prático Completo

### 1. Usuário faz login:
```javascript
// Frontend: Login.vue
await this.login({ email: 'user@empresa.com', password: '123456' })
```

### 2. Backend gera token:
```php
// Backend: AuthController.php
$token = JWTAuth::attempt($credentials);
// Retorna: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

### 3. Frontend salva:
```javascript
// store/index.js
localStorage.setItem('token', token)
```

### 4. Próxima requisição:
```javascript
// Componente faz:
api.get('/tasks')

// Interceptor transforma em:
GET /api/tasks
Headers: Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### 5. Backend valida:
```php
// Middleware auth:api valida token
// Se válido: permite acesso
// Se inválido: retorna 401
```

### 6. Frontend trata erro:
```javascript
// Se 401: Remove token e redireciona para login
```

---

## 🎓 Conceitos Importantes

1. **Token é stateless**: Backend não armazena sessão, valida token a cada requisição
2. **Token expira**: Configurado no backend (ex: 60 minutos)
3. **Token é assinado**: Não pode ser modificado sem invalidar
4. **Bearer Token**: Padrão OAuth 2.0 para enviar token no header
5. **Interceptor**: Adiciona token automaticamente, sem código manual

---

## 🔒 Segurança

- ✅ Token é assinado digitalmente (não pode ser falsificado)
- ✅ Token expira automaticamente
- ✅ Token inválido é removido automaticamente
- ✅ Requisições sem token são bloqueadas
- ✅ Token é enviado via HTTPS (em produção)

---

**Fim da explicação! 🎉**

