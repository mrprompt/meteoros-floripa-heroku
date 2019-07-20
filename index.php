<?php
/**
 * List captures files stored in S3 from GMN and BRAMON stations.
 *
 * @author Thiago Paes <mrprompt@gmail.com>
 */
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Etime\Flysystem\Plugin\AWS_S3 as AWS_S3_Plugin;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Symfony\Component\Dotenv\Dotenv;

try {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__.'/.env');
} catch (\Symfony\Component\Dotenv\Exception\PathException $ex) {
    // none :)
}

$stations = explode(',', $_ENV['STATIONS']);
$cameras = explode(',', $_ENV['CAMERAS']);
$lenses = explode(',', $_ENV['LENS']);
$lat = $_ENV['LAT'];
$lng = $_ENV['LNG'];
$logo = $_ENV['LOGO'];

$client = new S3Client([
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
    'region' => $_ENV['AWS_DEFAULT_REGION'],
    'version' => 'latest',
]);

$adapter = new AwsS3Adapter($client, $_ENV['AWS_BUCKET']);

$filesystem = new Filesystem($adapter);
$filesystem->addPlugin(new AWS_S3_Plugin\PresignedUrl());

$loader = new \Twig\Loader\FilesystemLoader(__DIR__);

$twig = new \Twig\Environment($loader, ['debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$recursive = true;
$path = '/';
$contents = [];

foreach ($_GET['station'] ?? [] as $station) {
    $date = DateTime::createFromFormat('Y-m-d', $_GET['date']);

    // BRAMON
    if (preg_match('/^[[:alpha:]]{3,4}[[:digit:]]{1,3}$/', $station)) {
        $path = sprintf("%s/%s/%s/%s/", $station, $date->format('Y'), $date->format('Ym'), $date->format('Ymd'));
    }

    // GMN
    if (preg_match('/^[[:alpha:]]{2}[[:digit:]]{4,}$/', $station)) {
        $path = sprintf("%s/", $station);
    }

    $captures = $filesystem->listContents($path, $recursive);

    // GMN
    if (preg_match('/^[[:alpha:]]{2}[[:digit:]]{4,}$/', $station)) {
        $er = sprintf("/^%s\/%s_%s_/", $station, $station, $date->format('Ymd'));
        $captures = array_filter($captures, function ($capture) use ($er) {
            if (preg_match($er, $capture['dirname'])) {
                return $capture;
            }
        });
    }

    $contents = array_merge($contents, $captures);
}

$contents = array_map(function ($capture) use ($filesystem) {
    $capture['path'] = $filesystem->getPresignedUrl($capture['path']);

    return $capture;
}, $contents);

echo $twig->render('index.twig', [
    'stations' => $stations,
    'cameras' => $cameras,
    'lenses' => $lenses,
    'lat' => $lat,
    'lng' => $lng,
    'captures' => $contents,
    'logo' => $logo,
    '_get' => $_GET
]);
