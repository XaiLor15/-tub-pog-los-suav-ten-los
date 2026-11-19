-- =====================================================================
-- Kwv Tij Hmoob Xyooj — 03 RPC functions + views
-- Multi-step operations run in ONE transaction inside a function.
-- Every function sets search_path = public and checks the caller's role itself.
-- Call from the frontend:  supabase.rpc('reorder_children', { p_father_id, p_ids })
-- =====================================================================

create or replace function public.require_member() returns void language plpgsql stable security definer set search_path = public as $$
begin
  if not public.is_member() then raise exception 'ທ່ານບໍ່ມີສິດດຳເນີນການນີ້' using errcode = '42501'; end if;
end $$;

create or replace function public.require_admin() returns void language plpgsql stable security definer set search_path = public as $$
begin
  if not public.is_admin() then raise exception 'ທ່ານບໍ່ມີສິດດຳເນີນການນີ້' using errcode = '42501'; end if;
end $$;

-- ===================== Family =====================

-- Internal: assign birth_order 1..n in the given order (cleared first so the unique index never collides).
create or replace function public.resequence_children(p_father_id uuid, p_ids uuid[])
returns void language plpgsql security definer set search_path = public as $$
begin
  perform set_config('app.via_rpc', 'on', true);
  update public.members set birth_order = null where father_id = p_father_id;
  update public.members m set birth_order = o.ord
  from unnest(p_ids) with ordinality as o(id, ord)
  where m.id = o.id and m.father_id = p_father_id and m.deleted_at is null;
end $$;
revoke execute on function public.resequence_children(uuid, uuid[]) from public, anon, authenticated;

-- Internal: current children of a father in birth order (locked).
create or replace function public.child_ids(p_father_id uuid)
returns uuid[] language sql security definer set search_path = public as $$
  select coalesce(array_agg(id order by birth_order nulls last, created_at), '{}')
  from (select id, birth_order, created_at from public.members
        where father_id = p_father_id and deleted_at is null for update) c
$$;
revoke execute on function public.child_ids(uuid) from public, anon, authenticated;

-- create_member(..., father_id, insert_at): insert + place among siblings (default: last).
create or replace function public.create_member(
  p_name text, p_sex public.sex, p_father_id uuid default null, p_insert_at int default null,
  p_generation smallint default null, p_birth_date date default null, p_birth_year_only boolean default false,
  p_is_alive boolean default true, p_death_year smallint default null, p_hmong_name text default null,
  p_phone text default null, p_province text default null, p_village text default null, p_photo_url text default null)
returns uuid language plpgsql security definer set search_path = public as $$
declare
  v_id uuid;
  v_ids uuid[];
  v_pos int;
begin
  perform public.require_member();
  if p_father_id is null and (p_generation is null or p_generation < 1) then
    raise exception 'ບໍ່ມີພໍ່ (ຕົ້ນຕະກູນ) — ກະລຸນາໃສ່ Phaj ≥ 1' using errcode = '23514';
  end if;
  if p_father_id is not null then
    v_ids := public.child_ids(p_father_id);
  end if;
  perform set_config('app.via_rpc', 'on', true);
  insert into public.members (name, sex, father_id, generation, birth_date, birth_year_only, is_alive, death_year,
                              hmong_name, phone, province, village, photo_url)
  values (btrim(p_name), p_sex, p_father_id, coalesce(p_generation, 1), p_birth_date, coalesce(p_birth_year_only, false),
          coalesce(p_is_alive, true), p_death_year, nullif(btrim(p_hmong_name), ''), nullif(p_phone, ''), p_province, p_village, p_photo_url)
  returning id into v_id;
  if p_father_id is not null then
    v_pos := least(greatest(coalesce(p_insert_at, cardinality(v_ids) + 1), 1), cardinality(v_ids) + 1);
    v_ids := v_ids[1:v_pos - 1] || v_id || v_ids[v_pos:];
    perform public.resequence_children(p_father_id, v_ids);
  end if;
  return v_id;
end $$;

-- Move a member to another father (or make them a root) at a position; Phaj cascades via trigger.
create or replace function public.set_member_father(p_member_id uuid, p_father_id uuid, p_insert_at int default null)
returns void language plpgsql security definer set search_path = public as $$
declare
  v_old uuid;
  v_ids uuid[];
  v_pos int;
