-- =====================================================================
-- Kwv Tij Hmoob Xyooj — 02 Role helpers + triggers (business rules in the DB)
-- =====================================================================

-- ---------- Role helpers (SECURITY DEFINER so RLS policies can call them) ----------
create or replace function public.current_app_role()
returns public.app_role language sql stable security definer set search_path = public as $$
  select ur.role from public.user_roles ur
  where ur.user_id = auth.uid() and not ur.is_disabled and ur.rejected_at is null
$$;

create or replace function public.is_member()   -- approved member OR admin
returns boolean language sql stable security definer set search_path = public as $$
  select coalesce(public.current_app_role() in ('member', 'admin'), false)
$$;

create or replace function public.is_admin()
returns boolean language sql stable security definer set search_path = public as $$
  select coalesce(public.current_app_role() = 'admin', false)
$$;

create or replace function public.my_member_id()
returns uuid language sql stable security definer set search_path = public as $$
  select member_id from public.profiles where id = auth.uid()
$$;

-- ---------- Generic helpers ----------
create or replace function public.set_updated_at()
returns trigger language plpgsql as $$
begin
  new.updated_at := now();
  return new;
end $$;

do $$
declare t text;
begin
  foreach t in array array['profiles','user_roles','members','leadership','elders','events','news','news_comments','transactions','donation_pledges','donation_payments']
  loop
    execute format('drop trigger if exists trg_%1$s_updated_at on public.%1$s', t);
    execute format('create trigger trg_%1$s_updated_at before update on public.%1$s for each row execute function public.set_updated_at()', t);
  end loop;
end $$;

create or replace function public.write_audit(p_action text, p_entity text, p_entity_id text, p_summary text, p_meta jsonb default null)
returns void language sql security definer set search_path = public as $$
  insert into public.audit_logs (actor_id, action, entity, entity_id, summary, meta)
  values (auth.uid(), p_action, p_entity, p_entity_id, left(p_summary, 500), p_meta)
$$;
revoke execute on function public.write_audit(text, text, text, text, jsonb) from public, anon, authenticated;

-- ---------- handle_new_user: every new account (email or Google) → profile + role 'pending' ----------
create or replace function public.handle_new_user()
returns trigger language plpgsql security definer set search_path = public as $$
begin
  insert into public.profiles (id, display_name, avatar_url, auth_provider)
  values (
    new.id,
    left(coalesce(nullif(new.raw_user_meta_data ->> 'display_name', ''), nullif(new.raw_user_meta_data ->> 'full_name', ''),
                  nullif(new.raw_user_meta_data ->> 'name', ''), split_part(new.email, '@', 1), 'User'), 100),
    new.raw_user_meta_data ->> 'avatar_url',
    coalesce(new.raw_app_meta_data ->> 'provider', 'email')
  )
  on conflict (id) do nothing;
  insert into public.user_roles (user_id, role) values (new.id, 'pending') on conflict (user_id) do nothing;
  return new;
end $$;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created after insert on auth.users
  for each row execute function public.handle_new_user();

-- ---------- validate_member_parents: father male, Phaj = father + 1, no cycles ----------
create or replace function public.validate_member_parents()
returns trigger language plpgsql security definer set search_path = public as $$
declare
  f public.members%rowtype;
begin
  if new.father_id is not null then
    select * into f from public.members where id = new.father_id;
    if not found or f.deleted_at is not null then
      raise exception 'ບໍ່ພົບຂໍ້ມູນພໍ່' using errcode = '23503';
    end if;
    if f.sex <> 'male' then
      raise exception 'ພໍ່ຕ້ອງເປັນເພດຊາຍ' using errcode = '23514';
    end if;
    if tg_op = 'UPDATE' and (new.father_id = new.id or exists (
        with recursive d as (
          select id from public.members where father_id = new.id
          union select m.id from public.members m join d on m.father_id = d.id
        ) select 1 from d where d.id = new.father_id)) then
      raise exception 'ເປັນລູກຫຼານ — ເລືອກເປັນພໍ່ບໍ່ໄດ້ (ສາຍພົວພັນວົງວຽນ)' using errcode = '23514';
    end if;
    new.generation := f.generation + 1;
  end if;

  if tg_op = 'UPDATE' and new.sex = 'female' and old.sex = 'male'
     and exists (select 1 from public.members where father_id = new.id and deleted_at is null) then
    raise exception 'ສະມາຊິກນີ້ມີລູກ (ເປັນພໍ່) — ປ່ຽນເພດເປັນຍິງບໍ່ໄດ້' using errcode = '23514';
  end if;

  if new.is_alive then
    new.death_year := null;
  end if;
  if new.birth_year_only and new.birth_date is not null then
    new.birth_date := make_date(extract(year from new.birth_date)::int, 1, 1);
  end if;
  if new.phone is not null then
    new.phone := public.normalize_phone(new.phone);
  end if;
  return new;
end $$;

