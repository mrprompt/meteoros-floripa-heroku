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

$stations_bramon = explode(',', $_ENV['STATIONS_BRAMON']);
$stations_gmn = explode(',', $_ENV['STATIONS_GMN']);
$bucket = $_ENV['AWS_BUCKET'];
$credentials = [
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
    'region' => $_ENV['AWS_DEFAULT_REGION'],
    'version' => 'latest',
];

$client = new S3Client($credentials);
$adapter = new AwsS3Adapter($client, $bucket);
$filesystem = new Filesystem($adapter);
$loader = new TwigFilesystem(__DIR__);
$twig = new TwigEnvironment($loader);

$recursive = true;
$path = '/';
$contents = [];

foreach ($_GET['station'] ?? array_merge($stations_bramon, $stations_gmn) as $station) {
    $date = DateTime::createFromFormat('Y-m-d', $_GET['date'] ?? (new DateTime())->modify('-1 day')->format('Y-m-d'));

    // BRAMON
    if (preg_match('/^[[:alpha:]]{3,4}[[:digit:]]{1,3}$/', $station)) {
        $path = sprintf("%s/%s/%s/%s/%s/", 'bramon', $station, $date->format('Y'), $date->format('Ym'), $date->format('Ymd'));
    }

    // GMN
    if (preg_match('/^[[:alpha:]]{2}[[:digit:]]{4,}$/', $station)) {
        $path = sprintf("%s/%s/", 'gmn', $station);
    }

    $captures = $filesystem->listContents($path, $recursive);

    // GMN
    if (preg_match('/^[[:alpha:]]{2}[[:digit:]]{4,}$/', $station)) {
        $er = sprintf("/^%s\/%s\/%s_%s_/", 'gmn', $station, $station, $date->format('Ymd'));
        $captures = array_filter($captures, function ($capture) use ($er) {
            if (preg_match($er, $capture['dirname'])) {
                return $capture;
            }
        });
    }

    $contents = array_merge($contents, $captures);
}

echo $twig->render('index.twig', [
    'captures'          => $contents,
    'bucket'            => $bucket,
    '_get'              => $_GET,
    'stations_bramon'   => $stations_bramon,
    'stations_gmn'      => $stations_gmn,
]);