begin
  perform public.require_member();
  select father_id into v_old from public.members where id = p_member_id and deleted_at is null for update;
  if not found then raise exception 'ບໍ່ພົບສະມາຊິກ' using errcode = 'P0002'; end if;
  perform set_config('app.via_rpc', 'on', true);
  update public.members set father_id = p_father_id, birth_order = null where id = p_member_id;   -- validates father / cycles
  if v_old is not null and v_old is distinct from p_father_id then
    perform public.resequence_children(v_old, public.child_ids(v_old));
  end if;
  if p_father_id is not null then
    v_ids := array_remove(public.child_ids(p_father_id), p_member_id);
    v_pos := least(greatest(coalesce(p_insert_at, cardinality(v_ids) + 1), 1), cardinality(v_ids) + 1);
    perform public.resequence_children(p_father_id, v_ids[1:v_pos - 1] || p_member_id || v_ids[v_pos:]);
  end if;
end $$;

-- reorder_children(father_id, ids[]): ids must be exactly the father's current children.
create or replace function public.reorder_children(p_father_id uuid, p_ids uuid[])
returns void language plpgsql security definer set search_path = public as $$
declare v_cur uuid[];
begin
  perform public.require_member();
  v_cur := public.child_ids(p_father_id);
  if cardinality(p_ids) <> cardinality(v_cur)
     or (select count(distinct x) from unnest(p_ids) x) <> cardinality(v_cur)
     or exists (select unnest(p_ids) except select unnest(v_cur)) then
    raise exception 'ລາຍການລູກບໍ່ກົງກັບຂໍ້ມູນປັດຈຸບັນ — ກະລຸນາໂຫຼດໜ້າໃໝ່' using errcode = '23514';
  end if;
  perform public.resequence_children(p_father_id, p_ids);
  perform public.write_audit('member_reorder', 'members', p_father_id::text, 'ຈັດລຳດັບລູກໃໝ່', jsonb_build_object('ids', p_ids));
end $$;

-- Move one child up (-1) or down (+1).
create or replace function public.move_child(p_member_id uuid, p_direction int)
returns void language plpgsql security definer set search_path = public as $$
declare
  v_father uuid;
  v_ids uuid[];
  i int;
  j int;
  tmp uuid;
begin
  perform public.require_member();
  select father_id into v_father from public.members where id = p_member_id;
  if v_father is null then return; end if;
  v_ids := public.child_ids(v_father);
  i := array_position(v_ids, p_member_id);
  j := i + sign(p_direction)::int;
  if j < 1 or j > cardinality(v_ids) then return; end if;
  tmp := v_ids[i]; v_ids[i] := v_ids[j]; v_ids[j] := tmp;
  perform public.reorder_children(v_father, v_ids);
end $$;

-- unlink_from_father(member_id): father_id = NULL, siblings close the gap. The member is NEVER deleted.
create or replace function public.unlink_from_father(p_member_id uuid)
returns void language plpgsql security definer set search_path = public as $$
declare v_father uuid;
begin
  perform public.require_member();
  select father_id into v_father from public.members where id = p_member_id and deleted_at is null for update;
  if v_father is null then return; end if;
  perform set_config('app.via_rpc', 'on', true);
  update public.members set father_id = null, birth_order = null where id = p_member_id;
  perform public.resequence_children(v_father, public.child_ids(v_father));
end $$;

-- Admin soft delete; refused while children are linked or the member holds a leadership/elder role.
create or replace function public.soft_delete_member(p_member_id uuid)
returns void language plpgsql security definer set search_path = public as $$
declare v_father uuid;
begin
  perform public.require_admin();
  if exists (select 1 from public.members where father_id = p_member_id and deleted_at is null) then
    raise exception 'ສະມາຊິກນີ້ຍັງມີລູກເຊື່ອມຢູ່ — ກະລຸນາຍ້າຍ ຫຼື ເອົາລູກອອກກ່ອນ' using errcode = '23503';
  end if;
  if exists (select 1 from public.leadership where member_id = p_member_id) or exists (select 1 from public.elders where member_id = p_member_id) then
    raise exception 'ສະມາຊິກນີ້ຢູ່ໃນຄະນະນຳ ຫຼື ຜູ້ອາວຸໂສ — ກະລຸນາເອົາອອກກ່ອນ' using errcode = '23503';
  end if;
  select father_id into v_father from public.members where id = p_member_id for update;
  perform set_config('app.via_rpc', 'on', true);
  update public.members set deleted_at = now(), birth_order = null where id = p_member_id and deleted_at is null;
  if v_father is not null then
    perform public.resequence_children(v_father, public.child_ids(v_father));
  end if;
