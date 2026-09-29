# Respostas

## 3.1 Segurança e qualidade de código

O código tem quatro problemas sérios:

**SQL injection.** `$id` vem de `$_GET['id']`, ou seja, direto da URL (`pagina.php?id=...`), e é concatenado na query. Com `?id=1 OR 1=1` a consulta devolve todos os usuários. Correção: usar consulta parametrizada, passando o `id` como parâmetro. `$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?'); $stmt->bind_param('i', $id);`. No Laravel, Eloquent e o query builder já parametrizam as consultas, desde que o valor não seja concatenado dentro de um `DB::raw()` ou `whereRaw()`.

**XSS.** `$user['name']` e `$user['email']` são impressos sem escape, então um nome cadastrado como `<script>...</script>` executa no navegador de quem abrir a página. Correção: `htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8')` na saída, ou um template que escape por padrão, como o Blade com `{{ }}`.

**Credenciais no código.** Host, usuário, senha e banco estão escritos no arquivo, que provavelmente está no controle de versão, e a conexão usa `root`, que tem acesso total ao servidor de banco, quando a aplicação precisa apenas ler e escrever nas tabelas. Correção: configuração em variáveis de ambiente fora do repositório, e um usuário de banco com permissão apenas para o que a aplicação precisa.

**Nenhum tratamento de erro.** Se a query falha ou se o ID não existe, `$result->fetch_assoc()` devolve `null` e o código segue acessando `$user['name']`. Correção: verificar se o registro existe e responder 404, e não expor mensagem de erro do banco para o usuário.

## 3.2 Organização da regra de negócio

Coloquei a regra em `app/Services/EnrollmentService.php`, no método `enroll()`.

No controller, ela ficaria presa ao HTTP: para reaproveitar em um comando Artisan ou em uma importação eu teria que duplicar o código, e para testar precisaria montar uma requisição. Além disso, se mais regras forem adicionadas (curso lotado, período de inscrição, pré-requisito) o controller facilmente fica longo e complexo, dificultando a leitura e separação de responsabilidades.

A divisão que usei: o `FormRequest` valida o formato da entrada, o service decide se a matrícula pode existir e lança exceção de domínio quando não pode, e o controller só recebe, chama e devolve. A tradução da exceção para status HTTP fica no `bootstrap/app.php`, então o controller também não trata erro.

## 3.3 Concorrência e duplicidade

Usar índice único em `enrollments (user_id, course_id)`. Assim, as duas requisições passam pela validação da aplicação, uma é inserida porém a outra falha na constraint. No projeto, o service captura essa violação e devolve 409.

O índice resolve a corrida que criaria linha duplicada, mas não resolve tudo. A conclusão do curso depende de contar as atividades já concluídas, e duas requisições simultâneas não enxergam a inserção uma da outra: as duas contam a menos e nenhuma grava a data de conclusão. Para esse caso travo a matrícula com `lockForUpdate()` no início da transação, serializando as conclusões da mesma matrícula.

Vale notar que helpers como `firstOrCreate` não substituem o índice: ele consulta, e se não achar tenta inserir e trata a violação da constraint, então continua dependendo dela.

## 3.4 Integração entre sistemas

A conclusão é gravada normalmente e o aviso ao outro sistema vira um job na fila, disparado só depois do commit para não avisar sobre algo que não foi salvo. O envio precisa ser idempotente, com um identificador do evento, porque uma retentativa pode chegar depois que o outro sistema já processou. Na prática, mando um `event_id` no payload, e do outro lado esse id é gravado numa tabela de eventos processados com índice único, o que descarta o reenvio.

Se o outro lado estiver fora do ar, o job falha e é tentado de novo com backoff; se as tentativas acabarem, ele fica registrado como falho para reprocessar depois, em vez de a informação se perder.

Para o aluno, a conclusão aparece na hora: o progresso já mostra a atividade concluída e a integração acontece em background sem ele perceber. Se o outro sistema emitisse algo visível, como um certificado, eu mostraria "em processamento" em vez de deixar a tela parada.

## 3.5 Alteração em curso já iniciado

Primeiro eu perguntaria se a mudança vale retroativamente ou só daqui para frente. Isso é decisão de negócio, e as duas levam a implementações diferentes.

Se vale só para quem entrar depois: quem já está matriculado continua com a exigência que tinha quando entrou. Nesse caso o conjunto de atividades obrigatórias precisa ficar registrado por matrícula, no momento da matrícula, ou o curso precisa ser versionado. O progresso passa a ser calculado sobre essa lista, não sobre as atividades atuais do curso.

Se é retroativa: o progresso é recalculado para todo mundo a partir das obrigatórias atuais. Quem está cursando vê o percentual cair, e quem já tinha concluído volta a ter atividade pendente, o que significa revisar a conclusão e avisar o aluno. O recálculo de todas as matrículas do curso rodaria em job, não na requisição do administrador.

Se a decisão fosse minha, eu aplicaria a nova exigência para quem ainda está cursando e manteria a conclusão de quem já terminou: certificado emitido não se revoga, e quem está no meio do curso ainda tem como cumprir.