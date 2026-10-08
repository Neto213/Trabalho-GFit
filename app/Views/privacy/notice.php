<div class="page-heading"><div><span class="eyebrow">LGPD · TRANSPARÊNCIA</span><h1>Aviso de Privacidade</h1><p>Como o GFit trata e protege seus dados pessoais.</p></div><?php if(\App\Support\Session::isLoggedIn()): ?><a class="btn btn-primary" href="<?= url('/minha-privacidade') ?>">Gerenciar meus dados</a><?php else: ?><a class="btn btn-outline-primary" href="<?= url('/login') ?>">Voltar ao acesso</a><?php endif; ?></div>

<article class="card p-4 p-lg-5 privacy-document">
    <p class="text-muted">Versão <?= e($version) ?> · Controlador: <?= e($controller) ?></p>
    <div class="alert alert-info"><strong>Canal de privacidade:</strong> <a href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a>. A academia responsável pela sua conta deve manter este contato atualizado antes do uso em produção.</div>

    <h2>Quais dados usamos</h2>
    <p>Tratamos dados de cadastro e contato, vínculo com rede e unidade, credenciais protegidas, registros de acesso e segurança, treinos, frequência e solicitações feitas pelo titular. Avaliações físicas podem conter peso, altura e observações profissionais e são tratadas como dados pessoais sensíveis.</p>

    <h2>Por que tratamos</h2>
    <p>Os dados cadastrais e operacionais permitem prestar o serviço, administrar a relação com alunos e profissionais, cumprir obrigações aplicáveis, prevenir fraudes e proteger contas. Dados de avaliação física somente podem ser registrados após consentimento específico do aluno, que pode ser revogado na Central de Privacidade. A revogação bloqueia novos registros e não elimina automaticamente dados que devam ser preservados por obrigação legal ou para exercício regular de direitos.</p>

    <h2>Proteção e compartilhamento</h2>
    <p>Aplicamos controle de acesso por perfil, rede e unidade, autenticação em duas etapas para perfis privilegiados, limitação de tentativas, registros de segurança pseudonimizados, proteção contra CSRF e criptografia autenticada das avaliações físicas. Os componentes visuais são servidos pelo próprio sistema, evitando o envio automático de navegação a provedores de fontes e CDNs.</p>
    <p>O sistema não usa cookies de publicidade ou analytics. O cookie de sessão é estritamente necessário para autenticação e segurança, com restrições HttpOnly e SameSite.</p>

    <h2>Retenção</h2>
    <p>Os dados permanecem pelo período necessário à prestação do serviço e às obrigações legais ou regulatórias. Registros técnicos seguem prazos documentados e devem ser eliminados ou anonimizados ao fim do ciclo. Registros de incidentes de segurança são preservados por pelo menos cinco anos conforme a regulamentação da ANPD.</p>

    <h2>Seus direitos</h2>
    <p>Você pode pedir confirmação de tratamento, acesso, correção, informação, portabilidade quando aplicável, anonimização, bloqueio ou eliminação de dados desnecessários, oposição, revisão de decisões automatizadas e revogação do consentimento. Use a Central de Privacidade depois de entrar ou contate o canal acima. A identidade poderá ser confirmada antes da entrega ou alteração dos dados.</p>

    <h2>Incidentes e atualizações</h2>
    <p>Incidentes com risco ou dano relevante são avaliados, documentados e comunicados à ANPD e aos titulares nos termos e prazos aplicáveis. Alterações materiais deste aviso recebem nova versão e comunicação adequada.</p>
</article>
