# ຊຸດກຽມສ້າງ Kwv Tij Hmoob Xyooj ໃນ Lovable

Lovable ສ້າງເວັບດ້ວຍ **React + Supabase** ແລະ ບໍ່ສາມາດນຳເຂົ້າໂປຣເຈັກ Laravel ໄດ້.
ຊຸດນີ້ຈຶ່ງກຽມສ່ວນທີ່ຍາກ ແລະ ສ່ຽງທີ່ສຸດໄວ້ໃຫ້ແລ້ວ — **ຖານຂໍ້ມູນທັງໝົດ** — ສ່ວນໜ້າຕາເວັບ ທ່ານສັ່ງ Lovable ສ້າງຕາມ prompt ທີລະຂັ້ນ.

| ໄຟລ໌ | ເນື້ອໃນ |
|---|---|
| `supabase/migrations/01…04_*.sql` | ຕາຕະລາງ, ກົດລະບຽບ (trigger), RPC, views, RLS ຕາມສິດ Guest/Pending/Member/Admin, Storage buckets, Realtime |
| `supabase/tests/` | ທົດສອບ 65 ຂໍ້ (ສິດ, ສາຍພໍ່, ເງິນບໍລິຈາກບໍ່ນັບຊ້ຳ …) — ຜ່ານທັງໝົດໃນ PostgreSQL 16 |
| `design/index.css`, `design/tailwind.config.extend.ts`, `design/logo.svg` | ສີ "Navy Trust" (Light/Dark), ຟອນ, ຂະໜາດຕົວອັກສອນ 100/112/125% ໃນຮູບແບບ shadcn ທີ່ Lovable ໃຊ້ |
| `PROMPTS.md` | Prompt 18 ຂັ້ນ (ພາສາອັງກິດ — Lovable ເຂົ້າໃຈດີກວ່າ) ພ້ອມຂໍ້ຄວາມລາວທຸກປຸ່ມ/ຫົວຂໍ້ |

> ກົດລະບຽບທາງທຸລະກິດ (Phaj = ພໍ່ + 1, ກັນສາຍພົວພັນວົງວຽນ, ລຳດັບການເກີດ 1..n, ເອົາອອກຈາກສາຍພໍ່ບໍ່ລຶບ,
> ເງິນບໍລິຈາກນັບຄັ້ງດຽວ, Admin ຢ່າງໜ້ອຍ 1 ຄົນ, Guest ບໍ່ເຫັນເບີໂທ…) ຢູ່ໃນ **database** ທັງໝົດ —
> ເຖິງ AI ຂອງ Lovable ຈະຂຽນໜ້າເວັບຜິດ ກໍບໍ່ສາມາດລະເມີດກົດເຫຼົ່ານີ້ໄດ້.

---

## ຂັ້ນ 1 — ສ້າງໂປຣເຈັກ Supabase

1. ໄປ <https://supabase.com> → **New project** (Region: Singapore ໃກ້ລາວທີ່ສຸດ). ບັນທຶກລະຫັດ database ໄວ້.
2. **SQL Editor** → **New query** → ວາງເນື້ອໃນໄຟລ໌ຕາມລຳດັບ ແລ້ວກົດ **Run** ເທື່ອລະໄຟລ໌:
   1. `supabase/migrations/20261120000001_schema.sql`
   2. `supabase/migrations/20261120000002_functions_triggers.sql`
   3. `supabase/migrations/20261120000003_rpc_views.sql`
   4. `supabase/migrations/20261120000004_rls_storage.sql`

   ທຸກໄຟລ໌ run ຊ້ຳໄດ້ ບໍ່ເສຍຫາຍ (idempotent). ຫ້າມຂ້າມລຳດັບ.
3. **Authentication → URL Configuration**: ໃສ່ Site URL ຂອງເວັບ (ຕອນທຳອິດໃຊ້ URL preview ຂອງ Lovable) ແລະ ເພີ່ມໃນ Redirect URLs.
4. (ທາງເລືອກ) **Authentication → Providers → Google**: ໃສ່ Client ID/Secret ຈາກ Google Cloud
   (Authorised redirect URI = `https://<project-ref>.supabase.co/auth/v1/callback`).

> Supabase **Security Advisor** ຈະເຕືອນວ່າ `members_public`, `leadership_public`, `elders_public`, `news_comments_view`,
> `v_dashboard_stats`, `v_member_breakdown`, `v_event_participant_counts` ເປັນ "security definer view". **ຕັ້ງໃຈເຮັດແບບນີ້** —
> views ເຫຼົ່ານີ້ໃຫ້ Guest ເຫັນພຽງຄໍລຳທີ່ອະນຸຍາດ (ບໍ່ມີເບີໂທ, ມີແຕ່ປີເກີດ) ແລະ ຈຳນວນສະຖິຕິ. ຢ່າປ່ຽນເປັນ security_invoker.

## ຂັ້ນ 2 — ສ້າງໂປຣເຈັກ Lovable ແລະ ເຊື່ອມ Supabase

1. ໄປ <https://lovable.dev> → ສ້າງໂປຣເຈັກໃໝ່.
2. ເຊື່ອມກັບໂປຣເຈັກ Supabase ທີ່ສ້າງໃນຂັ້ນ 1 (Integrations → Supabase → ເລືອກໂປຣເຈັກ).
   **ຢ່າ** ໃຫ້ Lovable ສ້າງຖານຂໍ້ມູນໃໝ່ ຫຼື ໃຊ້ backend ອື່ນ — ຖ້າມັນສະເໜີ migration ໃຫ້ກົດປະຕິເສດ.
