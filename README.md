# Bank Transaction System - Relational Database Transaction Control

## Overview

This project demonstrates the concepts of **transactional systems** and **transaction control** in relational databases, using MySQL as the database management system. The system simulates money transfers between bank accounts, allowing for transactions to be executed with proper controls, such as ensuring that money transfers happen atomically and that potential deadlocks are handled. This project aims to showcase how transactions in relational databases work and how to manage concurrent operations.

## Key Concepts

### Transaction Management
A **transaction** is a sequence of operations performed as a single logical unit of work. In this project, transactions are used to transfer money between bank accounts while ensuring the following properties:
- **Atomicity**: A transaction is either fully completed or not executed at all.
- **Consistency**: The database transitions from one valid state to another, ensuring all integrity constraints are met.
- **Isolation**: Transactions are isolated from each other, meaning the execution of one transaction does not affect the execution of another.
- **Durability**: Once a transaction is committed, the changes are permanent, even in the event of a system failure.

### Transaction Control in MySQL
The project implements various MySQL techniques for handling transactions:
- **Locking**: To prevent data corruption, we use `SELECT ... FOR UPDATE` to lock the necessary rows during a transaction, ensuring that no other transactions can interfere with the operation.
- **Deadlock Prevention**: A scenario is created where two transactions can cause a deadlock by locking the same resources in different orders. This demonstrates how deadlocks can occur and how they are automatically detected and resolved by MySQL.

### Transaction Example: Money Transfer
In this project, a stored procedure is implemented to simulate the process of transferring money from one account to another. The procedure includes the following steps:
1. Check if the sender has sufficient funds.
2. Subtract the transfer amount from the sender's account.
3. Add the transfer amount to the receiver's account.
4. Log the transaction in a transaction table.
5. Handle any errors or edge cases, such as insufficient funds.

### Database Schema
The project uses two tables to simulate the bank's accounts and transactions:
- **account**: Stores account details such as account ID, account holder name, and balance.
- **transaction**: Logs each money transfer, including the sender's ID, receiver's ID, amount transferred, and timestamp.

### Deadlock Scenario
A deadlock can occur when two transactions attempt to update the same records in reverse order. This project demonstrates how deadlocks can happen and how MySQL handles them by rolling back one of the transactions, allowing the other to proceed.

## Technologies Used
- **MySQL**: For the relational database management system.
- **PHP**: For the graphical user interface (GUI) to interact with the database and visualize the transactions.
- **HTML/CSS**: For building the front-end interface.
- **JavaScript**: For dynamic features in the GUI.

  
The PHP interface will connect to the MySQL database and execute the transaction procedures, making it easier to demonstrate the system during the presentation.
