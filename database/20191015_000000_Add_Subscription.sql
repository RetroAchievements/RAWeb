
CREATE TABLE IF NOT EXISTS Subscription (
    SubjectType ENUM('ForumTopic',
                     'UserWall',
                     'GameTickets',
                     'GameWall',
                     'GameAchievements',
                     'Achievement') NOT NULL COMMENT 'Type of the Subscription Subject',
    SubjectID   INT(10) UNSIGNED    NOT NULL COMMENT 'FK to the Subscription Subject',
    UserID      INT(10) UNSIGNED    NOT NULL COMMENT 'FK to UserAccounts',
    State       TINYINT(1) UNSIGNED NOT NULL COMMENT 'Whether UserID is subscribed (1) or unsubscribed (0)',
    PRIMARY KEY (SubjectType, SubjectID, UserID)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci
  COMMENT = 'Explicit user subscriptions';
