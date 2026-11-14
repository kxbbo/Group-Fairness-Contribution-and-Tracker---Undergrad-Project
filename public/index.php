<?php

require_once dirname(__DIR__) . '/app/Http/Controllers/EvidenceController.php';

session_start();

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$controller = new EvidenceController();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$page = $_GET['page'] ?? 'evidence';
$action = $_GET['action'] ?? 'index';
$taskId = $_GET['task_id'] ?? null;
$errorMessage = null;

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

try {
    if ($page !== 'evidence') {
        http_response_code(404);
        throw new RuntimeException('The requested page was not found.');
    }

    if ($method === 'POST') {
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
            http_response_code(403);
            throw new RuntimeException('The form expired. Reload the page and try again.');
        }

        if ($action === 'store' && $taskId !== null) {
            $controller->store($taskId);
        } elseif ($action === 'destroy' && isset($_GET['id'], $taskId)) {
            $controller->destroy($_GET['id'], $taskId);
        } else {
            http_response_code(405);
            throw new RuntimeException('This action is not available.');
        }
    } elseif ($method === 'GET') {
        if ($action === 'download' && isset($_GET['id'], $taskId)) {
            $controller->download($_GET['id'], $taskId);
        } elseif ($action === 'create' && $taskId !== null) {
            $controller->create($taskId);
        } elseif ($action === 'index' && $taskId !== null) {
            $controller->index($taskId);
        } elseif ($action === 'index' && $taskId === null) {
            $controller->tasks();
        } else {
            http_response_code(404);
            throw new RuntimeException('The requested page was not found.');
        }
    } else {
        header('Allow: GET, POST');
        http_response_code(405);
        throw new RuntimeException('This request method is not supported.');
    }
} catch (PDOException $exception) {
    error_log($exception->__toString());
    http_response_code(500);
    $errorMessage = 'The database is unavailable. Check the FairShare database settings and confirm that MySQL is running.';
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    $errorMessage = $exception->getMessage();
} catch (RuntimeException $exception) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    $errorMessage = $exception->getMessage();
} catch (Throwable $exception) {
    error_log($exception->__toString());
    http_response_code(500);
    $errorMessage = 'An unexpected error occurred. Please try again.';
}

if ($errorMessage !== null) {
    $safeErrorMessage = htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
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
    <main class="container py-4">
        <h1>Evidence Vault</h1>
        <div class="alert alert-danger" role="alert"><?php echo $safeErrorMessage; ?></div>
        <a class="btn btn-outline-secondary" href="index.php?page=evidence">Back to tasks</a>
    </main>
    </body>
    </html>
    <?php
}
