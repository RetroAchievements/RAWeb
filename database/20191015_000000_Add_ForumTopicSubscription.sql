
CREATE TABLE IF NOT EXISTS ForumTopicSubscription (
    ForumTopicID         INT(10) UNSIGNED NOT NULL COMMENT 'FK to ForumTopic',
    UserID               INT(10) UNSIGNED NOT NULL COMMENT 'FK to UserAccounts',
    SubscriptionState TINYINT(1) UNSIGNED NOT NULL COMMENT 'Whether UserID is subscribed to ForumTopicID',
    PRIMARY KEY (ForumTopicID, UserID)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci
  COMMENT = 'Explicit user subscriptions to forum topics';
