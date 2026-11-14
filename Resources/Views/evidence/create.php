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
    <title>Upload Evidence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
    <div class="card">
        <div class="card-header">Upload Evidence</div>
        <div class="card-body">
            <form method="POST"
                  action="index.php?page=evidence&amp;action=store&amp;task_id=<?php echo rawurlencode((string) $taskId); ?>"
                  enctype="multipart/form-data">
                <input type="hidden"
                       name="csrf_token"
                       value="<?php echo $escape($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="mb-3">
                    <label for="evidence_file" class="form-label">Evidence file</label>
                    <input type="file"
                           class="form-control"
                           id="evidence_file"
                           name="evidence_file"
                           required>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes (optional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
