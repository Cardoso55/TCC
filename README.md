# Sistema de Gestão com IA

Bem-vindo ao projeto **Sistema de Gestão com Inteligência Artificial**!  
Este projeto usa **PHP** para o frontend/backend principal e **Python** para a API de compras/reposição e integração com IA.

---

## 📂 Estrutura do Projeto

### **1. app/** – Lógica principal em PHP
- **controllers/** 🛠 – Controladores que recebem requisições do usuário e interagem com models e views  
- **models/** 📦 – Classes que representam entidades do sistema (Usuário, Produto, Pedido etc.)
- **views/** 🌐 – Páginas PHP exibidas no navegador  
- **helpers/** ⚙ – Funções auxiliares, como `api_helper.php` para consumir endpoints da API

---

### **2. storage/** – Armazenamento de arquivos
- **backups/** 💾 – Cópias de segurança do banco e arquivos importantes  
- **logs/** 📝 – Logs de ações do sistema e auditoria  
- **uploads/** 📂 – Arquivos enviados pelos usuários (imagens, PDFs, fotos de perfil etc.)

---

### **3. api/** – API Python para automação
- **endpoints/** 🔗 – Endpoints da API (`compras`, `requisicoes`, `estoque`)  
- **database/** 🗄 – Conexão e scripts do banco da API  
- **utils.py** ⚙ – Funções auxiliares da API  
- **app.py** 🚀 – Arquivo principal que roda a API

---

### **python/** - Inteligência Artificial em Python
- **utils/** ⚙ - Funções auxiliares da IA
- **alertas_automaticos.py**
- **analise_anomalias.py**
- **estoque_previsao**
- **ia_main**
- **niveis_estoque.py**
- **recomendacoes.py**
- 
---

### **4. Arquivos de configuração**
- **.env** 🔑 – Variáveis de ambiente (configurações de banco, API keys, debug)  
- **composer.json** 📦 – Dependências PHP (gerenciadas pelo Composer)  
- **requirements.txt** 📦 – Dependências Python (gerenciadas pelo pip)  
- **.htaccess** 🔐 – URLs amigáveis, redirecionamentos e proteção de pastas  

---

## ⚡ Funcionalidades Principais
- **Autenticação e Perfis:** Login, senha, níveis hierárquicos (diretor, gerente, supervisor, operário)  
- **Gestão de Usuários:** Cadastro, edição e exclusão de usuários  
- **Controle de Estoque:** Entradas, saídas, checklists e divergências  
- **Compras e Reposição:** Registro via API, geração automática de checklists  
- **Solicitações e Aprovações:** Mudanças de estoque, aprovadas por gerente/diretor  
- **Relatórios:** Vendas, compras, estoque e financeiro  
- **Alertas e IA:** Previsões, sugestões, produtos parados, estoque baixo e anomalias  
- **Configurações do Sistema:** Parâmetros globais, limites de estoque e preferências  
- **Logs/Auditoria:** Registro de todas as ações importantes  
