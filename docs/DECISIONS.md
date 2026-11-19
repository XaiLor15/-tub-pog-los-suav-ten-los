# Implementation decisions

The Dev Handoff targets React + Supabase. The project owner chose to build it on the same Laravel stack as
the existing **fdms** project, starting from a **fresh database** (no Legacy Phase 3 data was available).
This file records how each Supabase concept was implemented, and the defaults taken for open questions.

## Supabase → Laravel mapping

| Handoff (Supabase) | Here |
|---|---|
| `profiles` + `user_roles` | merged into `users` (`display_name`, `avatar_path`, `theme`, `font_scale`, `member_id`, `role` pending/member/admin, `approved_by/at`, `is_disabled`, `rejected_at`) |
| RLS policies | `access:` route middleware (guest/pending/member/admin) **and** a Gate per action in `AppServiceProvider`, checked inside every controller method. Hidden buttons are UX only. |
| `*_public` views (Guest/Pending) | `MemberPresenter` + Blade checks on `view-private`: birth **year** only, no phone, no village — also in search JSON, lineage JSON; CSV export is member-only |
| RPC (`reorder_children`, `unlink_from_father`, `create_member`, `get_family_tree`, `get_ancestors`, `get_descendant_ids`, `find_duplicate_members`) | `FamilyService` — each multi-step change in one DB transaction with sibling rows locked |
| RPC `record_/update_/delete_donation_payment`, `find_similar_transactions`, `v_finance_*`, `v_donation_*` | `FinanceService` |
| `approve_user`, `reject_user`, `protect_last_admin`, `prevent_self_role_change`, `match_pending_user` | `UserAdminService` |
| `audit_*` triggers | `AuditLogger` called from the services (member delete/unlink/reorder, role/approval, finance C/U/D, payments, pledges, fund, leadership, elders) |
| `block_donation_tx_direct_edit` | `FinanceService::assertManual()` + Finance UI shows "ແກ້ທີ່ໜ້າບໍລິຈາກ" instead of edit/delete |
| `validate_member_parents` trigger | `FamilyService::assertValidFather()` (male, not self, not a descendant) + Phaj = father + 1 with cascade to the subtree when a branch moves. DB CHECKs: `birth_order > 0`, `generation ≥ 1` (MySQL/MariaDB) |
| Supabase Storage buckets + policies | private `local` disk, streamed by `MediaController` after the same permission check as the page (private events / draft news return 404). Images re-encoded to WebP ≤ 1920 px (also strips EXIF/GPS). Donation slips: Admin only. |
| Realtime (`news`, `news_comments`, `events`, `transactions`) | notifications refresh every 60 s and on tab focus; pages read fresh data on each request. A websocket server (e.g. Laravel Reverb) can be added later if live updates are required. |
| pg_trgm search | `LIKE` on `name` / `hmong_name` (indexed). Fine for a clan-sized table; switch to MySQL FULLTEXT/ngram if it grows large. |
| pgTAP RLS tests | PHPUnit feature tests (`tests/Feature/AccessControlTest.php`, `AdminTest`, `FamilyTreeTest`, `FinanceTest`) |
| TanStack query invalidation | server-rendered pages; every mutation redirects back with a toast, so data is always fresh |
| Zod | Laravel validation with Lao messages (`App\Support\Rules`) + light client hints (phone preview, duplicate check, similar-transaction warning) |

## Schema items marked [ອະນຸມັດ] in the Handoff

Because this is a fresh database with no legacy rows, these were created in the initial migrations
(no backfill, no risk to existing data): `members.birth_order` (+ unique `(father_id, birth_order)`),
`birth_year_only`, `death_year`, `hmong_name`, `deleted_at` (soft delete), `leadership.term_start/term_end`,
`elder_successors`, `transactions.client_request_id` (unique, idempotency), `member_relations.relation_type`.

If a legacy Supabase/Phase 3 database is found later, **do not** run these migrations against it —
import its data into this schema with a reviewed one-off import script instead.

## Open questions — defaults taken (Handoff p.17–18)

1. **Name:** one `name` field (shown as entered) + optional `hmong_name`.
2. **Old clan-name column:** not created (fresh schema).
3. **Daughters' children:** not shown — daughters are terminal branches (no "+" button).
4. **Phaj limit:** UI shows the generations present (designed for 1–6); DB allows any value ≥ 1.
5. **Elder successors:** included (the Elders artboard shows them).
6. **Leadership terms:** `term_start/term_end` columns on `leadership` (no separate history table yet).
7. **Leadership phones:** visible to every Member (Guest/Pending see "ສະເພາະສະມາຊິກ").
8. **Payment slip:** optional image upload on the payment form, private (Admin only).
9. **Email / SMS notifications:** not in v1 (in-app only).
10. **Forms without artboards** (event, news, position, elder, pledge): same FormDialog pattern as the member form.

Other decisions:
- **News authoring** is Admin only (the matrix only grants Members comments).
- **Pending accounts** see exactly what Guests see, plus their profile and the pending banner.
- **Member soft delete** is refused while the member still has children linked or holds a leadership/elder role, so history is never silently orphaned.
- **Manual income** cannot use the "ບໍລິຈາກ" category — donation income only enters through donation payments, so it can never be counted twice.
- **Warning colour:** `#7A4800` (Handoff and prompt); the Design-Tokens artboard shows `#9A5B00`.
- **Chart colours** `#1F3259` / `#E8833A` are kept as specified. They separate well for colour-blind users (ΔE 36) but orange is 2.7:1 on white, so charts always have a legend, hover values and a screen-reader data table.
- **Logo** is a placeholder SVG (navy + gold diamond) until the official logo is approved.
- On phones the Finance table becomes a card list; editing transactions is done from a tablet/desktop width.

## Data still needed from the family committee (Handoff p.16–17)

Real member list, current leadership with terms, elder biographies, approved profile photos, opening balance
and historical transactions, pledge amounts per event, official logo, domain/hosting, SMTP account,
Google OAuth client (optional) and the first Admin email. Lookup lists (provinces, event/news/transaction
categories, leadership groups, elder categories, payment methods) live in `app/Support/Lookups.php`.
