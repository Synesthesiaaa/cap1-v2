# Migration Scripts Archive

This directory contains one-time migration scripts that have been executed and are no longer needed in the active codebase.

## Scripts Archived

- `migrate_passwords.php` - Password hashing migration
- `migrate_department_heads_to_new_table.php` - Department head table migration
- `migrate_external_users_to_customer_department.php` - External user department migration
- `migrate_remove_evaluator_fields.php` - Evaluator field removal
- `migrate_remove_evaluator_role.php` - Evaluator role removal
- `migrate_product_tables.php` - Product table migration

## Note

These scripts are kept for reference only. They should NOT be run again as they modify database structure and data.

## Restoration

If you need to restore any of these scripts, copy them back to `php/` directory. However, running them again may cause errors if the migrations have already been applied.
