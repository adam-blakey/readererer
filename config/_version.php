<?php
    // Taken from https://talltips.novate.co.uk/laravel/versioning-your-laravel-project
    // Viewed on 2024-12-31
    $tag  = exec('git describe --tags');

    if(empty($tag)) {
        $tag = '-.-.-';
    }

    if (substr($tag, 0, 1) === 'v') {
        $tag = substr($tag, 1);
    }

    $hash = trim(exec('git log --pretty="%h" -n1 HEAD'));
    $date = Carbon\Carbon::parse(trim(exec('git log -n1 --pretty=%ci HEAD')));

return [
    'tag' => $tag,
    'date' => $date,
    'hash' => $hash,
    'string' => sprintf('%s-%s (%s)',$tag, $hash, $date->format('d/m/y H:i')),
];