-- =====================================================================
-- Kwv Tij Hmoob Xyooj — 04 Row Level Security, grants, Storage
-- Permission matrix (Dev Handoff "ສິດຕາມ Role"):
--   Guest = not signed in (anon) · Pending = signed in, not approved
--   Member = is_member() · Admin = is_admin()
-- Guests/Pending read people through *_public views (no phone, birth year only).
-- =====================================================================

-- ---------- Grants (Supabase grants these by default; explicit here for clarity) ----------
grant usage on schema public to anon, authenticated;
grant select on all tables in schema public to anon, authenticated;
grant insert, update, delete on all tables in schema public to authenticated;
revoke insert, update, delete on public.audit_logs, public.donation_payments, public.user_roles from authenticated;

-- ---------- Enable RLS everywhere ----------
do $$
declare t text;
begin
  foreach t in array array['profiles','user_roles','members','member_relations','leadership','elders','elder_successors','events',
                           'participants','news','news_comments','media_items','transactions','donation_pledges','donation_payments',
                           'fund_members','audit_logs','notification_reads']
  loop
    execute format('alter table public.%I enable row level security', t);
  end loop;
end $$;

-- Helper to (re)create a policy idempotently.
create or replace function pg_temp.pol(p_table text, p_name text, p_sql text) returns void language plpgsql as $$
begin
  execute format('drop policy if exists %I on public.%I', p_name, p_table);
  execute p_sql;
end $$;

-- ---------- Accounts ----------
select pg_temp.pol('profiles', 'profiles_select', $p$create policy profiles_select on public.profiles for select to authenticated using (id = auth.uid() or public.is_admin())$p$);
select pg_temp.pol('profiles', 'profiles_update', $p$create policy profiles_update on public.profiles for update to authenticated using (id = auth.uid() or public.is_admin()) with check (id = auth.uid() or public.is_admin())$p$);
select pg_temp.pol('user_roles', 'user_roles_select', $p$create policy user_roles_select on public.user_roles for select to authenticated using (user_id = auth.uid() or public.is_admin())$p$);
-- user_roles has no write policy: changes only through approve_user / reject_user / set_user_role / set_user_disabled.

-- ---------- Members (full rows: members & admins only) ----------
select pg_temp.pol('members', 'members_select', $p$create policy members_select on public.members for select to authenticated using (public.is_member())$p$);
select pg_temp.pol('members', 'members_update', $p$create policy members_update on public.members for update to authenticated using (public.is_member()) with check (public.is_member())$p$);
-- No insert/delete policy: create via create_member(), delete via soft_delete_member() (Admin).

select pg_temp.pol('member_relations', 'relations_select', $p$create policy relations_select on public.member_relations for select to authenticated using (public.is_member())$p$);
select pg_temp.pol('member_relations', 'relations_admin', $p$create policy relations_admin on public.member_relations for all to authenticated using (public.is_admin()) with check (public.is_admin())$p$);

-- ---------- Leadership & elders (no private columns; phones come from members) ----------
select pg_temp.pol('leadership', 'leadership_select', $p$create policy leadership_select on public.leadership for select to anon, authenticated using (true)$p$);
select pg_temp.pol('leadership', 'leadership_admin', $p$create policy leadership_admin on public.leadership for all to authenticated using (public.is_admin()) with check (public.is_admin())$p$);
select pg_temp.pol('elders', 'elders_select', $p$create policy elders_select on public.elders for select to anon, authenticated using (true)$p$);
select pg_temp.pol('elders', 'elders_admin', $p$create policy elders_admin on public.elders for all to authenticated using (public.is_admin()) with check (public.is_admin())$p$);
select pg_temp.pol('elder_successors', 'successors_select', $p$create policy successors_select on public.elder_successors for select to anon, authenticated using (true)$p$);
select pg_temp.pol('elder_successors', 'successors_admin', $p$create policy successors_admin on public.elder_successors for all to authenticated using (public.is_admin()) with check (public.is_admin())$p$);

-- ---------- Events & participants ----------
select pg_temp.pol('events', 'events_select', $p$create policy events_select on public.events for select to anon, authenticated using (is_public or public.is_member())$p$);
select pg_temp.pol('events', 'events_insert', $p$create policy events_insert on public.events for insert to authenticated with check (public.is_member() and created_by = auth.uid())$p$);
select pg_temp.pol('events', 'events_update', $p$create policy events_update on public.events for update to authenticated using (public.is_admin() or (public.is_member() and created_by = auth.uid())) with check (public.is_admin() or (public.is_member() and created_by = auth.uid()))$p$);
select pg_temp.pol('events', 'events_delete', $p$create policy events_delete on public.events for delete to authenticated using (public.is_admin())$p$);

