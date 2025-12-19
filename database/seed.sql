-- Insert Default Admin User
-- Password: Admin123

INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`, `created_at`) 
VALUES ('admin', 'admin@pultech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin PulTech', 'admin', 1, NOW());

-- You can also add a test user
-- Password: User1234
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`, `created_at`) 
VALUES ('testuser', 'testuser@pultech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'user', 1, NOW());
