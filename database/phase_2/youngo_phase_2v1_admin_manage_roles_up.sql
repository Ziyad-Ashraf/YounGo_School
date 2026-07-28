-- Phase 2V.1 Admin manage_roles alignment.
-- Owner decision: YounGo Admin is the client operational owner and may manage
-- role assignments for non-root users. Root Admin remains separately protected
-- by application logic.

INSERT INTO `youngo_role_capabilities` (`role_id`, `capability_id`)
SELECT r.`id`, c.`id`
FROM `youngo_roles` r
INNER JOIN `youngo_capabilities` c ON c.`capability_key` = 'manage_roles'
WHERE r.`role_key` = 'admin'
  AND NOT EXISTS (
    SELECT 1
    FROM `youngo_role_capabilities` existing
    WHERE existing.`role_id` = r.`id`
      AND existing.`capability_id` = c.`id`
  );