create or replace function public.can_manage_event(p_event_id uuid)
returns boolean language sql stable security definer set search_path = public as $$
  select public.is_admin() or (public.is_member() and exists (select 1 from public.events where id = p_event_id and created_by = auth.uid()))
$$;

select pg_temp.pol('participants', 'participants_select', $p$create policy participants_select on public.participants for select to authenticated using (public.is_member())$p$);
select pg_temp.pol('participants', 'participants_write', $p$create policy participants_write on public.participants for all to authenticated
  using (public.can_manage_event(event_id) or (public.is_member() and member_id = public.my_member_id()))
  with check (public.can_manage_event(event_id) or (public.is_member() and member_id = public.my_member_id()))$p$);

-- ---------- News & comments ----------
select pg_temp.pol('news', 'news_select', $p$create policy news_select on public.news for select to anon, authenticated using (status = 'published' or public.is_admin())$p$);
select pg_temp.pol('news', 'news_admin', $p$create policy news_admin on public.news for all to authenticated using (public.is_admin()) with check (public.is_admin())$p$);

select pg_temp.pol('news_comments', 'comments_select', $p$create policy comments_select on public.news_comments for select to anon, authenticated
  using (exists (select 1 from public.news n where n.id = news_id and (n.status = 'published' or public.is_admin())))$p$);
select pg_temp.pol('news_comments', 'comments_insert', $p$create policy comments_insert on public.news_comments for insert to authenticated
  with check (public.is_member() and author_id = auth.uid() and exists (select 1 from public.news n where n.id = news_id and n.status = 'published'))$p$);
select pg_temp.pol('news_comments', 'comments_update', $p$create policy comments_update on public.news_comments for update to authenticated
  using (public.is_member() and author_id = auth.uid()) with check (public.is_member() and author_id = auth.uid())$p$);
select pg_temp.pol('news_comments', 'comments_delete', $p$create policy comments_delete on public.news_comments for delete to authenticated
  using (public.is_admin() or (public.is_member() and author_id = auth.uid()))$p$);

-- Comment list with author name for every viewer (profiles itself stays private).
create or replace view public.news_comments_view with (security_barrier = true) as
  select c.id, c.news_id, c.author_id, c.body, c.created_at, c.updated_at,
         coalesce(m.name, p.display_name) as author_name, m.sex as author_sex, p.avatar_url as author_avatar
  from public.news_comments c
  join public.news n on n.id = c.news_id
  left join public.profiles p on p.id = c.author_id
  left join public.members m on m.id = p.member_id
  where n.status = 'published' or public.is_admin();

-- ---------- Media records ----------
select pg_temp.pol('media_items', 'media_select', $p$create policy media_select on public.media_items for select to anon, authenticated using (
  (module = 'event' and exists (select 1 from public.events e where e.id = record_id and (e.is_public or public.is_member())))
  or (module = 'news' and exists (select 1 from public.news n where n.id = record_id and (n.status = 'published' or public.is_admin()))))$p$);
select pg_temp.pol('media_items', 'media_write', $p$create policy media_write on public.media_items for all to authenticated
  using (public.is_admin() or (module = 'event' and public.can_manage_event(record_id)))
  with check (public.is_admin() or (module = 'event' and public.can_manage_event(record_id)))$p$);

-- ---------- Finance ----------
select pg_temp.pol('transactions', 'tx_select', $p$create policy tx_select on public.transactions for select to authenticated using (public.is_member())$p$);
select pg_temp.pol('transactions', 'tx_admin', $p$create policy tx_admin on public.transactions for all to authenticated using (public.is_admin()) with check (public.is_admin())$p$);
-- (trigger block_donation_tx_direct_edit keeps donation rows read-only outside the donation RPCs)

select pg_temp.pol('donation_pledges', 'pledges_select', $p$create policy pledges_select on public.donation_pledges for select to authenticated using (public.is_member())$p$);
select pg_temp.pol('donation_pledges', 'pledges_admin', $p$create policy pledges_admin on public.donation_pledges for all to authenticated using (public.is_admin()) with check (public.is_admin())$p$);
select pg_temp.pol('donation_payments', 'payments_select', $p$create policy payments_select on public.donation_payments for select to authenticated using (public.is_member())$p$);
-- donation_payments: no write policy — record_/update_/delete_donation_payment only.

select pg_temp.pol('fund_members', 'fund_select', $p$create policy fund_select on public.fund_members for select to authenticated using (public.is_member())$p$);
select pg_temp.pol('fund_members', 'fund_admin', $p$create policy fund_admin on public.fund_members for all to authenticated using (public.is_admin()) with check (public.is_admin())$p$);

-- ---------- System ----------
select pg_temp.pol('audit_logs', 'audit_select', $p$create policy audit_select on public.audit_logs for select to authenticated using (public.is_admin())$p$);
select pg_temp.pol('notification_reads', 'reads_own', $p$create policy reads_own on public.notification_reads for all to authenticated using (user_id = auth.uid()) with check (user_id = auth.uid())$p$);

