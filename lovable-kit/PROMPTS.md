# Lovable prompts — Kwv Tij Hmoob Xyooj

Paste these into Lovable **one at a time, in order**. Wait until each step works before the next.
With each prompt, **attach the matching artboard image** from *Kwv Tij Hmoob Xyooj — UI Design.pdf*
(screenshot the page) — Lovable copies a design much better from an image than from text.

The database is already built by the SQL in `supabase/migrations/` (see README.md). Every prompt
tells Lovable to **use the existing tables / views / RPCs and not to create new tables or migrations**.

---

## Prompt 0 — Ground rules (paste first, once)

```
We are building "Kwv Tij Hmoob Xyooj" (ລະບົບຄຸ້ມຄອງຕະກູນ), a family/clan management web app in LAO language.
Stack: React + TypeScript + Vite + Tailwind + shadcn/ui + lucide-react + React Router + TanStack Query + React Hook Form + Zod + Supabase.

IMPORTANT RULES FOR THE WHOLE PROJECT:
1. The Supabase database ALREADY EXISTS (tables, RLS, views, RPC functions, storage buckets). Do NOT create, alter or drop
   any table, policy or function, and do NOT write migrations. Only read/write through the names I give you.
   If you think the database needs a change, STOP and tell me instead.
2. Security is enforced by RLS in the database. Hiding buttons in the UI is only UX.
3. All UI text is Lao. Font: Noto Sans (Latin + digits) then Noto Sans Lao. Line-height 1.6, min text 13px.
4. Colours ONLY via the CSS variables in src/index.css (I will paste them) — never hard-code hex in components.
5. Every list has 4 states: loading (skeleton), empty (Lao message + first-action button), error ("ບໍ່ສາມາດເຊື່ອມຕໍ່ເຄືອຂ່າຍໄດ້" + "ລອງໃໝ່"), filtered-empty.
6. Every mutation: disable the button while saving ("ກຳລັງບັນທຶກ..."), then toast "ບັນທຶກສຳເລັດ", then invalidate the related queries.
   Every delete goes through a confirm dialog.
7. Buttons/inputs at least 44px high. Radius: buttons/inputs 8px, cards 12px, dialogs 16px, badges 999px.
8. Map Postgres errors to Lao in src/lib/errors.ts: 23505 → "ຂໍ້ມູນນີ້ມີຢູ່ແລ້ວ", 42501 → "ທ່ານບໍ່ມີສິດດຳເນີນການນີ້",
   23514/P0001 → show the database message (already Lao), network → "ບໍ່ສາມາດເຊື່ອມຕໍ່ເຄືອຂ່າຍໄດ້".
Reply "OK" and wait for the next instruction.
```

## Prompt 1 — Design tokens, formatting, layout

Attach: Design Tokens artboard + Main (dashboard) artboard.

