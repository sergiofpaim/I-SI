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