end $$;

-- get_siblings(member_id): all children of the same father, in birth order (incl. self).
create or replace function public.get_siblings(p_member_id uuid)
returns table (id uuid, name text, sex public.sex, birth_order smallint, birth_year int, is_self boolean)
language sql stable security definer set search_path = public as $$
  select m.id, m.name, m.sex, m.birth_order, extract(year from m.birth_date)::int, m.id = p_member_id
  from public.members m
  where m.deleted_at is null and m.father_id = (select father_id from public.members where id = p_member_id)
  order by m.birth_order nulls last, m.created_at
$$;

-- Lifespan text: "1890 – 1965" / "1978" / "—"
create or replace function public.life_span(p_birth date, p_alive boolean, p_death smallint)
returns text language sql immutable as $$
  select case
    when not p_alive then coalesce(extract(year from p_birth)::text, '?') || ' – ' || coalesce(p_death::text, '?')
    else coalesce(extract(year from p_birth)::text, '—') end
$$;

-- get_family_tree(root_id, depth): flat node list (paternal line). Daughters are leaves. Safe for guests.
create or replace function public.get_family_tree(p_root_id uuid default null, p_depth int default 6)
returns table (id uuid, name text, sex public.sex, generation smallint, birth_order smallint, father_id uuid,
               years text, is_alive boolean, child_count bigint, level int)
language sql stable security definer set search_path = public as $$
  with recursive t as (
    select m.*, 0 as lvl from public.members m
    where m.deleted_at is null and (case when p_root_id is null then m.father_id is null else m.id = p_root_id end)
    union all
    select c.*, t.lvl + 1 from public.members c join t on c.father_id = t.id
    where c.deleted_at is null and t.sex = 'male' and t.lvl + 1 < least(greatest(p_depth, 1), 12)
  )
  select t.id, t.name, t.sex, t.generation, t.birth_order, case when t.lvl = 0 then null else t.father_id end,
         public.life_span(t.birth_date, t.is_alive, t.death_year) , t.is_alive,
         (select count(*) from public.members k where k.father_id = t.id and k.deleted_at is null), t.lvl
  from t order by t.generation, t.father_id nulls first, t.birth_order nulls last, t.name
$$;

-- get_ancestors(member_id): root → member, each with phaj_ok (child Phaj = father Phaj + 1).
create or replace function public.get_ancestors(p_member_id uuid)
returns table (id uuid, name text, sex public.sex, generation smallint, birth_order smallint, years text, phaj_ok boolean, depth int)
language sql stable security definer set search_path = public as $$
  with recursive a as (
    select m.*, 0 as d from public.members m where m.id = p_member_id
    union all
    select f.*, a.d + 1 from public.members f join a on f.id = a.father_id where a.d < 64
  )
  select a.id, a.name, a.sex, a.generation, a.birth_order, public.life_span(a.birth_date, a.is_alive, a.death_year),
         coalesce(a.generation = (lag(a.generation) over (order by a.d desc)) + 1, true), a.d
  from a order by a.d desc
$$;

-- get_descendant_ids(member_id): every descendant (excludes the member). Used to disable choices in the father picker.
create or replace function public.get_descendant_ids(p_member_id uuid)
returns uuid[] language sql stable security definer set search_path = public as $$
  with recursive d as (
    select id from public.members where father_id = p_member_id
    union select m.id from public.members m join d on m.father_id = d.id
  ) select coalesce(array_agg(id), '{}') from d
$$;

-- find_duplicate_members(name, birth_date): warning only, never blocks.
create or replace function public.find_duplicate_members(p_name text, p_birth_date date default null, p_exclude uuid default null)
returns table (id uuid, name text, generation smallint, birth_date date, father_name text, birth_order smallint, sex public.sex)
language sql stable security definer set search_path = public as $$
  select m.id, m.name, m.generation, m.birth_date, f.name, m.birth_order, m.sex
  from public.members m left join public.members f on f.id = m.father_id
  where public.is_member() and m.deleted_at is null
    and lower(btrim(m.name)) = lower(btrim(p_name))
    and (p_birth_date is null or m.birth_date = p_birth_date)
    and (p_exclude is null or m.id <> p_exclude)
  limit 5
$$;

-- ===================== Finance =====================

