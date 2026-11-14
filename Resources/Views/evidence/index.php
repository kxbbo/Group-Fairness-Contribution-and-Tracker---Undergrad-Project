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
    <title>Evidence Vault | FairShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/fairshare.css" rel="stylesheet">
</head>
<body>
<a class="fs-skip" href="#main">Skip to content</a>
<div id="fs-navbar"></div>
<main id="main" class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="mb-1 text-muted"><?php echo $escape($task['team_name']); ?></p>
            <h1 class="mb-0"><?php echo $escape($task['title']); ?> — Evidence Vault</h1>
        </div>
        <a class="btn btn-outline-secondary" href="index.php?page=evidence">All tasks</a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'uploaded'): ?>
        <div class="alert alert-success" role="alert">
            Evidence uploaded successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
        <div class="alert alert-success" role="alert">
            Evidence deleted successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?php echo $escape($errorMessage); ?></div>
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
                    <th scope="col">File</th>
                    <th scope="col">Version</th>
                    <th scope="col">SHA-256 Hash</th>
                    <th scope="col">Notes</th>
                    <th scope="col">Uploaded</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No evidence files have been uploaded.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td>
                                <?php echo $escape($file['file_name'] ?? ''); ?>
                                <?php if (EvidenceFile::isAltered($file) || !empty($file['is_altered'])): ?>
                                    <span class="badge bg-danger">Altered</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $escape($file['version'] ?? ''); ?></td>
                            <td><code><?php echo $escape($file['sha256_hash'] ?? ''); ?></code></td>
                            <td><?php echo $escape($file['notes'] ?? ''); ?></td>
                            <td><?php echo $escape($file['created_at'] ?? ''); ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="index.php?page=evidence&amp;action=download&amp;id=<?php echo rawurlencode((string) ($file['id'] ?? '')); ?>&amp;task_id=<?php echo rawurlencode((string) $taskId); ?>">
                                    Download
                                </a>
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
<div id="fs-footer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/layout.js"></script>
</body>
</html>
