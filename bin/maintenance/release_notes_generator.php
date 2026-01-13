<?php

/**
 * Script Generatore Note di Rilascio
 * Legge la history git e genera un markdown basato sui commit conventional.
 */

$tags = explode("\n", trim(shell_exec('git tag --sort=-creatordate') ?? ''));
$latestTag = $tags[0] ?? null;
$previousTag = $tags[1] ?? null;

if (!$latestTag) {
    echo "No tags found. Generating notes for HEAD.\n";
    $range = "HEAD";
} elseif (!$previousTag) {
    echo "Only one tag found ($latestTag). Generating notes from start.\n";
    $range = "$latestTag";
} else {
    echo "Generating notes between $previousTag and $latestTag\n";
    $range = "$previousTag..$latestTag";
}

$commits = explode("\n", trim(shell_exec("git log $range --pretty=format:\"%s\"") ?? ''));

$categories = [
    'feat' => [],
    'fix' => [],
    'docs' => [],
    'chore' => [],
    'other' => []
];

foreach ($commits as $msg) {
    if (preg_match('/^(feat|fix|docs|chore)(\(.*\))?: (.*)$/', $msg, $matches)) {
        $type = $matches[1];
        $desc = $matches[3];
        $categories[$type][] = $desc;
    } else {
        $categories['other'][] = $msg;
    }
}

$output = "# Release Notes\n\n";

if (!empty($categories['feat'])) {
    $output .= "## 🚀 New Features\n";
    foreach ($categories['feat'] as $c)
        $output .= "- $c\n";
    $output .= "\n";
}

if (!empty($categories['fix'])) {
    $output .= "## 🐛 Bug Fixes\n";
    foreach ($categories['fix'] as $c)
        $output .= "- $c\n";
    $output .= "\n";
}

if (!empty($categories['other'])) {
    $output .= "## 🔧 Other Changes\n";
    foreach ($categories['other'] as $c)
        $output .= "- $c\n";
}

echo $output;

