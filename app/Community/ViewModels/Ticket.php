<?php

declare(strict_types=1);

namespace App\Community\ViewModels;

// TODO refactor to legacy model
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
        $this->id = (int) $ticketDbResult['ID'];
        $this->achievementId = (int) $ticketDbResult['AchievementID'];
        $this->achievementTitle = $this->sanitize($ticketDbResult['AchievementTitle']);
        $this->achievementDesc = $this->sanitize($ticketDbResult['AchievementDesc']);
        $this->points = (int) $ticketDbResult['Points'];
        $this->badgeName = $this->sanitize($ticketDbResult['BadgeName']);
        $this->authorName = $this->sanitize($ticketDbResult['AchievementAuthor']);
        $this->gameId = (int) $ticketDbResult['GameID'];
        $this->consoleName = $this->sanitize($ticketDbResult['ConsoleName']);
        $this->gameTitle = $this->sanitize($ticketDbResult['GameTitle']);
        $this->createdOn = $this->sanitize($ticketDbResult['ReportedAt']);
        $this->ticketType = (int) $ticketDbResult['ReportType'];
        $this->ticketState = (int) $ticketDbResult['ReportState'];
        $this->notes = $this->sanitize($ticketDbResult['ReportNotes']);
        $this->createdBy = $this->sanitize($ticketDbResult['ReportedBy']);
        $this->closedOn = $this->sanitize($ticketDbResult['ResolvedAt']);
        $this->closedBy = $this->sanitize($ticketDbResult['ResolvedBy']);
    }

    private function sanitize(?string $input): string
    {
        return empty($input) ? '' : htmlentities($input, ENT_COMPAT, null, false);
    }
}
