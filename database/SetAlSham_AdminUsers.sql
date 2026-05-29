USE SetAlShamDB;
-- AdminUsers — login accounts for the admin panel.
-- Passwords are stored HASHED .

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


