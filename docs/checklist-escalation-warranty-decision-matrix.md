# Checklist, Escalation, and Warranty Decision Matrix

## Benchmark Snapshot
| System Pattern | Observed Practice | Adopted Decision |
| --- | --- | --- |
| CM systems with ticket templates | Category/queue specific templates with optional manual additions | Use category+department template fallback to category-only, allow manual additions |
| SLA monitoring | Time-window based automation for approaching/overdue tickets | Use `<=24h` and overdue windows with scheduled automation |
| Escalation controls | Dedupe windows and immutable audit logs | Keep 4-hour dedupe and write escalation records + ticket logs |
| Warranty handling | Separate claim lifecycle linked to tickets/assets | Add `tbl_warranty_claim` and `tbl_warranty_claim_history` |

## Decisions Implemented
1. Checklist mutation permissions: `technician`, `admin`, `department_head`.
2. Customer-facing checklist is read-only.
3. Checklist progress is exposed in detail and list views via batch endpoint.
4. Escalation status uses normalized values (`escalated`, `overdue`, `on-time`) and no ticket status overload.
5. Warranty flows use explicit claim states (`submitted`, `under_review`, `approved`, etc.) with transition history.

## Validation Targets
1. Ticket creation always produces checklist when matching template exists.
2. Manual checklist additions are tracked as `source_type='manual'`.
3. Approaching and overdue tickets are auto-escalated by scheduler path.
4. Warranty claim transitions are auditable and role-restricted.