```
1) Replace src/index.css with the file I paste below, and merge the colours from tailwind.config.extend.ts into tailwind.config.ts.
   [paste design/index.css] [paste design/tailwind.config.extend.ts]
   Put design/logo.svg in public/logo.svg and use it as the logo + favicon.

2) Create src/lib/format.ts with unit tests (vitest):
   - money(1250000) → "1.250.000 ₭" (dot thousands, no decimals); signed: "+ 1.000.000 ₭" / "− 2.000.000 ₭"
   - percent(61.5) → "61,5%" ; percent(100) → "100%" ; null → "—"
   - date → "15/11/2026" ; dateTime → "15/11/2026 14:20"
   - phone("+8562055123344") → "+856 20 5512 3344" ; "+856305123456" → "+856 30 512 3456"
   - normalizePhone("020 5512 3344") → "+8562055123344" (also "00856…", "856…", "20xxxxxxxx")
   - initial("ເປັງ ຊົ່ງ") → "ປ" (skip leading Lao vowels ເ ແ ໂ ໃ ໄ)
   - laoMonth(1..12) → ມັງກອນ ກຸມພາ ມີນາ ເມສາ ພຶດສະພາ ມິຖຸນາ ກໍລະກົດ ສິງຫາ ກັນຍາ ຕຸລາ ພະຈິກ ທັນວາ ; weekday → ວັນອາທິດ…ວັນເສົາ

3) Create src/lib/lookups.ts:
   PROVINCES = ນະຄອນຫຼວງວຽງຈັນ, ຜົ້ງສາລີ, ຫຼວງນ້ຳທາ, ອຸດົມໄຊ, ບໍ່ແກ້ວ, ຫຼວງພະບາງ, ຫົວພັນ, ໄຊຍະບູລີ, ຊຽງຂວາງ, ວຽງຈັນ,
               ບໍລິຄຳໄຊ, ຄຳມ່ວນ, ສະຫວັນນະເຂດ, ສາລະວັນ, ເຊກອງ, ຈຳປາສັກ, ອັດຕະປື, ໄຊສົມບູນ
   EVENT_CATEGORIES = ບຸນປະເພນີ, ກອງປະຊຸມ, ພິທີກຳ, ຈິດອາສາ
   NEWS_CATEGORIES = ປະກາດ, ກິດຈະກຳ, ກອງທຶນ, ຍິນດີ
   INCOME_CATEGORIES = ຄ່າບຳລຸງ, ລາຍຮັບອື່ນ   (ບໍລິຈາກ is created ONLY by donation payments)
   EXPENSE_CATEGORIES = ກິດຈະກຳ, ອາຫານ, ສະຫວັດດີການ, ລາຍຈ່າຍອື່ນ
   LEADERSHIP_GROUPS = executive: ຄະນະປະຈຳ, department_head: ຫົວໜ້າຝ່າຍ, committee: ກຳມະການ
   ELDER_CATEGORIES = rite: ພິທີກຳ, qeej: ແຄນ, herb: ຢາພື້ນເມືອງ, history: ປະຫວັດ, craft: ຫັດຖະກຳ, mediate: ໄກ່ເກ່ຍ
   PARTICIPANT_STATUS = confirmed: ຢືນຢັນ, maybe: ອາດຈະມາ ; PAYMENT_METHODS = cash: ເງິນສົດ, bank: ໂອນຜ່ານທະນາຄານ
   ROLES = pending: ລໍຖ້າອະນຸມັດ, member: ສະມາຊິກ, admin: ຜູ້ດູແລລະບົບ

4) Shared components in src/components/common/: AppSidebar, AppHeader, PageHeader(title, description, actions),
   StatCard(label, value, hint, icon?, tone?), StatusBadge(tone: success|warning|destructive|info|neutral|primary),
   FilterChips(options[{value,label,count?}], value, onChange, aria-pressed), SegmentedControl, DataTable (server pagination 20/page,
   becomes cards on mobile), DetailSheet (right panel on desktop, full sheet on mobile), FormDialog (title, RHF+Zod form, buttons bottom,
   errors under fields), MemberAvatar (photo or Lao initial; male bg avatar-male, female bg avatar-female, greyed when deceased),
   MoneyText(amount, sign?), ProgressBar(value|null, role=progressbar), LockedField ("🔒 ສະເພາະສະມາຊິກ"), EmptyState, LoadingState, ErrorState,
   UploadField (MIME/size limits below, client-side resize to ≤1920px, progress), ConfirmDialog.

5) Layout (match the artboard exactly):
   - AppSidebar (white card, logo + "Kwv Tij Hmoob Xyooj / ລະບົບຄຸ້ມຄອງຕະກູນ"), groups:
     ພາບລວມ: ໜ້າຫຼັກ(/dashboard, LayoutGrid) · ຕະກູນ: ສະມາຊິກ(/members, Users), ແຜນຜັງຕະກູນ(/members?view=tree, Network),
     ຄະນະນຳ(/leadership, Award), ຜູ້ອາວຸໂສ(/elders, User) · ກິດຈະກຳ: ກິດຈະກຳ(/activities, Calendar), ຂ່າວສານ(/news, Newspaper) ·
     ຄັງເງິນ (member+): ການເງິນ(/finance, Wallet) · ກອງທຶນ (member+): ຜູ້ຮ່ວມກອງທຶນ(/fund-contributors, Heart), ຕິດຕາມການບໍລິຈາກ(/donation-tracking, Target) ·
     ລະບົບ (admin): ຈັດການຜູ້ໃຊ້(/admin, Shield) with a badge = number of pending users.
     Bottom: user card (avatar, name, role label) → /profile, or "ເຂົ້າສູ່ລະບົບ" button for guests. Active item: primary-soft background.
   - AppHeader: breadcrumb left; search box "ຄົ້ນຫາສະມາຊິກ, ກິດຈະກຳ, ໜ້າ..." with Ctrl K hint (opens the command dialog later);
     theme toggle (Moon/Sun); notifications bell (signed-in only) with red count; user menu (ໂປຣໄຟລ໌ຂອງຂ້ອຍ, ອອກຈາກລະບົບ).
   - Below 1024px the sidebar is a drawer; on phones a bottom nav: ໜ້າຫຼັກ · ສະມາຊິກ · ກິດຈະກຳ · ຂ່າວ · ເມນູ.
   - Theme light/dark/system and font scale 100/112/125 (set html[data-font-scale]); stored in profiles.theme / profiles.font_scale
     for signed-in users, localStorage for guests.
```

## Prompt 2 — Auth, roles, guards, Unauthorized, 404

Attach: Login, Unauthorized and NotFound artboards.

```
Auth with Supabase Auth (email+password, Google OAuth button "ດຳເນີນການດ້ວຍ Google", password reset).
- /login and /register are two tabs of one page (left navy panel with lattice pattern, title "ເຊື່ອມໂຍງພີ່ນ້ອງ ຮັກສາຮາກເຫງົ້າຂອງຕະກູນ",
  link "ເບິ່ງຂໍ້ມູນສາທາລະນະ ໂດຍບໍ່ຕ້ອງເຂົ້າສູ່ລະບົບ →"). Register sends display_name in signUp options.data.display_name.
  Password ≥ 8 chars with a 4-segment strength meter; error "ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 8 ຕົວອັກສອນ".
- /reset-password: request link, then set new password (Supabase recovery flow).
- A database trigger creates profiles + user_roles(role='pending') for every new account — do not insert them yourself.
- useAuth(): session, profile (table profiles: display_name, avatar_url, member_id, theme, font_scale) and role from table user_roles
  (role pending|member|admin, is_disabled). Helpers: isMember = role in (member, admin) and not disabled; isAdmin.
- Route guards (React Router):
    Public: /dashboard /members /members/:id /leadership /elders /elders/:id /activities /activities/:id /news /news/:id
    Pending+ (signed in): /profile
    Member+: /finance /fund-contributors /donation-tracking
    Admin: /admin
  If blocked → /unauthorized?from=<path>&reason=guest|pending|role with 3 variants:
    guest: "ກະລຸນາເຂົ້າສູ່ລະບົບ" + button that logs in and returns to `from`; pending: "ບັນຊີກຳລັງລໍຖ້າອະນຸມັດ"; role: "ທ່ານບໍ່ມີສິດເຂົ້າໜ້ານີ້" (403).
- PendingBanner on Dashboard and Profile when role = pending: "ບັນຊີຂອງທ່ານກຳລັງລໍຖ້າອະນຸມັດ…".
- 404 page "ບໍ່ພົບໜ້າທີ່ທ່ານຊອກຫາ" with search box and buttons ກັບໜ້າຫຼັກ / ສະມາຊິກ / ກິດຈະກຳ.
```

