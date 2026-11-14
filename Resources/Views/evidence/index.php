<?php
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evidence Vault</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
    <h1 class="mb-4">Evidence Vault</h1>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'uploaded'): ?>
        <div class="alert alert-success" role="alert">
            Evidence uploaded successfully.
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <a class="btn btn-primary"
           href="index.php?page=evidence&amp;action=create&amp;task_id=<?php echo rawurlencode((string) $taskId); ?>">
            Upload Evidence
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">Original Name</th>
                    <th scope="col">Version</th>
                    <th scope="col">SHA-256 Hash</th>
                    <th scope="col">Notes</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No evidence files have been uploaded.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td>
                                <?php echo $escape($file['original_file_name'] ?? ''); ?>
                                <?php if (EvidenceFile::isAltered($file)): ?>
                                    <span class="badge bg-danger">⚠️ Altered</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $escape($file['version'] ?? ''); ?></td>
                            <td><code><?php echo $escape(substr((string) ($file['sha256_hash'] ?? ''), 0, 10)); ?>...</code></td>
                            <td><?php echo $escape($file['notes'] ?? ''); ?></td>
                            <td>
                                <form method="POST"
                                      action="index.php?page=evidence&amp;action=destroy&amp;id=<?php echo rawurlencode((string) ($file['id'] ?? '')); ?>&amp;task_id=<?php echo rawurlencode((string) $taskId); ?>">
                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?php echo $escape($_SESSION['csrf_token'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
