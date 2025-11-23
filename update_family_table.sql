-- First, let's fix the data inconsistencies
UPDATE family 
SET manager_email = managers_email 
WHERE manager_email IS NULL AND managers_email != '';

-- Now let's drop the duplicate column and constraints
ALTER TABLE family
DROP FOREIGN KEY family_ibfk_1,
DROP FOREIGN KEY family_ibfk_2,
DROP COLUMN managers_email;

-- Update the foreign key constraint to reference the correct column
ALTER TABLE family
ADD CONSTRAINT family_ibfk_1 
FOREIGN KEY (manager_email) 
REFERENCES users(email) 
ON DELETE CASCADE;

-- Clean up any empty or invalid records
DELETE FROM family 
WHERE member_email = '' 
OR member_name = '' 
OR member_pass = '';

-- Add some useful indexes
ALTER TABLE family
ADD INDEX idx_manager_email (manager_email),
ADD INDEX idx_member_email (member_email);

-- Add some constraints to ensure data integrity
ALTER TABLE family
MODIFY member_name VARCHAR(255) NOT NULL,
MODIFY member_email VARCHAR(255) NOT NULL,
MODIFY member_pass VARCHAR(255) NOT NULL,
MODIFY points INT(11) DEFAULT 0,
MODIFY manager_email VARCHAR(255) NOT NULL; 