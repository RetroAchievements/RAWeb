<?php

namespace RA;

abstract class ActivityType
{
	const Unknown = 0;
	const EarnedAchivement = 1;
	const Login = 2;
	const StartedPlaying = 3;
	const UploadAchievement = 4;
	const EditAchievement = 5;
	const CompleteGame = 6;
	const NewLeaderboardEntry = 7;
	const ImprovedLeaderboardEntry = 8;
}
