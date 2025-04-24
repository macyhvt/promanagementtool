-- Add columns for split functionality
ALTER TABLE orders_initial
ADD COLUMN parent_order_id INT DEFAULT NULL,
ADD COLUMN split_position INT DEFAULT NULL,
ADD FOREIGN KEY (parent_order_id) REFERENCES orders_initial(orderID) ON DELETE CASCADE; 