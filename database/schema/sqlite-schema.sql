CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "label" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "roles_name_unique" on "roles"("name");
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "password" varchar not null,
  "role_id" integer,
  "is_verified" tinyint(1) not null default '0',
  "email_verified_at" datetime,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "firstname" varchar,
  "lastname" varchar,
  "phone" varchar,
  "status" varchar check("status" in('approved', 'pending', 'rejected')) not null default 'approved',
  "google_id" varchar,
  "avatar" varchar,
  foreign key("role_id") references "roles"("id") on delete cascade
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "articles"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "slug" varchar not null,
  "content" text not null,
  "excerpt" text,
  "featured_image_url" varchar,
  "cover_image" varchar,
  "twitter_image" varchar,
  "og_image" varchar,
  "meta_title" varchar,
  "meta_description" text,
  "canonical_url" varchar,
  "status" varchar check("status" in('draft', 'published', 'scheduled')) not null default 'draft',
  "published_at" datetime,
  "author_name" varchar,
  "author_bio" text,
  "external_id" varchar not null,
  "source" varchar not null default 'webhook',
  "webhook_received_at" datetime,
  "webhook_data" text,
  "is_featured" tinyint(1) not null default '0',
  "views_count" integer not null default '0',
  "reading_time" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE INDEX "articles_status_published_at_index" on "articles"(
  "status",
  "published_at"
);
CREATE INDEX "articles_external_id_index" on "articles"("external_id");
CREATE INDEX "articles_slug_index" on "articles"("slug");
CREATE UNIQUE INDEX "articles_slug_unique" on "articles"("slug");
CREATE UNIQUE INDEX "articles_external_id_unique" on "articles"("external_id");
CREATE TABLE IF NOT EXISTS "categories"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "color" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "categories_slug_unique" on "categories"("slug");
CREATE TABLE IF NOT EXISTS "article_category"(
  "id" integer primary key autoincrement not null,
  "article_id" integer not null,
  "category_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("article_id") references "articles"("id") on delete cascade,
  foreign key("category_id") references "categories"("id") on delete cascade
);
CREATE UNIQUE INDEX "article_category_article_id_category_id_unique" on "article_category"(
  "article_id",
  "category_id"
);
CREATE TABLE IF NOT EXISTS "parent_profiles"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "children_count" integer,
  "children_ages" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "babysitter_profiles"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "bio" text,
  "experience_years" integer,
  "available_radius_km" integer not null default '10',
  "availability" text,
  "languages" text,
  "documents_verified" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "ad_applications"(
  "id" integer primary key autoincrement not null,
  "ad_id" integer not null,
  "babysitter_id" integer not null,
  "motivation_note" text,
  "status" varchar check("status" in('pending', 'accepted', 'declined')) not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("ad_id") references "ads"("id") on delete cascade,
  foreign key("babysitter_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "conversations"(
  "id" integer primary key autoincrement not null,
  "ad_id" integer not null,
  "parent_id" integer not null,
  "babysitter_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("ad_id") references "ads"("id") on delete cascade,
  foreign key("parent_id") references "users"("id") on delete cascade,
  foreign key("babysitter_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "messages"(
  "id" integer primary key autoincrement not null,
  "conversation_id" integer not null,
  "sender_id" integer not null,
  "message" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("conversation_id") references "conversations"("id") on delete cascade,
  foreign key("sender_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "wallets"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "balance" numeric not null default '0',
  "last_transfer_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "transactions"(
  "id" integer primary key autoincrement not null,
  "ad_id" integer not null,
  "payer_id" integer not null,
  "babysitter_id" integer not null,
  "amount" numeric not null,
  "fee" numeric not null default '1.99',
  "payment_method" varchar,
  "stripe_id" varchar,
  "status" varchar not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("ad_id") references "ads"("id") on delete cascade,
  foreign key("payer_id") references "users"("id") on delete cascade,
  foreign key("babysitter_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "reviews"(
  "id" integer primary key autoincrement not null,
  "reviewer_id" integer not null,
  "reviewed_id" integer not null,
  "role" varchar check("role" in('parent', 'babysitter')) not null,
  "rating" integer not null,
  "comment" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("reviewer_id") references "users"("id") on delete cascade,
  foreign key("reviewed_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "reports"(
  "id" integer primary key autoincrement not null,
  "reporter_id" integer not null,
  "reported_type" varchar not null,
  "reported_id" integer not null,
  "reason" text not null,
  "resolved" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("reporter_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "ads"(
  "id" integer primary key autoincrement not null,
  "parent_id" integer not null,
  "title" varchar not null,
  "description" text,
  "address" varchar not null,
  "latitude" numeric,
  "longitude" numeric,
  "date_start" datetime not null,
  "date_end" datetime not null,
  "status" varchar not null default('active'),
  "is_boosted" tinyint(1) not null default('0'),
  "confirmed_application_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("parent_id") references users("id") on delete cascade on update no action,
  foreign key("confirmed_application_id") references "ad_applications"("id") on delete set null
);

INSERT INTO migrations VALUES(1,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(3,'0001_04_22_220852_create_roles_table',1);
INSERT INTO migrations VALUES(4,'0002_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(5,'2024_12_20_000001_create_articles_table',1);
INSERT INTO migrations VALUES(6,'2024_12_20_000002_create_categories_table',1);
INSERT INTO migrations VALUES(7,'2024_12_20_000003_create_article_category_table',1);
INSERT INTO migrations VALUES(8,'2025_04_22_221054_create_parent_profiles_table',1);
INSERT INTO migrations VALUES(9,'2025_04_22_221134_create_babysitter_profiles_table',1);
INSERT INTO migrations VALUES(10,'2025_04_22_221932_create_ads_table',1);
INSERT INTO migrations VALUES(11,'2025_04_22_221957_create_ad_application_table',1);
INSERT INTO migrations VALUES(12,'2025_04_22_222920_create_conversations_table',1);
INSERT INTO migrations VALUES(13,'2025_04_22_222948_create_messages_table',1);
INSERT INTO migrations VALUES(14,'2025_04_22_223223_create_wallets_table',1);
INSERT INTO migrations VALUES(15,'2025_04_22_223248_create_transactions_table',1);
INSERT INTO migrations VALUES(16,'2025_04_22_223315_create_reviews_table',1);
INSERT INTO migrations VALUES(17,'2025_04_22_223344_create_reports_table',1);
INSERT INTO migrations VALUES(18,'2025_04_23_132115_add_foreign_key_to_ads_table',1);
INSERT INTO migrations VALUES(19,'2025_06_05_230715_add_fields_to_users_table',2);
INSERT INTO migrations VALUES(20,'2025_06_09_090340_add_status_to_users_table',3);
INSERT INTO migrations VALUES(21,'2025_06_09_133055_add_google_fields_to_users_table',4);
