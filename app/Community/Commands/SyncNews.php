<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\News;
use App\Support\MediaLibrary\Actions\AddMediaAction;
use App\Support\MediaLibrary\Actions\DeleteLatestMediaAction;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncNews extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:news {id?} {--f|full} {--p|no-post} {--m|no-media}';

    protected $description = 'Sync news';

    public function __construct(
        private AddMediaAction $addMediaAction,
        private DeleteLatestMediaAction $deleteLatestMediaAction
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('news');
    }

    protected function query(): Builder
    {
        return DB::table('News')
            ->select('News.*', 'UserAccounts.ID')
            ->join('UserAccounts', 'UserAccounts.ID', '=', 'News.user_id');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        if (!empty($transformed['lead'])) {
            /*
             * breaks had to be added as html - thus none of the line breaks matter
             * remove them to not have double/tripe breaks there
             */
            $transformed['lead'] = str_replace(["\n", "\r"], ['', ''], $transformed['lead']);

            /*
             * then make the remaining breaks the actual newlines
             */
            $transformed['lead'] = str_replace(['<br>', '<br/>', '<br />'], "\n", $transformed['lead']);

            /*
             * have all the html parsed into shortcode
             */
            $transformed['lead'] = html2shortcode(is_string($transformed['lead']) ? $transformed['lead'] : '');
        }

        return $transformed;
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
        /** @var News $news */
        $news = News::withTrashed()->find($transformed->id);

        if (!$news->publish_at) {
            $news->publish_at = $news->created_at;
        }

        /*
         * add link to lead
         */
        if ($origin->Link) {
            $link = url2shortcode($origin->Link);
            if (!$news->lead) {
                $news->lead = $link;
            }
            if (mb_strpos($news->lead, $link) === false) {
                $news->lead .= PHP_EOL . PHP_EOL . $link;
            }
        }

        $news->timestamps = false;
        $news->save();

        if (!$this->option('no-media')) {
            $this->updateImage($news, $origin->Image);
        }
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    private function updateImage(News $news, ?string $image): void
    {
        if (empty($image)) {
            $this->deleteLatestMediaAction->execute($news, 'image');

            return;
        }
        $path = parse_url($image, PHP_URL_PATH);
        if (!$path) {
            return;
        }
        $file = $image;
        if (mb_strpos($path, '/Images') === 0) {
            $file = config('sync.media_path') . $path;
        }
        $this->addMediaAction->execute($news, $file, 'image');
    }
}
