USE SetAlShamDB;

-- 1. SIMPLE SELECT QUERY: Affordable Main Dishes
SELECT ItemName, BasePrice FROM MenuItems WHERE ItemType = 'Main' AND BasePrice < 15.00 ORDER BY BasePrice ASC;

-- 2. MULTI-TABLE JOIN QUERY: Order Tracing
SELECT o.OrderID, c.FirstName AS CustomerName, e.FirstName AS ServerName, t.TableNumber, o.TotalAmount
FROM Orders o JOIN Customers c ON o.CustomerID = c.CustomerID JOIN Employees e ON o.EmployeeID = e.EmployeeID
LEFT JOIN DiningTables t ON o.TableID = t.TableID WHERE o.OrderType = 'Dine-in';

-- 3. AGGREGATE QUERY: Financial Health
SELECT COUNT(OrderID) AS TotalOrders, SUM(TotalAmount) AS TotalRevenue, AVG(TotalAmount) AS AverageOrderValue
FROM Orders WHERE Status = 'Completed';

-- 4. GROUP BY QUERY: Top Selling Items
SELECT m.ItemName, SUM(oi.Quantity) AS TotalQuantitySold FROM OrderItems oi
JOIN MenuItems m ON oi.ItemID = m.ItemID GROUP BY m.ItemName ORDER BY TotalQuantitySold DESC;

-- 5. HAVING QUERY: Highest Revenue Categories
SELECT c.CategoryName, SUM(oi.Subtotal) AS CategoryRevenue FROM Categories c
JOIN MenuItems m ON c.CategoryID = m.CategoryID JOIN OrderItems oi ON m.ItemID = oi.ItemID
GROUP BY c.CategoryName HAVING CategoryRevenue > 100.00 ORDER BY CategoryRevenue DESC;

-- 6. NESTED (SUBQUERY): High Value Customers
SELECT FirstName, LastName, Phone FROM Customers WHERE CustomerID IN (
SELECT CustomerID FROM Orders WHERE TotalAmount > (SELECT AVG(TotalAmount) FROM Orders));

-- 7. CORRELATED SUBQUERY: Premium Item Per Category
SELECT m1.ItemName, m1.BasePrice, c.CategoryName FROM MenuItems m1
JOIN Categories c ON m1.CategoryID = c.CategoryID WHERE m1.BasePrice = (
SELECT MAX(m2.BasePrice) FROM MenuItems m2 WHERE m2.CategoryID = m1.CategoryID);

-- 8. EXISTS QUERY: Validating Active Suppliers
SELECT SupplierName, ContactPerson, Phone FROM Suppliers s WHERE EXISTS (
SELECT 1 FROM SupplierOrders so WHERE so.SupplierID = s.SupplierID AND so.Status = 'Delivered');

-- 9. ANY / ALL QUERY: Premium Main Courses vs Beverages
SELECT ItemName, BasePrice FROM MenuItems WHERE ItemType = 'Main' AND BasePrice > ALL (
SELECT BasePrice FROM MenuItems WHERE ItemType = 'Beverage');

-- 10. VIEWS: Daily Managerial Dashboard
CREATE OR REPLACE VIEW DailySalesReport AS
SELECT DATE(OrderDateTime) AS SaleDate, COUNT(OrderID) AS TotalOrders, SUM(TotalAmount) AS DailyRevenue
FROM Orders WHERE Status = 'Completed' GROUP BY DATE(OrderDateTime);

-- 11. INDEX CREATION: Optimizing POS searches
CREATE INDEX idx_item_name ON MenuItems(ItemName);
CREATE INDEX idx_order_date ON Orders(OrderDateTime);

-- 12. SET OPERATION (INTERSECT): Customers who BOTH placed an order AND left feedback
SELECT CustomerID FROM Orders
INTERSECT
SELECT CustomerID FROM Feedback;

-- 13. SET OPERATION (EXCEPT): Menu items that have NEVER been sold ("dead" items)
SELECT ItemID FROM MenuItems
EXCEPT
SELECT ItemID FROM OrderItems;

-- 14. SET OPERATION (UNION): One combined contact directory of customers and suppliers
SELECT FirstName AS Name, Phone FROM Customers
UNION
SELECT SupplierName AS Name, Phone FROM Suppliers;

-- 15. NULL VALUES: Orders with no assigned table (Takeaway/Delivery have NULL TableID)
SELECT OrderID, OrderType, COALESCE(TableID, 0) AS TableNo
FROM Orders WHERE TableID IS NULL;

-- 16. TRANSACTION DEMO: place-order-and-pay as one atomic unit.
--     Shows START TRANSACTION, SAVEPOINT, UPDATE, DELETE, ROLLBACK.
--     Ends with ROLLBACK so the sample data is left unchanged on re-runs.
START TRANSACTION;

INSERT INTO Orders (CustomerID, EmployeeID, OrderType, Status)
VALUES (1, 1, 'Takeaway', 'In Progress');
SET @demo_order = LAST_INSERT_ID();

INSERT INTO OrderItems (OrderID, ItemID, Quantity, Subtotal)
VALUES (@demo_order, 1, 1, 11.00);

SAVEPOINT after_items;

INSERT INTO Payments (OrderID, PaymentMethod, AmountPaid)
VALUES (@demo_order, 'Cash', 11.00);

-- UPDATE example: mark the order completed
UPDATE Orders SET Status = 'Completed' WHERE OrderID = @demo_order;

-- DELETE example: remove the demo line item
DELETE FROM OrderItems WHERE OrderID = @demo_order;

-- Partial undo: discard everything done after the savepoint (the payment, update, delete)
ROLLBACK TO SAVEPOINT after_items;

-- In real use you would COMMIT here; we ROLLBACK to keep the sample data unchanged.
ROLLBACK;
