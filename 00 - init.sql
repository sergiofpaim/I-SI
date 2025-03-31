CREATE TABLE account (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(50) NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 200.00,
    UNIQUE (name)
) ENGINE=InnoDB;

INSERT INTO account(name, password, balance) VALUES
  ('Dionisio', '8cb2237d0679ca88db6464eac60da96345513964', 20.0),
  ('João Pedro', '8cb2237d0679ca88db6464eac60da96345513964', 1500.0);

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
