USE SetAlShamDB;
DELIMITER //

-- STORED PROCEDURES
-- 1. Place Customer Order
CREATE PROCEDURE PlaceCustomerOrder(IN p_CustomerID INT, IN p_EmployeeID INT, IN p_TableID INT, IN p_OrderType VARCHAR(20), OUT p_OrderID INT)
BEGIN
    INSERT INTO Orders (CustomerID, EmployeeID, TableID, OrderType, Status) VALUES (p_CustomerID, p_EmployeeID, p_TableID, p_OrderType, 'In Progress');
    SET p_OrderID = LAST_INSERT_ID();
END //

-- 2. Generate Daily Sales Report
CREATE PROCEDURE GenerateDailySalesReport(IN p_ReportDate DATE)
BEGIN
    SELECT DATE(OrderDateTime) AS ReportDate, COUNT(OrderID) AS TotalOrders, SUM(TotalAmount) AS TotalRevenue FROM Orders WHERE DATE(OrderDateTime) = p_ReportDate AND Status = 'Completed';
END //

-- 3. Update Inventory After Supplier Order
CREATE PROCEDURE ReceiveSupplierOrder(IN p_SupplierOrderID INT, IN p_IngredientID INT, IN p_QuantityReceived DECIMAL(10,2))
BEGIN
    UPDATE SupplierOrders SET Status = 'Delivered' WHERE SupplierOrderID = p_SupplierOrderID;
    UPDATE Inventory SET StockQuantity = StockQuantity + p_QuantityReceived, LastUpdated = CURRENT_TIMESTAMP WHERE IngredientID = p_IngredientID;
END //

-- 4. Create Reservation
CREATE PROCEDURE CreateReservation(IN p_CustomerID INT, IN p_TableID INT, IN p_ResDate DATE, IN p_ResTime TIME, IN p_GuestCount INT)
BEGIN
    DECLARE v_Capacity INT;
    SELECT Capacity INTO v_Capacity FROM DiningTables WHERE TableID = p_TableID;
    IF p_GuestCount > v_Capacity THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: Guest count exceeds maximum table capacity.';
    ELSE INSERT INTO Reservations (CustomerID, TableID, ResDate, ResTime, GuestCount) VALUES (p_CustomerID, p_TableID, p_ResDate, p_ResTime, p_GuestCount);
    END IF;
END //

-- 5. Generate Monthly Revenue Summary
CREATE PROCEDURE GenerateMonthlyRevenue(IN p_Month INT, IN p_Year INT)
BEGIN
    SELECT YEAR(OrderDateTime) AS RevYear, MONTH(OrderDateTime) AS RevMonth, SUM(TotalAmount) AS TotalMonthlyRevenue FROM Orders WHERE MONTH(OrderDateTime) = p_Month AND YEAR(OrderDateTime) = p_Year AND Status = 'Completed';
END //

-- FUNCTIONS
-- 1. Calculate Loyalty Points
CREATE FUNCTION CalculateLoyaltyPoints(p_Amount DECIMAL(10,2)) RETURNS INT DETERMINISTIC
BEGIN
    RETURN FLOOR(p_Amount / 10);
END //

-- 2. Calculate Total Bill With Tax
CREATE FUNCTION CalculateTotalWithTax(p_OrderID INT) RETURNS DECIMAL(10,2) READS SQL DATA
BEGIN
    DECLARE v_Subtotal DECIMAL(10,2);
    SELECT TotalAmount INTO v_Subtotal FROM Orders WHERE OrderID = p_OrderID;
    RETURN v_Subtotal * 1.05;
END //

-- 3. Calculate Most Popular Category Revenue
CREATE FUNCTION GetCategoryRevenue(p_CategoryID INT) RETURNS DECIMAL(10,2) READS SQL DATA
BEGIN
    DECLARE v_Revenue DECIMAL(10,2);
    SELECT COALESCE(SUM(oi.Subtotal), 0) INTO v_Revenue FROM OrderItems oi JOIN MenuItems m ON oi.ItemID = m.ItemID WHERE m.CategoryID = p_CategoryID;
    RETURN v_Revenue;
END //

-- TRIGGERS
-- 1. Automatically reduce inventory after order item insertion
CREATE TRIGGER trg_ReduceInventory AFTER INSERT ON OrderItems FOR EACH ROW
BEGIN
    UPDATE Inventory SET StockQuantity = StockQuantity - (0.25 * NEW.Quantity) WHERE InventoryID = 1; 
END //

-- 2. Prevent negative stock updates
CREATE TRIGGER trg_PreventNegativeStock BEFORE UPDATE ON Inventory FOR EACH ROW
BEGIN
    IF NEW.StockQuantity < 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: Insufficient stock in inventory to fulfill this request.'; END IF;
END //

-- 3. Automatically update loyalty points after payment
CREATE TRIGGER trg_UpdateLoyaltyPoints AFTER INSERT ON Payments FOR EACH ROW
BEGIN
    DECLARE v_CustomerID INT;
    SELECT CustomerID INTO v_CustomerID FROM Orders WHERE OrderID = NEW.OrderID;
    IF v_CustomerID IS NOT NULL THEN UPDATE LoyaltyAccounts SET PointsBalance = PointsBalance + CalculateLoyaltyPoints(NEW.AmountPaid) WHERE CustomerID = v_CustomerID; END IF;
END //

-- 4. Prevent reservation conflicts
CREATE TRIGGER trg_PreventDoubleBooking BEFORE INSERT ON Reservations FOR EACH ROW
BEGIN
    DECLARE v_ConflictCount INT;
    SELECT COUNT(*) INTO v_ConflictCount FROM Reservations WHERE TableID = NEW.TableID AND ResDate = NEW.ResDate AND ResTime = NEW.ResTime;
    IF v_ConflictCount > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Booking Error: This dining table is already reserved at the requested date and time.'; END IF;
END //

-- 5. Log payment transactions
CREATE TABLE IF NOT EXISTS PaymentAuditLog (LogID INT AUTO_INCREMENT PRIMARY KEY, OrderID INT, AmountPaid DECIMAL(10,2), LogTime DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TRIGGER trg_AuditLargePayments AFTER INSERT ON Payments FOR EACH ROW
BEGIN
    IF NEW.AmountPaid > 250.00 THEN INSERT INTO PaymentAuditLog (OrderID, AmountPaid) VALUES (NEW.OrderID, NEW.AmountPaid); END IF;
END //

-- 6. Keep an order's total in sync whenever a line item is added
CREATE TRIGGER trg_UpdateOrderTotal AFTER INSERT ON OrderItems FOR EACH ROW
BEGIN
    UPDATE Orders SET TotalAmount = TotalAmount + NEW.Subtotal WHERE OrderID = NEW.OrderID;
END //
DELIMITER ;
