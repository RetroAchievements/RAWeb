<?php

use Aws\CommandInterface;
use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use Aws\Ses\SesClient;

require_once __DIR__ . '/../lib/bootstrap.php';

function mail_ses($to, $subject = '(No subject)', $message = '')
{
    $client = new SesClient([
        'version' => 'latest',
        'region'  => getenv('AWS_DEFAULT_REGION'),
        // Note: automatically pulled from env when named properly
        // 'credentials' => [
        //     'key'    => getenv('AWS_ACCESS_KEY_ID'),
        //     'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
        // ],
    ]);

    $recipients = [
        $to,
    ];

    // Queue emails as SendEmail commands
    $i = 100;
    $commands = [];
    foreach ($recipients as $recipient) {
        $commands[] = $client->getCommand('SendEmail', [
            // Pass the message id so it can be updated after it is processed (it's ignored by SES)
            'x-message-id' => $i,
            'Source'       => getenv('MAIL_FROM_NAME') . ' <' . getenv('MAIL_FROM_ADDRESS') . '>',
            'Destination'  => [
                'ToAddresses' => [$recipient],
            ],
            'Message'      => [
                'Subject' => [
                    'Data'    => $subject,
                    'Charset' => 'UTF-8',
                ],
                'Body'    => [
                    'Html' => [
                        'Data'    => $message,
                        'Charset' => 'UTF-8',
                    ],
                ],
            ],
        ]);
        $i++;
    }

    try {
        $timeStart = microtime(true);
        $pool = new CommandPool($client, $commands, [
            'concurrency' => 10,
            'before'      => function (CommandInterface $cmd, $iteratorId) {
                $a = $cmd->toArray();
                // echo sprintf('About to send %d: %s' . PHP_EOL, $iteratorId, $a['Destination']['ToAddresses'][0]);
                error_log('About to send ' . $iteratorId . ': ' . $a['Destination']['ToAddresses'][0]);
            },
            'fulfilled'   => function (ResultInterface $result, $iteratorId) use ($commands) {
                // echo sprintf(
                //  'Completed %d: %s' . PHP_EOL,
                //  $commands[$iteratorId]['x-message-id'],
                //  $commands[$iteratorId]['Destination']['ToAddresses'][0]
                // );
                error_log('Completed ' . $commands[$iteratorId]['x-message-id'] . ' :' . $commands[$iteratorId]['Destination']['ToAddresses'][0]);
            },
            'rejected'    => function (AwsException $reason, $iteratorId) use ($commands) {
                // echo sprintf(
                //  'Failed %d: %s' . PHP_EOL,
                //  $commands[$iteratorId]['x-message-id'],
                //  $commands[$iteratorId]['Destination']['ToAddresses'][0]
                // );

                error_log('Reason : ' . $reason);
                error_log('Amazon SES Failed Rejected:' . $commands[$iteratorId]['x-message-id'] . ' :' . $commands[$iteratorId]['Destination']['ToAddresses'][0]);
            },
        ]);
        // Initiate the pool transfers
        $promise = $pool->promise();
        // Force the pool to complete synchronously
        $promise->wait();
        $timeEnd = microtime(true);
        // echo sprintf('Operation completed in %s seconds' . PHP_EOL, $timeEnd - $timeStart);
        return true;
    } catch (Exception $e) {
        // echo sprintf('Error: %s' . PHP_EOL, $e->getMessage());
        error_log('Catch Block: Amazon SES Exception : ' . $e->getMessage());
        return false;
    }
}
