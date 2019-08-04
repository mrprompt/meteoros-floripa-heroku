<?php
/**
 * List captures files stored in S3 from GMN and BRAMON stations.
 *
 * @author Thiago Paes <mrprompt@gmail.com>
 */
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;
use Twig\Loader\FilesystemLoader as TwigFilesystem;
use Twig\Environment as TwigEnvironment;

try {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__.'/.env');
} catch (PathException $ex) {
    // none :)
}

$stations = explode(',', $_ENV['STATIONS']);
$cameras = explode(',', $_ENV['CAMERAS']);
$lenses = explode(',', $_ENV['LENS']);
$lat = $_ENV['LAT'];
$lng = $_ENV['LNG'];
$logo = $_ENV['LOGO'];
$bucket = $_ENV['AWS_BUCKET'];
$region = $_ENV['AWS_DEFAULT_REGION'];

$client = new S3Client([
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
    'region' => $region,
    'version' => 'latest',
]);

$adapter = new AwsS3Adapter($client, $bucket);
$filesystem = new Filesystem($adapter);

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

$loader = new TwigFilesystem(__DIR__);
$twig = new TwigEnvironment($loader);

echo $twig->render('index.twig', [
    'stations'  => $stations,
    'cameras'   => $cameras,
    'lenses'    => $lenses,
    'lat'       => $lat,
    'lng'       => $lng,
    'captures'  => $contents,
    'logo'      => $logo,
    'bucket'    => $bucket,
    'region'    => $region,
    '_get'      => $_GET,
]);
