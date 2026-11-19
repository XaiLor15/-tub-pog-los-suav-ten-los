-- =====================================================================
-- Kwv Tij Hmoob Xyooj — 01 Schema (tables, constraints, indexes)
-- Run in Supabase: SQL Editor → paste → Run   (files 01 → 04 in order)
-- UI label "Phaj" = column `generation`. Money = numeric(14,0) Lao Kip.
-- =====================================================================

create extension if not exists pg_trgm with schema extensions;

-- ---------- Enums ----------
do $$ begin
  create type public.app_role as enum ('pending', 'member', 'admin');
exception when duplicate_object then null; end $$;
do $$ begin
  create type public.sex as enum ('male', 'female');
exception when duplicate_object then null; end $$;

-- ---------- Accounts: profiles + user_roles (one row per auth.users) ----------
create table if not exists public.profiles (
  id uuid primary key references auth.users (id) on delete cascade,
  display_name text not null check (char_length(display_name) between 1 and 100),
  avatar_url text,
  member_id uuid unique,                                   -- FK added below
  auth_provider text not null default 'email',
  theme text not null default 'system' check (theme in ('light', 'dark', 'system')),
  font_scale smallint not null default 100 check (font_scale in (100, 112, 125)),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.user_roles (
  user_id uuid primary key references auth.users (id) on delete cascade,
  role public.app_role not null default 'pending',          -- new accounts are ALWAYS pending
  approved_by uuid references auth.users (id) on delete set null,
  approved_at timestamptz,
  rejected_at timestamptz,
  is_disabled boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- ---------- Family ----------
create table if not exists public.members (
  id uuid primary key default gen_random_uuid(),
  name text not null check (char_length(btrim(name)) between 1 and 100),
  hmong_name text check (hmong_name is null or char_length(hmong_name) <= 100),
  sex public.sex not null,
  birth_date date check (birth_date is null or birth_date <= current_date),
  birth_year_only boolean not null default false,          -- "ຮູ້ແຕ່ປີ" → stored as 01/01/YYYY
  death_year smallint check (death_year is null or death_year between 1700 and 2200),
  generation smallint not null check (generation >= 1),    -- Phaj
  is_alive boolean not null default true,
  father_id uuid references public.members (id) on delete restrict,
  mother_id uuid references public.members (id) on delete restrict,   -- read-only in the UI
  birth_order smallint check (birth_order is null or birth_order > 0),
  phone text check (phone is null or phone ~ '^\+856(20[0-9]{8}|30[0-9]{7}|[2-8][1-9][0-9]{6})$'),
  province text,
  village text,
  photo_url text,
  created_by uuid references auth.users (id) on delete set null,
  updated_by uuid references auth.users (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz,                                  -- soft delete (Admin only)
  constraint members_not_own_father check (father_id is null or father_id <> id),
  constraint members_not_own_mother check (mother_id is null or mother_id <> id)
);
-- Siblings never share a birth_order. NULLs (unlinked / deleted) never collide.
create unique index if not exists ux_members_father_birth_order on public.members (father_id, birth_order)
  where deleted_at is null and father_id is not null and birth_order is not null;
create index if not exists ix_members_name_trgm on public.members using gin (name extensions.gin_trgm_ops);
create index if not exists ix_members_hmong_trgm on public.members using gin (hmong_name extensions.gin_trgm_ops);
create index if not exists ix_members_generation on public.members (generation);
create index if not exists ix_members_father on public.members (father_id);
create index if not exists ix_members_mother on public.members (mother_id);

do $$ begin
  alter table public.profiles add constraint profiles_member_fk foreign key (member_id) references public.members (id) on delete set null;
exception when duplicate_object then null; end $$;

create table if not exists public.member_relations (
  id uuid primary key default gen_random_uuid(),
  member_id uuid not null references public.members (id) on delete cascade,
  related_member_id uuid not null references public.members (id) on delete cascade,
  relation_type text not null default 'spouse' check (relation_type in ('spouse')),
  created_at timestamptz not null default now(),
  constraint member_relations_not_self check (member_id <> related_member_id),
  unique (member_id, related_member_id, relation_type)
);

create table if not exists public.leadership (
  id uuid primary key default gen_random_uuid(),
  member_id uuid not null references public.members (id) on delete restrict,
  position text not null check (char_length(position) between 1 and 100),
  "group" text not null check ("group" in ('executive', 'department_head', 'committee')),
  sort_order integer not null default 0,
  term_start date,
  term_end date,
  photo_url text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint leadership_term check (term_end is null or term_start is null or term_end >= term_start)
);
create index if not exists ix_leadership_sort on public.leadership (sort_order);

create table if not exists public.elders (
  id uuid primary key default gen_random_uuid(),
  member_id uuid not null unique references public.members (id) on delete restrict,
  role_title text not null check (char_length(role_title) between 1 and 100),
  role_category text not null check (role_category in ('rite', 'qeej', 'herb', 'history', 'craft', 'mediate')),
  bio text,
  skills text[] not null default '{}',
  status text not null default 'active' check (status in ('active', 'retired')),
  photo_url text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.elder_successors (
  elder_id uuid not null references public.elders (id) on delete cascade,
  member_id uuid not null references public.members (id) on delete restrict,
  created_at timestamptz not null default now(),
  primary key (elder_id, member_id)
);

-- ---------- Content ----------
create table if not exists public.events (
  id uuid primary key default gen_random_uuid(),
  title text not null check (char_length(title) between 1 and 200),
  description text,
  category text not null,
  start_date date not null,
  end_date date,
  start_time time,
  end_time time,
  location text,
  organizer text,
  is_public boolean not null default false,
  cover_url text,
  created_by uuid references auth.users (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint events_dates check (end_date is null or end_date >= start_date)
);
create index if not exists ix_events_start on public.events (start_date);

create table if not exists public.participants (
  id uuid primary key default gen_random_uuid(),
  event_id uuid not null references public.events (id) on delete cascade,
  member_id uuid not null references public.members (id) on delete restrict,
  duty text,
  status text not null default 'confirmed' check (status in ('confirmed', 'maybe')),
  created_at timestamptz not null default now(),
  unique (event_id, member_id)
);
create index if not exists ix_participants_event on public.participants (event_id);

create table if not exists public.news (
  id uuid primary key default gen_random_uuid(),
  title text not null check (char_length(title) between 1 and 200),
  body text not null,
  category text not null,
  tags text[] not null default '{}',
  is_pinned boolean not null default false,
  status text not null default 'draft' check (status in ('draft', 'published')),
  published_at timestamptz,
  author_id uuid references auth.users (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists ix_news_feed on public.news (status, is_pinned desc, published_at desc);

create table if not exists public.news_comments (
  id uuid primary key default gen_random_uuid(),
  news_id uuid not null references public.news (id) on delete cascade,
  author_id uuid not null references auth.users (id) on delete cascade,
  body text not null check (char_length(btrim(body)) between 1 and 2000),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists ix_news_comments_news on public.news_comments (news_id, created_at);

create table if not exists public.media_items (
  id uuid primary key default gen_random_uuid(),
  module text not null check (module in ('event', 'news')),
  record_id uuid not null,
  path text not null,                 -- storage path inside bucket event-media / news-media
  mime text not null,
  size bigint not null,
  sort_order integer not null default 0,
  created_by uuid references auth.users (id) on delete set null,
  created_at timestamptz not null default now()
);
create index if not exists ix_media_record on public.media_items (module, record_id, sort_order);

-- ---------- Finance ----------
create table if not exists public.transactions (
  id uuid primary key default gen_random_uuid(),
  date date not null,
  description text not null check (char_length(description) between 1 and 255),
  category text not null,
  type text not null check (type in ('income', 'expense')),
  amount numeric(14, 0) not null check (amount > 0),
  event_id uuid references public.events (id) on delete set null,
  source text not null default 'manual' check (source in ('manual', 'donation')),
  client_request_id uuid unique,      -- idempotency key (double click / retry)
  created_by uuid references auth.users (id) on delete set null,
  updated_by uuid references auth.users (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists ix_tx_date on public.transactions (date);
create index if not exists ix_tx_event on public.transactions (event_id);

create table if not exists public.donation_pledges (
  id uuid primary key default gen_random_uuid(),
  member_id uuid not null references public.members (id) on delete restrict,
  event_id uuid not null references public.events (id) on delete restrict,
  expected_amount numeric(14, 0) check (expected_amount is null or expected_amount >= 0),  -- null = not set
  created_by uuid references auth.users (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (member_id, event_id)
);

create table if not exists public.donation_payments (
  id uuid primary key default gen_random_uuid(),
  pledge_id uuid not null references public.donation_pledges (id) on delete restrict,
  transaction_id uuid not null unique references public.transactions (id) on delete restrict,  -- 1:1
  amount numeric(14, 0) not null check (amount > 0),
  paid_on date not null,
  method text not null check (method in ('cash', 'bank')),
  note text,
  slip_path text,                    -- private bucket clan-documents
  created_by uuid references auth.users (id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists ix_payments_pledge on public.donation_payments (pledge_id, paid_on);

create table if not exists public.fund_members (
  id uuid primary key default gen_random_uuid(),
  member_id uuid not null unique references public.members (id) on delete restrict,
  joined_at date not null default current_date,
  created_by uuid references auth.users (id) on delete set null,
  created_at timestamptz not null default now()
);

-- ---------- System ----------
create table if not exists public.audit_logs (
  id bigint generated always as identity primary key,
  actor_id uuid references auth.users (id) on delete set null,
  action text not null,
  entity text,
  entity_id text,
  summary text,
  meta jsonb,
  created_at timestamptz not null default now()
);
create index if not exists ix_audit_created on public.audit_logs (created_at desc);

create table if not exists public.notification_reads (
  user_id uuid not null references auth.users (id) on delete cascade,
  item_key text not null check (item_key ~ '^(event|news):[0-9a-f-]{36}$|^pending_users:[0-9]+$'),
  read_at timestamptz not null default now(),
  primary key (user_id, item_key)
);
