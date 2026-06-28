<?php
/**
 * One-time database installer for shared hosting.
 * Delete this file after installation is complete.
 */

require_once dirname(__FILE__) . '/config/database.php';

$lockFile = dirname(__FILE__) . '/config/installed.lock';
$schemaFile = dirname(__FILE__) . '/db/schema.sql';
$seedFile = dirname(__FILE__) . '/db/seed.sql';

function installerRunSqlFile($conn, $file)
{
    if (!is_file($file)) {
        throw new RuntimeException('File tidak ditemukan: ' . basename($file));
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('File SQL kosong atau tidak bisa dibaca: ' . basename($file));
    }

    if (!$conn->multi_query($sql)) {
        throw new RuntimeException($conn->error);
    }

    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
        if ($conn->more_results() && !$conn->next_result()) {
            throw new RuntimeException($conn->error);
        }
    } while ($conn->more_results());
}

$status = 'success';
$message = '';

try {
    if (file_exists($lockFile)) {
        throw new RuntimeException('Installer sudah pernah dijalankan. Hapus config/installed.lock jika ingin menjalankan ulang.');
    }

    installerRunSqlFile($conn, $schemaFile);
    installerRunSqlFile($conn, $seedFile);

    file_put_contents($lockFile, 'Installed at ' . date('Y-m-d H:i:s'));
    $message = 'Database berhasil dibuat dan akun demo sudah tersedia.';
} catch (Throwable $e) {
    $status = 'error';
    $message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Monitoring AP</title>
    <style>
        body {
            align-items: center;
            background: #f4f6fb;
            color: #1f2937;
            display: flex;
            font-family: Arial, sans-serif;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
        }

        main {
            background: #fff;
            border: 1px solid #d7dce7;
            border-radius: 8px;
            max-width: 560px;
            padding: 28px;
            width: calc(100% - 32px);
        }

        h1 {
            font-size: 22px;
            margin: 0 0 12px;
        }

        .message {
            border-radius: 6px;
            margin: 18px 0;
            padding: 14px;
        }

        .success {
            background: #ecfdf3;
            border: 1px solid #abefc6;
            color: #067647;
        }

        .error {
            background: #fef3f2;
            border: 1px solid #fecdca;
            color: #b42318;
        }

        code {
            background: #eef2f7;
            border-radius: 4px;
            padding: 2px 5px;
        }

        a {
            color: #4f46e5;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main>
        <h1>Installer Monitoring AP</h1>
        <div class="message <?php echo $status; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <?php if ($status === 'success'): ?>
            <p>Silakan login menggunakan akun demo yang sama.</p>
            <p><a href="auth/login.php">Buka halaman login</a></p>
            <p>Setelah berhasil, hapus file <code>install.php</code> dari hosting.</p>
        <?php else: ?>
            <p>Pastikan data koneksi di <code>config/database.php</code> sudah sesuai dengan database hosting.</p>
        <?php endif; ?>
    </main>
</body>
</html>
