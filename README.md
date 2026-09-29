# Teste Técnico · API de LMS

API para um cenário de plataforma de aprendizagem: usuários, cursos, atividades, matrículas e conclusão de atividades.

Construída em **Laravel 13**, sem front-end, sem autenticação e sem painel administrativo, conforme o enunciado.

## Requisitos

- PHP 8.3+
- Composer
- MySQL 8+

## Instruções para executar o projeto

```bash
git clone https://github.com/nohahjeong/manole-teste-php.git
cd manole-teste-php
composer install
cp .env.example .env
php artisan key:generate
```

Crie um banco de dados chamado `manole_teste_php` e ajuste `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env`.

Migrations e dados de exemplo:

```bash
php artisan migrate --seed
php artisan serve
```

A API fica em `http://localhost:8000/api`.

### Testes

```bash
php artisan test
```

Os testes rodam em **SQLite em memória** (configurado no `phpunit.xml`), então não dependem do banco da aplicação.

São **dois níveis**: os testes de serviço cobrem as regras de negócio e os de endpoint cobrem o contrato HTTP.

| Arquivo (em `tests/Feature/`) | O que testa |
|---|---|
| `EnrollmentServiceTest.php` | regras de matrícula e busca da matrícula |
| `ProgressServiceTest.php` | cálculo de progresso, incluindo casos de borda |
| `ActivityCompletionServiceTest.php` | conclusão, idempotência e data de conclusão |
| `EnrollmentEndpointTest.php` | contrato HTTP da matrícula |
| `ActivityCompletionEndpointTest.php` | contrato HTTP da conclusão |
| `ProgressEndpointTest.php` | contrato HTTP do progresso |

## Seeder

Em um banco novo, `php artisan migrate --seed` cria estes dados:

**Cursos**

| ID | Curso | Situação |
|---|---|---|
| 1 | Curso Ativo | Ativo |
| 2 | Curso Inativo | Inativo |

**Atividades do curso 1**

| IDs | Tipo |
|---|---|
| 1, 2, 3 | Obrigatórias |
| 4, 5 | Opcionais |

**Alunos**

| ID | Aluno | Situação |
|---|---|---|
| 1 | Aluno Ativo | Ativo, sem matrícula |
| 2 | Aluno Já Matriculado | Ativo, matriculado no curso 1 |
| 3 | Aluno Inativo | Inativo |

## Endpoints

As rotas ficam sob o prefixo `/api`, que é a convenção do Laravel para o arquivo `routes/api.php`.

### Matricular um usuário

`POST /api/courses/{course}/enrollments` · corpo: `user_id` · resposta **201**

```bash
curl -X POST http://localhost:8000/api/courses/1/enrollments \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"user_id": 1}'
```

```json
{"id":2,"user_id":1,"course_id":1,"completed_at":null}
```

### Concluir uma atividade

`POST /api/courses/{course}/activities/{activity}/completion` · corpo: `user_id` · resposta **200**

```bash
curl -X POST http://localhost:8000/api/courses/1/activities/1/completion \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"user_id": 1}'
```

```json
{"percentage":33.33,"required_completed":1,"required_total":3,"completed_at":null}
```

A resposta traz o progresso já recalculado.

### Consultar o progresso

`GET /api/users/{user}/courses/{course}/progress` · resposta **200**

```bash
curl http://localhost:8000/api/users/1/courses/1/progress -H "Accept: application/json"
```

```json
{"percentage":100,"required_completed":3,"required_total":3,"completed_at":"2026-09-28T22:02:40.000000Z"}
```

Exemplo de curso concluído. Enquanto há atividades obrigatórias pendentes, `completed_at` é `null`.

## Códigos de status

