<?php
$tasks = [
    [
        'title' => 'tytul jacek',
        'category' => 'praca',
        'priority' => 'wysoki',
        'status' => 'do zrobienia',
        'estimated_minutes' => 180,
        'tags' => ['nie trzy', 'pilne']
    ],
    [
        'title' => 'tytul placek',
        'category' => 'dom',
        'priority' => 'średni',
        'status' => 'do zrobienia',
        'estimated_minutes' => 45,
        'tags' => ['pilne', 'trrzy']
    ],
    [
        'title' => 'Tytul',
        'category' => 'zdrowie',
        'priority' => 'średni',
        'status' => 'zakończone',
        'estimated_minutes' => 60,
        'tags' => []
    ],
    [
        'title' => 'tytul inny niz trzeci',
        'category' => 'Nauka',
        'priority' => 'niski',
        'status' => 'w trakcie',
        'estimated_minutes' => 90,
        'tags' => ['nie dom']
    ]
];

$errors = [];

$inTitle = '';
$inCategory = '';
$inPriority = '';
$inStatus = '';
$inEstimated = '';
$inTags = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inTitle = trim($_POST['title'] ?? '');
    $inCategory = $_POST['category'] ?? '';
    $inPriority = $_POST['priority'] ?? '';
    $inStatus = $_POST['status'] ?? '';
    $inEstimated = trim($_POST['estimated_minutes'] ?? '');
    $inTags = $_POST['tags'] ?? []; 
    
    if (empty($inTitle)) {
        $errors[] = 'Tytuł nie może być pusty.';
    }

    if (!is_numeric($inEstimated) || $inEstimated <= 0) {
        $errors[] = 'Szacowany czas musi być liczbą dodatnią.';
    }
    
    if (!in_array($inCategory, ['Praca', 'Dom', 'Nauka', 'Zdrowie', 'Inne'])) {
        $errors[] = 'Nieprawidłowa kategoria.';
    }
    
    if (!in_array($inPriority, ['niski', 'średni', 'wysoki'])) {
        $errors[] = 'Nieprawidłowy priorytet.';
    }
    
    if (!in_array($inStatus, ['do zrobienia', 'w trakcie', 'zakończone'])) {
        $errors[] = 'Nieprawidłowy status.';
    }

    if (empty($inTags)) {
        $errors[] = 'Musi zostać wybrany co najmniej jeden tag.';
    }
    
    if (empty($errors)) {
        $cleanTags = array_filter($inTags);
        sort($cleanTags);
        
        $newTask = [
            'title' => $inTitle,
            'category' => $inCategory,
            'priority' => $inPriority,
            'status' => $inStatus,
            'estimated_minutes' => (int)$inEstimated,
            'tags' => $cleanTags
        ];
        
        $tasks[] = $newTask;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="tsk_mngr.css">
</head>

<body>

    <!--header-->
    <header class="top-header">
        <h1 id="jakies">Task Manager</h1>
        <nav class="top-nav">
            <a href="#" class="nav-link active">Wszystkie</a>
            <a href="#" class="nav-link">Do zrobienia</a>
            <a href="#" class="nav-link">W trakcie</a>
            <a href="#" class="nav-link">Zakończone</a>
        </nav>
    </header>

    <!--sidebar-->
    <aside class="sidebar">

        <?php if (!empty($errors)): ?>
            <div style="background-color: #ffcccc; color: #cc0000; padding: 10px; margin: 10px 5px; border-radius: 4px; border: 1px solid #cc0000;">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="taskName">Tytuł zadania</label>
            <input type="text" id="taskName" name="title" value="<?= htmlspecialchars($inTitle) ?>" required>

            <label for="taskCategory">Kategoria</label>
            <select id="taskCategory" name="category" required>
                <option value="Praca" <?= $inCategory === 'Praca' ? 'selected' : '' ?>>Praca</option>
                <option value="Dom" <?= $inCategory === 'Dom' ? 'selected' : '' ?>>Dom</option>
                <option value="Nauka" <?= $inCategory === 'Nauka' ? 'selected' : '' ?>>Nauka</option>
                <option value="Zdrowie" <?= $inCategory === 'Zdrowie' ? 'selected' : '' ?>>Zdrowie</option>
                <option value="Inne" <?= $inCategory === 'Inne' ? 'selected' : '' ?>>Inne</option>
            </select>

            <label for="taskPriority">Priorytet</label>
            <select id="taskPriority" name="priority" required>
                <option value="niski" <?= $inPriority === 'niski' ? 'selected' : '' ?>>Niski</option>
                <option value="średni" <?= $inPriority === 'średni' ? 'selected' : '' ?>>Średni</option>
                <option value="wysoki" <?= $inPriority === 'wysoki' ? 'selected' : '' ?>>Wysoki</option>
            </select>

            <label for="taskStatus">Status</label>
            <select id="taskStatus" name="status" required>
                <option value="do zrobienia" <?= $inStatus === 'do zrobienia' ? 'selected' : '' ?>>Do zrobienia</option>
                <option value="w trakcie" <?= $inStatus === 'w trakcie' ? 'selected' : '' ?>>W trakcie</option>
                <option value="zakończone" <?= $inStatus === 'zakończone' ? 'selected' : '' ?>>Zakończone</option>
            </select>

            <label for="taskTime">Szacowany czas (w minutach)</label>
            <input type="text" id="taskTime" name="estimated_minutes" value="<?= htmlspecialchars($inEstimated) ?>" required>

            <label>Tagi zadania</label>
            <div style="display:flex; flex-direction: column; gap: 5px; margin-bottom: 10px;">
                <label style="margin-top:0"><input type="checkbox" name="tags[]" value="pilne" <?= in_array('pilne', $inTags) ? 'checked' : '' ?>> pilne</label>
                <label style="margin-top:0"><input type="checkbox" name="tags[]" value="nie pilne" <?= in_array('nie pilne', $inTags) ? 'checked' : '' ?>> nie pilne</label>
                <label style="margin-top:0"><input type="checkbox" name="tags[]" value="trzy" <?= in_array('trzy', $inTags) ? 'checked' : '' ?>> trzy</label>
                <label style="margin-top:0"><input type="checkbox" name="tags[]" value="nie trzy" <?= in_array('nie trzy', $inTags) ? 'checked' : '' ?>> nie trzy</label>
                <label style="margin-top:0"><input type="checkbox" name="tags[]" value="dom" <?= in_array('dom', $inTags) ? 'checked' : '' ?>> dom</label>
                <label style="margin-top:0"><input type="checkbox" name="tags[]" value="nie dom" <?= in_array('nie dom', $inTags) ? 'checked' : '' ?>> nie dom</label>
            </div>

            <button type="submit">Dodaj zadanie</button>
        </form>



    </aside>

    <!--main-->
    <main class="main-shii">

        <?php

            $totalTasks = count($tasks);

            $todoCount = 0;
            $doneCount = 0;
            foreach ($tasks as $task) {
                if ($task['status'] === 'do zrobienia') {
                    $todoCount++;
                } elseif ($task['status'] === 'zakończone') {
                    $doneCount++;
                }
            }

            $totalMinutes = array_sum(array_column($tasks, 'estimated_minutes'));
        ?>
        <div class="stats-bar">
            <span class="stat-box">Wszystkie: <?= $totalTasks ?></span>
            <span class="stat-box">Do zrobienia: <?= $todoCount ?></span>
            <span class="stat-box">Zakończone: <?= $doneCount ?></span>
            <span class="stat-box">Całkowity czas: <?= $totalMinutes ?> min</span>
        </div>

        <div class="sorting-bar">
            <span>Sortuj:</span>
            <button type="button" class="sort-btn active">Tytuł &uarr;</button>
            <button type="button" class="sort-btn">Priorytet</button>
            <button type="button" class="sort-btn">Data</button>
            <button type="button" class="sort-btn">Kategoria</button>
            <button type="button" class="sort-btn">Status</button>
        </div>

        <div class="task-list">
            <?php foreach ($tasks as $task): ?>
                <?php
                    $title = htmlspecialchars($task['title']);
                    $category = htmlspecialchars($task['category']);
                    $priority = htmlspecialchars($task['priority']);
                    $status = htmlspecialchars($task['status']);
                    $time = htmlspecialchars((string)$task['estimated_minutes']);
                    $tagsArrayForHtml = [];
                    foreach ($task['tags'] as $t) {
                        $tagsArrayForHtml[] = htmlspecialchars($t);
                    }
                    $tagsString = empty($tagsArrayForHtml) ? 'Brak tagów' : implode(', ', $tagsArrayForHtml);

                    $bgClass = 'bg-low';
                    if ($task['priority'] === 'wysoki') $bgClass = 'bg-high';
                    if ($task['priority'] === 'średni') $bgClass = 'bg-medium';

                    $statusClass = 'status-pending';
                    if ($task['status'] === 'w trakcie') $statusClass = 'status-working';
                    if ($task['status'] === 'zakończone') $statusClass = 'status-done';
                ?>
                
                <div class="task-card <?= $bgClass ?>">
                    <div class="card-header">
                        <h3><?= $title ?></h3>
                        <span class="priority-badge">Priorytet: <?= $priority ?></span>
                    </div>
                    
                    <div class="card-desc">
                        <p><strong>Kategoria:</strong> <?= $category ?></p>
                        <p><strong>Szacowany czas:</strong> <?= $time ?> min</p>
                        <p><strong>Tagi:</strong> <?= $tagsString ?></p>
                    </div>

                    <div class="card-footer">
                        <span class="status-badge <?= $statusClass ?>"><?= $status ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        </div>
    </main>

</body>

</html>