-- Manual transaction (Admin). Idempotent on client_request_id.
create or replace function public.create_transaction(
  p_date date, p_description text, p_category text, p_type text, p_amount numeric,
  p_event_id uuid default null, p_client_request_id uuid default null)
returns uuid language plpgsql security definer set search_path = public as $$
declare v_id uuid;
begin
  perform public.require_admin();
  if p_client_request_id is not null then
    select id into v_id from public.transactions where client_request_id = p_client_request_id;
    if found then return v_id; end if;
  end if;
  insert into public.transactions (date, description, category, type, amount, event_id, source, client_request_id)
  values (p_date, btrim(p_description), p_category, p_type, p_amount, p_event_id, 'manual', p_client_request_id)
  on conflict (client_request_id) do nothing
  returning id into v_id;
  if v_id is null then
    select id into v_id from public.transactions where client_request_id = p_client_request_id;
  end if;
  return v_id;
end $$;

-- find_similar_transactions(date, amount, category): same amount + category within ±24 h.
create or replace function public.find_similar_transactions(p_date date, p_amount numeric, p_category text, p_exclude uuid default null)
returns setof public.transactions language sql stable security invoker set search_path = public as $$
  select * from public.transactions
  where date between p_date - 1 and p_date + 1 and amount = p_amount and category = p_category
    and (p_exclude is null or id <> p_exclude)
  limit 5
$$;

-- record_donation_payment: payment + its ONE income transaction, in one DB transaction.
-- Pass p_pledge_id, or p_member_id + p_event_id to create a pledge without an expected amount.
create or replace function public.record_donation_payment(
  p_amount numeric, p_paid_on date, p_method text, p_pledge_id uuid default null, p_note text default null,
  p_slip_path text default null, p_client_request_id uuid default null, p_member_id uuid default null, p_event_id uuid default null)
returns uuid language plpgsql security definer set search_path = public as $$
declare
  v_pledge public.donation_pledges%rowtype;
  v_tx uuid;
  v_payment uuid;
  v_desc text;
begin
  perform public.require_admin();
  if p_amount is null or p_amount <= 0 then raise exception 'ຈຳນວນເງິນຕ້ອງຫຼາຍກວ່າ 0' using errcode = '23514'; end if;
  if p_client_request_id is not null then
    select p.id into v_payment from public.donation_payments p join public.transactions t on t.id = p.transaction_id
    where t.client_request_id = p_client_request_id;
    if found then return v_payment; end if;      -- idempotent re-submit
  end if;
  if p_pledge_id is null then
    if p_member_id is null or p_event_id is null then raise exception 'ກະລຸນາເລືອກຜູ້ບໍລິຈາກ' using errcode = '23502'; end if;
    insert into public.donation_pledges (member_id, event_id, expected_amount, created_by)
    values (p_member_id, p_event_id, null, auth.uid()) on conflict (member_id, event_id) do nothing;
    select * into v_pledge from public.donation_pledges where member_id = p_member_id and event_id = p_event_id for update;
  else
    select * into v_pledge from public.donation_pledges where id = p_pledge_id for update;
    if not found then raise exception 'ບໍ່ພົບລາຍການບໍລິຈາກ' using errcode = 'P0002'; end if;
  end if;

  select 'ບໍລິຈາກ ' || coalesce(e.title, '') || ' — ' || coalesce(m.name, '') into v_desc
  from public.events e, public.members m where e.id = v_pledge.event_id and m.id = v_pledge.member_id;

  perform set_config('app.via_rpc', 'on', true);
  insert into public.transactions (date, description, category, type, amount, event_id, source, client_request_id)
  values (p_paid_on, left(v_desc, 255), 'ບໍລິຈາກ', 'income', p_amount, v_pledge.event_id, 'donation', p_client_request_id)
  returning id into v_tx;
  insert into public.donation_payments (pledge_id, transaction_id, amount, paid_on, method, note, slip_path, created_by)
  values (v_pledge.id, v_tx, p_amount, p_paid_on, p_method, p_note, p_slip_path, auth.uid())
  returning id into v_payment;
  perform set_config('app.via_rpc', '', true);
  return v_payment;
end $$;