| Situação | Código |
|---|---|
| Matrícula criada | 201 |
| Conclusão registrada e consulta de progresso | 200 |
| Usuário ou curso inativo | 422 |
| Atividade que não pertence ao curso da matrícula | 422 |
| Payload inválido (`user_id` ausente ou inexistente) | 422 |
| Matrícula duplicada | 409 |
| Usuário, curso, atividade ou matrícula não encontrados | 404 |

**409 para duplicidade** porque a requisição é válida e o conflito está no estado atual do recurso, não no corpo enviado.

**422 para atividade de outro curso** porque a atividade existe e o problema é a combinação com a matrícula.

Erros de domínio devolvem apenas `message`; erros de validação seguem o formato padrão do Laravel, com `message` e `errors` por campo.

## Organização

| Caminho | Responsabilidade |
|---|---|
| `app/Services/` | Regras de negócio |
| `app/Exceptions/` | Exceções de domínio, sem conhecimento de HTTP |
| `app/Http/Controllers/` | Recebem a requisição, chamam o service, devolvem JSON |
| `app/Http/Requests/` | Validação de entrada |
| `bootstrap/app.php` | Tradução das exceções de domínio para códigos HTTP |
| `database/migrations/` | Schema |
| `database/seeders/` | Cenários do enunciado |
| `tests/Feature/` | Testes de serviço e de endpoint |

## Decisões

**Regras em `app/Services/`.** O controller recebe a requisição, chama o service e devolve JSON. Assim as regras não dependem de HTTP e podem ser testadas sem simular requisição.

**As exceções de domínio não conhecem HTTP.** Elas carregam apenas a mensagem. A tradução para código HTTP acontece em um único lugar, no `bootstrap/app.php`.

**Matrícula duplicada e conclusão repetida são impedidas pelo banco.** Os índices únicos em `enrollments` (`user_id`, `course_id`) e `activity_completions` (`enrollment_id`, `activity_id`) cobrem requisições simultâneas. Na matrícula, a segunda tentativa é barrada e o service devolve 409. Na conclusão o comportamento é idempotente: devolve 200 sem duplicar o registro nem alterar a data original, porque a data marca quando a atividade foi concluída e não a última tentativa. São respostas diferentes porque matricular é criar um recurso, e o conflito com o estado atual merece 409, enquanto concluir é registrar que a atividade está feita, e repetir a chamada apenas confirma o que já está feito.

**Cancelamento de matrícula está fora do escopo.** Por isso uma matrícula por usuário e curso é suficiente e o índice único simples resolve. Se houvesse cancelamento, esse índice bloquearia a rematrícula, e seria preciso uma coluna `status` e uma coluna anulável `active_flag` para manter a unicidade entre as matrículas ativas.

**`completed_at` não é mass assignable**, nem na matrícula nem na conclusão. A data é sempre definida pelo service. Se não fosse, `completed_at` no payload poderia gravar uma conclusão que não aconteceu.

**A conclusão da atividade roda em transação.** O `complete()` grava a conclusão e, quando é a última obrigatória, também grava a data de conclusão da matrícula. As duas escritas acontecem em conjunto para evitar que a atividade fique concluída sem a data de conclusão na matrícula. A matrícula também é travada com `lockForUpdate()` no início da transação: sem isso, duas requisições concluindo as últimas obrigatórias ao mesmo tempo contariam o progresso sem enxergar a inserção uma da outra, e nenhuma gravaria a data.

**O progresso é contado no banco.** `ProgressService` usa `COUNT` em vez de carregar as atividades para contar na memória. Para uma leitura individual são poucas consultas indexadas; em uma listagem de matrículas isso viraria uma consulta por matrícula, e as contagens precisariam vir na mesma consulta da listagem (subselect ou join).

**Curso sem atividades obrigatórias fica em 0% e não registra data de conclusão.** Nesse caso não há percentual a calcular (divisão por zero). Escolhi 0% porque 100% marcaria como concluída uma matrícula recém-criada e esconderia um curso mal configurado.

**Idioma.** Mensagens de erro em português. Código, identificadores e commits em inglês.
