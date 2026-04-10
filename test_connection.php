<?php
header('Content-Type: text/plain');
echo "PHP: " . PHP_VERSION . "\n";
echo "PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n\n";

$pass    = 'ARVINBALIW666';
$project = 'vksdulfntdhilgpcmalh';
$user    = "postgres.$project";

// Try every known Supabase pooler region
$regions = [
    'ap-southeast-1','ap-northeast-1','ap-south-1','ap-southeast-2',
    'us-east-1','us-west-1','us-west-2','us-east-2',
    'eu-west-1','eu-west-2','eu-west-3','eu-central-1',
    'sa-east-1','ca-central-1',
];

foreach ($regions as $region) {
    $host = "aws-0-$region.pooler.supabase.com";
    foreach ([6543, 5432] as $port) {
        $dsn = "pgsql:host=$host;port=$port;dbname=postgres;sslmode=require";
        try {
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 3]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "SUCCESS! Region: $region Port: $port\n";
            echo "Use: postgresql://$user:[pass]@$host:$port/postgres\n";
            exit;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            // Only print non-timeout errors
            if (strpos($msg, 'Unknown host') === false && strpos($msg, 'Connection refused') === false) {
                echo "[$region:$port] $msg\n";
            }
        }
    }
}
echo "\nNo working region found. Please share the exact URI from Supabase dashboard.\n";
