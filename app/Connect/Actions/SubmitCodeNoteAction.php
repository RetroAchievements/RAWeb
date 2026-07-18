<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Models\User;
use Illuminate\Http\Request;

class SubmitCodeNoteAction extends BaseAuthenticatedApiAction
{
    protected int $gameId;
    protected int $address;
    protected string $note;

    public function execute(int $gameId, int $address, string $note, User $user): array
    {
        $this->gameId = $gameId;
        $this->address = $address;
        $this->note = $note;
        $this->user = $user;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g', 'm'])) {
            return $this->missingParameters();
        }

        $this->gameId = request()->integer('g', 0);
        $this->address = request()->integer('m', 0);
        $this->note = request()->input('n') ?? '';

        return null;
    }

    protected function process(): array
    {
        $action = new SubmitCodeNotesAction();
        $result = $action->execute($this->gameId, [$this->address => $this->note], $this->user);

        if ($result['Success']) {
            return ['Success' => true];
        }

        // strip out the extra fields provided by bulk update response
        return [
            'Success' => false,
            'Status' => $result['Status'],
            'Code' => $result['Code'],
            'Error' => $result['Error'],
        ];
    }
}
