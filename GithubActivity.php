<?php

class GithubActivity
{
    public function fetchActivity($username)
    {
        // Create cache directory if it doesn't exist
        if (!is_dir('cache')) {
            mkdir('cache', 0777, true);
        }

        $cacheFile = "cache/{$username}.json";

        // Check cache (valid for 5 minutes)
        if (file_exists($cacheFile)) {

            $cacheAge = time() - filemtime($cacheFile);

            if ($cacheAge < 300) {
                return json_decode(
                    file_get_contents($cacheFile),
                    true
                );
            }
        }

        $url = "https://api.github.com/users/$username/events";

        $options = [
            "http" => [
                "method" => "GET",
                "header" => [
                    "User-Agent: PHP-CLI\r\n",
                    "Accept: application/vnd.github+json\r\n"
                ]
            ]
        ];

        $context = stream_context_create($options);

        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            throw new Exception("Failed to fetch activity from GitHub");
        }

        // Save fresh response to cache
        file_put_contents($cacheFile, $response);

        return json_decode($response, true);
    }

    private function green($text)
    {
        return "\033[32m{$text}\033[0m";
    }

    private function yellow($text)
    {
        return "\033[33m{$text}\033[0m";
    }

    private function blue($text)
    {
        return "\033[34m{$text}\033[0m";
    }

    public function displayGithubActivity(array $activities, $filter = null)
    {
        $countEvents = 0;

        foreach ($activities as $event) {

            $type = $event['type'];

            // Filter events
            if ($filter && $type !== $filter) {
                continue;
            }

            $repo = $event['repo']['name'] ?? 'Unknown Repo';

            switch ($type) {

                case "PushEvent":
                    $count = count($event['payload']['commits'] ?? []);

                    echo $this->green(
                        "- Pushed {$count} commit(s) to {$repo}"
                    ) . PHP_EOL;

                    break;

                case "WatchEvent":

                    echo $this->yellow(
                        "- Starred {$repo}"
                    ) . PHP_EOL;

                    break;

                case "ForkEvent":

                    echo $this->blue(
                        "- Forked {$repo}"
                    ) . PHP_EOL;

                    break;

                case "IssuesEvent":

                    $action = $event['payload']['action'] ?? '';

                    echo $this->yellow(
                        "- {$action} an issue in {$repo}"
                    ) . PHP_EOL;

                    break;

                case "PullRequestEvent":

                    $action = $event['payload']['action'] ?? '';

                    echo $this->green(
                        "- {$action} a pull request in {$repo}"
                    ) . PHP_EOL;

                    break;

                default:

                    echo "- {$type} on {$repo}" . PHP_EOL;
            }

            $countEvents++;

            // Show only latest 10 events
            if ($countEvents >= 10) {
                break;
            }
        }
    }
}
