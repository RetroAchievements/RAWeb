CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "Console"(
  "ID" integer primary key autoincrement not null,
  "Name" varchar not null,
  "Created" datetime,
  "Updated" datetime,
  "name_full" varchar,
  "name_short" varchar,
  "manufacturer" varchar,
  "active" tinyint(1),
  "order_column" integer,
  "deleted_at" datetime
);
CREATE TABLE IF NOT EXISTS "EmailConfirmations"(
  "id" integer primary key autoincrement not null,
  "User" varchar not null,
  "user_id" integer,
  "EmailCookie" varchar not null,
  "Expires" date not null
);
CREATE INDEX "emailconfirmations_emailcookie_index" on "EmailConfirmations"(
  "EmailCookie"
);
CREATE TABLE IF NOT EXISTS "forum_categories"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "description" varchar not null,
  "order_column" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE TABLE IF NOT EXISTS "StaticData"(
  "NumAchievements" integer not null,
  "NumAwarded" integer not null,
  "NumGames" integer not null,
  "NumRegisteredUsers" integer not null,
  "TotalPointsEarned" integer not null,
  "LastAchievementEarnedID" integer not null,
  "LastAchievementEarnedByUser" varchar not null,
  "LastAchievementEarnedAt" datetime not null default CURRENT_TIMESTAMP,
  "LastRegisteredUser" varchar not null,
  "LastRegisteredUserAt" datetime,
  "LastUpdatedGameID" integer not null,
  "LastUpdatedAchievementID" integer not null,
  "LastCreatedGameID" integer not null,
  "LastCreatedAchievementID" integer not null,
  "NextGameToScan" integer not null default '1',
  "NextUserIDToScan" integer not null default '1',
  "Event_AOTW_AchievementID" integer not null default '1',
  "Event_AOTW_ForumID" integer not null default '1',
  "Event_AOTW_StartAt" datetime,
  "num_hardcore_mastery_awards" integer not null default '0',
  "num_hardcore_game_beaten_awards" integer not null default '0',
  "last_game_hardcore_mastered_game_id" integer not null default '1',
  "last_game_hardcore_mastered_user_id" integer not null default '1',
  "last_game_hardcore_mastered_at" datetime,
  "last_game_hardcore_beaten_game_id" integer not null default '1',
  "last_game_hardcore_beaten_user_id" integer not null default '1',
  "last_game_hardcore_beaten_at" datetime
);
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
CREATE TABLE IF NOT EXISTS "queue_jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "queue_jobs_queue_index" on "queue_jobs"("queue");
CREATE TABLE IF NOT EXISTS "oauth_auth_codes"(
  "id" varchar not null,
  "user_id" integer not null,
  "client_id" integer not null,
  "scopes" text,
  "revoked" tinyint(1) not null,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_auth_codes_user_id_index" on "oauth_auth_codes"("user_id");
CREATE TABLE IF NOT EXISTS "oauth_access_tokens"(
  "id" varchar not null,
  "user_id" integer,
  "client_id" integer not null,
  "name" varchar,
  "scopes" text,
  "revoked" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_access_tokens_user_id_index" on "oauth_access_tokens"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "oauth_refresh_tokens"(
  "id" varchar not null,
  "access_token_id" varchar not null,
  "revoked" tinyint(1) not null,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_refresh_tokens_access_token_id_index" on "oauth_refresh_tokens"(
  "access_token_id"
);
CREATE TABLE IF NOT EXISTS "oauth_clients"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "name" varchar not null,
  "secret" varchar,
  "provider" varchar,
  "redirect" text not null,
  "personal_access_client" tinyint(1) not null,
  "password_client" tinyint(1) not null,
  "revoked" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "oauth_clients_user_id_index" on "oauth_clients"("user_id");
CREATE TABLE IF NOT EXISTS "oauth_personal_access_clients"(
  "id" integer primary key autoincrement not null,
  "client_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" varchar not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE TABLE IF NOT EXISTS "queue_failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "queue_failed_jobs_uuid_unique" on "queue_failed_jobs"(
  "uuid"
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "sync_status"(
  "kind" varchar not null,
  "reference" varchar,
  "remaining" integer,
  "updated" datetime,
  primary key("kind")
);
CREATE TABLE IF NOT EXISTS "audit_log"(
  "id" integer primary key autoincrement not null,
  "log_name" varchar,
  "description" text not null,
  "subject_type" varchar,
  "subject_id" integer,
  "causer_type" varchar,
  "causer_id" integer,
  "properties" text,
  "created_at" datetime,
  "updated_at" datetime,
  "event" varchar,
  "batch_uuid" varchar
);
CREATE INDEX "audit_log_subject_index" on "audit_log"(
  "subject_type",
  "subject_id"
);
CREATE INDEX "audit_log_causer_index" on "audit_log"(
  "causer_type",
  "causer_id"
);
CREATE INDEX "audit_log_log_name_index" on "audit_log"("log_name");
CREATE TABLE IF NOT EXISTS "media"(
  "id" integer primary key autoincrement not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  "uuid" varchar,
  "collection_name" varchar not null,
  "name" varchar not null,
  "file_name" varchar not null,
  "mime_type" varchar,
  "disk" varchar not null,
  "conversions_disk" varchar,
  "size" integer not null,
  "manipulations" text not null,
  "custom_properties" text not null,
  "generated_conversions" text not null,
  "responsive_images" text not null,
  "order_column" integer,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "media_model_type_model_id_index" on "media"(
  "model_type",
  "model_id"
);
CREATE TABLE IF NOT EXISTS "auth_permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "auth_roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "display" integer not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "auth_model_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "auth_permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "auth_model_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "auth_model_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "auth_roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "auth_model_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "auth_role_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "auth_permissions"("id") on delete cascade,
  foreign key("role_id") references "auth_roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "tags"(
  "id" integer primary key autoincrement not null,
  "name" text not null,
  "slug" text not null,
  "type" varchar,
  "order_column" integer,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "taggables"(
  "tag_id" integer not null,
  "taggable_type" varchar not null,
  "taggable_id" integer not null,
  foreign key("tag_id") references "tags"("id") on delete cascade
);
CREATE INDEX "taggables_taggable_type_taggable_id_index" on "taggables"(
  "taggable_type",
  "taggable_id"
);
CREATE UNIQUE INDEX "taggables_tag_id_taggable_id_taggable_type_unique" on "taggables"(
  "tag_id",
  "taggable_id",
  "taggable_type"
);
CREATE TABLE IF NOT EXISTS "emulators"(
  "id" integer primary key autoincrement not null,
  "name" varchar,
  "original_name" varchar,
  "description" text,
  "order_column" integer,
  "active" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "documentation_url" varchar,
  "download_url" varchar,
  "download_x64_url" varchar,
  "source_url" varchar,
  "website_url" varchar,
  "can_debug_triggers" tinyint(1) not null default '1'
);
CREATE TABLE IF NOT EXISTS "system_emulators"(
  "id" integer primary key autoincrement not null,
  "system_id" integer not null,
  "emulator_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("system_id") references "Console"("ID") on delete cascade,
  foreign key("emulator_id") references "emulators"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "emulator_releases"(
  "id" integer primary key autoincrement not null,
  "emulator_id" integer not null,
  "version" varchar,
  "stable" tinyint(1) not null default '0',
  "minimum" tinyint(1) not null default '0',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("emulator_id") references "emulators"("id") on delete cascade
);
CREATE UNIQUE INDEX "emulator_releases_emulator_id_version_unique" on "emulator_releases"(
  "emulator_id",
  "version"
);
CREATE TABLE IF NOT EXISTS "integration_releases"(
  "id" integer primary key autoincrement not null,
  "version" varchar not null,
  "stable" tinyint(1) not null default '0',
  "minimum" tinyint(1) not null default '0',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE UNIQUE INDEX "integration_releases_version_unique" on "integration_releases"(
  "version"
);
CREATE TABLE IF NOT EXISTS "user_activities"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "type" varchar not null,
  "subject_type" varchar,
  "subject_id" integer,
  "subject_context" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade
);
CREATE INDEX "user_activities_subject_type_subject_id_index" on "user_activities"(
  "subject_type",
  "subject_id"
);
CREATE TABLE IF NOT EXISTS "user_connections"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "provider" varchar not null,
  "provider_user_id" varchar,
  "token" varchar,
  "token_secret" varchar,
  "refresh_token" varchar,
  "expires" varchar,
  "nickname" varchar,
  "name" varchar,
  "email" varchar,
  "avatar" varchar,
  "url" varchar,
  "raw" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade
);
CREATE UNIQUE INDEX "user_connections_user_id_provider_provider_user_id_unique" on "user_connections"(
  "user_id",
  "provider",
  "provider_user_id"
);
CREATE INDEX "user_connections_provider_provider_user_id_index" on "user_connections"(
  "provider",
  "provider_user_id"
);
CREATE TABLE IF NOT EXISTS "game_set_games"(
  "id" integer primary key autoincrement not null,
  "game_set_id" integer not null,
  "game_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("game_set_id") references "game_sets"("id") on delete cascade,
  foreign key("game_id") references "GameData"("ID") on delete cascade
);
CREATE TABLE IF NOT EXISTS "achievement_set_achievements"(
  "id" integer primary key autoincrement not null,
  "achievement_set_id" integer not null,
  "achievement_id" integer not null,
  "order_column" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("achievement_set_id") references "achievement_sets"("id") on delete cascade,
  foreign key("achievement_id") references "Achievements"("ID") on delete cascade
);
CREATE UNIQUE INDEX "achievement_set_achievement_unique" on "achievement_set_achievements"(
  "achievement_set_id",
  "achievement_id"
);
CREATE TABLE IF NOT EXISTS "achievement_set_versions"(
  "id" integer primary key autoincrement not null,
  "achievement_set_id" integer not null,
  "version" integer,
  "definition" text,
  "players_total" integer,
  "achievements_published" integer,
  "achievements_unpublished" integer,
  "points_total" integer,
  "points_weighted" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "players_hardcore" integer,
  foreign key("achievement_set_id") references "achievement_sets"("id") on delete cascade
);
CREATE UNIQUE INDEX "achievement_set_versions_achievement_set_id_version_unique" on "achievement_set_versions"(
  "achievement_set_id",
  "version"
);
CREATE TABLE IF NOT EXISTS "achievement_set_authors"(
  "id" integer primary key autoincrement not null,
  "achievement_set_id" integer not null,
  "user_id" integer not null,
  "task" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("achievement_set_id") references "achievement_sets"("id") on delete cascade,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade
);
CREATE TABLE IF NOT EXISTS "game_achievement_sets"(
  "id" integer primary key autoincrement not null,
  "game_id" integer not null,
  "achievement_set_id" integer not null,
  "type" varchar,
  "title" varchar,
  "order_column" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("game_id") references "GameData"("ID") on delete cascade,
  foreign key("achievement_set_id") references "achievement_sets"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "game_hash_sets"(
  "id" integer primary key autoincrement not null,
  "game_id" integer not null,
  "compatible" tinyint(1),
  "type" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("game_id") references "GameData"("ID") on delete cascade
);
CREATE TABLE IF NOT EXISTS "game_hash_set_hashes"(
  "id" integer primary key autoincrement not null,
  "game_hash_set_id" integer not null,
  "game_hash_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("game_hash_set_id") references "game_hash_sets"("id") on delete cascade,
  foreign key("game_hash_id") references "game_hashes"("id") on delete cascade
);
CREATE UNIQUE INDEX "game_hash_set_hashes_game_hash_set_id_game_hash_id_unique" on "game_hash_set_hashes"(
  "game_hash_set_id",
  "game_hash_id"
);
CREATE TABLE IF NOT EXISTS "player_sessions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "game_hash_set_id" integer,
  "game_hash_id" integer,
  "game_id" integer,
  "hardcore" tinyint(1),
  "rich_presence" text,
  "rich_presence_updated_at" datetime,
  "duration" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "user_agent" varchar,
  "ip_address" varchar,
  foreign key("user_id") references "UserAccounts"("ID") on delete set null,
  foreign key("game_id") references "GameData"("ID") on delete set null,
  foreign key("game_hash_set_id") references "game_hash_sets"("id") on delete set null,
  foreign key("game_hash_id") references "game_hashes"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "player_achievements"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "achievement_id" integer not null,
  "trigger_id" integer,
  "player_session_id" integer,
  "unlocker_id" integer,
  "unlocked_at" datetime,
  "unlocked_hardcore_at" datetime,
  foreign key("achievement_id") references "Achievements"("ID") on delete cascade,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade,
  foreign key("trigger_id") references "triggers"("id") on delete set null,
  foreign key("player_session_id") references "player_sessions"("id") on delete set null,
  foreign key("unlocker_id") references "UserAccounts"("ID") on delete set null
);
CREATE UNIQUE INDEX "player_achievements_user_id_achievement_id_unique" on "player_achievements"(
  "user_id",
  "achievement_id"
);
CREATE INDEX "player_achievements_unlocked_at_index" on "player_achievements"(
  "unlocked_at"
);
CREATE TABLE IF NOT EXISTS "leaderboard_entries"(
  "id" integer primary key autoincrement not null,
  "leaderboard_id" integer not null,
  "user_id" integer not null,
  "score" integer not null,
  "trigger_id" integer,
  "player_session_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade,
  foreign key("trigger_id") references "triggers"("id") on delete set null,
  foreign key("player_session_id") references "player_sessions"("id") on delete set null
);
CREATE UNIQUE INDEX "leaderboard_entries_leaderboard_id_user_id_unique" on "leaderboard_entries"(
  "leaderboard_id",
  "user_id"
);
CREATE INDEX "player_achievements_unlocked_hardcore_at_index" on "player_achievements"(
  "unlocked_hardcore_at"
);
CREATE INDEX "achievement_set_versions_players_total_index" on "achievement_set_versions"(
  "players_total"
);
CREATE INDEX "achievement_set_versions_players_hardcore_index" on "achievement_set_versions"(
  "players_hardcore"
);
CREATE TABLE player_achievement_sets(
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  user_id INTEGER NOT NULL,
  achievement_set_id INTEGER NOT NULL,
  achievements_unlocked INTEGER DEFAULT NULL,
  achievements_unlocked_hardcore INTEGER DEFAULT NULL,
  completion_percentage NUMERIC(10, 9) DEFAULT NULL,
  completion_percentage_hardcore NUMERIC(10, 0) DEFAULT NULL,
  completion_dates CLOB DEFAULT NULL,
  completion_dates_hardcore CLOB DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  completed_hardcore_at DATETIME DEFAULT NULL,
  last_unlock_at DATETIME DEFAULT NULL,
  last_unlock_hardcore_at DATETIME DEFAULT NULL,
  points INTEGER DEFAULT NULL,
  points_weighted INTEGER DEFAULT NULL,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  "points_hardcore" integer,
  "achievements_unlocked_softcore" integer,
  "time_taken" integer,
  "time_taken_hardcore" integer,
  FOREIGN KEY(achievement_set_id) REFERENCES achievement_sets(id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
  FOREIGN KEY(user_id) REFERENCES UserAccounts(ID) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
);
CREATE UNIQUE INDEX player_achievement_sets_user_id_achievement_set_id_unique ON player_achievement_sets(
  user_id,
  achievement_set_id
);
CREATE INDEX IDX_1FDB85E3F7EC4085 ON player_achievement_sets(
  achievement_set_id
);
CREATE INDEX IDX_1FDB85E3A76ED395 ON player_achievement_sets(user_id);
CREATE TABLE IF NOT EXISTS "queue_job_batches"(
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
CREATE INDEX "player_achievements_achievement_id_user_id_unlocked_hardcore_at" on "player_achievements"(
  "achievement_id",
  "user_id",
  "unlocked_hardcore_at"
);
CREATE INDEX "player_achievements_user_date_achievement" on "player_achievements"(
  "user_id",
  "unlocked_at",
  "unlocked_hardcore_at",
  "achievement_id"
);
CREATE INDEX "player_sessions_game_id_user_id_rich_presence_updated_at_index" on "player_sessions"(
  "game_id",
  "user_id",
  "rich_presence_updated_at"
);
CREATE INDEX "player_sessions_user_id_game_id_rich_presence_updated_at_index" on "player_sessions"(
  "user_id",
  "game_id",
  "rich_presence_updated_at"
);
CREATE TABLE IF NOT EXISTS "player_stats"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "system_id" integer,
  "type" varchar not null,
  "last_game_id" integer,
  "value" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "stat_updated_at" datetime,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade,
  foreign key("system_id") references "Console"("ID") on delete cascade,
  foreign key("last_game_id") references "GameData"("ID") on delete set null
);
CREATE UNIQUE INDEX "player_stats_user_id_system_id_type_unique" on "player_stats"(
  "user_id",
  "system_id",
  "type"
);
CREATE INDEX "player_stats_user_id_index" on "player_stats"("user_id");
CREATE INDEX "player_stats_system_id_index" on "player_stats"("system_id");
CREATE INDEX "player_stats_type_index" on "player_stats"("type");
CREATE TABLE IF NOT EXISTS "message_threads"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "num_messages" integer not null default '0',
  "last_message_id" integer,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "message_thread_participants"(
  "id" integer primary key autoincrement not null,
  "thread_id" integer not null,
  "user_id" integer not null,
  "num_unread" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade,
  foreign key("thread_id") references "message_threads"("ID") on delete cascade
);
CREATE UNIQUE INDEX "auth_roles_name_guard_name_unique" on "auth_roles"(
  "name",
  "guard_name"
);
CREATE TABLE Comment(
  ID INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  ArticleType INTEGER NOT NULL,
  ArticleID INTEGER NOT NULL,
  user_id BIGINT UNSIGNED DEFAULT NULL,
  Payload CLOB NOT NULL COLLATE "BINARY",
  Submitted DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
  Edited DATETIME DEFAULT NULL,
  commentable_type VARCHAR(255) DEFAULT NULL COLLATE "BINARY",
  commentable_id INTEGER DEFAULT NULL,
  deleted_at DATETIME DEFAULT NULL
);
CREATE INDEX comments_commentable_index ON Comment(
  commentable_type,
  commentable_id
);
CREATE INDEX comment_articleid_index ON Comment(ArticleID);
CREATE TABLE IF NOT EXISTS "subscriptions"(
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  subject_type VARCHAR(255) NOT NULL,
  subject_id INTEGER NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  state INTEGER NOT NULL,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL
);
CREATE UNIQUE INDEX subscription_subjecttype_subjectid_userid_unique ON "subscriptions"(
  subject_type,
  subject_id,
  user_id
);
CREATE UNIQUE INDEX "subscription_subject_type_subject_id_user_id_unique" on "subscriptions"(
  "subject_type",
  "subject_id",
  "user_id"
);
CREATE TABLE SetRequest(
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  GameID BIGINT UNSIGNED NOT NULL,
  Updated DATETIME DEFAULT CURRENT_TIMESTAMP,
  user_id INTEGER DEFAULT NULL,
  type VARCHAR(255) DEFAULT NULL COLLATE "BINARY",
  created_at DATETIME DEFAULT NULL
);
CREATE UNIQUE INDEX user_game_list_entry_user_id_game_id_type_unique ON SetRequest(
  user_id,
  GameID,
  type
);
CREATE TABLE IF NOT EXISTS "achievement_set_incompatible_game_hashes"(
  "id" integer primary key autoincrement not null,
  "achievement_set_id" integer not null,
  "game_hash_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("achievement_set_id") references "achievement_sets"("id") on delete cascade,
  foreign key("game_hash_id") references "game_hashes"("id") on delete cascade
);
CREATE UNIQUE INDEX "set_hash_unique" on "achievement_set_incompatible_game_hashes"(
  "achievement_set_id",
  "game_hash_id"
);
CREATE TABLE Friends(
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  Friendship INTEGER NOT NULL,
  Created DATETIME DEFAULT NULL,
  Updated DATETIME DEFAULT NULL,
  user_id INTEGER DEFAULT NULL,
  related_user_id INTEGER DEFAULT NULL,
  status INTEGER DEFAULT NULL
);
CREATE TABLE SetClaim(
  ID INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  game_id BIGINT UNSIGNED NOT NULL,
  ClaimType INTEGER NOT NULL,
  SetType INTEGER NOT NULL,
  Status INTEGER NOT NULL,
  Extension INTEGER NOT NULL,
  Special INTEGER NOT NULL,
  Created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
  Finished DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
  Updated DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
  user_id INTEGER DEFAULT NULL
);
CREATE TABLE SiteAwards(
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  AwardDate DATETIME NOT NULL,
  AwardType INTEGER NOT NULL,
  AwardData INTEGER DEFAULT NULL,
  AwardDataExtra INTEGER DEFAULT 0 NOT NULL,
  DisplayOrder INTEGER DEFAULT 0 NOT NULL,
  user_id INTEGER DEFAULT NULL
);
CREATE INDEX siteawards_user_id_index ON SiteAwards(user_id);
CREATE INDEX siteawards_awarddata_awardtype_awarddate_index ON SiteAwards(
  AwardData,
  AwardType,
  AwardDate
);
CREATE INDEX siteawards_awardtype_index ON SiteAwards(AwardType);
CREATE INDEX "siteawards_user_id_awarddata_awardtype_awarddataextra_index" on "SiteAwards"(
  "user_id",
  "AwardData",
  "AwardType",
  "AwardDataExtra"
);
CREATE TABLE memory_notes(
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  game_id BIGINT UNSIGNED DEFAULT NULL,
  address INTEGER NOT NULL,
  user_id BIGINT UNSIGNED DEFAULT NULL,
  body CLOB DEFAULT NULL COLLATE "BINARY",
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  game_hash_set_id INTEGER DEFAULT NULL,
  deleted_at DATETIME DEFAULT NULL
);
CREATE INDEX memory_notes_game_id_index ON memory_notes(game_id);
CREATE INDEX memory_notes_address_index ON memory_notes(address);
CREATE INDEX memory_notes_game_hash_set_id_address_index ON memory_notes(
  game_hash_set_id,
  address
);
CREATE INDEX memory_notes_user_id_index ON memory_notes(user_id);
CREATE TABLE IF NOT EXISTS "pulse_values"(
  "id" integer primary key autoincrement not null,
  "timestamp" integer not null,
  "type" varchar not null,
  "key" text not null,
  "key_hash" varchar not null,
  "value" text not null
);
CREATE INDEX "pulse_values_timestamp_index" on "pulse_values"("timestamp");
CREATE INDEX "pulse_values_type_index" on "pulse_values"("type");
CREATE UNIQUE INDEX "pulse_values_type_key_hash_unique" on "pulse_values"(
  "type",
  "key_hash"
);
CREATE TABLE IF NOT EXISTS "pulse_entries"(
  "id" integer primary key autoincrement not null,
  "timestamp" integer not null,
  "type" varchar not null,
  "key" text not null,
  "key_hash" varchar not null,
  "value" integer
);
CREATE INDEX "pulse_entries_timestamp_index" on "pulse_entries"("timestamp");
CREATE INDEX "pulse_entries_type_index" on "pulse_entries"("type");
CREATE INDEX "pulse_entries_key_hash_index" on "pulse_entries"("key_hash");
CREATE INDEX "pulse_entries_timestamp_type_key_hash_value_index" on "pulse_entries"(
  "timestamp",
  "type",
  "key_hash",
  "value"
);
CREATE TABLE IF NOT EXISTS "pulse_aggregates"(
  "id" integer primary key autoincrement not null,
  "bucket" integer not null,
  "period" integer not null,
  "type" varchar not null,
  "key" text not null,
  "key_hash" varchar not null,
  "aggregate" varchar not null,
  "value" numeric not null,
  "count" integer
);
CREATE UNIQUE INDEX "pulse_aggregates_bucket_period_type_aggregate_key_hash_unique" on "pulse_aggregates"(
  "bucket",
  "period",
  "type",
  "aggregate",
  "key_hash"
);
CREATE INDEX "pulse_aggregates_period_bucket_index" on "pulse_aggregates"(
  "period",
  "bucket"
);
CREATE INDEX "pulse_aggregates_type_index" on "pulse_aggregates"("type");
CREATE INDEX "pulse_aggregates_period_type_aggregate_bucket_index" on "pulse_aggregates"(
  "period",
  "type",
  "aggregate",
  "bucket"
);
CREATE TABLE IF NOT EXISTS "game_set_links"(
  "id" integer primary key autoincrement not null,
  "parent_game_set_id" integer not null,
  "child_game_set_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("parent_game_set_id") references "game_sets"("id") on delete cascade,
  foreign key("child_game_set_id") references "game_sets"("id") on delete cascade
);
CREATE INDEX "game_set_links_parent_game_set_id_index" on "game_set_links"(
  "parent_game_set_id"
);
CREATE INDEX "game_set_links_child_game_set_id_index" on "game_set_links"(
  "child_game_set_id"
);
CREATE UNIQUE INDEX "game_set_links_parent_game_set_id_child_game_set_id_unique" on "game_set_links"(
  "parent_game_set_id",
  "child_game_set_id"
);
CREATE UNIQUE INDEX "game_set_games_game_set_id_game_id_unique" on "game_set_games"(
  "game_set_id",
  "game_id"
);
CREATE TABLE IF NOT EXISTS "achievement_authors"(
  "id" integer primary key autoincrement not null,
  "achievement_id" integer not null,
  "user_id" integer not null,
  "task" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("user_id") references UserAccounts("ID") on delete cascade on update no action,
  foreign key("achievement_id") references "Achievements"("ID") on delete cascade
);
CREATE UNIQUE INDEX "achievement_authors_achievement_id_user_id_task_unique" on "achievement_authors"(
  "achievement_id",
  "user_id",
  "task"
);
CREATE TABLE IF NOT EXISTS "user_game_achievement_set_preferences"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "game_achievement_set_id" integer not null,
  "opted_in" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade,
  foreign key("game_achievement_set_id") references "game_achievement_sets"("id") on delete cascade
);
CREATE UNIQUE INDEX "unique_user_gasp" on "user_game_achievement_set_preferences"(
  "user_id",
  "game_achievement_set_id"
);
CREATE TABLE IF NOT EXISTS "event_achievements"(
  "id" integer primary key autoincrement not null,
  "achievement_id" integer not null,
  "source_achievement_id" integer,
  "active_from" date,
  "active_until" date,
  "created_at" datetime,
  "updated_at" datetime,
  "decorator" varchar,
  foreign key("achievement_id") references "Achievements"("ID") on delete cascade,
  foreign key("source_achievement_id") references "Achievements"("ID") on delete cascade
);
CREATE INDEX "event_achievements_source_achievement_id_index" on "event_achievements"(
  "source_achievement_id"
);
CREATE INDEX "event_achievements_active_from_index" on "event_achievements"(
  "active_from"
);
CREATE INDEX "event_achievements_active_until_index" on "event_achievements"(
  "active_until"
);
CREATE TABLE IF NOT EXISTS "emulator_user_agents"(
  "id" integer primary key autoincrement not null,
  "emulator_id" integer not null,
  "client" varchar not null,
  "minimum_allowed_version" varchar,
  "minimum_hardcore_version" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("emulator_id") references "emulators"("id") on delete cascade
);
CREATE INDEX "emulator_user_agents_client_index" on "emulator_user_agents"(
  "client"
);
CREATE TABLE IF NOT EXISTS "forums"(
  "id" integer primary key autoincrement not null,
  "forum_category_id" bigint unsigned default(NULL),
  "title" varchar(255) not null,
  "description" varchar(255) not null,
  "latest_comment_id" bigint unsigned default(NULL),
  "order_column" integer not null default(0),
  "created_at" datetime default(NULL),
  "updated_at" datetime default(NULL),
  "forumable_model" varchar,
  "forumable_id" integer,
  "deleted_at" datetime,
  foreign key("forum_category_id") references "forum_categories"("id") on delete set null
);
CREATE INDEX "forums_forum_category_id_index" on "forums"("forum_category_id");
CREATE UNIQUE INDEX "forums_forumable_unique" on "forums"(
  "forumable_model",
  "forumable_id"
);
CREATE TABLE IF NOT EXISTS "forum_topics"(
  "id" integer primary key autoincrement not null,
  "forum_id" bigint unsigned default(NULL) collate 'binary',
  "title" varchar(255) not null collate 'binary',
  "author_id" bigint unsigned default(NULL),
  "created_at" datetime not null default(CURRENT_TIMESTAMP),
  "latest_comment_id" bigint unsigned default(NULL),
  "required_permissions" integer not null default(0),
  "updated_at" datetime default(NULL),
  "pinned_at" datetime default(NULL),
  "locked_at" datetime default(NULL),
  "deleted_at" datetime default(NULL),
  "body" text,
  foreign key("forum_id") references forums("id") on delete set null on update no action,
  foreign key("author_id") references "UserAccounts"("ID") on delete set null
);
CREATE INDEX "forum_topics_created_at_index" on "forum_topics"("created_at");
CREATE INDEX "forum_topics_forum_id_index" on "forum_topics"("forum_id");
CREATE INDEX "idx_permissions_deleted_latest" on "forum_topics"(
  "required_permissions",
  "deleted_at",
  "latest_comment_id"
);
CREATE TABLE IF NOT EXISTS "forum_topic_comments"(
  "id" integer primary key autoincrement not null,
  "forum_topic_id" bigint unsigned default(NULL),
  "body" clob not null collate 'binary',
  "author_id" bigint unsigned default(NULL),
  "created_at" datetime default(NULL),
  "updated_at" datetime default(CURRENT_TIMESTAMP),
  "is_authorized" integer default(NULL),
  "authorized_at" datetime default(NULL),
  "deleted_at" datetime default(NULL),
  foreign key("forum_topic_id") references forum_topics("id") on delete set null on update no action,
  foreign key("author_id") references "UserAccounts"("ID") on delete set null
);
CREATE INDEX "forum_topic_comments_author_id_created_at_index" on "forum_topic_comments"(
  "author_id",
  "created_at"
);
CREATE INDEX "forum_topic_comments_created_at_index" on "forum_topic_comments"(
  "created_at"
);
CREATE INDEX "forum_topic_comments_forum_topic_id_index" on "forum_topic_comments"(
  "forum_topic_id"
);
CREATE TABLE IF NOT EXISTS "news"(
  "id" integer primary key autoincrement not null,
  "created_at" datetime not null default(CURRENT_TIMESTAMP) collate 'binary',
  "title" varchar(255) default(NULL) collate 'binary',
  "body" clob not null collate 'binary',
  "link" varchar(255) default(NULL) collate 'binary',
  "image_asset_path" varchar(255) default(NULL) collate 'binary',
  "updated_at" datetime default(NULL) collate 'binary',
  "lead" clob default(NULL) collate 'binary',
  "user_id" integer default(NULL),
  "publish_at" datetime default(NULL),
  "unpublish_at" datetime default(NULL),
  "deleted_at" datetime default(NULL),
  "pinned_at" datetime,
  "category" varchar,
  foreign key("user_id") references "UserAccounts"("ID") on delete set null
);
CREATE TABLE IF NOT EXISTS "game_sets"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "definition" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "game_id" integer,
  "type" varchar not null,
  "title" varchar,
  "image_asset_path" varchar default('/Images/000001.png'),
  "internal_notes" text,
  "has_mature_content" tinyint(1) not null default('0'),
  "forum_topic_id" integer,
  foreign key("game_id") references GameData("ID") on delete cascade on update no action,
  foreign key("user_id") references UserAccounts("ID") on delete set null on update no action,
  foreign key("forum_topic_id") references "forum_topics"("id") on delete set null
);
CREATE INDEX "game_sets_user_id_index" on "game_sets"("user_id");
CREATE INDEX "game_sets_forum_topic_id_index" on "game_sets"("forum_topic_id");
CREATE INDEX "idx_recent_entries" on "leaderboard_entries"(
  "deleted_at",
  "updated_at",
  "leaderboard_id"
);
CREATE TABLE IF NOT EXISTS "events"(
  "id" integer primary key autoincrement not null,
  "legacy_game_id" integer not null,
  "image_asset_path" varchar not null default('/Images/000001.png'),
  "active_from" date,
  "active_until" date,
  "created_at" datetime,
  "updated_at" datetime,
  "gives_site_award" tinyint(1) not null default '0',
  foreign key("legacy_game_id") references "GameData"("ID") on delete cascade
);
CREATE TABLE IF NOT EXISTS "Achievements"(
  "ID" integer primary key autoincrement not null,
  "GameID" bigint unsigned default(NULL),
  "Title" varchar(255) not null collate 'binary',
  "Description" varchar(255) default(NULL) collate 'binary',
  "MemAddr" clob not null collate 'binary',
  "Progress" varchar(255) default(NULL) collate 'binary',
  "ProgressMax" varchar(255) default(NULL) collate 'binary',
  "ProgressFormat" varchar(255) default(NULL) collate 'binary',
  "Points" integer not null default(0),
  "Flags" integer not null default(5),
  "DateCreated" datetime default(NULL),
  "DateModified" datetime default(CURRENT_TIMESTAMP),
  "VotesPos" integer not null default(0),
  "VotesNeg" integer not null default(0),
  "BadgeName" varchar(255) default('00001') collate 'binary',
  "DisplayOrder" integer not null default(0),
  "AssocVideo" varchar(255) default(NULL) collate 'binary',
  "TrueRatio" integer not null default(0),
  "Updated" datetime default(NULL),
  "user_id" integer default(NULL),
  "unlocks_total" integer default(NULL),
  "unlocks_hardcore_total" integer default(NULL),
  "unlock_percentage" numeric(10, 0) default(NULL),
  "unlock_hardcore_percentage" numeric(10, 0) default(NULL),
  "deleted_at" datetime default(NULL),
  "type" varchar(255) default(NULL) collate 'binary',
  "trigger_id" integer,
  foreign key("trigger_id") references "triggers"("id") on delete set null
);
CREATE INDEX "achievements_game_id_published_index" on "Achievements"(
  "GameID",
  "Flags"
);
CREATE INDEX "achievements_gameid_datemodified_deleted_at_index" on "Achievements"(
  "GameID",
  "DateModified",
  "deleted_at"
);
CREATE INDEX "achievements_gameid_index" on "Achievements"("GameID");
CREATE INDEX "achievements_points_index" on "Achievements"("Points");
CREATE INDEX "achievements_trueratio_index" on "Achievements"("TrueRatio");
CREATE INDEX "achievements_type_index" on "Achievements"("type");
CREATE INDEX "achievements_trigger_id_index" on "Achievements"("trigger_id");
CREATE TABLE IF NOT EXISTS "GameData"(
  "ID" integer primary key autoincrement not null,
  "Title" varchar(80) default(NULL),
  "ConsoleID" integer unsigned default(NULL),
  "ForumTopicID" bigint unsigned default(NULL),
  "Flags" integer default(NULL),
  "ImageIcon" varchar(255) default('/Images/000001.png'),
  "ImageTitle" varchar(255) default('/Images/000002.png'),
  "ImageIngame" varchar(255) default('/Images/000002.png'),
  "ImageBoxArt" varchar(255) default('/Images/000002.png'),
  "Publisher" varchar(255) default(NULL),
  "Developer" varchar(255) default(NULL),
  "Genre" varchar(255) default(NULL),
  "RichPresencePatch" clob default(NULL),
  "TotalTruePoints" integer not null default(0),
  "Created" datetime default(NULL),
  "Updated" datetime default(NULL),
  "players_total" integer default(NULL),
  "achievements_published" integer default(NULL),
  "achievements_unpublished" integer default(NULL),
  "points_total" integer default(NULL),
  "released_at" datetime default(NULL),
  "deleted_at" datetime default(NULL),
  "GuideURL" varchar(255) default(NULL),
  "players_hardcore" integer default(NULL),
  "achievement_set_version_hash" varchar(255) default(NULL),
  "released_at_granularity" varchar,
  "sort_title" varchar,
  "trigger_id" integer,
  "times_beaten" integer not null default '0',
  "times_beaten_hardcore" integer not null default '0',
  "median_time_to_beat" integer,
  "median_time_to_beat_hardcore" integer,
  foreign key("trigger_id") references "triggers"("id") on delete set null
);
CREATE INDEX "gamedata_sort_title_index" on "GameData"("sort_title");
CREATE INDEX "games_players_hardcore_index" on "GameData"("players_hardcore");
CREATE INDEX "games_players_total_index" on "GameData"("players_total");
CREATE INDEX "games_released_at_index" on "GameData"("released_at");
CREATE INDEX "games_system_id_index" on "GameData"("ConsoleID");
CREATE INDEX "games_title_index" on "GameData"("Title");
CREATE UNIQUE INDEX "games_title_system_id_unique" on "GameData"(
  "Title",
  "ConsoleID"
);
CREATE INDEX "gamedata_trigger_id_index" on "GameData"("trigger_id");
CREATE TABLE IF NOT EXISTS "triggers"(
  "id" integer primary key autoincrement not null,
  "triggerable_type" varchar not null,
  "triggerable_id" integer not null,
  "user_id" integer,
  "version" integer,
  "parent_id" integer,
  "conditions" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("user_id") references UserAccounts("ID") on delete set null on update no action,
  foreign key("parent_id") references "triggers"("id") on delete set null
);
CREATE INDEX "triggers_triggerable_type_triggerable_id_index" on "triggers"(
  "triggerable_type",
  "triggerable_id"
);
CREATE UNIQUE INDEX "triggers_triggerable_type_triggerable_id_version_unique" on "triggers"(
  "triggerable_type",
  "triggerable_id",
  "version"
);
CREATE INDEX "triggers_parent_id_index" on "triggers"("parent_id");
CREATE TABLE IF NOT EXISTS "event_awards"(
  "id" integer primary key autoincrement not null,
  "event_id" integer not null,
  "tier_index" integer not null,
  "label" varchar not null,
  "points_required" integer not null,
  "image_asset_path" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("event_id") references "events"("id") on delete cascade
);
CREATE UNIQUE INDEX "event_awards_event_id_tier_index_unique" on "event_awards"(
  "event_id",
  "tier_index"
);
CREATE TABLE IF NOT EXISTS "user_usernames"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "username" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "approved_at" datetime,
  "denied_at" datetime,
  foreign key("user_id") references "UserAccounts"("ID") on delete set null
);
CREATE INDEX "user_usernames_username_index" on "user_usernames"("username");
CREATE TABLE IF NOT EXISTS "UserAccounts"(
  "ID" integer primary key autoincrement not null,
  "User" varchar(255) not null,
  "SaltedPass" varchar(255) not null,
  "EmailAddress" varchar(255) not null,
  "Permissions" integer not null,
  "RAPoints" integer not null,
  "fbUser" integer not null,
  "fbPrefs" integer default(NULL),
  "cookie" varchar(255) default(NULL),
  "appToken" varchar(255) default(NULL),
  "appTokenExpiry" datetime default(NULL),
  "websitePrefs" integer unsigned default(0),
  "LastLogin" datetime default(NULL),
  "LastActivityID" integer not null default(0),
  "Motto" varchar(255) not null default(''),
  "ContribCount" integer not null default(0),
  "ContribYield" integer not null default(0),
  "APIKey" varchar(255) default(NULL),
  "APIUses" integer not null default(0),
  "LastGameID" integer not null default(0),
  "RichPresenceMsg" varchar(255) default(NULL),
  "RichPresenceMsgDate" datetime default(NULL),
  "ManuallyVerified" integer default(0),
  "UnreadMessageCount" integer default(NULL),
  "TrueRAPoints" integer default(NULL),
  "UserWallActive" boolean not null default(1),
  "PasswordResetToken" varchar(255) default(NULL),
  "Untracked" boolean not null default(0),
  "email_backup" varchar(255) default(NULL),
  "Created" datetime default(NULL),
  "Updated" datetime default(NULL),
  "Password" varchar(255) default(NULL),
  "DeleteRequested" datetime default(NULL),
  "Deleted" datetime default(NULL),
  "RASoftcorePoints" integer default(0),
  "display_name" varchar(255) default(NULL),
  "email_verified_at" datetime default(NULL),
  "remember_token" varchar(255) default(NULL),
  "preferences" clob default(NULL),
  "country" varchar(255) default(NULL),
  "timezone" varchar(255) default(NULL),
  "locale" varchar(255) default(NULL),
  "locale_date" varchar(255) default(NULL),
  "locale_number" varchar(255) default(NULL),
  "forum_verified_at" datetime default(NULL),
  "unranked_at" datetime default(NULL),
  "banned_at" datetime default(NULL),
  "muted_until" datetime default(NULL),
  "achievements_unlocked" integer default(NULL),
  "achievements_unlocked_hardcore" integer default(NULL),
  "completion_percentage_average" numeric(10, 0) default(NULL),
  "completion_percentage_average_hardcore" numeric(10, 0) default(NULL),
  "two_factor_secret" clob default(NULL),
  "two_factor_recovery_codes" clob default(NULL),
  "two_factor_confirmed_at" datetime default(NULL),
  "visible_role_id" integer,
  "ulid" varchar,
  foreign key("visible_role_id") references "auth_roles"("id") on delete set null
);
CREATE UNIQUE INDEX "useraccounts_display_name_unique" on "UserAccounts"(
  "display_name"
);
CREATE INDEX "useraccounts_lastlogin_deleted_index" on "UserAccounts"(
  "LastLogin",
  "Deleted"
);
CREATE INDEX "users_apikey_index" on "UserAccounts"("APIKey");
CREATE INDEX "users_apptoken_index" on "UserAccounts"("appToken");
CREATE INDEX "users_last_activity_id_index" on "UserAccounts"(
  "LastActivityID"
);
CREATE INDEX "users_points_softcore_unranked_at_index" on "UserAccounts"(
  "RASoftcorePoints",
  "unranked_at"
);
CREATE INDEX "users_points_softcore_untracked_index" on "UserAccounts"(
  "RASoftcorePoints",
  "Untracked"
);
CREATE INDEX "users_points_unranked_at_index" on "UserAccounts"(
  "RAPoints",
  "unranked_at"
);
CREATE INDEX "users_points_untracked_index" on "UserAccounts"(
  "RAPoints",
  "Untracked"
);
CREATE INDEX "users_points_weighted_unranked_at_index" on "UserAccounts"(
  "TrueRAPoints",
  "unranked_at"
);
CREATE INDEX "users_points_weighted_untracked_index" on "UserAccounts"(
  "TrueRAPoints",
  "Untracked"
);
CREATE INDEX "users_unranked_at_index" on "UserAccounts"("unranked_at");
CREATE INDEX "users_untracked_points_index" on "UserAccounts"(
  "Untracked",
  "RAPoints"
);
CREATE UNIQUE INDEX "users_username_unique" on "UserAccounts"("User");
CREATE INDEX "users_username_untracked_index" on "UserAccounts"(
  "User",
  "Untracked"
);
CREATE INDEX "useraccounts_visible_role_id_index" on "UserAccounts"(
  "visible_role_id"
);
CREATE UNIQUE INDEX "useraccounts_ulid_unique" on "UserAccounts"("ulid");
CREATE INDEX "comment_user_id_submitted_index" on "Comment"(
  "user_id",
  "Submitted"
);
CREATE TABLE IF NOT EXISTS "Ticket"(
  "ID" integer primary key autoincrement not null,
  "AchievementID" bigint unsigned default(NULL),
  "reporter_id" bigint unsigned default(NULL),
  "ReportType" integer not null,
  "ReportNotes" clob not null collate 'binary',
  "ReportedAt" datetime default(NULL),
  "ResolvedAt" datetime default(NULL),
  "resolver_id" bigint unsigned default(NULL),
  "ReportState" integer not null default(1),
  "Updated" datetime default(NULL),
  "Hardcore" boolean default(NULL),
  "ticketable_model" varchar(255) default(NULL) collate 'binary',
  "ticketable_id" integer default(NULL),
  "deleted_at" datetime default(NULL),
  "ticketable_author_id" integer,
  "game_hash_id" integer,
  "emulator_id" integer,
  "emulator_version" varchar,
  "emulator_core" varchar,
  foreign key("game_hash_id") references "game_hashes"("id") on delete set null,
  foreign key("emulator_id") references "emulators"("id") on delete set null
);
CREATE INDEX "ticket_achievementid_reportstate_deleted_at_index" on "Ticket"(
  "AchievementID",
  "ReportState",
  "deleted_at"
);
CREATE INDEX "tickets_achievement_id_reporter_id_index" on "Ticket"(
  "AchievementID",
  "reporter_id"
);
CREATE INDEX "tickets_created_at_index" on "Ticket"("ReportedAt");
CREATE INDEX "tickets_ticketable_index" on "Ticket"(
  "ticketable_model",
  "ticketable_id"
);
CREATE UNIQUE INDEX "tickets_ticketable_reporter_id_index" on "Ticket"(
  "ticketable_model",
  "ticketable_id",
  "reporter_id"
);
CREATE TABLE IF NOT EXISTS "platforms"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "execution_environment" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "order_column" integer not null default '0'
);
CREATE TABLE IF NOT EXISTS "emulator_platforms"(
  "id" integer primary key autoincrement not null,
  "emulator_id" integer not null,
  "platform_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("emulator_id") references "emulators"("id") on delete cascade,
  foreign key("platform_id") references "platforms"("id") on delete cascade
);
CREATE UNIQUE INDEX "emulator_platforms_emulator_id_platform_id_unique" on "emulator_platforms"(
  "emulator_id",
  "platform_id"
);
CREATE TABLE IF NOT EXISTS "emulator_downloads"(
  "id" integer primary key autoincrement not null,
  "emulator_id" integer not null,
  "platform_id" integer not null,
  "label" varchar,
  "url" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("emulator_id") references "emulators"("id") on delete cascade,
  foreign key("platform_id") references "platforms"("id") on delete cascade
);
CREATE UNIQUE INDEX "emulator_downloads_emulator_id_platform_id_unique" on "emulator_downloads"(
  "emulator_id",
  "platform_id"
);
CREATE TABLE IF NOT EXISTS "game_hashes"(
  "id" integer primary key autoincrement not null,
  "md5" varchar(32) default(NULL) collate 'binary',
  "game_id" bigint unsigned not null,
  "created_at" datetime default(NULL),
  "name" varchar(255) default(NULL) collate 'binary',
  "labels" varchar(255) default(NULL) collate 'binary',
  "system_id" integer default(NULL),
  "hash" varchar(255) default(NULL) collate 'binary',
  "type" varchar(255) default(NULL) collate 'binary',
  "crc" varchar(255) default(NULL) collate 'binary',
  "sha1" varchar(255) default(NULL) collate 'binary',
  "file_crc" varchar(255) default(NULL) collate 'binary',
  "file_md5" varchar(255) default(NULL) collate 'binary',
  "file_sha1" varchar(255) default(NULL) collate 'binary',
  "file_name_md5" varchar(255) default(NULL) collate 'binary',
  "description" varchar(255) default(NULL) collate 'binary',
  "file_names" clob default(NULL) collate 'binary',
  "file_size" integer default(NULL),
  "parent_id" integer default(NULL),
  "regions" clob default(NULL) collate 'binary',
  "languages" clob default(NULL) collate 'binary',
  "source" varchar(255) default(NULL) collate 'binary',
  "source_status" varchar(255) default(NULL) collate 'binary',
  "source_version" varchar(255) default(NULL) collate 'binary',
  "imported_at" datetime default(NULL),
  "updated_at" datetime default(NULL),
  "deleted_at" datetime default(NULL),
  "compatibility" varchar(255) default(NULL) collate 'binary',
  "patch_url" varchar(255) default(NULL) collate 'binary',
  "user_id" integer default(NULL),
  "compatibility_tester_id" integer,
  foreign key("compatibility_tester_id") references "UserAccounts"("ID") on delete set null
);
CREATE UNIQUE INDEX "game_hashes_md5_unique" on "game_hashes"("md5");
CREATE UNIQUE INDEX "game_hashes_system_id_hash_unique" on "game_hashes"(
  "system_id",
  "hash"
);
CREATE TABLE IF NOT EXISTS "achievement_maintainers"(
  "id" integer primary key autoincrement not null,
  "achievement_id" integer not null,
  "user_id" integer not null,
  "effective_from" datetime not null default(CURRENT_TIMESTAMP),
  "effective_until" datetime,
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("achievement_id") references "Achievements"("ID") on delete cascade,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade
);
CREATE TABLE IF NOT EXISTS "messages"(
  "id" integer primary key autoincrement not null,
  "thread_id" integer not null,
  "author_id" integer not null,
  "body" text,
  "created_at" datetime,
  "updated_at" datetime,
  "sent_by_id" integer,
  foreign key("thread_id") references message_threads("ID") on delete cascade on update no action,
  foreign key("author_id") references UserAccounts("ID") on delete cascade on update no action,
  foreign key("sent_by_id") references "UserAccounts"("ID") on delete set null
);
CREATE TABLE IF NOT EXISTS "achievement_maintainer_unlocks"(
  "id" integer primary key autoincrement not null,
  "player_achievement_id" integer not null,
  "maintainer_id" integer not null,
  "achievement_id" integer not null,
  foreign key("player_achievement_id") references "player_achievements"("id") on delete cascade,
  foreign key("maintainer_id") references "UserAccounts"("ID") on delete cascade,
  foreign key("achievement_id") references "Achievements"("ID") on delete cascade
);
CREATE TABLE IF NOT EXISTS "downloads_popularity_metrics"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "ordered_ids" text not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "downloads_popularity_metrics_key_unique" on "downloads_popularity_metrics"(
  "key"
);
CREATE TABLE IF NOT EXISTS "achievement_sets"(
  "id" integer primary key autoincrement not null,
  "players_total" integer,
  "achievements_published" integer,
  "achievements_unpublished" integer,
  "points_total" integer,
  "points_weighted" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "players_hardcore" integer,
  "image_asset_path" varchar not null default('/Images/000001.png'),
  "achievements_first_published_at" datetime,
  "times_completed" integer not null default '0',
  "times_completed_hardcore" integer not null default '0',
  "median_time_to_complete" integer,
  "median_time_to_complete_hardcore" integer
);
CREATE INDEX "achievement_sets_players_hardcore_index" on "achievement_sets"(
  "players_hardcore"
);
CREATE INDEX "achievement_sets_players_total_index" on "achievement_sets"(
  "players_total"
);
CREATE TABLE IF NOT EXISTS "player_games"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "game_id" integer not null,
  "achievements_total" integer default(NULL),
  "achievements_unlocked" integer default(NULL),
  "achievements_unlocked_hardcore" integer default(NULL),
  "completion_percentage" numeric(10, 9) default(NULL),
  "completion_percentage_hardcore" numeric(10, 0) default(NULL),
  "last_played_at" datetime default(NULL),
  "time_taken" integer default(NULL),
  "completion_dates" clob default(NULL) collate 'binary',
  "completion_dates_hardcore" clob default(NULL) collate 'binary',
  "completed_at" datetime default(NULL),
  "completed_hardcore_at" datetime default(NULL),
  "last_unlock_at" datetime default(NULL),
  "last_unlock_hardcore_at" datetime default(NULL),
  "first_unlock_at" datetime default(NULL),
  "points_total" integer default(NULL),
  "points" integer default(NULL),
  "points_weighted" integer default(NULL),
  "created_at" datetime default(NULL),
  "updated_at" datetime default(NULL),
  "deleted_at" datetime default(NULL),
  "beaten_dates" clob default(NULL) collate 'binary',
  "beaten_dates_hardcore" clob default(NULL) collate 'binary',
  "beaten_at" datetime default(NULL),
  "beaten_hardcore_at" datetime default(NULL),
  "points_hardcore" integer default(NULL),
  "achievements_unlocked_softcore" integer,
  "playtime_total" integer,
  "time_to_beat" integer,
  "time_to_beat_hardcore" integer,
  "all_achievements_total" integer,
  "all_achievements_unlocked" integer,
  "all_achievements_unlocked_hardcore" integer,
  "all_points_total" integer,
  "all_points" integer,
  "all_points_hardcore" integer,
  "all_points_weighted" integer,
  foreign key("game_id") references GameData("ID") on delete cascade on update no action,
  foreign key("user_id") references UserAccounts("ID") on delete cascade on update no action
);
CREATE INDEX "idx_40515077a76ed395" on "player_games"("user_id");
CREATE INDEX "idx_40515077e48fd905" on "player_games"("game_id");
CREATE INDEX "player_games_created_at_index" on "player_games"("created_at");
CREATE INDEX "player_games_game_id_achievements_unlocked_hardcore_index" on "player_games"(
  "game_id",
  "achievements_unlocked_hardcore"
);
CREATE INDEX "player_games_game_id_achievements_unlocked_index" on "player_games"(
  "game_id",
  "achievements_unlocked"
);
CREATE INDEX "player_games_game_id_achievements_unlocked_softcore_index" on "player_games"(
  "game_id",
  "achievements_unlocked_softcore"
);
CREATE INDEX "player_games_game_id_user_id_index" on "player_games"(
  "game_id",
  "user_id"
);
CREATE INDEX "player_games_suggestions_index" on "player_games"(
  "user_id",
  "achievements_unlocked",
  "achievements_total",
  "game_id"
);
CREATE UNIQUE INDEX "player_games_user_id_game_id_unique" on "player_games"(
  "user_id",
  "game_id"
);
CREATE TABLE IF NOT EXISTS "LeaderboardDef"(
  "ID" integer primary key autoincrement not null,
  "GameID" bigint unsigned not null default(0) collate 'binary',
  "Mem" clob not null collate 'binary',
  "Format" varchar(50) default('') collate 'binary',
  "Title" varchar(255) default('Leaderboard Title') collate 'binary',
  "Description" varchar(255) default('Leaderboard Description') collate 'binary',
  "LowerIsBetter" boolean not null default(0),
  "DisplayOrder" integer not null default(0),
  "Created" datetime default(NULL),
  "Updated" datetime default(NULL),
  "deleted_at" datetime default(NULL),
  "author_id" integer default(NULL),
  "trigger_id" integer,
  "top_entry_id" integer,
  foreign key("trigger_id") references triggers("id") on delete set null on update no action,
  foreign key("top_entry_id") references "leaderboard_entries"("id") on delete set null
);
CREATE INDEX "leaderboarddef_trigger_id_index" on "LeaderboardDef"(
  "trigger_id"
);
CREATE INDEX "leaderboards_game_id_index" on "LeaderboardDef"("GameID");
CREATE TABLE IF NOT EXISTS "game_releases"(
  "id" integer primary key autoincrement not null,
  "game_id" integer not null,
  "released_at" datetime,
  "released_at_granularity" varchar,
  "title" varchar not null,
  "region" varchar,
  "is_canonical_game_title" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("game_id") references "GameData"("ID") on delete cascade
);
CREATE INDEX "game_releases_title_index" on "game_releases"("title");
CREATE TABLE IF NOT EXISTS "game_set_roles"(
  "id" integer primary key autoincrement not null,
  "game_set_id" integer not null,
  "role_id" integer not null,
  "permission" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("game_set_id") references "game_sets"("id") on delete cascade,
  foreign key("role_id") references "auth_roles"("id") on delete cascade
);
CREATE INDEX "game_set_roles_game_set_id_permission_index" on "game_set_roles"(
  "game_set_id",
  "permission"
);
CREATE UNIQUE INDEX "game_set_roles_game_set_id_role_id_permission_unique" on "game_set_roles"(
  "game_set_id",
  "role_id",
  "permission"
);
CREATE TABLE IF NOT EXISTS "unranked_users"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade
);
CREATE INDEX "unranked_users_user_id_index" on "unranked_users"("user_id");
CREATE TABLE IF NOT EXISTS "player_progress_resets"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "initiated_by_user_id" integer,
  "type" varchar not null,
  "type_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade,
  foreign key("initiated_by_user_id") references "UserAccounts"("ID") on delete set null
);
CREATE INDEX "player_progress_resets_user_id_type_created_at_index" on "player_progress_resets"(
  "user_id",
  "type",
  "created_at"
);
CREATE INDEX "player_progress_resets_user_id_type_type_id_created_at_index" on "player_progress_resets"(
  "user_id",
  "type",
  "type_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "game_recent_players"(
  "id" integer primary key autoincrement not null,
  "game_id" integer not null,
  "user_id" integer not null,
  "rich_presence" text,
  "rich_presence_updated_at" datetime not null,
  foreign key("game_id") references "GameData"("ID") on delete cascade,
  foreign key("user_id") references "UserAccounts"("ID") on delete cascade
);
CREATE UNIQUE INDEX "game_recent_players_game_id_user_id_unique" on "game_recent_players"(
  "game_id",
  "user_id"
);
CREATE INDEX "idx_game_updated" on "game_recent_players"(
  "game_id",
  "rich_presence_updated_at"
);
CREATE INDEX "setrequest_gameid_type_index" on "SetRequest"("GameID", "type");

