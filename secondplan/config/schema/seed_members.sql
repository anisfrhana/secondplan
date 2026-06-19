INSERT INTO `users` (`email`, `password_hash`, `name`, `phone`, `position`, `status`, `email_verified`) VALUES
('ameer@secondplan.local', '$2y$10$1ywvWuYJBRP7PrfxVfUpvOOq.UPfgcT6Ij8HOLZVaimjUFjxO545G', 'Amir Mahyidin', NULL, 'Vocalist', 'active', TRUE),
('zimi@secondplan.local', '$2y$10$1ywvWuYJBRP7PrfxVfUpvOOq.UPfgcT6Ij8HOLZVaimjUFjxO545G', 'Zarimi Zahari', NULL, 'Guitarist', 'active', TRUE),
('fairuz@secondplan.local', '$2y$10$1ywvWuYJBRP7PrfxVfUpvOOq.UPfgcT6Ij8HOLZVaimjUFjxO545G', 'Ahmad Fairouz Mohamad', NULL, 'Bassist', 'active', TRUE),
('one@secondplan.local', '$2y$10$1ywvWuYJBRP7PrfxVfUpvOOq.UPfgcT6Ij8HOLZVaimjUFjxO545G', 'Wan Shahbaharom Niktah', NULL, 'Drummer', 'active', TRUE);

INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.user_id, r.role_id
FROM users u, roles r
WHERE u.email IN ('ameer@secondplan.local', 'zimi@secondplan.local', 'fairuz@secondplan.local', 'one@secondplan.local')
AND r.role_name = 'band_member';