create or replace function public.update_donation_payment(p_payment_id uuid, p_amount numeric, p_paid_on date, p_method text, p_note text default null)
returns void language plpgsql security definer set search_path = public as $$
declare v_tx uuid;
begin
  perform public.require_admin();
  if p_amount is null or p_amount <= 0 then raise exception 'ຈຳນວນເງິນຕ້ອງຫຼາຍກວ່າ 0' using errcode = '23514'; end if;
  select transaction_id into v_tx from public.donation_payments where id = p_payment_id for update;
  if not found then raise exception 'ບໍ່ພົບການຊຳລະ' using errcode = 'P0002'; end if;
  perform set_config('app.via_rpc', 'on', true);
  update public.donation_payments set amount = p_amount, paid_on = p_paid_on, method = p_method, note = p_note where id = p_payment_id;
  update public.transactions set amount = p_amount, date = p_paid_on where id = v_tx;
  perform set_config('app.via_rpc', '', true);
end $$;

create or replace function public.delete_donation_payment(p_payment_id uuid)
returns text language plpgsql security definer set search_path = public as $$
declare
  v_tx uuid;
  v_slip text;
begin
  perform public.require_admin();
  select transaction_id, slip_path into v_tx, v_slip from public.donation_payments where id = p_payment_id for update;
  if not found then raise exception 'ບໍ່ພົບການຊຳລະ' using errcode = 'P0002'; end if;
  perform set_config('app.via_rpc', 'on', true);
  delete from public.donation_payments where id = p_payment_id;
  delete from public.transactions where id = v_tx;
  perform set_config('app.via_rpc', '', true);
  return v_slip;   -- frontend removes the slip file from storage
end $$;

-- v_finance_summary for a date range (members only — RLS on transactions returns nothing to others).
create or replace function public.finance_summary(p_from date default null, p_to date default null)
returns table (total_income numeric, total_expense numeric, balance numeric, donation_income numeric, tx_count bigint, expense_count bigint)
language sql stable security invoker set search_path = public as $$
  select coalesce(sum(amount) filter (where type = 'income'), 0),
         coalesce(sum(amount) filter (where type = 'expense'), 0),
         coalesce(sum(amount) filter (where type = 'income'), 0) - coalesce(sum(amount) filter (where type = 'expense'), 0),
         coalesce(sum(amount) filter (where source = 'donation'), 0),
         count(*), count(*) filter (where type = 'expense')
  from public.transactions
  where (p_from is null or date >= p_from) and (p_to is null or date <= p_to)
$$;

-- ===================== Users / Admin =====================

create or replace function public.approve_user(p_user_id uuid, p_member_id uuid default null)
returns void language plpgsql security definer set search_path = public as $$
begin
  perform public.require_admin();
  if not exists (select 1 from public.user_roles where user_id = p_user_id and role = 'pending') then
    raise exception 'ບັນຊີນີ້ບໍ່ໄດ້ຢູ່ໃນສະຖານະລໍຖ້າອະນຸມັດ' using errcode = '23514';
  end if;
  if p_member_id is not null then
    if exists (select 1 from public.profiles where member_id = p_member_id and id <> p_user_id) then
      raise exception 'ສະມາຊິກນີ້ເຊື່ອມກັບບັນຊີອື່ນແລ້ວ' using errcode = '23505';
    end if;
    update public.profiles set member_id = p_member_id where id = p_user_id;
  end if;
  update public.user_roles set role = 'member', approved_by = auth.uid(), approved_at = now(), rejected_at = null where user_id = p_user_id;
  perform public.write_audit('user_approve', 'user_roles', p_user_id::text, (select display_name from public.profiles where id = p_user_id));
end $$;

create or replace function public.reject_user(p_user_id uuid)
returns void language plpgsql security definer set search_path = public as $$
begin
  perform public.require_admin();
  update public.user_roles set rejected_at = now(), is_disabled = true where user_id = p_user_id;
  perform public.write_audit('user_reject', 'user_roles', p_user_id::text, (select display_name from public.profiles where id = p_user_id));
end $$;

-- Role change (member ↔ admin). Self-change and removing the last admin are blocked by trigger guard_user_roles.
create or replace function public.set_user_role(p_user_id uuid, p_role public.app_role)
returns void language plpgsql security definer set search_path = public as $$
begin
  perform public.require_admin();
  if p_role not in ('member', 'admin') then raise exception 'Role ບໍ່ຖືກຕ້ອງ' using errcode = '22023'; end if;
  update public.user_roles set role = p_role,
         approved_at = coalesce(approved_at, now()), approved_by = coalesce(approved_by, auth.uid())
  where user_id = p_user_id;
end $$;