INSERT INTO migrations VALUES(1,'2023_06_07_000001_create_pulse_tables',1);
INSERT INTO migrations VALUES(2,'2024_06_23_000000_update_useraccounts_table',1);
INSERT INTO migrations VALUES(3,'2024_08_10_000000_create_game_set_links_table',1);
INSERT INTO migrations VALUES(4,'2024_08_24_000000_add_forum_indexes',1);
INSERT INTO migrations VALUES(5,'2024_09_07_000000_update_achievement_authors_table',1);
INSERT INTO migrations VALUES(6,'2024_09_08_000000_update_emulators_table',1);
INSERT INTO migrations VALUES(7,'2024_09_11_000000_add_datatable_indexes',1);
INSERT INTO migrations VALUES(8,'2024_09_12_000000_update_gamedata_table',1);
INSERT INTO migrations VALUES(9,'2024_09_27_000000_create_user_game_achievement_set_preferences_table',1);
INSERT INTO migrations VALUES(10,'2024_10_12_000000_update_achievement_set_game_hashes_table',1);
INSERT INTO migrations VALUES(11,'2024_10_21_000000_create_event_achievements_table',1);
INSERT INTO migrations VALUES(12,'2024_11_02_000000_update_useraccounts_table',1);
INSERT INTO migrations VALUES(13,'2024_11_15_000000_create_emulator_user_agents_table',1);
INSERT INTO migrations VALUES(14,'2024_12_14_000000_update_game_sets_table',1);
INSERT INTO migrations VALUES(15,'2024_12_15_000000_update_gamedata_table',1);
INSERT INTO migrations VALUES(16,'2024_12_16_000000_update_forumcategory_table',1);
INSERT INTO migrations VALUES(17,'2024_12_16_000001_update_forum_table',1);
INSERT INTO migrations VALUES(18,'2024_12_16_000002_update_forumtopic_table',1);
INSERT INTO migrations VALUES(19,'2024_12_16_000003_update_forumtopiccomment_table',1);
INSERT INTO migrations VALUES(20,'2024_12_17_000000_drop_votes_and_rating_tables',1);
INSERT INTO migrations VALUES(21,'2024_12_17_000001_update_news_table',1);
INSERT INTO migrations VALUES(22,'2024_12_18_000000_update_game_sets_tables',1);
INSERT INTO migrations VALUES(23,'2024_12_22_000000_update_game_sets_table',1);
INSERT INTO migrations VALUES(24,'2024_12_24_000000_update_player_games_table',1);
INSERT INTO migrations VALUES(25,'2024_12_25_000000_update_leaderboard_entries_table',1);
INSERT INTO migrations VALUES(26,'2025_01_06_000000_create_events_table',1);
INSERT INTO migrations VALUES(27,'2025_01_07_000000_denormalize_triggerables',1);
INSERT INTO migrations VALUES(28,'2025_01_07_000001_update_triggers_table',1);
INSERT INTO migrations VALUES(29,'2025_01_10_000000_create_event_awards_table',1);
INSERT INTO migrations VALUES(30,'2025_01_17_000000_update_user_usernames_table',1);
INSERT INTO migrations VALUES(31,'2025_01_18_000000_update_news_table',1);
INSERT INTO migrations VALUES(32,'2025_01_20_000000_update_useraccounts_table',1);
INSERT INTO migrations VALUES(33,'2025_01_25_000000_update_player_games_table',1);
INSERT INTO migrations VALUES(34,'2025_01_26_000000_update_useraccounts_table',1);
INSERT INTO migrations VALUES(35,'2025_01_29_000000_update_event_awards_table',1);
INSERT INTO migrations VALUES(36,'2025_01_29_000001_update_comments_table',1);
INSERT INTO migrations VALUES(37,'2025_03_07_000000_update_ticket_table',1);
INSERT INTO migrations VALUES(38,'2025_03_12_000000_update_events_table',1);
INSERT INTO migrations VALUES(39,'2025_03_16_000000_create_emulator_tables',1);
INSERT INTO migrations VALUES(40,'2025_04_03_000000_update_emulators_table',1);
INSERT INTO migrations VALUES(41,'2025_04_08_000000_update_emulator_tables',1);
INSERT INTO migrations VALUES(42,'2025_04_14_000000_update_achievement_sets_table',1);
INSERT INTO migrations VALUES(43,'2025_04_19_000000_drop_gamealternatives_table',1);
INSERT INTO migrations VALUES(44,'2025_04_19_000000_update_game_hashes_table',1);
INSERT INTO migrations VALUES(45,'2025_04_22_000000_create_achievement_maintainers_table',1);
INSERT INTO migrations VALUES(46,'2025_04_23_000000_update_messages_table',1);
INSERT INTO migrations VALUES(47,'2025_04_27_000000_create_achievement_maintainer_unlocks_table',1);
INSERT INTO migrations VALUES(48,'2025_05_02_000000_create_downloads_popularity_metrics_table',1);
INSERT INTO migrations VALUES(49,'2025_05_05_000000_update_player_achievement_sets',1);
INSERT INTO migrations VALUES(50,'2025_05_05_000001_update_achievement_sets',1);
INSERT INTO migrations VALUES(51,'2025_05_05_000002_update_player_games',1);
INSERT INTO migrations VALUES(52,'2025_05_05_000003_update_gamedata',1);
INSERT INTO migrations VALUES(53,'2025_05_11_000000_update_leaderboarddef_table',1);
INSERT INTO migrations VALUES(54,'2025_05_18_000000_update_events_table',1);
INSERT INTO migrations VALUES(55,'2025_05_19_000000_create_game_releases_table',1);
INSERT INTO migrations VALUES(56,'2025_05_25_000000_create_game_set_roles_table',1);
INSERT INTO migrations VALUES(57,'2025_05_31_000000_create_unranked_users_table',1);
INSERT INTO migrations VALUES(58,'2025_06_07_000000_update_event_achievements_table',1);
INSERT INTO migrations VALUES(59,'2025_06_14_000000_update_gamedata_table',1);
INSERT INTO migrations VALUES(60,'2025_06_29_000000_create_player_progress_resets_table',1);
INSERT INTO migrations VALUES(61,'2025_07_04_000000_create_game_recent_players_table',1);
INSERT INTO migrations VALUES(62,'2025_07_13_000000_update_setrequests_table',1);
