# Sistema de Transações Bancárias - Controle de Transações em Banco de Dados Relacional

## Visão Geral

Este projeto demonstra os conceitos de **sistemas transacionais** e **controle de transações** em bancos de dados relacionais, utilizando o MySQL como sistema de gerenciamento de banco de dados. O sistema simula transferências de dinheiro entre contas bancárias, permitindo que as transações sejam executadas com os devidos controles, como a garantia de que as transferências ocorram de forma atômica e que possíveis deadlocks sejam tratados. O objetivo deste projeto é mostrar como as transações em bancos de dados relacionais funcionam e como gerenciar operações concorrentes.

## Conceitos Principais

### Gerenciamento de Transações
Uma **transação** é uma sequência de operações realizadas como uma única unidade lógica de trabalho. Neste projeto, as transações são usadas para transferir dinheiro entre contas bancárias, garantindo as seguintes propriedades:
- **Atomicidade**: Uma transação é ou totalmente concluída ou não é executada.
- **Consistência**: O banco de dados transita de um estado válido para outro, assegurando que todas as restrições de integridade sejam atendidas.
- **Isolamento**: As transações são isoladas umas das outras, ou seja, a execução de uma transação não afeta a execução de outra.
- **Durabilidade**: Uma vez que uma transação é confirmada (commit), as alterações se tornam permanentes, mesmo em caso de falha do sistema.

### Controle de Transações no MySQL
O projeto implementa várias técnicas do MySQL para lidar com transações:
- **Locking (Bloqueio)**: Para evitar a corrupção de dados, utiliza-se `SELECT ... FOR UPDATE` para bloquear as linhas necessárias durante uma transação, garantindo que nenhuma outra transação interfira na operação.
- **Prevenção de Deadlock**: É criado um cenário onde duas transações podem causar um deadlock ao bloquearem os mesmos recursos em ordens diferentes. Isso demonstra como os deadlocks podem ocorrer e como são detectados e resolvidos automaticamente pelo MySQL.

### Exemplo de Transação: Transferência de Dinheiro
Neste projeto, é implementada uma procedure para simular o processo de transferência de dinheiro de uma conta para outra. A procedure inclui os seguintes passos:
1. Verificar se o remetente possui fundos suficientes.
2. Subtrair o valor da transferência da conta do remetente.
3. Adicionar o valor da transferência à conta do destinatário.
4. Registrar a transação em uma tabela de transações.
5. Tratar quaisquer erros ou casos extremos, como fundos insuficientes.

### Esquema do Banco de Dados
O projeto utiliza duas tabelas para simular as contas e as transações do banco:
- **account**: Armazena detalhes da conta, como ID da conta, nome do titular da conta e saldo.
- **transaction**: Registra cada transferência de dinheiro, incluindo o ID do remetente, o ID do destinatário, o valor transferido e a data/hora da transação.

### Cenário de Deadlock
Um deadlock pode ocorrer quando duas transações tentam atualizar os mesmos registros em ordem inversa. Este projeto demonstra como os deadlocks podem acontecer e como o MySQL os trata, revertendo (rollback) uma das transações e permitindo que a outra prossiga.

## Tecnologias Utilizadas
- **MySQL**: Para o sistema de gerenciamento de banco de dados relacional.
- **PHP**: Para a interface gráfica (GUI) que interage com o banco de dados e visualiza as transações.
- **HTML/CSS**: Para a construção da interface front-end.
- **JavaScript**: Para os recursos dinâmicos na interface gráfica.

A interface em PHP se conectará ao banco de dados MySQL e executará as procedures de transação, facilitando a demonstração do sistema durante a apresentação.
