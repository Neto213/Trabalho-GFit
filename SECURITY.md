# Segurança do GFit

## Relato responsável

Relate vulnerabilidades de forma privada ao administrador da instalação. Não inclua senhas, tokens, avaliações físicas ou dados pessoais. A organização deve substituir este texto por seu canal de segurança antes da publicação.

## Controles aplicados

- autorização por perfil, rede e unidade;
- CSRF em todas as rotas POST;
- cookies HttpOnly, SameSite e Secure quando HTTPS está ativo;
- CSP com scripts locais e nonce por resposta;
- MFA para perfis privilegiados, com códigos temporários apenas como hash;
- limitação de tentativas e tokens de redefinição de uso único;
- avaliações físicas com AES-256-GCM e contexto autenticado;
- auditoria com IP e e-mail pseudonimizados;
- bloqueio web de código, configuração, banco, scripts, logs, testes e Git;
- exportações SQL sensíveis bloqueadas sem confirmação explícita por CLI.

## Operação

Use HTTPS em produção, mantenha `APP_DEBUG=false`, gere chaves aleatórias distintas, restrinja permissões do `.env`, faça backups criptografados e teste restauração. Execute `composer audit` e `php scripts/privacy_maintenance.php --apply` em rotina controlada. Nunca troque `DATA_ENCRYPTION_KEY` sem recriptografar os registros existentes.
