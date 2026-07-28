-- DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.1 rollback
-- Drops only the additive YounGo subscription plan translation table.
-- Operational subscription plans, slugs, prices, payment, checkout, and access tables are not changed.

DROP TABLE IF EXISTS `youngo_subscription_plan_translations`;