create or replace function public.set_user_disabled(p_user_id uuid, p_disabled boolean)
returns void language plpgsql security definer set search_path = public as $$
begin
  perform public.require_admin();
  update public.user_roles set is_disabled = p_disabled, rejected_at = case when p_disabled then rejected_at end where user_id = p_user_id;
end $$;

create or replace function public.link_user_member(p_user_id uuid, p_member_id uuid)
returns void language plpgsql security definer set search_path = public as $$
begin
  perform public.require_admin();
  if p_member_id is not null and exists (select 1 from public.profiles where member_id = p_member_id and id <> p_user_id) then
    raise exception 'ສະມາຊິກນີ້ເຊື່ອມກັບບັນຊີອື່ນແລ້ວ' using errcode = '23505';
  end if;
  update public.profiles set member_id = p_member_id where id = p_user_id;
  perform public.write_audit('user_link_member', 'profiles', p_user_id::text, (select name from public.members where id = p_member_id));
end $$;

-- admin_list_users(role?): email + last sign-in come from auth.users (Admin only).
create or replace function public.admin_list_users(p_role public.app_role default null)
returns table (user_id uuid, email text, display_name text, role public.app_role, is_disabled boolean, rejected_at timestamptz,
               member_id uuid, member_name text, member_generation smallint, auth_provider text, created_at timestamptz, last_sign_in_at timestamptz)
language sql stable security definer set search_path = public as $$
  select u.id, u.email::text, p.display_name, r.role, r.is_disabled, r.rejected_at, p.member_id, m.name, m.generation,
         p.auth_provider, u.created_at, u.last_sign_in_at
  from auth.users u join public.profiles p on p.id = u.id join public.user_roles r on r.user_id = u.id
  left join public.members m on m.id = p.member_id
  where public.is_admin() and (p_role is null or r.role = p_role)
  order by (r.role = 'pending') desc, u.created_at desc
$$;

-- match_pending_user: members whose name matches the account's display name and are not linked yet.
create or replace function public.match_pending_user(p_user_id uuid)
returns table (id uuid, name text, generation smallint, father_name text)
language sql stable security definer set search_path = public as $$
  select m.id, m.name, m.generation, f.name
  from public.members m left join public.members f on f.id = m.father_id, public.profiles p
  where public.is_admin() and p.id = p_user_id and m.deleted_at is null
    and not exists (select 1 from public.profiles x where x.member_id = m.id)
    and (lower(m.name) = lower(p.display_name) or m.name ilike split_part(p.display_name, ' ', 1) || '%'
         or m.hmong_name ilike '%' || split_part(p.display_name, ' ', 1) || '%')
  order by (lower(m.name) = lower(p.display_name)) desc
  limit 3
$$;

-- reorder_leadership(ids[]): save the whole order at once.
create or replace function public.reorder_leadership(p_ids uuid[])
returns void language plpgsql security definer set search_path = public as $$
begin
  perform public.require_admin();
  update public.leadership l set sort_order = o.ord from unnest(p_ids) with ordinality as o(id, ord) where l.id = o.id;
  perform public.write_audit('leadership_reorder', 'leadership', null, 'ຈັດລຳດັບຄະນະນຳ');
end $$;

-- ===================== Search & notifications =====================

-- global_search(q): members (Lao + Hmong names), events — filtered by the caller's role. Phone only for members.
create or replace function public.global_search(p_q text)
returns table (kind text, id uuid, title text, subtitle text, sex public.sex)
language sql stable security definer set search_path = public, extensions as $$
  (select 'member', m.id, m.name || coalesce(' (' || m.hmong_name || ')', ''),
          concat_ws(' · ', 'Phaj ' || m.generation, m.province, case when public.is_member() then m.phone end), m.sex
   from public.members m
   where m.deleted_at is null and length(btrim(p_q)) > 0
     and (m.name ilike '%' || btrim(p_q) || '%' or m.hmong_name ilike '%' || btrim(p_q) || '%' or m.name % btrim(p_q))
   order by similarity(m.name, btrim(p_q)) desc, m.generation limit 6)
  union all
  (select 'event', e.id, e.title, to_char(e.start_date, 'DD/MM/YYYY') || coalesce(' · ' || e.location, ''), null
   from public.events e
   where length(btrim(p_q)) > 0 and (e.is_public or public.is_member())
     and (e.title ilike '%' || btrim(p_q) || '%' or e.location ilike '%' || btrim(p_q) || '%')
   order by e.start_date desc limit 4)
$$;

