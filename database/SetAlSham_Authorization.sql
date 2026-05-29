USE SetAlShamDB;

-- 1. Create two roles
CREATE ROLE IF NOT EXISTS manager_role, waiter_role;

-- 2. Grant privileges to each role
-- Manager: broad access across the whole database
GRANT SELECT, INSERT, UPDATE ON SetAlShamDB.* TO manager_role;

-- Waiter: can read the menu and take orders only (no Payments, no Employees)
GRANT SELECT ON SetAlShamDB.MenuItems  TO waiter_role;
GRANT INSERT ON SetAlShamDB.Orders     TO waiter_role;
GRANT INSERT ON SetAlShamDB.OrderItems TO waiter_role;

-- 3. Create demo login users and assign each a role
CREATE USER IF NOT EXISTS 'alice_manager'@'localhost' IDENTIFIED BY 'pass123';
CREATE USER IF NOT EXISTS 'bob_waiter'@'localhost'    IDENTIFIED BY 'pass123';

GRANT manager_role TO 'alice_manager'@'localhost';
GRANT waiter_role  TO 'bob_waiter'@'localhost';

-- 4. Activate the role automatically at login.
SET DEFAULT ROLE manager_role TO 'alice_manager'@'localhost';
SET DEFAULT ROLE waiter_role  TO 'bob_waiter'@'localhost';

-- 5. WITH GRANT OPTION: let the manager pass a privilege on to other users
GRANT SELECT ON SetAlShamDB.* TO 'alice_manager'@'localhost' WITH GRANT OPTION;

-- 6. REVOKE: take a privilege back from a role.
REVOKE INSERT ON SetAlShamDB.* FROM manager_role;


-- 7. ADDITIONAL ROLES — mirror the admin-panel roles at the database level (Cashier, Waiter already above). 

CREATE ROLE IF NOT EXISTS cashier_role;

-- Cashier: works with the sales tables only
GRANT SELECT, INSERT, UPDATE ON SetAlShamDB.Orders     TO cashier_role;
GRANT SELECT, INSERT, UPDATE ON SetAlShamDB.OrderItems TO cashier_role;
GRANT SELECT, INSERT          ON SetAlShamDB.Payments  TO cashier_role;
GRANT SELECT                  ON SetAlShamDB.Feedback  TO cashier_role;

-- Demo login for the cashier role
CREATE USER IF NOT EXISTS 'cara_cashier'@'localhost' IDENTIFIED BY 'pass123';
GRANT cashier_role TO 'cara_cashier'@'localhost';
SET DEFAULT ROLE cashier_role TO 'cara_cashier'@'localhost';

-- Quick check: see which roles a user has been granted
-- SHOW GRANTS FOR 'cara_cashier'@'localhost';