## Prompt 3 — Dashboard

Attach: Main artboard.

```
/dashboard — data only from views (no counting in the frontend):
- v_dashboard_stats → 6 StatCards: ສະມາຊິກທັງໝົດ (+members_added_30d ໃນ 30 ວັນ), ຄະນະນຳ (ວາລະ term_start–term_end years),
  ຜູ້ອາວຸໂສ (ຜູ້ນຳພິທີ elders_rite ທ່ານ), ຈຳນວນ Phaj (generations), ກິດຈະກຳປີນີ້ (ຈະມາເຖິງ upcoming_events ລາຍການ), ຂ່າວສານ (+news_added_7d ໃນ 7 ວັນ).
- v_member_breakdown → card "ສະມາຊິກຕາມ Phaj" (horizontal bars Phaj 1..n, click → /members?phaj=n) and
  card "ສະມາຊິກຕາມເພດ ແລະ ສະຖານະ" (stacked bar male chart-1 / female chart-2 with %, then ມີຊີວິດ / ເສຍຊີວິດແລ້ວ).
- Member+ only: card "ການເງິນ 6 ເດືອນຫຼ້າສຸດ": v_finance_summary.balance (ຍອດຄົງເຫຼືອ), this month's income/expense via
  rpc finance_summary(p_from, p_to), grouped bar chart from v_finance_monthly (last 6 rows, income = chart-1, expense = chart-2),
  legend + hover tooltip with values + a visually-hidden table for screen readers. Link "ໄປທີ່ການເງິນ".
- ກິດຈະກຳທີ່ຈະມາເຖິງ: events where coalesce(end_date,start_date) >= today order by start_date limit 5 (RLS hides private ones from guests);
  date tile (day + Lao month), title, time · location, badge ສາທາລະນະ (success) / ສະມາຊິກ (primary).
- ຂ່າວລ່າສຸດ: news where status='published' order by is_pinned desc, published_at desc limit 5; category badge, date, "ປັກໝຸດ" marker.
- Header: "ວັນພະຫັດ, 19/11/2026" + "ສະບາຍດີ, <first name>"; member+ buttons "ເພີ່ມກິດຈະກຳ", "ເພີ່ມສະມາຊິກ".
```

## Prompt 4 — Members list + DetailSheet

Attach: Members artboard.

```
/members (and /members/:id opens the DetailSheet for that member).
PRIVACY: guests/pending read view members_public (id, name, hmong_name, sex, generation, is_alive, father_id, birth_order,
birth_year, death_year, province, photo_url) — NO phone, village or full birth date (show LockedField instead).
Members/admins read table members (all columns). Use isMember to pick the source.
- Header: "ສະມາຊິກ", "ທັງໝົດ N ຄົນ ໃນ X Phaj · ເຊື່ອມໂຍງຕາມສາຍພໍ່"; SegmentedControl ຕາຕະລາງ | ແຜນຜັງ (→ ?view=tree);
  Export CSV (member+, UTF-8 BOM, prefix ' to cells starting with = + - @ except phone numbers); "+ ເພີ່ມສະມາຊິກ" (member+, opens form dialog ?new=1).
- Filters: search name/hmong_name (ilike, debounce 300ms), FilterChips ທັງໝົດ / Phaj 1…n, select ທຸກເພດ/ຊາຍ/ຍິງ, select ທຸກສະຖານະ/ມີຊີວິດ/ເສຍຊີວິດແລ້ວ.
- Table (server pagination 20, order generation, father_id, birth_order): ຊື່ (avatar + name + "ຊາຍ · 12/03/1978" or birth year),
  Phaj badge, ພໍ່ (father name), ລຳດັບລູກ ("ລູກຊາຍຄົນທີ 3" / "ລູກສາວຄົນທີ 2" / "ຕົ້ນຕະກູນ"), ແຂວງ, ເບີໂທ (formatted, or LockedField).
- DetailSheet: avatar, name, badges (Phaj, child label, ມີຊີວິດ/ເສຍຊີວິດແລ້ວ), edit button (member+); fields ວັນເກີດ, ແຂວງ, ບ້ານ, ເບີໂທ;
  "ສາຍພໍ່" box: ພໍ່ and ປູ່ with Phaj badges + link "ເບິ່ງສາຍບັນພະບຸລຸດ →" (/members?view=tree&id=…);
  "ອ້າຍນ້ອງ · ລູກຂອງ X N ຄົນ" from rpc get_siblings(p_member_id): order number, name, "ລູກສາວ · ເກີດ 1981", and a RELATIVE label computed
  in the frontend (never stored): older brother ອ້າຍ, older sister ເອື້ອຍ, younger ນ້ອງຊາຍ/ນ້ອງສາວ, self ຕົນເອງ (dark badge).
  Member+ controls per sibling: ↑ / ↓ → rpc move_child(p_member_id, p_direction -1|1); ✕ → confirm then rpc unlink_from_father(p_member_id)
  ("ສະມາຊິກຈະບໍ່ຖືກລຶບ"). Button "+ ເພີ່ມອ້າຍນ້ອງ (ລູກຂອງ X)" opens the form with that father. Caption "↑↓ ປັບລຳດັບການເກີດ · ✕ ເອົາອອກຈາກຄອບຄົວ (ບໍ່ລຶບສະມາຊິກ)".
  "ລູກ · N ຄົນ" list (children by birth_order) + "+ ເພີ່ມລູກ" for males. Spouses from member_relations are READ-ONLY.
  Admin only: "ລຶບສະມາຊິກ" → confirm → rpc soft_delete_member(p_member_id) (fails with a Lao message if the member still has children).
NEVER update father_id or birth_order with .update() — the database rejects it; use the RPCs.
```

