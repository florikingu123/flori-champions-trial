-- Create family_events table if it doesn't exist
CREATE TABLE IF NOT EXISTS family_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manager_email VARCHAR(255) NOT NULL,
    event_title VARCHAR(255) NOT NULL,
    event_description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    event_type ENUM('school', 'busy', 'other') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_email) REFERENCES users(email) ON DELETE CASCADE
);

-- Drop existing family_chat table if it exists
DROP TABLE IF EXISTS family_chat;

-- Create family_chat table with correct structure
CREATE TABLE family_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add manager_email column to family table if it doesn't exist
ALTER TABLE family
ADD COLUMN IF NOT EXISTS manager_email VARCHAR(255),
ADD FOREIGN KEY (manager_email) REFERENCES users(email) ON DELETE CASCADE; 