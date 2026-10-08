# Controles LGPD e responsabilidades operacionais

## Inventário resumido

| Categoria | Finalidade | Controle principal |
|---|---|---|
| Nome, e-mail, perfil e unidade | Conta e prestação do serviço | acesso por rede e perfil |
| Senha e MFA | autenticação e prevenção a fraude | hashes, criptografia e limitação de tentativas |
| Treinos e frequência | execução do serviço contratado | autorização por rede/unidade |
| Avaliações físicas | acompanhamento físico | consentimento específico e AES-256-GCM |
| IP e eventos de segurança | prevenção e investigação | pseudonimização e retenção limitada |
| Solicitações do titular | cumprimento de direitos | trilha de status e resposta |

## Decisões que a organização deve completar

1. Identificar formalmente controlador, operadores, encarregado ou canal aplicável.
2. Validar a base legal de cada operação. O software usa consentimento específico para avaliação física; isso deve refletir o serviço real e a relação contratual.
3. Definir prazos de retenção por obrigação legal, fiscal, consumerista, trabalhista e exercício de direitos.
4. Firmar contratos e instruções com hospedagem, e-mail, suporte e demais operadores.
5. Avaliar crianças e adolescentes. O cadastro atual não coleta data de nascimento nem verifica responsável; impeça contas infantis até criar um fluxo compatível.
6. Preparar relatório de impacto quando o risco ou a determinação da ANPD justificar.

## Resposta a incidentes

Preserve evidências, contenha o acesso, determine dados e titulares afetados, avalie risco ou dano relevante, registre decisões e envolva o responsável de privacidade. Quando aplicável, comunique ANPD e titulares no prazo regulamentar vigente. Preserve o registro do incidente por pelo menos cinco anos.

## Verificação antes de produção

- `APP_ENV=production`, `APP_DEBUG=false` e HTTPS ativo;
- chaves aleatórias fora do repositório;
- identidade e contato do controlador atualizados;
- contratos de operadores e SMTP revisados;
- backup criptografado e restauração testada;
- rotina de retenção aprovada e agendada;
- perfis privilegiados com MFA confirmado;
- isolamento entre redes e unidades testado;
- plano de incidentes ensaiado.

Referências oficiais: Lei 13.709/2018, guias e regulamentos da ANPD. Este checklist técnico não representa certificação jurídica.
