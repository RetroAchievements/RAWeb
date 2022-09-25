<?php

namespace RA;

// TODO refactor into legacy model
class Ticket
{
    public int $id;
    public int $achievementId;
    public string $achievementTitle;
    public string $achievementDesc;
    public int $points;
    public string $badgeName;
    public string $authorName;
    public int $gameId;
    public string $consoleName;
    public string $gameTitle;
    public string $createdOn;
    public string $closedOn;
    public int $ticketType;
    public int $ticketState;
    public string $notes;
    public string $createdBy;
    public string $closedBy;

    public function __construct(array $ticketDbResult)
    {
        $this->id = $ticketDbResult['ID'];
        $this->achievementId = $ticketDbResult['AchievementID'];
        $this->achievementTitle = $this->sanitize($ticketDbResult['AchievementTitle']);
        $this->achievementDesc = $this->sanitize($ticketDbResult['AchievementDesc']);
        $this->points = $ticketDbResult['Points'];
        $this->badgeName = $this->sanitize($ticketDbResult['BadgeName']);
        $this->authorName = $this->sanitize($ticketDbResult['AchievementAuthor']);
        $this->gameId = $ticketDbResult['GameID'];
        $this->consoleName = $this->sanitize($ticketDbResult['ConsoleName']);
        $this->gameTitle = $this->sanitize($ticketDbResult['GameTitle']);
        $this->createdOn = $this->sanitize($ticketDbResult['ReportedAt']);
        $this->ticketType = $ticketDbResult['ReportType'];
        $this->ticketState = $ticketDbResult['ReportState'];
        $this->notes = $this->sanitize($ticketDbResult['ReportNotes']);
        $this->createdBy = $this->sanitize($ticketDbResult['ReportedBy']);
        $this->closedOn = $this->sanitize($ticketDbResult['ResolvedAt']);
        $this->closedBy = $this->sanitize($ticketDbResult['ResolvedBy']);
    }

    private function sanitize(?string $input): string
    {
        return htmlentities($input, ENT_COMPAT, null, false);
    }
}