-- +856 normalisation: "020 5512 3344" → "+8562055123344"
create or replace function public.normalize_phone(p text)
returns text language plpgsql immutable as $$
declare d text := regexp_replace(coalesce(p, ''), '[^0-9+]', '', 'g');
begin
  if d = '' then return null; end if;
  if d like '+856%' then return d; end if;
  if d like '00856%' then return '+' || substr(d, 3); end if;
  if d like '856%' and length(d) >= 10 then return '+' || d; end if;
  if d like '0%' then return '+856' || substr(d, 2); end if;
  if d ~ '^(20[0-9]{8}|30[0-9]{7})$' then return '+856' || d; end if;
  return d;
end $$;

drop trigger if exists trg_members_validate on public.members;
create trigger trg_members_validate before insert or update of father_id, sex, generation, is_alive, death_year, birth_date, birth_year_only, phone
  on public.members for each row execute function public.validate_member_parents();

-- Keep the whole subtree consistent when a member's Phaj changes (each child re-runs the trigger above).
create or replace function public.cascade_generation()
returns trigger language plpgsql security definer set search_path = public as $$
begin
  if new.generation is distinct from old.generation then
    update public.members set generation = new.generation + 1 where father_id = new.id;
  end if;
  return null;
end $$;
drop trigger if exists trg_members_cascade_generation on public.members;
-- Not "UPDATE OF generation": the change usually comes from the BEFORE trigger, not the SET list.
create trigger trg_members_cascade_generation after update on public.members
  for each row when (old.generation is distinct from new.generation) execute function public.cascade_generation();

-- Guard: soft delete / restore and created_by are Admin-only; mother_id is frozen in the UI.
create or replace function public.guard_member_update()
returns trigger language plpgsql security definer set search_path = public as $$
begin
  if auth.uid() is null then return new; end if;   -- service role / SQL editor
  if new.deleted_at is distinct from old.deleted_at and not public.is_admin() then
    raise exception 'ລຶບສະມາຊິກໄດ້ສະເພາະຜູ້ດູແລລະບົບ' using errcode = '42501';
  end if;
  if (new.father_id is distinct from old.father_id or new.birth_order is distinct from old.birth_order)
     and coalesce(current_setting('app.via_rpc', true), '') <> 'on' then
    raise exception 'ປ່ຽນພໍ່ ຫຼື ລຳດັບການເກີດ ຜ່ານ RPC (set_member_father / reorder_children / unlink_from_father) ເທົ່ານັ້ນ' using errcode = '42501';
  end if;
  if new.mother_id is distinct from old.mother_id and not public.is_admin() then
    raise exception 'mother_id ແກ້ໄຂບໍ່ໄດ້' using errcode = '42501';
  end if;
  new.created_by := old.created_by;
  new.updated_by := auth.uid();
  return new;
end $$;
drop trigger if exists trg_members_guard on public.members;
create trigger trg_members_guard before update on public.members
  for each row execute function public.guard_member_update();

create or replace function public.stamp_member_insert()
returns trigger language plpgsql as $$
begin
  if auth.uid() is not null then
    new.created_by := auth.uid();
    new.updated_by := auth.uid();
    if coalesce(current_setting('app.via_rpc', true), '') <> 'on' then
      new.birth_order := null;   -- order is assigned by create_member()
    end if;
  end if;
  return new;
end $$;
drop trigger if exists trg_members_stamp on public.members;
create trigger trg_members_stamp before insert on public.members
  for each row execute function public.stamp_member_insert();

-- ---------- Profiles: users edit their own display fields only ----------
create or replace function public.guard_profile_update()
returns trigger language plpgsql security definer set search_path = public as $$
begin
  if auth.uid() is null or public.is_admin() then return new; end if;
  if new.member_id is distinct from old.member_id then
    raise exception 'ການເຊື່ອມສະມາຊິກ ເຮັດໄດ້ສະເພາະຜູ້ດູແລລະບົບ' using errcode = '42501';
  end if;
  new.auth_provider := old.auth_provider;
  return new;
end $$;
drop trigger if exists trg_profiles_guard on public.profiles;
create trigger trg_profiles_guard before update on public.profiles
  for each row execute function public.guard_profile_update();

-- ---------- protect_last_admin + prevent_self_role_change ----------
create or replace function public.guard_user_roles()
returns trigger language plpgsql security definer set search_path = public as $$
begin
  if tg_op = 'UPDATE' and auth.uid() is not null and new.user_id = auth.uid()
     and (new.role is distinct from old.role or new.is_disabled is distinct from old.is_disabled) then
    raise exception 'ບໍ່ສາມາດປ່ຽນ Role ຫຼື ສະຖານະຂອງຕົນເອງ' using errcode = '42501';
  end if;
  if old.role = 'admin' and not old.is_disabled and (
       tg_op = 'DELETE' or (to_jsonb(new) ->> 'role') <> 'admin' or (to_jsonb(new) ->> 'is_disabled')::boolean
       or (to_jsonb(new) ->> 'rejected_at') is not null)
     and not exists (select 1 from public.user_roles where role = 'admin' and not is_disabled
                     and rejected_at is null and user_id <> old.user_id) then
    raise exception 'ລະບົບຕ້ອງມີຜູ້ດູແລຢ່າງໜ້ອຍ 1 ຄົນສະເໝີ' using errcode = '42501';
  end if;
  return case when tg_op = 'DELETE' then old else new end;