-- ---------- Views & functions access ----------
grant select on public.members_public, public.leadership_public, public.elders_public, public.v_dashboard_stats,
                public.v_member_breakdown, public.v_event_participant_counts, public.news_comments_view to anon, authenticated;
grant select on public.v_finance_summary, public.v_finance_monthly, public.v_donation_progress, public.v_donation_event_totals,
                public.v_event_summary, public.v_fund_contributor_totals, public.v_fund_by_province to authenticated;
revoke select on public.v_finance_summary, public.v_finance_monthly, public.v_donation_progress, public.v_donation_event_totals,
                 public.v_event_summary, public.v_fund_contributor_totals, public.v_fund_by_province from anon;
revoke execute on function public.resequence_children(uuid, uuid[]), public.child_ids(uuid),
                           public.write_audit(text, text, text, text, jsonb) from public, anon, authenticated;

-- =====================================================================
-- Storage buckets (Supabase Storage). Paths:
--   member-photos/<member_id>/<file>     public read, members upload
--   avatars/<user_id>/<file>             public read, owner uploads
--   event-media/<event_id>/<file>        private, same visibility as the event
--   news-media/<news_id>/<file>          private, published news (admins: drafts)
--   clan-documents/slips/<file>          private, Admin only (payment slips)
-- Private files are shown with createSignedUrl(path, 3600).
-- =====================================================================
insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types) values
  ('member-photos', 'member-photos', true, 10485760, array['image/jpeg', 'image/png', 'image/webp']),
  ('avatars', 'avatars', true, 10485760, array['image/jpeg', 'image/png', 'image/webp']),
  ('event-media', 'event-media', false, 52428800, array['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm']),
  ('news-media', 'news-media', false, 52428800, array['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm']),
  ('clan-documents', 'clan-documents', false, 26214400, array['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
on conflict (id) do update set public = excluded.public, file_size_limit = excluded.file_size_limit, allowed_mime_types = excluded.allowed_mime_types;

create or replace function public.storage_uuid(p_name text)
returns uuid language plpgsql immutable as $$
begin
  return (storage.foldername(p_name))[1]::uuid;
exception when others then return null;
end $$;

drop policy if exists kths_read on storage.objects;
create policy kths_read on storage.objects for select to anon, authenticated using (
  bucket_id in ('member-photos', 'avatars')
  or (bucket_id = 'event-media' and exists (select 1 from public.events e where e.id = public.storage_uuid(name) and (e.is_public or public.is_member())))
  or (bucket_id = 'news-media' and exists (select 1 from public.news n where n.id = public.storage_uuid(name) and (n.status = 'published' or public.is_admin())))
  or (bucket_id = 'clan-documents' and public.is_admin())
);

drop policy if exists kths_write on storage.objects;
create policy kths_write on storage.objects for insert to authenticated with check (
  (bucket_id = 'member-photos' and public.is_member())
  or (bucket_id = 'avatars' and (storage.foldername(name))[1] = auth.uid()::text)
  or (bucket_id = 'event-media' and public.can_manage_event(public.storage_uuid(name)))
  or (bucket_id in ('news-media', 'clan-documents') and public.is_admin())
);

drop policy if exists kths_modify on storage.objects;
create policy kths_modify on storage.objects for update to authenticated using (
  (bucket_id = 'member-photos' and public.is_member())
  or (bucket_id = 'avatars' and (storage.foldername(name))[1] = auth.uid()::text)
  or (bucket_id = 'event-media' and public.can_manage_event(public.storage_uuid(name)))
  or (bucket_id in ('news-media', 'clan-documents') and public.is_admin())
);

drop policy if exists kths_delete on storage.objects;
create policy kths_delete on storage.objects for delete to authenticated using (
  (bucket_id = 'member-photos' and public.is_admin())
  or (bucket_id = 'avatars' and (storage.foldername(name))[1] = auth.uid()::text)
  or (bucket_id = 'event-media' and public.can_manage_event(public.storage_uuid(name)))
  or (bucket_id in ('news-media', 'clan-documents') and public.is_admin())
);

-- ---------- Realtime (Dev Handoff: news, news_comments, events, transactions) ----------
-- RLS still applies to realtime events; the frontend only invalidates queries on change.
do $$
declare t text;
begin
  if exists (select 1 from pg_publication where pubname = 'supabase_realtime') then
    foreach t in array array['news', 'news_comments', 'events', 'transactions'] loop
      if not exists (select 1 from pg_publication_tables where pubname = 'supabase_realtime' and schemaname = 'public' and tablename = t) then
        execute format('alter publication supabase_realtime add table public.%I', t);
      end if;
    end loop;
  end if;
end $$;
