# Sistema de Gestão com IA

Bem-vindo ao projeto **Sistema de Gestão com Inteligência Artificial**.  
Este sistema foi desenvolvido em **PHP** para a interface e backend principal, e **Python** para a parte de IA e automação de compras/reposição via API.

---

## 📂 Estrutura do Projeto

project/
├── app/
│ ├── controllers/ # Lógica de controle (Auth, Usuários, Estoque, Compras, IA etc.)
│ ├── models/ # Estrutura de dados e classes que representam entidades
│ ├── views/ # Páginas PHP que são exibidas no navegador
│ └── helpers/ # Funções auxiliares (ex: api_helper.php)
├── storage/
│ ├── backups/ # Cópias de segurança do banco e arquivos
│ ├── logs/ # Logs do sistema e auditoria
│ └── uploads/ # Arquivos enviados pelos usuários (imagens, PDFs etc.)
├── api/ # API Python para compras e pedidos de reposição
│ ├── endpoints/ # Endpoints da API (compras, reposições, estoque)
│ ├── database/ # Conexão e scripts do banco da API
│ ├── utils.py # Funções auxiliares da API
│ └── app.py # Arquivo principal da API
├── .env # Variáveis de ambiente (configurações de DB, API keys, etc.)
├── composer.json # Dependências do PHP
├── requirements.txt # Dependências do Python
├── .htaccess # Regras do servidor (URLs amigáveis, segurança, redirecionamentos)
└── README.md # Este arquivo


---

## ⚡ Funcionalidades

- **Autenticação e Perfis de Usuário:** login, senha, níveis hierárquicos (diretor, gerente, supervisor, operário).  
- **Gestão de Usuários:** cadastro, edição e exclusão de contas.  
- **Controle de Estoque:** entradas, saídas, checklists, divergências.  
- **Compras e Reposição:** registro via API, geração automática de checklists.  
- **Solicitações e Aprovações:** mudanças de estoque, aprovações de gerente/diretor.  
- **Relatórios:** vendas, compras, estoque e financeiros.  
- **Alertas e IA:** previsões, sugestões de compras, produtos parados, estoque baixo e anomalias.  
- **Configurações do Sistema:** parâmetros globais, limites de estoque, preferências.  
- **Logs/Auditoria:** registro de todas as ações importantes do sistema.


