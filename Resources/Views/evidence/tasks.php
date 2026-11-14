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
    <h1 class="mb-4">Evidence Vault</h1>
    <p>Select a task to view or upload its evidence.</p>

    <?php if (empty($tasks)): ?>
        <p class="text-muted">No tasks are available yet.</p>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($tasks as $task): ?>
                <a class="list-group-item list-group-item-action"
                   href="index.php?page=evidence&amp;task_id=<?php echo rawurlencode((string) $task['id']); ?>">
                    <strong><?php echo $escape($task['title']); ?></strong>
                    <span class="text-muted"> — <?php echo $escape($task['team_name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<div id="fs-footer"></div>
<script src="js/layout.js"></script>
</body>
</html>
