<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Note.php';
require_once __DIR__ . '/src/NoteRepository.php';

$db = Database::getConnection();
$repo = new NoteRepository($db);

$errors = [];
$success = '';
$oldInput = [];
$editNote = null;

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$search = trim($_GET['search'] ?? '');
$filterTag = isset($_GET['tag']) && $_GET['tag'] !== '' ? trim($_GET['tag']) : null;
$filterPriority = isset($_GET['priority']) && $_GET['priority'] !== '' ? (int)$_GET['priority'] : null;
$sortBy = trim($_GET['sort'] ?? 'created_at');
$sortOrder = trim($_GET['order'] ?? 'desc');

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectSort = $_POST['sort'] ?? 'created_at';
    $redirectOrder = $_POST['order'] ?? 'desc';
    $redirectSearch = $_POST['search'] ?? '';
    $redirectTag = $_POST['tag_filter'] ?? '';
    $redirectPriority = $_POST['priority_filter'] ?? '';

    $redirectParams = [
        'sort' => $redirectSort,
        'order' => $redirectOrder,
        'search' => $redirectSearch,
        'tag' => $redirectTag,
        'priority' => $redirectPriority
    ];
    $redirectUrl = 'index.php?' . http_build_query($redirectParams);

    if ($action === 'create') {
        $note = new Note($_POST);
        $validationErrors = $note->validate();
        if (empty($validationErrors)) {
            $repo->save($note);
            $_SESSION['success_message'] = 'Notatka została pomyślnie dodana.';
            header("Location: " . $redirectUrl);
            exit;
        } else {
            $errors = $validationErrors;
            $oldInput = $_POST;
        }
    } elseif ($action === 'update') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $note = new Note(array_merge($_POST, ['id' => $id]));
        $validationErrors = $note->validate();
        if (empty($validationErrors)) {
            $repo->update($note);
            $_SESSION['success_message'] = 'Notatka została pomyślnie zaktualizowana.';
            header("Location: " . $redirectUrl);
            exit;
        } else {
            $errors = $validationErrors;
            $editNote = $repo->getById($id);
            $oldInput = $_POST;
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $repo->delete($id);
            $_SESSION['success_message'] = 'Notatka została pomyślnie usunięta.';
        }
        header("Location: " . $redirectUrl);
        exit;
    }
} else {
    if ($action === 'edit') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $editNote = $repo->getById($id);
        if ($editNote === null) {
            $errors['general'] = 'Nie znaleziono notatki o podanym ID.';
        }
    }
}

$notes = $repo->getAll($search, $filterTag, $filterPriority, $sortBy, $sortOrder);
$tags = $repo->getUniqueTags();

$view = __DIR__ . '/views/index.view.php';
require __DIR__ . '/views/layout.view.php';
