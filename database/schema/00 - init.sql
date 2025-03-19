CREATE DATABASE bank_exercise;
USE bank_exercise;

CREATE TABLE account (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB;

INSERT INTO account(name, balance) VALUES
  ('Dionisio', 20.0),
  ('Sérgio', 500.0),
  ('Andre', 200.0),
  ('Caio', 1000.0),
  ('João Pedro', 1500.0);

SELECT * FROM account;

CREATE TABLE transaction (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (sender_id, receiver_id, created_at),
    FOREIGN KEY (sender_id) REFERENCES account(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES account(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DELIMITER $$

CREATE PROCEDURE TransferMoney(
    IN sender_account_id INT,
    IN receiver_account_id INT,
    IN transfer_amount DECIMAL(10,2)
)
BEGIN
    DECLARE sender_balance DECIMAL(10,2);

    SELECT balance INTO sender_balance
    FROM account
    WHERE id = sender_account_id;

    IF sender_balance >= transfer_amount THEN
        UPDATE account
        SET balance = balance - transfer_amount
        WHERE id = sender_account_id;

        UPDATE account
        SET balance = balance + transfer_amount
        WHERE id = receiver_account_id;

        INSERT INTO transaction (sender_id, receiver_id, amount)
        VALUES (sender_account_id, receiver_account_id, transfer_amount);

        SELECT 'Transfer Successful' AS message;
    ELSE
        SELECT 'Insufficient funds' AS message;
    END IF;
END $$

DELIMITER ;