## Prompt 5 — Member form dialog

Attach: MemberForm artboard.

```
FormDialog "ເພີ່ມສະມາຊິກໃໝ່" / "ແກ້ໄຂສະມາຊິກ" (?new=1 / ?edit=<id>, full-screen sheet on mobile). Sections with numbered circles:
1 ຂໍ້ມູນພື້ນຖານ: ຊື່* (1–100, "ກະລຸນາໃສ່ຊື່"), ຊື່ມົ້ງ, ເພດ* radio cards ຊາຍ/ຍິງ, ວັນເກີດ (≤ today, "ວັນເກີດຕ້ອງບໍ່ເກີນມື້ນີ້") with checkbox
  "ຮູ້ແຕ່ປີເກີດ" (switches to a year input; send birth_date = YYYY-01-01 and birth_year_only = true), ສະຖານະ segmented ມີຊີວິດ/ເສຍຊີວິດແລ້ວ
  (deceased shows ປີເສຍຊີວິດ).
2 ສາຍພໍ່ ແລະ ອ້າຍນ້ອງ: text "ລະບົບບັນທຶກສາຍຕະກູນທາງຝ່າຍພໍ່…". MemberCombobox for ພໍ່: only sex=male, search debounce 300ms.
  When editing, disable the member itself and all ids from rpc get_descendant_ids(p_member_id) with reason "ເປັນລູກຫຼານ — ເລືອກບໍ່ໄດ້".
  Green box "Phaj N (ຄຳນວນອັດຕະໂນມັດ) — Phaj ຂອງພໍ່ (n) + 1". No father → number input "Phaj (ຕົ້ນຕະກູນ)" ≥ 1.
  Right side "ລຳດັບການເກີດໃນຄອບຄົວ": the father's children (get_siblings of any child, or select members where father_id = X) with the new
  person inserted (highlighted, ↑↓ to move) → insert position; summary "✓ ຈະເປັນລູກຊາຍຄົນທີ 5 ຈາກ 5 ຄົນ ຂອງ ເຈ ຊົ່ງ".
3 ຕິດຕໍ່ ແລະ ທີ່ຢູ່ (badge "🔒 ບໍ່ສະແດງຕໍ່ສາທາລະນະ"): ເບີໂທ with live preview "✓ ຈະບັນທຶກເປັນ +856 20 5577 8899" (normalizePhone; invalid →
  "ເບີໂທບໍ່ຖືກຕ້ອງ"), ແຂວງ select (PROVINCES), ບ້ານ/ເມືອງ.
4 ຮູບພາບ: UploadField JPG/PNG/WebP ≤ 10 MB, resize to ≤ 1920px → bucket member-photos path <member_id>/<uuid>.webp, save public URL in photo_url.
Duplicate check: when name/birth date change call rpc find_duplicate_members(p_name, p_birth_date, p_exclude) and show the amber box
"ອາດມີຂໍ້ມູນຊ້ຳ — …" with "ເປີດເບິ່ງ" links and a required checkbox "ຂ້ອຍກວດແລ້ວ — ບໍ່ແມ່ນຄົນດຽວກັນ, ບັນທຶກຕໍ່" (warn, never block after confirm).
SAVE:
 - new: rpc create_member(p_name, p_sex, p_father_id, p_insert_at, p_generation, p_birth_date, p_birth_year_only, p_is_alive, p_death_year,
        p_hmong_name, p_phone, p_province, p_village, p_photo_url) → returns id (upload the photo after, then update photo_url).
 - edit: .update() name, hmong_name, sex, birth_date, birth_year_only, is_alive, death_year, phone, province, village, photo_url;
        if father or position changed → rpc set_member_father(p_member_id, p_father_id, p_insert_at).
 Phaj is computed by the database; never send generation when a father is set.
```

## Prompt 6 — Family tree

Attach: FamilyTree artboard.

