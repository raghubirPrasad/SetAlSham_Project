USE SetAlShamDB;

-- ============================================================
-- AdminUsers — login accounts for the admin panel.
-- Each account has a role (controls what they can see/do in the
-- panel) and may be linked to a real Employees row.
-- Passwords are stored HASHED (filled in by setup_admins.php,
-- not in plain text here).
-- ============================================================
DROP TABLE IF EXISTS AdminUsers;

CREATE TABLE AdminUsers (
    AdminID      INT AUTO_INCREMENT PRIMARY KEY,
    Username     VARCHAR(50)  NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role         VARCHAR(20)  NOT NULL CHECK (Role IN ('Admin','Manager','Cashier','Waiter')),
    EmployeeID   INT,
    CreatedAt    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE SET NULL
);

-- Seed accounts are created by running:  php setup_admins.php
-- (that script hashes the passwords correctly for your machine)