-- get_notifications(): events in the next 7 days, news from the last 7 days, pending users (Admin) + read state.
create or replace function public.get_notifications()
returns table (item_key text, kind text, title text, subtitle text, record_id uuid, at timestamptz, is_read boolean)
language sql stable security definer set search_path = public as $$
  with items as (
    select 'pending_users:' || count(*) as item_key, 'pending_users' as kind,
           'ມີຜູ້ໃຊ້ໃໝ່ ' || count(*) || ' ຄົນ ລໍຖ້າອະນຸມັດ' as title,
           string_agg(p.display_name, ', ' order by r.created_at desc) as subtitle, null::uuid as record_id, max(r.created_at) as at
    from public.user_roles r join public.profiles p on p.id = r.user_id
    where public.is_admin() and r.role = 'pending' and r.rejected_at is null and not r.is_disabled
    having count(*) > 0
    union all
    select 'event:' || e.id, 'event', e.title, to_char(e.start_date, 'DD/MM/YYYY') || coalesce(' · ' || to_char(e.start_time, 'HH24:MI'), '') || coalesce(' · ' || e.location, ''),
           e.id, e.start_date::timestamptz
    from public.events e
    where auth.uid() is not null and (e.is_public or public.is_member()) and e.start_date between current_date and current_date + 7
    union all
    select 'news:' || n.id, 'news', 'ຂ່າວໃໝ່: ' || n.title, n.category || ' · ' || to_char(n.published_at, 'DD/MM/YYYY'), n.id, n.published_at
    from public.news n
    where auth.uid() is not null and n.status = 'published' and n.published_at >= now() - interval '7 days'
  )
  select i.item_key, i.kind, i.title, i.subtitle, i.record_id, i.at,
         exists (select 1 from public.notification_reads nr where nr.user_id = auth.uid() and nr.item_key = i.item_key)
  from items i order by i.at desc
$$;

create or replace function public.mark_notifications_read(p_keys text[] default null)
returns void language plpgsql security definer set search_path = public as $$
begin
  if auth.uid() is null then raise exception 'ກະລຸນາເຂົ້າສູ່ລະບົບ' using errcode = '42501'; end if;
  insert into public.notification_reads (user_id, item_key)
  select auth.uid(), k from unnest(coalesce(p_keys, array(select item_key from public.get_notifications()))) k
  on conflict (user_id, item_key) do update set read_at = now();
end $$;

-- ===================== Views =====================
-- *_public views expose only columns Guests/Pending may see (birth YEAR, no phone, no village).
-- They run with the owner's rights on purpose (security_barrier keeps filters safe).

create or replace view public.members_public with (security_barrier = true) as
  select id, name, hmong_name, sex, generation, is_alive, father_id, birth_order,
         extract(year from birth_date)::int as birth_year, death_year, province, photo_url
  from public.members where deleted_at is null;

create or replace view public.leadership_public with (security_barrier = true) as
  select l.id, l.member_id, l.position, l."group", l.sort_order, l.term_start, l.term_end,
         coalesce(l.photo_url, m.photo_url) as photo_url, m.name, m.sex
  from public.leadership l join public.members m on m.id = l.member_id;

create or replace view public.elders_public with (security_barrier = true) as
  select e.id, e.member_id, e.role_title, e.role_category, e.bio, e.skills, e.status,
         coalesce(e.photo_url, m.photo_url) as photo_url, m.name, m.sex, m.generation, m.is_alive,
         extract(year from m.birth_date)::int as birth_year, m.death_year,
         (select coalesce(array_agg(s.member_id), '{}') from public.elder_successors s where s.elder_id = e.id) as successor_ids
  from public.elders e join public.members m on m.id = e.member_id;

create or replace view public.v_dashboard_stats as
  select (select count(*) from public.members where deleted_at is null) as members,
         (select count(*) from public.members where deleted_at is null and created_at >= now() - interval '30 days') as members_added_30d,
         (select count(*) from public.leadership) as leaders,
         (select min(term_start) from public.leadership) as term_start,
         (select max(term_end) from public.leadership) as term_end,
         (select count(*) from public.elders) as elders,
         (select count(*) from public.elders where role_category = 'rite') as elders_rite,
         (select count(distinct generation) from public.members where deleted_at is null) as generations,
         (select count(*) from public.events where extract(year from start_date) = extract(year from current_date)) as events_this_year,
         (select count(*) from public.events where coalesce(end_date, start_date) >= current_date) as upcoming_events,
         (select count(*) from public.news where status = 'published') as news_total,
         (select count(*) from public.news where status = 'published' and published_at >= now() - interval '7 days') as news_added_7d;