```
/members?view=tree — data: rpc get_family_tree(p_root_id null, p_depth 12) (safe for guests) and rpc get_ancestors(p_member_id) for the side panel.
- Toolbar: SegmentedControl ຕາຕະລາງ|ແຜນຜັງ, buttons "ເປີດທຸກສາຍ", "ສະເພາະສາຍທີ່ເລືອກ", "ພິມ". Legend: ທີ່ເລືອກ, ສາຍບັນພະບຸລຸດ, ລູກຊາຍ, ລູກສາວ, (+3) ລູກທີ່ພັບໄວ້.
- Canvas: horizontal bands per Phaj with "Phaj n" labels; nodes 150×56 (avatar initial, name, "ຄົນທີ 1 · 1978" or "ລູກສາວ 2 · 2007");
  tidy layout: children left→right by birth_order, parent centred above; orthogonal connector lines; daughters are terminal (no expand button);
  "−"/"+N" pill under fathers to collapse/expand. Large trees start collapsed below the 3rd row except the selected lineage.
- Selected node: thick primary border + primary-soft fill; its ancestor path drawn thicker in primary.
- Zoom −/+ with % label, fit-to-screen, Ctrl+wheel zoom, drag to pan, two-finger pinch on mobile, arrow keys pan.
- Right panel (DetailSheet style): "ສາຍບັນພະບຸລຸດ · ສາຍພໍ່", name, "Phaj 4 · ລູກຊາຍຄົນທີ 1 · 1978"; vertical timeline from get_ancestors
  (numbered circles = Phaj, line between), each step shows "✓ Phaj ຕໍ່ເນື່ອງ (1 → 2)" in success colour if phaj_ok else a red warning;
  "ລູກຂອງ X (n)" chips; buttons "ເປີດຂໍ້ມູນສະມາຊິກ" and (member+, males) "+ ເພີ່ມລູກ". Selecting updates ?id= in the URL.
- Print view: hide chrome, print the canvas.
```

## Prompt 7 — Leadership

Attach: Leadership artboard.

```
/leadership (?view=chart|list). Data: view leadership_public (everyone); phones: member+ read members.phone by member_id, guests see LockedField.
StatCards: ຄະນະນຳທັງໝົດ (ຊາຍ n · ຍິງ n), ຄະນະປະຈຳ (positions), ຫົວໜ້າຝ່າຍ (ກຳມະການ n ທ່ານ), ໄລຍະວາລະ (min term_start – max term_end, progress bar,
"ຍັງເຫຼືອ x ປີ y ເດືອນ"). Segmented: ແຜນຜັງການຈັດຕັ້ງ | ລາຍຊື່ ແລະ ຈັດລຳດັບ.
Chart: ຄະນະປະຈຳ (first by sort_order as the big centred card, others below with connector), ຫົວໜ້າຝ່າຍ cards, ກຳມະການ chips "7 ທ່ານ".
List: table #, ຊື່, ຕຳແໜ່ງ, ກຸ່ມ, ເບີໂທ, ວາລະ; Admin: ↑↓ reorder → rpc reorder_leadership(p_ids uuid[]) with the full new order; edit/delete.
Admin FormDialog "ເພີ່ມຕຳແໜ່ງ": member (MemberCombobox), position, group (LEADERSHIP_GROUPS), term_start, term_end (≥ start), optional photo
(bucket member-photos). Insert/update table leadership directly (RLS: admin only). Buttons ພິມ and Export CSV (member+).
```

## Prompt 8 — Elders

Attach: Elders artboard.

```
/elders and /elders/:id. Data: view elders_public (everyone: role_title, role_category, bio, skills, status, photo_url, name, sex, generation,
is_alive, birth_year, death_year, successor_ids). Phone: member+ from members.
StatCards: ຜູ້ອາວຸໂສທັງໝົດ (n ບົດບາດ), ຍັງມີຊີວິດ (ອາຍຸສະເລ່ຍ x ປີ), ເສຍຊີວິດແລ້ວ (ບັນທຶກໄວ້ເພື່ອລະນຶກ), ຜູ້ສືບທອດ (total; "n ທ່ານຍັງບໍ່ມີຜູ້ສືບທອດ" in warning).
Search + FilterChips by ELDER_CATEGORIES with counts. Card grid (big avatar, name, role badge, "Phaj 3 · ອາຍຸ 74 ປີ" or years + "ເສຍຊີວິດແລ້ວ").
Detail panel: name, badges, Phaj, ອາຍຸ (ເກີດ yyyy), ເບີໂທ, ປະຫວັດຫຍໍ້, ຄວາມຊຳນານ chips, ຜູ້ສືບທອດ chips (link to members), buttons
"ເບິ່ງໃນລາຍຊື່ສະມາຊິກ", "ເບິ່ງໃນແຜນຜັງ", Admin "ແກ້ໄຂຂໍ້ມູນ".
Admin form: member, role_title, role_category, bio, skills (comma separated → text[]), successors (multi MemberCombobox → table elder_successors), photo.
```

## Prompt 9 — Activities

Attach: Activities artboard.

