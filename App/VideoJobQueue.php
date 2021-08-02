<?php
declare(strict_types=1);

namespace App;
require "vendor/autoload.php";

putenv('VIDEO_TEMP_FILE_LOCATION=/home/theo/Documents/cs-project/master/api/uploads');
putenv('DO_KEY=UE7BS2KB5QQTVFBXSM5Z');
putenv('DO_SECRET=coaIMssswJRpKwvcN2k1TltkeYvUMq0uDR5C8jLk3zw');
putenv('SENDGRID_KEY=ShNmGdCyT3m7qq0GTuCfOQ');
putenv('SENDGRID_SECRET=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');

use App\Domain\Database;
use App\Models\Video;
use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\TypeException;

error_log("Job processor loaded");

for (;;) {
    // Select un processed video from database
    $database = new Database();

    $video = $database->getVideoToProcess();

    if (isset($video) && $video !== false) {
        error_log("Job found");

        $video = new Video(
            $video["file_name"],
            $video["user_id"],
            $database->getUserById($video["user_id"]),
            $video["title"],
            $video["description"] ?: "",
            $video["upload_date"],
            0,
            $video["status"],
            $video["id"]);

        $tempPath = getenv("VIDEO_TEMP_FILE_LOCATION")."/$video->fileName";
        $sourcePath =  "$tempPath/source.mp4";
        $destinationPath = "$tempPath/output.m3u8";

        try {
            error_log("Transcoding video...");
            exec("ffmpeg -i $sourcePath $destinationPath -loglevel quiet");
            error_log("Transcoding complete.");

            error_log("Uploading...");
            $client = new S3Client([
                'version' => 'latest',
                'region'  => 'us-east-1',
                'endpoint' => 'https://fra1.digitaloceanspaces.com',
                'credentials' => [
                    'key'    => getenv("DO_KEY"),
                    'secret' => getenv("DO_SECRET")
                ],
            ]);

            try {
                // Transfers the newly created .m3u8 files to the DO Space
                // No need to store the source .mp4 on the DO Space
                exec("rm $sourcePath");
                $client->uploadDirectory($tempPath, "cinema-storage/videos", $video->fileName, [
                    "before" => function(Command $command) {
                        $command['ACL'] = 'public-read';
                    }
                ]);

                error_log("Upload complete.");

                error_log("Cleaning up...");
                exec("rm -r $tempPath");
                error_log("Clean up done.");

                error_log("Saving to DB...");
                $database->markVideoAsReady($video->id);
                error_log("Saved.");

                // TODO send email to user saying upload complete

                error_log("Notifying user...");
                $email = new Mail();
                try {
                    $email->setFrom("noreply@em3833.theocrowley.co.uk", "noreply");
                    $email->setSubject("Upload process complete!");
                    $email->addTo($video->user->email, $video->user->username);
                    $email->addContent("text/plain", "Your Upload $video->title has processed and is now live on the site!");

                    $sendgrid = new SendGrid(getenv('SENDGRID_SECRET'));
                    $sendgrid->send($email);
                    error_log("User notified.");
                } catch (TypeException $e) {
                    error_log($e->getMessage());
                }

                error_log("Done.");
                error_log("Waiting for next job.");

            } catch (S3Exception $e) {
                // If something goes wrong we don't want to be storing useless data
                exec("rm -f -r $tempPath");
            }
        } catch(\Exception $e) {
            error_log($e->getMessage());
        }
    }
}