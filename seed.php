#!/usr/bin/env php
<?php

if (php_sapi_name() !== 'cli') {
    exit(1);
}

$dbPath = __DIR__ . '/db/journal.db';
if (!is_dir(__DIR__ . '/db')) {
    mkdir(__DIR__ . '/db', 0755, true);
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure the entry table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS entry (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

$sampleEntries = [
    "Reviewed pull requests and left constructive feedback on architectural choices.",
    "Morning run was refreshing today. Focused on cadence and steady pace.",
    "Debugging SQLite pagination query optimizations and LIMIT/OFFSET behavior.",
    "Refactored CLI command structure for cleaner separation of concerns.",
    "Configured local development environment and updated dependencies.",
    "Brainstormed ideas for markdown formatting in terminal outputs.",
    "Read chapters on database indexing strategies and B-Tree mechanics.",
    "Took time to organize notes and set priorities for upcoming sprints.",
    "Experimented with Symfony Console table formatting and custom styling.",
    "Resolved an edge case in date timezone conversions across UTC offsets.",
];

$count = 50;
$stmt = $db->prepare("
    INSERT INTO entry (content, created_at, updated_at)
    VALUES (:content, :created_at, :updated_at)
");

$db->beginTransaction();

$baseTime = new DateTimeImmutable('now', new DateTimeZone('UTC'));

for ($i = 1; $i <= $count; $i++) {
    // Generate timestamps distributed backwards in time (e.g. 1 hour apart)
    $createdAt = $baseTime->sub(new DateInterval('PT' . (($count - $i) * 2) . 'H'))->format('Y-m-d H:i:s');
    $sampleText = $sampleEntries[($i - 1) % count($sampleEntries)];
    $content = sprintf("Journal Entry #%d: %s", $i, $sampleText);

    $stmt->execute([
        ':content' => htmlspecialchars($content),
        ':created_at' => $createdAt,
        ':updated_at' => $createdAt,
    ]);
}

$db->commit();

$totalEntries = $db->query("SELECT COUNT(*) FROM entry")->fetchColumn();
echo "Successfully seeded {$count} entries!\n";
echo "Total entries in database: {$totalEntries}\n";
