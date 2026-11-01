# Laravel Mail

Painel para disparar e-mails de marketing, avisos e notícias em massa. Cada usuário cadastra os próprios contatos, monta os próprios templates de e-mail e acompanha o resultado de cada envio. O super admin enxerga e gerencia tudo.

- **Stack:** Laravel 13, PHP 8.3+, Filament 5 (Livewire 4), filas no banco de dados.
- **Painel:** `/admin` (não há site público; a rota `/` é só a página padrão do Laravel).

---

## Sumário

1. [Como funciona](#como-funciona)
2. [Perfis e permissões](#perfis-e-permissões)
3. [Instalação](#instalação)
4. [Configuração (.env)](#configuração-env)
5. [Como usar](#como-usar)
6. [Templates: blocos e variáveis](#templates-blocos-e-variáveis)
7. [Arquitetura](#arquitetura)
8. [Banco de dados](#banco-de-dados)
9. [Testes](#testes)
10. [Problemas comuns](#problemas-comuns)

---

## Como funciona

O fluxo tem quatro etapas:

1. **Contatos:** o usuário cadastra quem vai receber os e-mails (nome, e-mail, telefone, empresa, observações).
2. **Templates:** o usuário monta o e-mail visualmente, com blocos (título, texto, imagem, botão, rodapé, espaço, divisor). Ninguém escreve HTML: o HTML é gerado por trás.
3. **Disparo:** na lista de Templates, a ação **Disparar** enfileira um e-mail por contato (todos ou só alguns).
4. **Logs:** um worker processa a fila, envia cada e-mail e grava se deu certo ou errado, com a mensagem de erro quando falha.

```
Contato + Template ──► Disparar ──► fila (jobs) ──► worker envia (SMTP) ──► Log (Enviado | Falhou)
```

Cada envio é independente: se um e-mail falha, os outros continuam. Não há reenvio automático (uma tentativa por e-mail); o erro fica registrado no log.

---

## Perfis e permissões

| Perfil                        | O que vê e faz                                                                            |
| ----------------------------- | ----------------------------------------------------------------------------------------- |
| **Usuário comum**             | Só os **próprios** contatos, templates e logs. Não vê a tela de Usuários.                 |
| **Super admin** (`is_master`) | Vê **tudo** de todos os usuários, gerencia usuários e pode escolher o dono de um contato. |

Como o isolamento é feito:

- Contatos, templates e logs têm `user_id`. O trait `app/Models/Concerns/BelongsToUser.php` aplica um **escopo global**: se há usuário logado e ele não é super admin, toda consulta recebe `where user_id = <usuário>`.
- Ao criar um registro, o mesmo trait preenche o `user_id` com o usuário logado.
- Não há policies. O escopo global vale para qualquer consulta, então não depende de lembrar de filtrar.
- A tela de **Usuários** só abre para o super admin (`canViewAny`, `canCreate`, `canEdit`, `canDelete`).
- O job de envio roda sem usuário logado, então não aplica o escopo e usa o `user_id` do próprio template.

---

## Instalação

Requisitos: PHP 8.3+, Composer, Node/npm, um banco (SQLite, MySQL ou PostgreSQL) e o [Mailpit](https://mailpit.axllent.org/) para testar e-mails localmente.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Ajuste o `.env` (veja a próxima seção), depois:

```bash
php artisan migrate --seed
php artisan storage:link
```

- `migrate --seed` cria as tabelas e o usuário **admin** (super admin) a partir das variáveis `APP_ADMIN_*`.
- `storage:link` expõe `storage/app/public`, onde ficam as imagens enviadas nos templates.

> Cuidado: `migrate:fresh` **apaga todas as tabelas** do banco configurado no `.env`. Confira `DB_CONNECTION` e `DB_DATABASE` antes de rodar.

---

## Configuração (.env)

### Aplicação

| Variável     | Para que serve                                                                                                   |
| ------------ | ---------------------------------------------------------------------------------------------------------------- |
| `APP_NAME`   | Nome do painel e remetente padrão.                                                                               |
| `APP_URL`    | URL em que você abre o painel. Precisa bater com o endereço real: as imagens salvas dos templates usam essa URL. |
| `APP_LOCALE` | Idioma (`pt_BR`).                                                                                                |

### Admin inicial

| Variável             | Para que serve                                                         |
| -------------------- | ---------------------------------------------------------------------- |
| `APP_ADMIN_NAME`     | Nome do super admin criado pelo seeder.                                |
| `APP_ADMIN_MAIL`     | E-mail de login do super admin.                                        |
| `APP_ADMIN_PASSWORD` | Senha do super admin. **Troque antes de usar fora do ambiente local.** |

O `AdminSeeder` usa `updateOrCreate` pelo e-mail: rodar de novo atualiza o admin em vez de duplicar.

```bash
php artisan db:seed --class=AdminSeeder
```

### Banco

`DB_CONNECTION` (`sqlite`, `mysql` ou `pgsql`) e as variáveis `DB_*` correspondentes.

### E-mail (Mailpit no ambiente local)

```
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@laravel-mail.test"
MAIL_FROM_NAME="${APP_NAME}"
```

O Mailpit recebe os e-mails em SMTP na porta `1025` e mostra a caixa de entrada em http://localhost:8025. Em produção, troque pelos dados do seu provedor SMTP.

### Fila

`QUEUE_CONNECTION=database` (padrão). Os e-mails só saem se houver um worker rodando.

---

## Como usar

### 1. Subir o projeto

Em três terminais:

```bash
php artisan serve          # painel em http://localhost:8000/admin
php artisan queue:work     # envia os e-mails da fila
mailpit                    # caixa de entrada de teste em http://localhost:8025
```

Entre em http://localhost:8000/admin com o e-mail e a senha de `APP_ADMIN_*`.

### 2. Cadastrar usuários (super admin)

**Administração → Usuários → Criar.** Informe nome, e-mail, senha e, se quiser, marque **Super admin**. Há filtros por perfil, e-mail verificado e data de criação.

### 3. Cadastrar contatos

**Cadastros → Contatos → Criar.**

- Campos: nome, e-mail, telefone, empresa e observações.
- O e-mail é **único por usuário**: dois usuários podem ter o mesmo e-mail em suas listas, mas o mesmo usuário não.
- O super admin vê o campo **Usuário** e escolhe de quem é o contato. Para o usuário comum, o contato é sempre dele.
- Filtros: usuário (super admin), telefone, empresa e data de criação.

### 4. Importar contatos por planilha (.xlsx)

Em **Cadastros → Contatos**, clique em **Importar planilha**.

1. (Opcional) Clique em **Baixar modelo** para obter uma planilha de exemplo.
2. Preencha a primeira linha com os títulos das colunas: `nome`, `email`, `telefone`, `empresa`, `observacoes`. Só **nome** e **email** são obrigatórios; as colunas podem estar em qualquer ordem e os títulos aceitam variações (`e-mail`, `celular`, `whatsapp`, `obs`...), com ou sem acento.
3. Envie o arquivo `.xlsx` (até 5 MB e 5.000 linhas). O super admin escolhe também o usuário dono dos contatos; para o usuário comum, os contatos são sempre dele.

Regras da importação:

- Se o e-mail já existe para aquele usuário, o contato é **atualizado** (só os campos preenchidos na planilha; campos vazios não apagam o que já existe). Contatos excluídos com o mesmo e-mail são restaurados.
- Linhas sem nome, com e-mail inválido ou vazias são **ignoradas**; o aviso final mostra quantos foram criados, atualizados e ignorados e os primeiros erros (com o número da linha).
- Só a primeira aba da planilha é lida. A importação é feita na hora, sem fila.
- O código fica em `app/Services/Contact/ImportContactsService.php`; a ação está em `ListContacts`.

### 5. Criar um template

**Cadastros → Templates → Criar.** Veja a seção [Templates](#templates-blocos-e-variáveis) para os detalhes.

1. Informe **nome** e **assunto**.
2. Em **Aparência**, escolha a cor principal, a cor de fundo e a fonte.
3. Em **Conteúdo do e-mail**, clique em **Adicionar bloco** e monte o e-mail. Arraste pela alça (⠿) ou use as setas para mudar a posição.
4. Confira o resultado no painel **Pré-visualização**, que mostra o e-mail como será enviado, usando "Maria Exemplo" como contato de teste.

### 6. Visualizar um template salvo

Na lista de Templates, o botão **Visualizar e-mail** abre o e-mail renderizado em um modal. A prévia usa o primeiro contato do dono do template ou, se não houver, "Maria Exemplo".

### 7. Disparar

Na lista de Templates, clique em **Disparar**:

- **Todos os contatos** (padrão): envia para todos os contatos do dono do template.
- Desligando essa opção, você escolhe quais contatos receberão.

O painel avisa quantos e-mails foram para a fila. Com o worker rodando (`php artisan queue:work`), eles saem em segundos.

### 8. Acompanhar os logs

**Disparos → Logs** lista cada e-mail com data, destinatário, assunto, status (**Enviado** ou **Falhou**) e o erro, quando houver. Filtros: status, template, usuário (super admin) e data. O usuário comum só vê os próprios logs.

---

## Templates: blocos e variáveis

### Blocos

| Bloco       | Opções                                                                                                 |
| ----------- | ------------------------------------------------------------------------------------------------------ |
| **Título**  | Texto, tamanho (pequeno, médio, grande) e alinhamento. Usa a cor principal.                            |
| **Texto**   | Editor visual (negrito, itálico, sublinhado, link, listas) e alinhamento.                              |
| **Imagem**  | Upload, link ao clicar (opcional), largura (total, média, pequena), bordas arredondadas e alinhamento. |
| **Botão**   | Texto, link, estilo (preenchido ou contorno), formato (reto, arredondado, pílula) e alinhamento.       |
| **Rodapé**  | Texto pequeno e cinza, com alinhamento.                                                                |
| **Espaço**  | Espaço vertical (pequeno, médio, grande).                                                              |
| **Divisor** | Linha horizontal.                                                                                      |

Um e-mail só com uma foto é um template com um único bloco de imagem. Os blocos podem ser reordenados, clonados e recolhidos.

### Variáveis

Valem no **assunto**, em **títulos**, **textos**, **botões** e **rodapés**:

| Variável       | Vira                |
| -------------- | ------------------- |
| `{{nome}}`     | Nome do contato     |
| `{{email}}`    | E-mail do contato   |
| `{{telefone}}` | Telefone do contato |

Os valores são escapados (HTML), então um nome com `<` ou `&` não quebra o e-mail.

### Como o HTML é gerado

Os blocos ficam salvos como JSON na coluna `templates.blocks`. Na hora de enviar (ou pré-visualizar), `app/Mail/TemplateMail.php` converte cada bloco em dados normalizados e a view `resources/views/mail/template.blade.php` os transforma em HTML de e-mail (tabelas e estilos inline, largura de 600px), compatível com a maioria dos clientes de e-mail.

No envio real, as imagens vão **embutidas** no e-mail. Na pré-visualização, usam a URL do arquivo.

---

## Arquitetura

```
app/
├── Enums/MailStatus.php                  Enviado | Falhou
├── Filament/
│   ├── Filters/                          Filtros das tabelas (Common, Contact, User, MailLog)
│   └── Resources/                        Contacts, Templates, MailLogs, Users
│       └── <Nome>/                       Resource + Pages/ + Schemas/ + Tables/
├── Jobs/SendTemplateMailJob.php          Envia um e-mail e grava o log
├── Mail/TemplateMail.php                 Converte os blocos e renderiza o e-mail
├── Services/Contact/ImportContactsService.php   Importa contatos de .xlsx
├── Models/
│   ├── BaseModel.php                     HasFactory + SoftDeletes
│   ├── Concerns/BelongsToUser.php        Escopo global por usuário
│   └── Contact, Template, MailLog, User
└── Providers/Filament/AdminPanelProvider.php   Painel /admin
```

### Painel (Filament)

Cada resource segue a estrutura `Resource` + `Pages` + `Schemas` (form e infolist) + `Tables`. `MailLogResource` é somente leitura (só lista). O acesso ao painel é liberado a qualquer usuário (`User::canAccessPanel`); o que cada um vê é decidido pelo escopo global.

### Disparo

A ação **Disparar** (`TemplatesTable`) cria um `SendTemplateMailJob(templateId, contactId)` por contato. O job:

1. Carrega template e contato sem o escopo global.
2. Envia `TemplateMail` por `Mail::to($contato->email)`.
3. Cria um `MailLog` com `status = sent` (e `sent_at`) ou, se der exceção, `status = failed` com a mensagem de erro. A exceção é capturada e **não** derruba os outros envios (`tries = 1`).

### Pré-visualização ao vivo

No form de template, o painel **Pré-visualização** renderiza o e-mail a cada alteração, dentro de um `iframe` com `sandbox="allow-same-origin"` (sem scripts). O formulário atualiza ao adicionar, remover ou reordenar blocos, ao enviar uma imagem, ao sair de um campo e, no editor de texto, cerca de 0,8 s depois de parar de digitar.

---

## Banco de dados

| Tabela      | Campos principais                                                                                 |
| ----------- | ------------------------------------------------------------------------------------------------- |
| `users`     | `name`, `email`, `password`, `is_master`, timestamps                                              |
| `contacts`  | `user_id`, `name`, `email`, `phone`, `company`, `notes`, soft delete. Único em `(user_id, email)` |
| `templates` | `user_id`, `name`, `subject`, `color`, `background_color`, `font`, `blocks` (JSON), soft delete   |
| `mail_logs` | `user_id`, `contact_id`, `template_id`, `email`, `subject`, `status`, `error`, `sent_at`          |

Observações:

- Contatos e templates usam **soft delete**. Os logs guardam `email` e `subject` próprios e mantêm o vínculo mesmo se o contato ou template for excluído.
- `mail_logs` não tem soft delete: é um registro histórico.
- `jobs`, `cache` e `sessions` são as tabelas padrão do Laravel (fila e sessão no banco).

---

## Testes

```bash
php artisan test
```

Os testes rodam em SQLite em memória (`phpunit.xml`), então **não** tocam no banco do `.env`. A suíte cobre:

- **Models** (`tests/Unit/Models`): criar, ler, atualizar, excluir e relações de `Contact`, `Template`, `MailLog` e `User`.
- **Importação** (`tests/Feature/ImportContactsTest.php`): criar, atualizar, ignorar linhas inválidas, restaurar excluídos e atribuição de usuário.
- **Fluxo de e-mail** (`tests/Feature/MassMailTest.php`): isolamento por usuário, envio com log de sucesso e de falha, e acesso ao painel.
- **Painel** (`tests/Feature/ViewPagesTest.php`): páginas dos resources, prévia do e-mail, estilos dos blocos, atribuição de usuário em contatos e filtros das tabelas.

---

## Problemas comuns

| Sintoma                                    | Causa e solução                                                                                                                      |
| ------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------ |
| Disparei, mas nada chega                   | O worker não está rodando. Execute `php artisan queue:work`.                                                                         |
| E-mails na fila, mas o log mostra "Falhou" | Veja a coluna **Erro** em Logs. Costuma ser SMTP fora do ar (confira se o Mailpit está rodando na porta 1025) ou `MAIL_*` incorreto. |
| Imagem de template salvo não aparece       | Rode `php artisan storage:link` e confira se `APP_URL` é o endereço que você usa no navegador.                                       |
| Prévia não atualiza ao digitar             | A atualização acontece ao sair do campo (e após uma pequena pausa no editor de texto).                                               |
| Mudei o `.env` e nada mudou                | Rode `php artisan config:clear`.                                                                                                     |
| Usuário comum não vê a tela de Usuários    | É o esperado: ela é exclusiva do super admin.                                                                                        |
| Admin não consegue entrar                  | Rode `php artisan db:seed --class=AdminSeeder` para recriar o admin com os valores atuais de `APP_ADMIN_*`.                          |
