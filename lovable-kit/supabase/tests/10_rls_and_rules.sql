-- =====================================================================
-- Kwv Tij Hmoob Xyooj — RLS & business-rule tests
-- Run against a SCRATCH database (never production):
--   psql -v ON_ERROR_STOP=1 -d <db> -f 00_supabase_stub.sql -f ../migrations/*.sql -f 10_rls_and_rules.sql
-- Each check raises an exception on failure; the script ends with "ALL TESTS PASSED".
-- =====================================================================
\set QUIET on
set client_min_messages = notice;

create or replace function pg_temp.ok(cond boolean, label text) returns void language plpgsql as $$
begin
  if cond is not true then raise exception 'FAIL: %', label; end if;
  raise notice 'ok - %', label;
end $$;

-- Expect a statement to fail (permission / rule). Runs as the CURRENT role.
create or replace function pg_temp.fails(sql text, label text) returns void language plpgsql as $$
begin
  begin
    execute sql;
  exception when others then
    raise notice 'ok - % (blocked: %)', label, sqlerrm;
    return;
  end;
  raise exception 'FAIL: % — statement was allowed', label;
end $$;

-- Switch identity like PostgREST does. NULL = guest (anon).
create or replace function pg_temp.act_as(p_uid uuid) returns void language plpgsql as $$
begin
  perform set_config('request.jwt.claim.sub', coalesce(p_uid::text, ''), false);
  execute case when p_uid is null then 'set role anon' else 'set role authenticated' end;
end $$;
-- Back to the SQL-editor / service identity (no signed-in user).
create or replace function pg_temp.as_owner() returns void language plpgsql as $$
begin
  execute 'reset role';
  perform set_config('request.jwt.claim.sub', '', false);
end $$;
grant execute on all functions in schema pg_temp to anon, authenticated;

-- ---------- Accounts ----------
select pg_temp.as_owner();
insert into auth.users (id, email, raw_user_meta_data) values
  ('00000000-0000-0000-0000-00000000000a', 'admin@test.la', '{"display_name":"Admin"}'),
  ('00000000-0000-0000-0000-00000000000b', 'member@test.la', '{"display_name":"ເປັງ ຊົ່ງ"}'),
  ('00000000-0000-0000-0000-00000000000c', 'pending@test.la', '{"display_name":"ທອງ ຊົ່ງ"}'),
  ('00000000-0000-0000-0000-00000000000d', 'member2@test.la', '{"display_name":"Other"}');
select pg_temp.ok((select count(*) from public.user_roles where role = 'pending') = 4, 'handle_new_user: every new account starts pending');
update public.user_roles set role = 'admin' where user_id = '00000000-0000-0000-0000-00000000000a';      -- bootstrap first admin (SQL editor)
update public.user_roles set role = 'member' where user_id in ('00000000-0000-0000-0000-00000000000b', '00000000-0000-0000-0000-00000000000d');

-- ---------- Member builds part of the tree ----------
select pg_temp.act_as('00000000-0000-0000-0000-00000000000b');
create temp table ids (k text primary key, id uuid) on commit preserve rows;
grant all on ids to anon, authenticated;
insert into ids values ('root', public.create_member('ເຈົ໋ ຊົ່ງ', 'male', p_generation => 1::smallint, p_birth_date => '1890-01-01', p_birth_year_only => true, p_is_alive => false, p_death_year => 1965::smallint));
insert into ids values ('a', public.create_member('A', 'male', (select id from ids where k = 'root'), p_phone => '020 5512 3344', p_birth_date => '1978-03-12'));
insert into ids values ('b', public.create_member('B', 'female', (select id from ids where k = 'root')));
insert into ids values ('c', public.create_member('C', 'male', (select id from ids where k = 'root'), p_insert_at => 1));
insert into ids values ('a1', public.create_member('A1', 'male', (select id from ids where k = 'a')));

select pg_temp.ok((select generation from public.members where id = (select id from ids where k = 'a')) = 2, 'Phaj = father + 1');
select pg_temp.ok((select generation from public.members where id = (select id from ids where k = 'a1')) = 3, 'Phaj = father + 1 (grandchild)');
select pg_temp.ok((select array_agg(name order by birth_order) from public.members where father_id = (select id from ids where k = 'root')) = array['C', 'A', 'B'], 'insert_at shifts siblings');
select pg_temp.ok((select phone from public.members where id = (select id from ids where k = 'a')) = '+8562055123344', 'phone normalised to +856');
select pg_temp.ok((select array_agg(birth_order order by birth_order) from public.members where father_id = (select id from ids where k = 'root')) = array[1, 2, 3]::smallint[], 'birth_order is 1..n');

-- reorder_children keeps 1..n and rejects partial lists
select public.reorder_children((select id from ids where k = 'root'), array[(select id from ids where k = 'b'), (select id from ids where k = 'a'), (select id from ids where k = 'c')]);
select pg_temp.ok((select array_agg(name order by birth_order) from public.members where father_id = (select id from ids where k = 'root')) = array['B', 'A', 'C'], 'reorder_children applies order');
select pg_temp.fails(format('select public.reorder_children(%L, array[%L]::uuid[])', (select id from ids where k = 'root'), (select id from ids where k = 'a')), 'reorder_children rejects a partial id list');
select public.move_child((select id from ids where k = 'c'), -1);
select pg_temp.ok((select array_agg(birth_order order by birth_order) from public.members where father_id = (select id from ids where k = 'root')) = array[1, 2, 3]::smallint[]
                  and (select birth_order from public.members where id = (select id from ids where k = 'c')) = 2, 'move_child keeps consecutive order');

-- cycles & invalid fathers
select pg_temp.fails(format('select public.set_member_father(%L, %L)', (select id from ids where k = 'root'), (select id from ids where k = 'a1')), 'a member cannot become their own ancestor (cycle)');
select pg_temp.fails(format('select public.set_member_father(%L, %L)', (select id from ids where k = 'a'), (select id from ids where k = 'a')), 'a member cannot be their own father');
select pg_temp.fails(format('select public.create_member(%L, %L, %L)', 'X', 'male', (select id from ids where k = 'b')), 'father must be male');
select pg_temp.fails(format('update public.members set birth_order = 9 where id = %L', (select id from ids where k = 'a')), 'birth_order cannot be edited directly');
select pg_temp.fails(format('update public.members set sex = %L where id = %L', 'female', (select id from ids where k = 'a')), 'a father cannot become female');

-- unlink keeps the member and closes the gap
select public.unlink_from_father((select id from ids where k = 'a'));
select pg_temp.ok((select father_id from public.members where id = (select id from ids where k = 'a')) is null
                  and exists (select 1 from public.members where id = (select id from ids where k = 'a') and deleted_at is null), 'unlink sets father_id NULL and never deletes');
select pg_temp.ok((select array_agg(birth_order order by birth_order) from public.members where father_id = (select id from ids where k = 'root')) = array[1, 2]::smallint[], 'siblings close the gap after unlink');

-- moving a branch recomputes Phaj for the subtree
select public.set_member_father((select id from ids where k = 'a'), (select id from ids where k = 'c'));
select pg_temp.ok((select generation from public.members where id = (select id from ids where k = 'a')) = 3
                  and (select generation from public.members where id = (select id from ids where k = 'a1')) = 4, 'moving a branch cascades Phaj');

-- member is not admin
select pg_temp.fails(format('select public.soft_delete_member(%L)', (select id from ids where k = 'b')), 'member cannot delete members');
select pg_temp.fails($$select public.approve_user('00000000-0000-0000-0000-00000000000c')$$, 'member cannot approve users');
select pg_temp.fails($$select public.set_user_role('00000000-0000-0000-0000-00000000000b', 'admin')$$, 'member cannot change roles');
select pg_temp.fails($$update public.user_roles set role = 'admin' where user_id = '00000000-0000-0000-0000-00000000000b'$$, 'member cannot write user_roles');
select pg_temp.ok((select count(*) from public.user_roles where role = 'admin') = 0, 'member does not see other users'' roles');
select pg_temp.fails($$insert into public.transactions (date, description, category, type, amount) values (current_date, 'x', 'ອາຫານ', 'expense', 5)$$, 'member cannot record transactions');
select pg_temp.ok((select count(*) from public.audit_logs) = 0, 'member cannot read audit log');

-- ---------- Guest ----------
select pg_temp.act_as(null);
select pg_temp.ok((select count(*) from public.members) = 0, 'guest cannot read full member rows');
select pg_temp.ok((select count(*) from public.members_public) = 5, 'guest reads members_public');
select pg_temp.ok(not exists (select 1 from information_schema.columns where table_name = 'members_public' and column_name in ('phone', 'village', 'birth_date')), 'members_public has no phone / village / full birth date');
select pg_temp.ok((select count(*) from public.get_family_tree()) >= 5, 'guest can load the family tree');
select pg_temp.ok(not exists (select 1 from public.global_search('A') where subtitle like '%+856%'), 'guest search never returns phone numbers');
select pg_temp.ok((select count(*) from public.transactions) = 0, 'guest cannot read transactions');
select pg_temp.fails('select * from public.v_finance_summary', 'guest cannot read finance summary');
select pg_temp.fails($$select public.create_member('G', 'male', p_generation => 1::smallint)$$, 'guest cannot create members');

-- ---------- Pending ----------
select pg_temp.act_as('00000000-0000-0000-0000-00000000000c');
select pg_temp.ok((select count(*) from public.members) = 0, 'pending cannot read full member rows');
select pg_temp.ok((select count(*) from public.profiles) = 1, 'pending reads only own profile');
select pg_temp.fails($$select public.create_member('P', 'male', p_generation => 1::smallint)$$, 'pending cannot create members');
select pg_temp.fails($$update public.user_roles set role = 'member' where user_id = '00000000-0000-0000-0000-00000000000c'$$, 'pending cannot approve itself');
select pg_temp.ok(exists (select 1 from public.global_search('A') where kind = 'member') and not exists (select 1 from public.global_search('A') where subtitle like '%+856%'), 'pending search has no phones');

-- ---------- Admin: users ----------
select pg_temp.act_as('00000000-0000-0000-0000-00000000000a');
select public.approve_user('00000000-0000-0000-0000-00000000000c', (select id from ids where k = 'b'));
select pg_temp.ok((select role from public.user_roles where user_id = '00000000-0000-0000-0000-00000000000c') = 'member'
                  and (select member_id from public.profiles where id = '00000000-0000-0000-0000-00000000000c') = (select id from ids where k = 'b'), 'admin approves and links member');
select pg_temp.ok(exists (select 1 from public.audit_logs where action = 'user_approve'), 'approval is audited');
select pg_temp.fails($$select public.set_user_role('00000000-0000-0000-0000-00000000000a', 'member')$$, 'admin cannot change own role');
select pg_temp.fails($$select public.link_user_member('00000000-0000-0000-0000-00000000000d', (select member_id from public.profiles where id = '00000000-0000-0000-0000-00000000000c'))$$, 'one member links to one account only');
select pg_temp.ok((select count(*) from public.admin_list_users()) = 4, 'admin_list_users returns emails for admin');

-- last admin protection (a second admin tries to demote the only other admin after being demoted)
select public.set_user_role('00000000-0000-0000-0000-00000000000d', 'admin');
select pg_temp.act_as('00000000-0000-0000-0000-00000000000d');
select public.set_user_role('00000000-0000-0000-0000-00000000000a', 'member');
select pg_temp.fails($$select public.set_user_disabled('00000000-0000-0000-0000-00000000000d', true)$$, 'cannot disable yourself');
select pg_temp.as_owner();
select pg_temp.fails($$update public.user_roles set role = 'member' where user_id = '00000000-0000-0000-0000-00000000000d'$$, 'the last admin cannot be removed');
update public.user_roles set role = 'admin' where user_id = '00000000-0000-0000-0000-00000000000a';

-- ---------- Admin: money ----------
select pg_temp.act_as('00000000-0000-0000-0000-00000000000a');
insert into public.events (title, category, start_date, is_public, created_by) values ('ບຸນກິນຈຽງ', 'ບຸນປະເພນີ', current_date + 20, true, auth.uid());
insert into ids values ('ev', (select id from public.events where title = 'ບຸນກິນຈຽງ'));
insert into public.donation_pledges (member_id, event_id, expected_amount) values ((select id from ids where k = 'c'), (select id from ids where k = 'ev'), 1000000);
insert into ids values ('pl', (select id from public.donation_pledges limit 1));
select public.create_transaction(current_date, 'ຄ່າບຳລຸງ', 'ຄ່າບຳລຸງ', 'income', 500000, null, '11111111-1111-1111-1111-111111111111');
select public.create_transaction(current_date, 'ຄ່າບຳລຸງ', 'ຄ່າບຳລຸງ', 'income', 500000, null, '11111111-1111-1111-1111-111111111111');
select pg_temp.ok((select count(*) from public.transactions where source = 'manual') = 1, 'create_transaction is idempotent');

select public.record_donation_payment(300000, current_date, 'cash', (select id from ids where k = 'pl'), p_client_request_id => '22222222-2222-2222-2222-222222222222');
select public.record_donation_payment(300000, current_date, 'cash', (select id from ids where k = 'pl'), p_client_request_id => '22222222-2222-2222-2222-222222222222');
select pg_temp.ok((select count(*) from public.donation_payments) = 1 and (select count(*) from public.transactions where source = 'donation') = 1, 're-submitted payment is not duplicated');
select pg_temp.ok((select total_income from public.v_finance_summary) = 800000 and (select donation_income from public.v_finance_summary) = 300000, 'donation counted exactly once in income');
select pg_temp.ok((select status from public.v_donation_progress) = 'partial' and (select progress_pct from public.v_donation_progress) = 30.0, 'donation progress / status');

select pg_temp.fails($$update public.transactions set amount = 1 where source = 'donation'$$, 'donation transactions cannot be edited directly');
select pg_temp.fails($$delete from public.transactions where source = 'donation'$$, 'donation transactions cannot be deleted directly');
select pg_temp.fails($$insert into public.transactions (date, description, category, type, amount) values (current_date, 'x', 'ບໍລິຈາກ', 'income', 5)$$, 'manual income cannot use the donation category');
select pg_temp.fails($$insert into public.donation_payments (pledge_id, transaction_id, amount, paid_on, method) select pledge_id, transaction_id, 1, current_date, 'cash' from public.donation_payments$$, 'payments cannot be inserted directly');

select public.update_donation_payment((select id from public.donation_payments), 450000, current_date, 'bank');
select pg_temp.ok((select amount from public.transactions where source = 'donation') = 450000, 'updating a payment updates its transaction');
select public.record_donation_payment(100000, current_date, 'cash', p_member_id => (select id from ids where k = 'b'), p_event_id => (select id from ids where k = 'ev'));
select pg_temp.ok(exists (select 1 from public.donation_pledges where member_id = (select id from ids where k = 'b') and expected_amount is null), 'payment for a new donor creates a pledge without expectation');
select public.delete_donation_payment((select id from public.donation_payments where amount = 450000));
select pg_temp.ok((select count(*) from public.transactions where source = 'donation') = 1 and (select donation_income from public.v_finance_summary) = 100000, 'deleting a payment deletes its transaction');
select pg_temp.ok(exists (select 1 from public.audit_logs where action like 'payment_%') and exists (select 1 from public.audit_logs where action = 'tx_insert'), 'money changes are audited');

-- ---------- Soft delete (admin) ----------
select pg_temp.fails(format('select public.soft_delete_member(%L)', (select id from ids where k = 'root')), 'cannot delete a member who still has children');
select public.soft_delete_member((select id from ids where k = 'a1'));
select pg_temp.ok(exists (select 1 from public.members where id = (select id from ids where k = 'a1') and deleted_at is not null), 'soft delete keeps the row');

-- ---------- Private events & storage ----------
insert into public.events (title, category, start_date, is_public, created_by) values ('ກອງປະຊຸມລັບ', 'ກອງປະຊຸມ', current_date + 3, false, auth.uid());
select pg_temp.as_owner();
insert into storage.objects (bucket_id, name) select 'event-media', id || '/x.webp' from public.events where title = 'ກອງປະຊຸມລັບ';
insert into storage.objects (bucket_id, name) select 'clan-documents', 'slips/s.webp';
select pg_temp.act_as(null);
select pg_temp.ok(not exists (select 1 from public.events where title = 'ກອງປະຊຸມລັບ'), 'guest cannot see private events');
select pg_temp.ok((select count(*) from storage.objects) = 0, 'guest cannot read private event media or slips');
select pg_temp.act_as('00000000-0000-0000-0000-00000000000b');
select pg_temp.ok((select count(*) from storage.objects where bucket_id = 'event-media') = 1 and (select count(*) from storage.objects where bucket_id = 'clan-documents') = 0, 'member reads private event media but not slips');
select pg_temp.ok(exists (select 1 from public.get_notifications() where kind = 'event'), 'member gets upcoming-event notifications');
select public.mark_notifications_read(null);
select pg_temp.ok(not exists (select 1 from public.get_notifications() where not is_read), 'mark all notifications read');

-- ---------- Comments ----------
select pg_temp.act_as('00000000-0000-0000-0000-00000000000a');
insert into public.news (title, body, category, status, published_at, author_id) values ('ຂ່າວ', 'x', 'ປະກາດ', 'published', now(), auth.uid());
select pg_temp.act_as('00000000-0000-0000-0000-00000000000b');
insert into public.news_comments (news_id, author_id, body) select id, auth.uid(), 'ສະບາຍດີ' from public.news;
select pg_temp.act_as('00000000-0000-0000-0000-00000000000d');
select pg_temp.ok((select count(*) from public.news_comments_view) = 1, 'comments are visible with author name');
select pg_temp.as_owner();
update public.user_roles set role = 'member' where user_id = '00000000-0000-0000-0000-00000000000d';
select pg_temp.act_as('00000000-0000-0000-0000-00000000000d');
update public.news_comments set body = 'hack';
select pg_temp.ok((select body from public.news_comments_view) = 'ສະບາຍດີ', 'members cannot edit other people''s comments');

select pg_temp.as_owner();
select 'ALL TESTS PASSED';
