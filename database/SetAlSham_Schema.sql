-- Create the database
CREATE DATABASE IF NOT EXISTS SetAlShamDB;
USE SetAlShamDB;

-- Drop existing tables to ensure a clean slate
DROP TABLE IF EXISTS Feedback;
DROP TABLE IF EXISTS Payments;
DROP TABLE IF EXISTS OrderItems;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Reservations;
DROP TABLE IF EXISTS LoyaltyAccounts;
DROP TABLE IF EXISTS Customers;
DROP TABLE IF EXISTS Shifts;
DROP TABLE IF EXISTS Employees;
DROP TABLE IF EXISTS DiningTables;
DROP TABLE IF EXISTS MenuItems;
DROP TABLE IF EXISTS Categories;
DROP TABLE IF EXISTS Inventory;
DROP TABLE IF EXISTS SupplierOrders;
DROP TABLE IF EXISTS Ingredients;
DROP TABLE IF EXISTS Suppliers;

CREATE TABLE Customers (
    CustomerID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Phone VARCHAR(15) UNIQUE NOT NULL, 
    Email VARCHAR(100) UNIQUE
);

CREATE TABLE LoyaltyAccounts (
    AccountID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL UNIQUE,
    PointsBalance INT DEFAULT 0 CHECK (PointsBalance >= 0),
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE CASCADE
);

CREATE TABLE DiningTables (
    TableID INT AUTO_INCREMENT PRIMARY KEY,
    TableNumber INT UNIQUE NOT NULL,
    Capacity INT NOT NULL CHECK (Capacity > 0),
    Status VARCHAR(20) DEFAULT 'Available' CHECK (Status IN ('Available', 'Occupied', 'Reserved', 'Maintenance'))
);

CREATE TABLE Reservations (
    ReservationID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    TableID INT NOT NULL,
    ResDate DATE NOT NULL,
    ResTime TIME NOT NULL,
    GuestCount INT NOT NULL CHECK (GuestCount > 0),
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE CASCADE,
    FOREIGN KEY (TableID) REFERENCES DiningTables(TableID) ON DELETE RESTRICT
);

CREATE TABLE Employees (
    EmployeeID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Role VARCHAR(30) NOT NULL CHECK (Role IN ('Manager', 'Cashier', 'Waiter', 'Chef')),
    Phone VARCHAR(15) UNIQUE NOT NULL
);

CREATE TABLE Shifts (
    ShiftID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    ShiftDate DATE NOT NULL,
    StartTime TIME NOT NULL,
    EndTime TIME NOT NULL,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
);

CREATE TABLE Categories (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(50) UNIQUE NOT NULL,
    Description TEXT
);

CREATE TABLE MenuItems (
    ItemID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryID INT NOT NULL,
    ItemName VARCHAR(100) NOT NULL,
    BasePrice DECIMAL(8, 2) NOT NULL CHECK (BasePrice >= 0),
    ItemType VARCHAR(30) NOT NULL CHECK (ItemType IN ('Main', 'Beverage', 'Dessert', 'Combo', 'Add-on')),
    IsAvailable BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (CategoryID) REFERENCES Categories(CategoryID) ON DELETE RESTRICT
);

CREATE TABLE Orders (
    OrderID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT,
    EmployeeID INT NOT NULL,
    TableID INT,
    OrderType VARCHAR(20) NOT NULL CHECK (OrderType IN ('Dine-in', 'Takeaway', 'Delivery')),
    OrderDateTime DATETIME DEFAULT CURRENT_TIMESTAMP,
    TotalAmount DECIMAL(10, 2) DEFAULT 0.00,
    Status VARCHAR(20) DEFAULT 'In Progress' CHECK (Status IN ('In Progress', 'Completed', 'Cancelled')),
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE SET NULL,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE RESTRICT,
    FOREIGN KEY (TableID) REFERENCES DiningTables(TableID) ON DELETE SET NULL
);

CREATE TABLE OrderItems (
    OrderItemID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL,
    ItemID INT NOT NULL,
    Quantity INT NOT NULL CHECK (Quantity > 0),
    Subtotal DECIMAL(10, 2) NOT NULL CHECK (Subtotal >= 0),
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES MenuItems(ItemID) ON DELETE RESTRICT
);

CREATE TABLE Payments (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL UNIQUE,
    PaymentMethod VARCHAR(20) NOT NULL CHECK (PaymentMethod IN ('Cash', 'Card', 'Online')),
    AmountPaid DECIMAL(10, 2) NOT NULL CHECK (AmountPaid > 0),
    PaymentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE
);

CREATE TABLE Feedback (
    FeedbackID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    OrderID INT NOT NULL UNIQUE,
    Rating INT NOT NULL CHECK (Rating BETWEEN 1 AND 5),
    Comment TEXT,
    ReviewDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE CASCADE,
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE
);

CREATE TABLE Suppliers (
    SupplierID INT AUTO_INCREMENT PRIMARY KEY,
    SupplierName VARCHAR(100) NOT NULL UNIQUE,
    ContactPerson VARCHAR(50),
    Phone VARCHAR(15) UNIQUE NOT NULL
);

CREATE TABLE Ingredients (
    IngredientID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL UNIQUE,
    UnitOfMeasure VARCHAR(20) NOT NULL
);

CREATE TABLE Inventory (
    InventoryID INT AUTO_INCREMENT PRIMARY KEY,
    IngredientID INT NOT NULL UNIQUE,
    StockQuantity DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    ReorderLevel DECIMAL(10, 2) NOT NULL DEFAULT 10.00,
    LastUpdated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (IngredientID) REFERENCES Ingredients(IngredientID) ON DELETE CASCADE
);

CREATE TABLE SupplierOrders (
    SupplierOrderID INT AUTO_INCREMENT PRIMARY KEY,
    SupplierID INT NOT NULL,
    OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    TotalCost DECIMAL(10, 2) NOT NULL CHECK (TotalCost >= 0),
    Status VARCHAR(20) DEFAULT 'Pending' CHECK (Status IN ('Pending', 'Delivered', 'Cancelled')),
    FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID) ON DELETE RESTRICT
);