```
/activities and /activities/:id?tab=overview|participants|donations|expenses|photos.
List (left): search, category select, Segmented ຈະມາເຖິງ | ຜ່ານມາແລ້ວ; cards with date tile, title, "category · location", "N ຄົນເຂົ້າຮ່ວມ"
(view v_event_participant_counts), ສາທາລະນະ badge. RLS already hides private events from guests/pending.
Header "ປີ 2026 · N ກິດຈະກຳ · ຈະມາເຖິງ M"; "+ ສ້າງກິດຈະກຳ" (member+).
Detail: cover image (or navy lattice placeholder), badges, title, buttons ແກ້ໄຂ (admin or creator) and ລົງຊື່ເຂົ້າຮ່ວມ (member with a linked member:
insert participants {event_id, member_id: profile.member_id}; if no linked member show "ບັນຊີຂອງທ່ານຍັງບໍ່ໄດ້ເຊື່ອມກັບຂໍ້ມູນສະມາຊິກ").
Tabs — guests/pending only see ພາບລວມ + ຮູບພາບ:
 - ພາບລວມ: member+ stat boxes from v_event_summary (ຜູ້ເຂົ້າຮ່ວມ, ຍອດບໍລິຈາກ green, ລາຍຈ່າຍ red, ຍອດສຸດທິ primary-soft); ວັນທີ, ເວລາ, ສະຖານທີ່, ຜູ້ຈັດ, description.
 - ຜູ້ເຂົ້າຮ່ວມ: participants + member names, duty, status badge; managers add (MemberCombobox, duty, status) / remove.
 - ການບໍລິຈາກ: v_donation_progress for this event + link to /donation-tracking?event=id.
 - ລາຍຈ່າຍ: transactions where event_id and type='expense'; admin link "+ ບັນທຶກລາຍຈ່າຍ".
 - ຮູບພາບ: media_items (module='event') grid; files in private bucket event-media/<event_id>/… shown with createSignedUrl; full-screen lightbox;
   managers upload images ≤10MB / videos ≤50MB (insert media_items row after upload) and delete.
Form (member+ create; admin/creator edit): title*, category*, start_date*, end_date (≥ start), start/end time, location, organizer,
is_public ("ສາທາລະນະ (Guest ເບິ່ງໄດ້)"), description, cover (bucket event-media). Insert with created_by = auth user id. Admin delete.
```

## Prompt 10 — News

Attach: News artboard.

```
/news and /news/:id. RLS: everyone reads published; admin also drafts. Author names for comments: view news_comments_view.
Left list: search (title or #tag), FilterChips categories, Admin link "ສະບັບຮ່າງ (n)"; cards: thumbnail, category badge, date, "ປັກໝຸດ", title, "n ຄຳເຫັນ";
"ໂຫຼດເພີ່ມ" pagination. Order: is_pinned desc, published_at desc.
Article: 16:9 media (bucket news-media, signed URLs, "1 / 6" counter, full-screen lightbox with ←/→), category, date, "ໂດຍ …", title, body
(plain text, keep line breaks), tag chips, "ສຳເນົາລິ້ງ". Admin: ແກ້ໄຂ, ປັກໝຸດ/ຖອນປັກໝຸດ, ລຶບ (confirm).
Comments (n): member+ textarea "ຂຽນຄຳເຫັນ..." + ສົ່ງ (insert news_comments {news_id, author_id: auth uid, body}); guests see
"ເຂົ້າສູ່ລະບົບ ເພື່ອຂຽນຄຳເຫັນ"; own comments: ແກ້ໄຂ / ລຶບ; admin can delete any. Show relative time ("2 ຊົ່ວໂມງກ່ອນ", "ມື້ວານ").
Admin form "ຂຽນຂ່າວ": title, category, tags, body, images/videos, ປັກໝຸດ checkbox, Segmented ສະບັບຮ່າງ | ເຜີຍແຜ່
(publishing sets status='published' and published_at=now() the first time).
```

## Prompt 11 — Finance

Attach: Finance artboard.

```
/finance (member+). Member = read only (info banner "ທ່ານເບິ່ງໄດ້ຢ່າງດຽວ"); Admin = record / edit / delete.
Cards: navy card ຍອດຄົງເຫຼືອທັງໝົດ from v_finance_summary.balance ("ອັບເດດ dd/mm/yyyy · ຈາກ v_finance_summary");
ລາຍຮັບ (ຊ່ວງທີ່ເລືອກ) + "ລວມເງິນບໍລິຈາກ x" and ລາຍຈ່າຍ (ຊ່ວງທີ່ເລືອກ) + "n ລາຍການ" from rpc finance_summary(p_from, p_to).
Filters: Segmented ທັງໝົດ/ລາຍຮັບ/ລາຍຈ່າຍ, date range (default first day of last month → today), category, event.
Table (server pagination): ວັນທີ, ລາຍລະອຽດ (+ badge "🔒 ມາຈາກການບໍລິຈາກ" when source='donation'), ໝວດ, ກິດຈະກຳ, ຈຳນວນ signed & coloured,
actions: donation rows show link "ແກ້ທີ່ໜ້າບໍລິຈາກ" (NO edit/delete — the database blocks it); manual rows: edit / delete (admin).
Footer "ສະແດງ n ລາຍການ" and "ຍອດສຸດທິຂອງຊ່ວງ". Mobile: card list. Buttons ພິມ (print view with title + range) and Export CSV.
Admin dialog "ບັນທຶກທຸລະກຳ": type segmented, date, amount (integer > 0, "ຈຳນວນເງິນຕ້ອງຫຼາຍກວ່າ 0", live "1.250.000 ₭" preview),
description, category (filtered by type; never ບໍລິຈາກ), event. Generate a client_request_id (crypto.randomUUID) when the dialog opens.
Before saving call rpc find_similar_transactions(p_date, p_amount, p_category) and show an amber warning "ອາດຈະບັນທຶກຊ້ຳ…" (do not block).
Create: rpc create_transaction(p_date, p_description, p_category, p_type, p_amount, p_event_id, p_client_request_id) (idempotent).
Edit / delete manual rows with .update() / .delete() on transactions.
```

