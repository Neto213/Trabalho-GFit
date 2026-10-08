# Trabalho-GFit

Sistema de Gestão de Academia (GFit) com controle de alunos, profissionais, treinos, infraestrutura multi-tenant, autenticação com segurança reforçada (MFA), central completa de conformidade com a LGPD e galeria visual institucional.

## 🚀 Tecnologias
- **Backend:** PHP 8+ (Arquitetura MVC pura, sem frameworks pesados)
- **Banco de Dados:** MySQL / MariaDB (XAMPP)
- **Frontend:** HTML5, CSS3 moderno, Bootstrap 5, Bootstrap Icons
- **Segurança:** CSP com Nonce, Criptografia AES-256-GCM, Proteção contra CSRF, Rate Limiting contra força bruta, Hashes Bcrypt

## ⚙️ Instalação e Execução Local
1. Clone o repositório na pasta `htdocs` do seu XAMPP:
   ```bash
   git clone https://github.com/Neto213/Trabalho-GFit.git GFit
   ```
2. Inicie o **Apache** e o **MySQL** pelo XAMPP Control Panel.
3. Importe o banco de dados em `database/schema.sql` ou execute as migrações.
4. Acesse pelo navegador:
   `http://localhost/GFit/public`