end $$;
drop trigger if exists trg_user_roles_guard on public.user_roles;
create trigger trg_user_roles_guard before update or delete on public.user_roles
  for each row execute function public.guard_user_roles();

-- ---------- block_donation_tx_direct_edit ----------
-- Donation income rows are written only by the donation RPCs (they set app.via_rpc = 'on').
create or replace function public.block_donation_tx_direct_edit()
returns trigger language plpgsql as $$
begin
  if coalesce(current_setting('app.via_rpc', true), '') <> 'on' then
    if (tg_op in ('UPDATE', 'DELETE') and old.source = 'donation') or (tg_op in ('INSERT', 'UPDATE') and new.source = 'donation') then
      raise exception 'ທຸລະກຳນີ້ມາຈາກການບໍລິຈາກ — ແກ້ໄຂໄດ້ຈາກໜ້າຕິດຕາມການບໍລິຈາກເທົ່ານັ້ນ' using errcode = '42501';
    end if;
    if tg_op = 'INSERT' and new.category = 'ບໍລິຈາກ' then
      raise exception 'ລາຍຮັບບໍລິຈາກ ບັນທຶກຜ່ານໜ້າຕິດຕາມການບໍລິຈາກເທົ່ານັ້ນ' using errcode = '23514';
    end if;
  end if;
  return case when tg_op = 'DELETE' then old else new end;
end $$;
drop trigger if exists trg_tx_block_donation on public.transactions;
create trigger trg_tx_block_donation before insert or update or delete on public.transactions
  for each row execute function public.block_donation_tx_direct_edit();

create or replace function public.stamp_transaction()
returns trigger language plpgsql as $$
begin
  if tg_op = 'INSERT' then new.created_by := coalesce(auth.uid(), new.created_by); end if;
  new.updated_by := coalesce(auth.uid(), new.updated_by);
  return new;
end $$;
drop trigger if exists trg_tx_stamp on public.transactions;
create trigger trg_tx_stamp before insert or update on public.transactions
  for each row execute function public.stamp_transaction();

-- ---------- audit_* triggers ----------
create or replace function public.audit_row()
returns trigger language plpgsql security definer set search_path = public as $$
declare
  r jsonb := to_jsonb(case when tg_op = 'DELETE' then old else new end);
  o jsonb := case when tg_op = 'UPDATE' then to_jsonb(old) end;
  act text;
  summary text;
begin
  if tg_table_name = 'members' then
    -- Automatic renumbering (birth_order) and Phaj cascades are audited by the calling RPC.
    if tg_op = 'UPDATE' and (r - array['birth_order','generation','updated_at','updated_by']) = (o - array['birth_order','generation','updated_at','updated_by']) then
      return null;
    end if;
    if tg_op = 'UPDATE' and o ->> 'deleted_at' is null and r ->> 'deleted_at' is not null then act := 'member_delete';
    elsif tg_op = 'UPDATE' and o ->> 'father_id' is not null and r ->> 'father_id' is null then act := 'member_unlink';
    elsif tg_op = 'INSERT' then act := 'member_create';
    elsif tg_op = 'UPDATE' then act := 'member_update';
    else act := 'member_hard_delete'; end if;
    summary := r ->> 'name';
  elsif tg_table_name = 'user_roles' then
    if tg_op = 'UPDATE' and o ->> 'role' is distinct from r ->> 'role' then act := 'role_change';
      summary := (o ->> 'role') || ' → ' || (r ->> 'role');
    elsif tg_op = 'UPDATE' and o ->> 'is_disabled' is distinct from r ->> 'is_disabled' then
      act := case when (r ->> 'is_disabled')::boolean then 'user_disable' else 'user_enable' end;
    else return null; end if;
  elsif tg_table_name = 'transactions' then
    act := 'tx_' || lower(tg_op);
    summary := (r ->> 'type') || ' ' || (r ->> 'amount') || ' ₭ · ' || (r ->> 'description');
  elsif tg_table_name = 'donation_payments' then
    act := 'payment_' || lower(tg_op);
    summary := (r ->> 'amount') || ' ₭';
  else
    act := tg_table_name || '_' || lower(tg_op);
  end if;
  insert into public.audit_logs (actor_id, action, entity, entity_id, summary, meta)
  values (auth.uid(), act, tg_table_name, coalesce(r ->> 'id', r ->> 'user_id'), left(summary, 500),
          case when tg_op = 'UPDATE' then jsonb_build_object('before', o) end);
  return null;
end $$;

do $$
declare t text;
begin
  foreach t in array array['members','user_roles','transactions','donation_payments','donation_pledges','fund_members','leadership','elders']
  loop
    execute format('drop trigger if exists trg_%1$s_audit on public.%1$s', t);
    execute format('create trigger trg_%1$s_audit after insert or update or delete on public.%1$s for each row execute function public.audit_row()', t);
  end loop;
end $$;
