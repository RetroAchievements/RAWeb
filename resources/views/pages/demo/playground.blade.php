<?php

use Illuminate\View\View;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Enums\GameListType;
use App\Models\User;

use function Laravel\Folio\{name, render};

name('playground.index');

render(function (View $view) {
    $user = User::firstWhere("User", "Jamiras");
    $action = new BuildGameListAction();

    $result = $action->execute(
        GameListType::UserPlay,
        $user,
        perPage: 50,
        sort: ["field" => "title", "direction" => "asc"], // see $validSortFields in BuildGameListAction for possible sorts
        filters: [], // eg: ['system' => [1]] or ['system' => [1, 5]] or ['award' => ['completed', 'mastered']]
    );

    return $view->with(['result' => $result]);
});

?>

<x-app-layout>
    @dump($result)
</x-app-layout>