## Prompt 12 — Fund contributors

Attach: FundContributors artboard.

```
/fund-contributors (member+ read, admin write). Data: v_fund_contributor_totals and v_fund_by_province.
StatCards: ຜູ້ຮ່ວມທັງໝົດ "124 ຄົນ" (+n ໃນເດືອນນີ້), ຍອດບໍລິຈາກສະສົມ (green, "ຕັ້ງແຕ່ປີ yyyy"), ສະເລ່ຍຕໍ່ຄົນ ("ຄິດໄລ່ຈາກ transactions"), ພື້ນທີ່ "n ແຂວງ" / "n ບ້ານ".
Left card "ສະຖິຕິຕາມແຂວງ" (click a province to filter the list; bars proportional to total). Right: search + table ຊື່, ແຂວງ · ບ້ານ,
ຍອດບໍລິຈາກລວມ, ຄັ້ງ, ເຂົ້າຮ່ວມ (year), admin delete (confirm "ປະຫວັດການບໍລິຈາກຍັງຢູ່").
Admin "+ ເພີ່ມຜູ້ຮ່ວມ": MemberCombobox (members only) + joined_at → insert fund_members (unique → "ເປັນຜູ້ຮ່ວມແລ້ວ"). Export CSV.
```

## Prompt 13 — Donation tracking

Attach: Donations artboard.

```
/donation-tracking?event=<id> (member+ read, admin write).
Header: event select (default: next upcoming event that has pledges), ພິມ, Export CSV, Admin "+ ບັນທຶກການຊຳລະ".
Cards from v_donation_event_totals: ຍອດຄາດຫວັງ ("ຈາກ n ຜູ້ບໍລິຈາກ"), ຊຳລະແລ້ວ (green, "61,5% ຂອງເປົ້າໝາຍ"), ຄ້າງຊຳລະ (red, "n ຄົນຍັງຄ້າງ"),
ຄວາມຄືບໜ້າລວມ (progress + "ຄົບ n · ບາງສ່ວນ n · ຍັງບໍ່ຈ່າຍ n").
Info banner: "ທຸກການຊຳລະຈະຖືກບັນທຶກເປັນ ລາຍຮັບ ໃນໜ້າການເງິນໂດຍອັດຕະໂນມັດ ແລະ ນັບພຽງຄັ້ງດຽວ. ແກ້ໄຂ ຫຼື ລຶບໄດ້ຈາກໜ້ານີ້ເທົ່ານັ້ນ."
FilterChips ທັງໝົດ/ຍັງບໍ່ຈ່າຍ/ບາງສ່ວນ/ຄົບແລ້ວ/ເກີນ + search. Table from v_donation_progress: ຜູ້ບໍລິຈາກ, ຄາດຫວັງ ("—" if null), ຊຳລະແລ້ວ,
ຄ້າງ (red if > 0), progress bar + % ("—" if no expected), ຊຳລະລ່າສຸດ, status badge (paid ຄົບແລ້ວ success, partial ບາງສ່ວນ warning,
unpaid ຍັງບໍ່ຈ່າຍ destructive, over ເກີນ info), Admin "+ ຊຳລະ" and payment history (donation_payments) with edit / delete.
Admin actions — ONLY via RPC (never insert into transactions or donation_payments yourself):
 - record: rpc record_donation_payment(p_amount, p_paid_on, p_method, p_pledge_id, p_note, p_slip_path, p_client_request_id,
           p_member_id, p_event_id) — for a donor not in the list pass p_member_id + p_event_id instead of p_pledge_id.
           Optional slip image → private bucket clan-documents/slips/<uuid>.webp, pass the path.
 - edit: rpc update_donation_payment(p_payment_id, p_amount, p_paid_on, p_method, p_note)
 - delete: rpc delete_donation_payment(p_payment_id) → returns the slip path to remove from storage.
 - pledges: insert/update/delete donation_pledges (member_id, event_id, expected_amount — empty = null).
```

## Prompt 14 — Admin

Attach: Admin artboard.

