-- Phase 2V.1 Admin manage_roles alignment rollback.
-- Removes only the admin -> manage_roles capability mapping added by the up
-- script. Role, capability, user-role, and Root Admin rows are not changed.

DELETE rc
FROM `youngo_role_capabilities` rc
INNER JOIN `youngo_roles` r ON r.`id` = rc.`role_id`
INNER JOIN `youngo_capabilities` c ON c.`id` = rc.`capability_id`
WHERE r.`role_key` = 'admin'
  AND c.`capability_key` = 'manage_roles';