3. ເປີດ `PROMPTS.md` ແລ້ວວາງ **Prompt 0** ກ່ອນ, ຈາກນັ້ນ Prompt 1, 2, … ເທື່ອລະອັນ.
   ແຕ່ລະ prompt **ແນບຮູບ artboard** ທີ່ກ່ຽວຂ້ອງຈາກ PDF *UI Design* (capture ໜ້າຈໍ) — ໄດ້ຜົນໃກ້ design ຫຼາຍຂຶ້ນ.
   ກວດໃຫ້ແຕ່ລະຂັ້ນເຮັດວຽກກ່ອນ ຈຶ່ງໄປຂັ້ນຕໍ່ໄປ.
4. ໃນ Prompt 1 ໃຫ້ copy ເນື້ອໃນ `design/index.css` ແລະ `design/tailwind.config.extend.ts` ໃສ່ບ່ອນ `[paste …]`.

## ຂັ້ນ 3 — ສ້າງ Admin ຄົນທຳອິດ

ທຸກບັນຊີໃໝ່ເລີ່ມເປັນ **pending**. ຫຼັງຈາກລົງທະບຽນບັນຊີທຳອິດຜ່ານໜ້າເວັບແລ້ວ, run ໃນ SQL Editor (ປ່ຽນອີເມວ):

```sql
update public.user_roles set role = 'admin', approved_at = now()
where user_id = (select id from auth.users where email = 'admin@example.com');
```

ຈາກນັ້ນ Admin ອະນຸມັດ ແລະ ກຳນົດ Role ຄົນອື່ນໄດ້ໃນໜ້າ "ຈັດການຜູ້ໃຊ້".

## ຂັ້ນ 4 — ກວດກ່ອນເປີດໃຊ້

- ເຂົ້າເວັບແບບບໍ່ login → ໜ້າສະມາຊິກຕ້ອງ **ບໍ່ມີເບີໂທ** ແລະ ສະແດງແຕ່ປີເກີດ; ໜ້າການເງິນຕ້ອງໄປໜ້າ "ກະລຸນາເຂົ້າສູ່ລະບົບ".
- ບັນຊີ pending → ເຫັນຄືກັບ Guest + ໂປຣໄຟລ໌ ແລະ ແຖບ "ລໍຖ້າອະນຸມັດ".
- ບັນທຶກການຊຳລະບໍລິຈາກ 1 ຄັ້ງ → ໜ້າການເງິນຕ້ອງມີລາຍຮັບເພີ່ມ **1 ລາຍການ** ແລະ ແກ້ໄຂ/ລຶບຈາກໜ້າການເງິນບໍ່ໄດ້.
- ໃຊ້ Prompt 17 ໃຫ້ Lovable ກວດ UI ທັງໝົດ (ມືຖື 375px, ໂໝດມືດ, ຕົວອັກສອນ 125%).

## ຂໍ້ມູນອ້າງອີງສຳລັບນັກພັດທະນາ

**RPC ທີ່ມີ** (ເອີ້ນດ້ວຍ `supabase.rpc('<ຊື່>', {...})`):
`create_member`, `set_member_father`, `reorder_children`, `move_child`, `unlink_from_father`, `soft_delete_member`, `get_siblings`,
`get_family_tree`, `get_ancestors`, `get_descendant_ids`, `find_duplicate_members`, `create_transaction`, `find_similar_transactions`,
`finance_summary`, `record_donation_payment`, `update_donation_payment`, `delete_donation_payment`, `approve_user`, `reject_user`,
`set_user_role`, `set_user_disabled`, `link_user_member`, `admin_list_users`, `match_pending_user`, `reorder_leadership`,
`global_search`, `get_notifications`, `mark_notifications_read`.

**Views**: `members_public`, `leadership_public`, `elders_public`, `news_comments_view`, `v_dashboard_stats`, `v_member_breakdown`,
`v_event_participant_counts` (ທຸກຄົນ) · `v_finance_summary`, `v_finance_monthly`, `v_donation_progress`, `v_donation_event_totals`,
`v_event_summary`, `v_fund_contributor_totals`, `v_fund_by_province` (Member ຂຶ້ນໄປ).

**Storage buckets**: `member-photos`, `avatars` (public) · `event-media`, `news-media`, `clan-documents` (private → ໃຊ້ `createSignedUrl`).

**ທົດສອບ SQL ໃນເຄື່ອງ** (PostgreSQL 15+ ໃນເຄື່ອງ — ບໍ່ແມ່ນ Supabase ຈິງ):

```bash
DB=kit_test bash supabase/tests/run.sh
```

ຜົນທີ່ຖືກຕ້ອງ: ທຸກແຖວຂຶ້ນຕົ້ນດ້ວຍ `ok -` ແລະ ຈົບດ້ວຍ `ALL TESTS PASSED`.
ໄຟລ໌ `tests/00_supabase_stub.sql` ຈຳລອງ `auth`/`storage` ຂອງ Supabase ສຳລັບທົດສອບເທົ່ານັ້ນ — **ຫ້າມ run ໃນ Supabase ຈິງ**.

**ຄວາມແຕກຕ່າງຈາກເວີຊັນ Laravel**: ກົດລະບຽບ ແລະ ສິດຄືກັນ; id ເປັນ `uuid`; ບັນຊີແຍກເປັນ `profiles` + `user_roles` ຕາມ Dev Handoff;
ການແກ້ພໍ່/ລຳດັບການເກີດ ຕ້ອງຜ່ານ RPC ເທົ່ານັ້ນ (database ປະຕິເສດການ update ໂດຍກົງ).
