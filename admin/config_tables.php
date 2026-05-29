<?php
// ============================================================
// config_tables.php
// One definition per table. The generic engine (lib.php + list.php
// + form.php) reads this to render lists, build forms, and save data.
//
// Column "type" values:
//   pk       auto primary key (shown in lists, never in forms)
//   text / textarea / int / decimal / date / time / datetime
//   bool     -> checkbox (TINYINT/BOOLEAN)
//   enum     -> dropdown of fixed values (mirrors a CHECK constraint); needs 'opts'
//   fk       -> dropdown from another table; needs 'fk' => [table, pk, labelCols]
// Extra flags:
//   'req'=>true    required in the form
//   'form'=>false  shown in the list but NOT in the add/edit form (auto columns)
//   'money'=>true  format as AED currency in the list
// Per-table 'can' => ['add'=>..,'edit'=>..,'delete'=>..] limits operations.
// ============================================================

$TABLES = [

// ---------------- SALES ----------------
'orders' => [
    'label' => 'Orders', 'singular' => 'Order', 'table' => 'Orders', 'pk' => 'OrderID', 'group' => 'Sales',
    'cols' => [
        ['name'=>'OrderID','label'=>'#','type'=>'pk'],
        ['name'=>'CustomerID','label'=>'Customer','type'=>'fk','fk'=>['table'=>'Customers','pk'=>'CustomerID','labelCols'=>['FirstName','LastName']]],
        ['name'=>'EmployeeID','label'=>'Server','type'=>'fk','req'=>true,'fk'=>['table'=>'Employees','pk'=>'EmployeeID','labelCols'=>['FirstName','LastName']]],
        ['name'=>'TableID','label'=>'Table','type'=>'fk','fk'=>['table'=>'DiningTables','pk'=>'TableID','labelCols'=>['TableNumber']]],
        ['name'=>'OrderType','label'=>'Type','type'=>'enum','req'=>true,'opts'=>['Dine-in','Takeaway','Delivery']],
        ['name'=>'Status','label'=>'Status','type'=>'enum','req'=>true,'opts'=>['In Progress','Completed','Cancelled']],
        ['name'=>'TotalAmount','label'=>'Total','type'=>'decimal','money'=>true,'form'=>false],
        ['name'=>'OrderDateTime','label'=>'Placed','type'=>'datetime','form'=>false],
    ],
],
'orderitems' => [
    'label' => 'Order Items', 'singular' => 'Order Item', 'table' => 'OrderItems', 'pk' => 'OrderItemID', 'group' => 'Sales',
    'cols' => [
        ['name'=>'OrderItemID','label'=>'#','type'=>'pk'],
        ['name'=>'OrderID','label'=>'Order','type'=>'fk','req'=>true,'fk'=>['table'=>'Orders','pk'=>'OrderID','labelCols'=>['OrderID']]],
        ['name'=>'ItemID','label'=>'Menu Item','type'=>'fk','req'=>true,'fk'=>['table'=>'MenuItems','pk'=>'ItemID','labelCols'=>['ItemName']]],
        ['name'=>'Quantity','label'=>'Qty','type'=>'int','req'=>true],
        ['name'=>'Subtotal','label'=>'Subtotal','type'=>'decimal','req'=>true,'money'=>true],
    ],
],
'payments' => [
    'label' => 'Payments', 'singular' => 'Payment', 'table' => 'Payments', 'pk' => 'PaymentID', 'group' => 'Sales',
    'cols' => [
        ['name'=>'PaymentID','label'=>'#','type'=>'pk'],
        ['name'=>'OrderID','label'=>'Order','type'=>'fk','req'=>true,'fk'=>['table'=>'Orders','pk'=>'OrderID','labelCols'=>['OrderID']]],
        ['name'=>'PaymentMethod','label'=>'Method','type'=>'enum','req'=>true,'opts'=>['Cash','Card','Online']],
        ['name'=>'AmountPaid','label'=>'Amount','type'=>'decimal','req'=>true,'money'=>true],
        ['name'=>'PaymentDate','label'=>'Date','type'=>'datetime','form'=>false],
    ],
],
'feedback' => [
    'label' => 'Feedback', 'singular' => 'Feedback', 'table' => 'Feedback', 'pk' => 'FeedbackID', 'group' => 'Sales',
    'cols' => [
        ['name'=>'FeedbackID','label'=>'#','type'=>'pk'],
        ['name'=>'CustomerID','label'=>'Customer','type'=>'fk','req'=>true,'fk'=>['table'=>'Customers','pk'=>'CustomerID','labelCols'=>['FirstName','LastName']]],
        ['name'=>'OrderID','label'=>'Order','type'=>'fk','req'=>true,'fk'=>['table'=>'Orders','pk'=>'OrderID','labelCols'=>['OrderID']]],
        ['name'=>'Rating','label'=>'Rating','type'=>'int','req'=>true],
        ['name'=>'Comment','label'=>'Comment','type'=>'textarea'],
        ['name'=>'ReviewDate','label'=>'Date','type'=>'datetime','form'=>false],
    ],
],

// ---------------- FRONT OF HOUSE ----------------
'reservations' => [
    'label' => 'Reservations', 'singular' => 'Reservation', 'table' => 'Reservations', 'pk' => 'ReservationID', 'group' => 'Front of House',
    'cols' => [
        ['name'=>'ReservationID','label'=>'#','type'=>'pk'],
        ['name'=>'CustomerID','label'=>'Customer','type'=>'fk','req'=>true,'fk'=>['table'=>'Customers','pk'=>'CustomerID','labelCols'=>['FirstName','LastName']]],
        ['name'=>'TableID','label'=>'Table','type'=>'fk','req'=>true,'fk'=>['table'=>'DiningTables','pk'=>'TableID','labelCols'=>['TableNumber']]],
        ['name'=>'ResDate','label'=>'Date','type'=>'date','req'=>true],
        ['name'=>'ResTime','label'=>'Time','type'=>'time','req'=>true],
        ['name'=>'GuestCount','label'=>'Guests','type'=>'int','req'=>true],
    ],
],
'diningtables' => [
    'label' => 'Dining Tables', 'singular' => 'Dining Table', 'table' => 'DiningTables', 'pk' => 'TableID', 'group' => 'Front of House',
    'cols' => [
        ['name'=>'TableID','label'=>'#','type'=>'pk'],
        ['name'=>'TableNumber','label'=>'Table No.','type'=>'int','req'=>true],
        ['name'=>'Capacity','label'=>'Capacity','type'=>'int','req'=>true],
        ['name'=>'Status','label'=>'Status','type'=>'enum','req'=>true,'opts'=>['Available','Occupied','Reserved','Maintenance']],
    ],
],

// ---------------- MENU ----------------
'categories' => [
    'label' => 'Categories', 'singular' => 'Category', 'table' => 'Categories', 'pk' => 'CategoryID', 'group' => 'Menu',
    'cols' => [
        ['name'=>'CategoryID','label'=>'#','type'=>'pk'],
        ['name'=>'CategoryName','label'=>'Name','type'=>'text','req'=>true],
        ['name'=>'Description','label'=>'Description','type'=>'textarea'],
    ],
],
'menuitems' => [
    'label' => 'Menu Items', 'singular' => 'Menu Item', 'table' => 'MenuItems', 'pk' => 'ItemID', 'group' => 'Menu',
    'cols' => [
        ['name'=>'ItemID','label'=>'#','type'=>'pk'],
        ['name'=>'CategoryID','label'=>'Category','type'=>'fk','req'=>true,'fk'=>['table'=>'Categories','pk'=>'CategoryID','labelCols'=>['CategoryName']]],
        ['name'=>'ItemName','label'=>'Name','type'=>'text','req'=>true],
        ['name'=>'BasePrice','label'=>'Price','type'=>'decimal','req'=>true,'money'=>true],
        ['name'=>'ItemType','label'=>'Type','type'=>'enum','req'=>true,'opts'=>['Main','Beverage','Dessert','Combo','Add-on']],
        ['name'=>'IsAvailable','label'=>'Available','type'=>'bool'],
    ],
],

// ---------------- PEOPLE ----------------
'customers' => [
    'label' => 'Customers', 'singular' => 'Customer', 'table' => 'Customers', 'pk' => 'CustomerID', 'group' => 'People',
    'cols' => [
        ['name'=>'CustomerID','label'=>'#','type'=>'pk'],
        ['name'=>'FirstName','label'=>'First Name','type'=>'text','req'=>true],
        ['name'=>'LastName','label'=>'Last Name','type'=>'text','req'=>true],
        ['name'=>'Phone','label'=>'Phone','type'=>'text','req'=>true],
        ['name'=>'Email','label'=>'Email','type'=>'text'],
    ],
],
'loyaltyaccounts' => [
    'label' => 'Loyalty Accounts', 'singular' => 'Loyalty Account', 'table' => 'LoyaltyAccounts', 'pk' => 'AccountID', 'group' => 'People',
    'cols' => [
        ['name'=>'AccountID','label'=>'#','type'=>'pk'],
        ['name'=>'CustomerID','label'=>'Customer','type'=>'fk','req'=>true,'fk'=>['table'=>'Customers','pk'=>'CustomerID','labelCols'=>['FirstName','LastName']]],
        ['name'=>'PointsBalance','label'=>'Points','type'=>'int'],
    ],
],
'employees' => [
    'label' => 'Employees', 'singular' => 'Employee', 'table' => 'Employees', 'pk' => 'EmployeeID', 'group' => 'People',
    'cols' => [
        ['name'=>'EmployeeID','label'=>'#','type'=>'pk'],
        ['name'=>'FirstName','label'=>'First Name','type'=>'text','req'=>true],
        ['name'=>'LastName','label'=>'Last Name','type'=>'text','req'=>true],
        ['name'=>'Role','label'=>'Role','type'=>'enum','req'=>true,'opts'=>['Manager','Cashier','Waiter','Chef']],
        ['name'=>'Phone','label'=>'Phone','type'=>'text','req'=>true],
    ],
],
'shifts' => [
    'label' => 'Shifts', 'singular' => 'Shift', 'table' => 'Shifts', 'pk' => 'ShiftID', 'group' => 'People',
    'cols' => [
        ['name'=>'ShiftID','label'=>'#','type'=>'pk'],
        ['name'=>'EmployeeID','label'=>'Employee','type'=>'fk','req'=>true,'fk'=>['table'=>'Employees','pk'=>'EmployeeID','labelCols'=>['FirstName','LastName']]],
        ['name'=>'ShiftDate','label'=>'Date','type'=>'date','req'=>true],
        ['name'=>'StartTime','label'=>'Start','type'=>'time','req'=>true],
        ['name'=>'EndTime','label'=>'End','type'=>'time','req'=>true],
    ],
],

// ---------------- SUPPLY CHAIN ----------------
'suppliers' => [
    'label' => 'Suppliers', 'singular' => 'Supplier', 'table' => 'Suppliers', 'pk' => 'SupplierID', 'group' => 'Supply Chain',
    'cols' => [
        ['name'=>'SupplierID','label'=>'#','type'=>'pk'],
        ['name'=>'SupplierName','label'=>'Name','type'=>'text','req'=>true],
        ['name'=>'ContactPerson','label'=>'Contact','type'=>'text'],
        ['name'=>'Phone','label'=>'Phone','type'=>'text','req'=>true],
    ],
],
'ingredients' => [
    'label' => 'Ingredients', 'singular' => 'Ingredient', 'table' => 'Ingredients', 'pk' => 'IngredientID', 'group' => 'Supply Chain',
    'cols' => [
        ['name'=>'IngredientID','label'=>'#','type'=>'pk'],
        ['name'=>'Name','label'=>'Name','type'=>'text','req'=>true],
        ['name'=>'UnitOfMeasure','label'=>'Unit','type'=>'text','req'=>true],
    ],
],
'inventory' => [
    'label' => 'Inventory', 'singular' => 'Inventory Item', 'table' => 'Inventory', 'pk' => 'InventoryID', 'group' => 'Supply Chain',
    'cols' => [
        ['name'=>'InventoryID','label'=>'#','type'=>'pk'],
        ['name'=>'IngredientID','label'=>'Ingredient','type'=>'fk','req'=>true,'fk'=>['table'=>'Ingredients','pk'=>'IngredientID','labelCols'=>['Name']]],
        ['name'=>'StockQuantity','label'=>'In Stock','type'=>'decimal','req'=>true],
        ['name'=>'ReorderLevel','label'=>'Reorder At','type'=>'decimal','req'=>true],
        ['name'=>'LastUpdated','label'=>'Updated','type'=>'datetime','form'=>false],
    ],
],
'supplierorders' => [
    'label' => 'Supplier Orders', 'singular' => 'Supplier Order', 'table' => 'SupplierOrders', 'pk' => 'SupplierOrderID', 'group' => 'Supply Chain',
    'cols' => [
        ['name'=>'SupplierOrderID','label'=>'#','type'=>'pk'],
        ['name'=>'SupplierID','label'=>'Supplier','type'=>'fk','req'=>true,'fk'=>['table'=>'Suppliers','pk'=>'SupplierID','labelCols'=>['SupplierName']]],
        ['name'=>'TotalCost','label'=>'Cost','type'=>'decimal','req'=>true,'money'=>true],
        ['name'=>'Status','label'=>'Status','type'=>'enum','req'=>true,'opts'=>['Pending','Delivered','Cancelled']],
        ['name'=>'OrderDate','label'=>'Ordered','type'=>'datetime','form'=>false],
    ],
],

// ---------------- LOGS ----------------
'paymentauditlog' => [
    'label' => 'Payment Audit Log', 'singular' => 'Log Entry', 'table' => 'PaymentAuditLog', 'pk' => 'LogID', 'group' => 'Logs',
    'can' => ['add'=>false, 'edit'=>false, 'delete'=>true],   // auto-filled by a trigger
    'cols' => [
        ['name'=>'LogID','label'=>'#','type'=>'pk'],
        ['name'=>'OrderID','label'=>'Order','type'=>'int'],
        ['name'=>'AmountPaid','label'=>'Amount','type'=>'decimal','money'=>true],
        ['name'=>'LogTime','label'=>'Logged','type'=>'datetime','form'=>false],
    ],
],

// ---------------- ACCESS (admin accounts) ----------------
'adminusers' => [
    'label' => 'Admin Users', 'singular' => 'Admin User', 'table' => 'AdminUsers', 'pk' => 'AdminID', 'group' => 'Access',
    'cols' => [
        ['name'=>'AdminID','label'=>'#','type'=>'pk'],
        ['name'=>'Username','label'=>'Username','type'=>'text','req'=>true],
        ['name'=>'Role','label'=>'Role','type'=>'enum','req'=>true,'opts'=>['Admin','Manager','Cashier','Waiter']],
        ['name'=>'EmployeeID','label'=>'Linked Employee','type'=>'fk','fk'=>['table'=>'Employees','pk'=>'EmployeeID','labelCols'=>['FirstName','LastName']]],
        // NewPassword is a virtual field: typed in the form, hashed on save (see lib.php).
        ['name'=>'NewPassword','label'=>'Set / Change Password','type'=>'password','list'=>false],
        ['name'=>'CreatedAt','label'=>'Created','type'=>'datetime','form'=>false],
    ],
],

];
