<?php

use App\Platform\Listeners\DispatchUpdateDeveloperContributionYieldJob;
use App\Platform\Listeners\DispatchUpdateGameMetricsJob;
use App\Platform\Listeners\DispatchUpdatePlayerGameMetricsJob;
use App\Platform\Listeners\DispatchUpdatePlayerMetricsJob;
use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'queue',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    |
    | Silencing a job will instruct Horizon to not place the job in the list
    | of completed jobs within the Horizon dashboard. This setting may be
    | used to fully remove any noisy jobs from the completed jobs list.
    |
    */

    'silenced' => [
        // silence listeners which only dispatch unique jobs
        DispatchUpdateDeveloperContributionYieldJob::class,
        DispatchUpdateGameMetricsJob::class,
        DispatchUpdatePlayerGameMetricsJob::class,
        DispatchUpdatePlayerMetricsJob::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => true,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. For
    | configuring these limits on your workers, see the next section.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    /**
     * QUEUE SUPERVISOR ARCHITECTURE
     *
     * This configuration was originally optimized in July 2025 to handle Sunday traffic
     * spikes and prevent queue starvation. It was updated again in August 2025 to account
     * for a CCX53 server upgrade which doubled the CPU and RAM. The architecture isolates
     * high-volume and slow queues to prevent them from monopolizing shared workers.
     *
     * Total Workers: 37 (16+10+1+6+4)
     * - supervisor-1: General queues (fast, medium volume)
     * - supervisor-2: Batch processing (slower, larger timeout)
     * - supervisor-3: Search indexing (very fast, isolated)
     * - supervisor-4: Player sessions (very high volume, fast - 49M jobs/month)
     * - supervisor-5: Game player count (very slow - 351ms avg)
     */
    'defaults' => [
        /**
         * Primary supervisor for general application queues.
         * Handles most day-to-day queue processing with auto-scaling.
         * Volume: ~200k jobs/day across 9 queue types.
         */
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => [
                'achievement-metrics',
                'default',
                'developer-metrics',
                'game-metrics',
                'player-achievements',
                'player-beaten-games-stats',
                'player-game-metrics',
                'player-metrics',
                'player-points-stats',
            ],
            'balance' => 'auto',
            'autoScalingStrategy' => 'size',
            'maxProcesses' => 16, // Optimized for high-volume queues with auto-scaling
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 300, // NOTE timeout should always be at least several seconds shorter than the queue config's retry_after configuration value
            'nice' => 0,
        ],

        /**
         * Batch processing supervisor for heavy computational jobs.
         * Uses time-based scaling strategy with longer timeouts.
         * Volume: ~1k jobs/day but they're CPU-intensive operations.
         */
        'supervisor-2' => [
            'connection' => 'redis',
            'queue' => [
                'game-beaten-metrics',
                'game-player-games',
                'player-game-metrics-batch',
                'player-points-stats-batch',
            ],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 600, // NOTE timeout should always be at least several seconds shorter than the queue config's retry_after configuration value
            'nice' => 0,
        ],

        /**
         * Search indexing supervisor (Laravel Scout / Meilisearch).
         * This is isolated to prevent search reindexing from affecting other queues.
         * Volume: ~400k jobs/day but very lightweight operations. High I/O.
         */
        'supervisor-3' => [
            'connection' => 'redis',
            'queue' => [
                'scout',
            ],
            'balance' => 'simple',
            'processes' => 1, // Pinned at 1 - search indexing is not time-critical.
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 300, // NOTE timeout should always be at least several seconds shorter than the queue config's retry_after configuration value.
            'nice' => 0,
        ],

        /**
         * Player sessions supervisor (real-time gaming activity & playtime tracking).
         * Isolated due to _extremely_ high volume (49M jobs/month).
         */
        'supervisor-4' => [
            'connection' => 'redis',
            'queue' => [
                'player-sessions',
            ],
            'balance' => 'simple',
            'processes' => 6, // Pinned at 6 - 38ms avg job time
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 300,
            'nice' => 0,
        ],

        /**
         * Game player count supervisor - handles slow database aggregation calculations.
         * Isolated due to slow processing time (351ms avg per job).
         * Prevents blocking faster queues during calculation-heavy operations.
         */
        'supervisor-5' => [
            'connection' => 'redis',
            'queue' => [
                'game-player-count',
            ],
            'balance' => 'simple',
            'processes' => 4, // Pinned at 4 - limited due to slow job execution time
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 300, // NOTE timeout should always be at least several seconds shorter than the queue config's retry_after configuration value.
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
            ],
        ],

        'stage' => [
            'supervisor-1' => [
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
