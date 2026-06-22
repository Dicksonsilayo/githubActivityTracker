<?php

require_once 'GithubActivity.php';

if ($argc < 2) {
    echo "Usage: php github_activity.php <username> [event_type]\n";
    exit(1);
}

$username = $argv[1];
$filter = $argv[2] ?? null;

try {

    $github = new GithubActivity();

    $activities = $github->fetchActivity($username);

    $github->displayGithubActivity(
        $activities,
        $filter
    );

} catch (Exception $e) {

    echo "Error: " . $e->getMessage() . PHP_EOL;
}