create or replace view public.v_member_breakdown as
  select generation, sex, is_alive, count(*) as count
  from public.members where deleted_at is null group by generation, sex, is_alive;

create or replace view public.v_event_participant_counts as
  select p.event_id, count(*) as participants from public.participants p
  join public.events e on e.id = p.event_id where e.is_public or public.is_member() group by p.event_id;

-- Money views run with the CALLER's rights → Guests / Pending get no rows.
create or replace view public.v_finance_summary with (security_invoker = true) as
  select * from public.finance_summary(null, null);

create or replace view public.v_finance_monthly with (security_invoker = true) as
  select to_char(m, 'YYYY-MM') as month,
         coalesce(sum(t.amount) filter (where t.type = 'income'), 0) as income,
         coalesce(sum(t.amount) filter (where t.type = 'expense'), 0) as expense
  from generate_series(date_trunc('month', current_date) - interval '11 months', date_trunc('month', current_date), interval '1 month') m
  left join public.transactions t on date_trunc('month', t.date) = m
  group by m order by m;

create or replace view public.v_donation_progress with (security_invoker = true) as
  select p.id as pledge_id, p.event_id, p.member_id, m.name as member_name, m.sex as member_sex,
         p.expected_amount as expected, coalesce(sum(d.amount), 0) as paid_total,
         greatest(coalesce(p.expected_amount, 0) - coalesce(sum(d.amount), 0), 0) as outstanding,
         case when coalesce(p.expected_amount, 0) > 0 then round(coalesce(sum(d.amount), 0) * 100.0 / p.expected_amount, 1) end as progress_pct,
         case when coalesce(sum(d.amount), 0) = 0 then 'unpaid'
              when coalesce(p.expected_amount, 0) = 0 then 'paid'
              when sum(d.amount) < p.expected_amount then 'partial'
              when sum(d.amount) > p.expected_amount then 'over'
              else 'paid' end as status,
         max(d.paid_on) as last_paid_on, count(d.id) as payments
  from public.donation_pledges p join public.members m on m.id = p.member_id
  left join public.donation_payments d on d.pledge_id = p.id
  group by p.id, m.name, m.sex;

create or replace view public.v_donation_event_totals with (security_invoker = true) as
  select event_id, sum(coalesce(expected, 0)) as expected, sum(paid_total) as paid, sum(outstanding) as outstanding,
         case when sum(coalesce(expected, 0)) > 0 then round(sum(paid_total) * 100.0 / sum(coalesce(expected, 0)), 1) end as pct,
         count(*) as donors,
         count(*) filter (where status = 'paid') as count_paid, count(*) filter (where status = 'partial') as count_partial,
         count(*) filter (where status = 'unpaid') as count_unpaid, count(*) filter (where status = 'over') as count_over,
         count(*) filter (where outstanding > 0) as people_outstanding
  from public.v_donation_progress group by event_id;

create or replace view public.v_event_summary with (security_invoker = true) as
  select e.id as event_id,
         (select count(*) from public.participants p where p.event_id = e.id) as participants,
         coalesce((select sum(amount) from public.transactions t where t.event_id = e.id and t.source = 'donation'), 0) as donations,
         coalesce((select sum(amount) from public.transactions t where t.event_id = e.id and t.type = 'income'), 0) as income,
         coalesce((select sum(amount) from public.transactions t where t.event_id = e.id and t.type = 'expense'), 0) as expenses,
         coalesce((select sum(case when type = 'income' then amount else -amount end) from public.transactions t where t.event_id = e.id), 0) as net
  from public.events e;

create or replace view public.v_fund_contributor_totals with (security_invoker = true) as
  select f.id, f.member_id, m.name, m.sex, m.province, m.village, f.joined_at,
         coalesce(sum(d.amount), 0) as total_donated, count(d.id) as times
  from public.fund_members f join public.members m on m.id = f.member_id
  left join public.donation_pledges p on p.member_id = f.member_id
  left join public.donation_payments d on d.pledge_id = p.id
  group by f.id, m.name, m.sex, m.province, m.village;

create or replace view public.v_fund_by_province with (security_invoker = true) as
  select coalesce(province, 'ບໍ່ລະບຸ') as province, count(*) as contributors, sum(total_donated) as total
  from public.v_fund_contributor_totals group by 1 order by total desc;
