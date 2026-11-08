<?php
// scripts/ingest.php

// Ensure output is displayed immediately in terminal
ob_implicit_flush(true);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

use Smalot\PdfParser\Parser;

echo "Starting document ingestion process...\n";

// Map PDF file locations relative to database/data/
$pdfManifest = [
    ['path' => __DIR__ . '/../database/data/maize.pdf',     'crop' => 'Maize'],
    ['path' => __DIR__ . '/../database/data/tomato.pdf',    'crop' => 'Tomato'],
    ['path' => __DIR__ . '/../database/data/potato.pdf',    'crop' => 'Potato'],
    ['path' => __DIR__ . '/../database/data/dry_bean.pdf',  'crop' => 'Dry Bean'],
];

$parser = new Parser();

function chunkText(string $text, int $chunkSize = 800, int $overlap = 100): array {
    $chunks = [];
    $start = 0;
    $length = strlen($text);

    while ($start < $length) {
        $chunk = trim(substr($text, $start, $chunkSize));
        if (!empty($chunk)) {
            $chunks[] = $chunk;
        }
        $start += ($chunkSize - $overlap);
    }
    return $chunks;
}

foreach ($pdfManifest as $item) {
    $realPath = realpath($item['path']);
    
    if (!$realPath || !file_exists($realPath)) {
        echo "[!] File not found at: {$item['path']}\n";
        continue;
    }

    echo "[+] Processing {$item['crop']} manual...\n";

    try {
        $pdf = $parser->parseFile($realPath);
        $text = $pdf->getText();

        if (empty(trim($text))) {
            echo "[-] Warning: Could not extract text from {$item['crop']}.\n";
            continue;
        }

        $chunks = chunkText($text);

        // Clear existing chunks for this crop to avoid duplicates if re-run
        $deleteStmt = $pdo->prepare("DELETE FROM document_chunks WHERE crop_type = :crop");
        $deleteStmt->execute([':crop' => $item['crop']]);

        $stmt = $pdo->prepare("
            INSERT INTO document_chunks (crop_type, chunk_text)
            VALUES (:crop_type, :chunk_text)
        ");

        $insertedCount = 0;
        foreach ($chunks as $chunk) {
            $stmt->execute([
                ':crop_type'  => $item['crop'],
                ':chunk_text' => $chunk
            ]);
            $insertedCount++;
        }

        echo "[✓] Successfully stored {$insertedCount} text chunks for {$item['crop']}.\n";

    } catch (Exception $e) {
        echo "[X] Error processing {$item['crop']}: " . $e->getMessage() . "\n";
    }
}

echo "Document ingestion completed!\n";