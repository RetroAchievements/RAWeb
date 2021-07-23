<?php

namespace RA;

abstract class ActivityType
{
    public const Unknown = 0;

    public const EarnedAchivement = 1;

    public const Login = 2;

    public const StartedPlaying = 3;

    public const UploadAchievement = 4;

    public const EditAchievement = 5;

    public const CompleteGame = 6;

    public const NewLeaderboardEntry = 7;

    public const ImprovedLeaderboardEntry = 8;

    public const OpenedTicket = 9;

    public const ClosedTicket = 10;
}
