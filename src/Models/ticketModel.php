<?php

namespace RA\Models;

class TicketModel
{
    public function __construct(array $ticketDbResult)
    {
        $this->TicketId = $ticketDbResult['ID'];
        $this->AchievementId = $ticketDbResult['AchievementID'];
        $this->AchievementTitle = $this->Sanitize($ticketDbResult['AchievementTitle']);
        $this->AchievementDesc = $this->Sanitize($ticketDbResult['AchievementDesc']);
        $this->Points = $ticketDbResult['Points'];
        $this->BadgeName = $this->Sanitize($ticketDbResult['BadgeName']);
        $this->AuthorName = $this->Sanitize($ticketDbResult['AchievementAuthor']);
        $this->GameId = $ticketDbResult['GameID'];
        $this->ConsoleName = $this->Sanitize($ticketDbResult['ConsoleName']);
        $this->GameTitle = $this->Sanitize($ticketDbResult['GameTitle']);
        $this->CreatedOn = $this->Sanitize($ticketDbResult['ReportedAt']);
        $this->TicketType = $ticketDbResult['ReportType'];
        $this->TicketState = $ticketDbResult['ReportState'];
        $this->Notes = $this->Sanitize($ticketDbResult['ReportNotes']);
        $this->CreatedBy = $this->Sanitize($ticketDbResult['ReportedBy']);
        $this->ClosedOn = $this->Sanitize($ticketDbResult['ResolvedAt']);
        $this->ClosedBy = $this->Sanitize($ticketDbResult['ResolvedBy']);
    }

    private function Sanitize(?string $input)
    {
        return htmlentities($input, ENT_COMPAT, null, false);
    }

    public int $TicketId;
    public int $AchievementId;
    public string $AchievementTitle;
    public string $AchievementDesc;
    public int $Points;
    public string $BadgeName;
    public string $AuthorName;
    public int $GameId;
    public string $ConsoleName;
    public string $GameTitle;
    public string $CreatedOn;
    public string $ClosedOn;
    public int $TicketType;
    public int $TicketState;
    public string $Notes;
    public string $CreatedBy;
    public string $ClosedBy;
}
