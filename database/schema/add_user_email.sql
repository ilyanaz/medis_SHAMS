ALTER TABLE users
    ADD COLUMN email VARCHAR(150) NOT NULL DEFAULT 'admin@medisshams.test' AFTER username,
    ADD UNIQUE KEY email (email);

UPDATE users
SET email = CASE user_id
    WHEN 1 THEN 'admin@medisshams.test'
    ELSE CONCAT('user', user_id, '@medisshams.test')
END
WHERE email IS NULL OR email = '' OR email = 'admin@medisshams.test';