```
/admin (admin only). Data: rpc admin_list_users(p_role) (email, provider, last_sign_in_at, member link), rpc match_pending_user(p_user_id), audit_logs.
- "ລໍຖ້າອະນຸມັດ n" amber section: cards (avatar, name, "email · Google|Email", "ລົງທະບຽນ dd/mm/yyyy · ອາດແມ່ນ: <match name> · Phaj n" or
  "ບໍ່ພົບໃນລາຍຊື່", MemberCombobox to change the link) with green "ອະນຸມັດເປັນສະມາຊິກ" → rpc approve_user(p_user_id, p_member_id)
  and "ປະຕິເສດ" (confirm) → rpc reject_user(p_user_id).
- Users table: FilterChips ທັງໝົດ / ສະມາຊິກ / ຜູ້ດູແລ with counts, search; columns ຜູ້ໃຊ້ (name, "(ທ່ານ)" for self, email), Role select
  (disabled for self) → rpc set_user_role(p_user_id, p_role), ເຊື່ອມກັບສະມາຊິກ (combobox → rpc link_user_member; "— ຍັງບໍ່ເຊື່ອມ" in warning),
  ເຂົ້າລ່າສຸດ (relative), "…" menu ປິດບັນຊີ/ເປີດບັນຊີ → rpc set_user_disabled. Footer: "ທ່ານບໍ່ສາມາດປ່ຽນ Role ຂອງຕົນເອງ, ແລະ ລະບົບຕ້ອງມີຜູ້ດູແລຢ່າງໜ້ອຍ 1 ຄົນສະເໝີ."
  (the database enforces both — show its Lao error message in a toast).
- Audit Log card: latest 6 rows (coloured dot, "<actor> <action label> <summary>", date · module) + "ທັງໝົດ" → full paginated list with action filter.
  Action labels: member_create ເພີ່ມສະມາຊິກ, member_update ແກ້ໄຂສະມາຊິກ, member_delete ລຶບສະມາຊິກ, member_unlink ເອົາອອກຈາກສາຍພໍ່,
  member_reorder ຈັດລຳດັບລູກ, user_approve ອະນຸມັດຜູ້ໃຊ້, user_reject ປະຕິເສດຜູ້ໃຊ້, role_change ປ່ຽນ Role, user_link_member ເຊື່ອມບັນຊີ,
  user_disable/user_enable ປິດ/ເປີດບັນຊີ, tx_insert/tx_update/tx_delete ບັນທຶກ/ແກ້ໄຂ/ລຶບທຸລະກຳ, payment_insert/update/delete ການຊຳລະ,
  donation_pledges_*, fund_members_*, leadership_*, elders_* (ຄະນະນຳ / ຜູ້ອາວຸໂສ / ກອງທຶນ / ຜູ້ບໍລິຈາກ).
```

## Prompt 15 — Profile

Attach: Profile artboard.

```
/profile (any signed-in user, incl. pending). Header card: big avatar with camera button (upload to bucket avatars/<user_id>/…,
save profiles.avatar_url), name, "email · ເຂົ້າສູ່ລະບົບດ້ວຍ Google|ອີເມວ", role badge, "ສະມາຊິກແຕ່ mm/yyyy" (user_roles.approved_at), "ອອກຈາກລະບົບ".
Cards: "ຂໍ້ມູນບັນຊີ" (display_name + the linked member card "Phaj 4 · ລູກຂອງ ເຈ ຊົ່ງ  ເບິ່ງ →" or "ຍັງບໍ່ໄດ້ເຊື່ອມ"),
"ປ່ຽນລະຫັດຜ່ານ" (supabase.auth.updateUser; strength meter; "ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 8 ຕົວອັກສອນ"),
"ການສະແດງຜົນ": Segmented ແຈ້ງ / ມືດ / ຕາມອຸປະກອນ and ປົກກະຕິ / ໃຫຍ່ 112% / ໃຫຍ່ຫຼາຍ 125% applied instantly and saved to profiles,
with a preview box. PendingBanner when role = pending.
```

## Prompt 16 — Global search, notifications, realtime

Attach: CommandSearch and Notifications artboards.

```
1) Command dialog (Ctrl/Cmd + K from anywhere, or the header search): input debounce 300 ms → rpc global_search(p_q) (members + events,
   phones only for members — the function handles it); also filter a static page list by role and quick commands
   ("ປ່ຽນເປັນໂໝດມືດ / ແຈ້ງ", member+ "ເພີ່ມສະມາຊິກ", "ສ້າງກິດຈະກຳ", admin "ບັນທຶກທຸລະກຳ"). Groups: ສະມາຊິກ · ໜ້າ · ກິດຈະກຳ · ຄຳສັ່ງດ່ວນ.
   ↑↓ select, Enter open, Esc close; footer "↑↓ ເລືອກ · Enter ເປີດ · ຜົນສະແດງຕາມສິດຂອງທ່ານ". Keep the last 3 queries in localStorage (try/catch).
2) Notifications popover (420px, signed-in only): rpc get_notifications() → tabs ທັງໝົດ / ຍັງບໍ່ອ່ານ (n), groups ມື້ນີ້ / 7 ວັນຜ່ານມາ,
   unread dot, click marks read (rpc mark_notifications_read(p_keys => [key])) and navigates (event:<id> → /activities/<id>,
   news:<id> → /news/<id>, pending_users → /admin); "ໝາຍວ່າອ່ານທັງໝົດ" → rpc mark_notifications_read(). Bell shows the unread count.
3) Realtime: subscribe to postgres_changes on news, news_comments, events, transactions → only invalidate the related TanStack queries
   (do not merge payloads). removeChannel on unmount.
```

## Prompt 17 — QA pass

```
Review the whole app against the design artboards at 375px and 1440px, light and dark, and font scale 125%:
no horizontal scrolling, tables become cards on mobile, all buttons ≥ 44px, every list has loading/empty/error states,
all text is Lao, no hard-coded hex colours in components, no mock data anywhere (remove any placeholder arrays).
Test as Guest, Pending, Member and Admin that each page shows only what the permission rules allow.
```
