<?php
$tasks = [
    [
        'title' => 'tytul jacek',
        'category' => 'praca',
        'priority' => 'wysoki',
        'status' => 'do zrobienia',
        'estimated_minutes' => 180,
        'tags' => ['nie_trzy', 'pilne'],
        'description' => "Trzeba napisać maila do jacek@example.com.\nZajrzyj na stronę http://google.com.\n\n- zrób to\n- zrób tamto\n\n#pilne",
        'end_date' => '2026-05-15'
    ],
    [
        'title' => 'tytul placek',
        'category' => 'dom',
        'priority' => 'średni',
        'status' => 'do zrobienia',
        'estimated_minutes' => 45,
        'tags' => ['pilne', 'trrzy'],
        'description' => 'Posprzątać pokój.',
        'end_date' => '2026-05-20'
    ],
    [
        'title' => 'Tytul',
        'category' => 'zdrowie',
        'priority' => 'średni',
        'status' => 'zakończone',
        'estimated_minutes' => 60,
        'tags' => [],
        'description' => 'Wizyta u lekarza.',
        'end_date' => '2026-05-10'
    ],
    [
        'title' => 'tytul inny niz trzeci',
        'category' => 'Nauka',
        'priority' => 'niski',
        'status' => 'w trakcie',
        'estimated_minutes' => 90,
        'tags' => ['nie_dom'],
        'description' => 'Pouczyć się PHP. Warto przejrzeć manual.',
        'end_date' => '2026-06-01'
    ]
];

$errors = [];

/**
 * Funkcja do walidacji i czyszczenia danych wejściowych (zapobieganie XSS)
 */
function validateInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Wyodrębnianie tagów z tekstu (słowa zaczynające się od #)
 */
function extractTags($text) {
    preg_match_all('/#([a-zA-Z0-9_]+)/', $text, $matches);
    return $matches[1] ?? [];
}

/**
 * Formatowanie opisu zadania: URL -> linki, #tagi -> span, listy -> ul/li, daty -> wyróżnienie
 */
function formatTaskDescription($description) {
    // Ochrona przed XSS (bezpieczne wyświetlanie)
    $description = htmlspecialchars($description, ENT_NOQUOTES, 'UTF-8');

    // 1. Zamiana URL na klikalne linki
    $description = preg_replace(
        '/\b(?:https?|ftp):\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|]/i',
        '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>',
        $description
    );
    
    // 2. Wykrywanie i formatowanie tagów (hasztagów)
    $description = preg_replace(
        '/#([a-zA-Z0-9_]+)/',
        '<span class="tag">#$1</span>',
        $description
    );
    
    // 3. Wykrywanie i formatowanie list punktowanych
    $description = preg_replace(
        '/^[\s]*[-*+][\s]+(.*)$/m',
        '<li>$1</li>',
        $description
    );
    $description = preg_replace(
        '/(?:<li>.*?<\/li>\s*)+/',
        "<ul>\n$0</ul>\n",
        $description
    );

    // 4. Wyróżnianie dat (np. 2026-05-15)
    $description = preg_replace(
        '/\b(\d{4}-\d{2}-\d{2})\b/',
        '<strong class="highlight-date">$1</strong>',
        $description
    );

    return nl2br($description);
}

/**
 * Wyszukiwanie zadań według wzorca regex (w tytule lub opisie)
 */
function searchTasks($tasks, $pattern) {
    if (empty($pattern)) return $tasks;
    
    // Ograniczniki dla regexu i ignorowanie wielkości liter.
    $regex = '@' . str_replace('@', '\@', $pattern) . '@i';
    
    $filtered = [];
    foreach ($tasks as $task) {
        $subject = $task['title'] . ' ' . ($task['description'] ?? '');
        // Używamy @ aby uniknąć błędów PHP, gdy użytkownik wpisze niepoprawny regex
        if (@preg_match($regex, $subject)) {
            $filtered[] = $task;
        }
    }
    return $filtered;
}

/**
 * Filtrowanie zadań po konkretnym tagu
 */
function filterTasksByTag($tasks, $tag) {
    if (empty($tag)) return $tasks;
    
    $filtered = [];
    foreach ($tasks as $task) {
        // Sprawdzamy czy tag występuje w tablicy (ignorując wielkość liter)
        $lowerTags = array_map('strtolower', $task['tags']);
        if (in_array(strtolower($tag), $lowerTags)) {
            $filtered[] = $task;
        }
    }
    return $filtered;
}

/**
 * Podświetlanie znalezionego tekstu
 */
function highlightSearchTerm($text, $pattern) {
    if (empty($pattern)) return $text;
    $regex = '@(' . str_replace('@', '\@', $pattern) . ')@i';
    // Tłumimy błędy w przypadku niepoprawnego regexu wpisanego przez użytkownika
    $highlighted = @preg_replace($regex, '<mark style="background-color: #fef08a; padding: 0 2px; border-radius: 2px;">$1</mark>', $text);
    return $highlighted !== null ? $highlighted : $text;
}

$inTitle = '';
$inCategory = '';
$inPriority = '';
$inStatus = '';
$inEstimated = '';
$inTags = '';
$inDescription = '';
$inEndDate = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inTitle = validateInput($_POST['title'] ?? '');
    $inCategory = validateInput($_POST['category'] ?? '');
    $inPriority = validateInput($_POST['priority'] ?? '');
    $inStatus = validateInput($_POST['status'] ?? '');
    $inEstimated = validateInput($_POST['estimated_minutes'] ?? '');
    $inTags = validateInput($_POST['tags'] ?? ''); 
    $inDescription = validateInput($_POST['description'] ?? '');
    $inEndDate = validateInput($_POST['end_date'] ?? ''); 
    
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

    // Walidacja daty (format RRRR-MM-DD) za pomocą wyrażenia regularnego
    if (!empty($inEndDate)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inEndDate)) {
            $errors[] = 'Data musi być w formacie RRRR-MM-DD.';
        } else {
            $parts = explode('-', $inEndDate);
            if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
                $errors[] = 'Podana data nie istnieje w kalendarzu.';
            }
        }
    }

    if (empty($inTags)) {
        $errors[] = 'Musi zostać wpisany co najmniej jeden tag.';
    } else {
        // Walidacja tagów (tylko litery, cyfry, podkreślniki, spacje do rozdzielenia)
        if (!preg_match('/^[a-zA-Z0-9_ ]+$/', $inTags)) {
            $errors[] = 'Tagi mogą zawierać tylko litery, cyfry i podkreślniki, bez znaków specjalnych.';
        }
    }

    // Wykrywanie i walidacja emaila w opisie
    if (!empty($inDescription)) {
        $emailRegex = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        if (preg_match_all($emailRegex, $inDescription, $matches)) {
            foreach ($matches[0] as $email) {
                // Sprawdzenie poprawności znalezionego emaila
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Znaleziony w opisie adres email ($email) jest niepoprawny.";
                }
            }
        }
    }
    
    if (empty($errors)) {
        $cleanTags = array_filter(explode(' ', $inTags));
        
        // Wyciąganie tagów z opisu i połączenie z wpisanymi ręcznie
        $extractedTags = extractTags($inDescription);
        $allTags = array_unique(array_merge($cleanTags, $extractedTags));
        sort($allTags);
        
        $newTask = [
            'title' => $inTitle,
            'category' => $inCategory,
            'priority' => $inPriority,
            'status' => $inStatus,
            'estimated_minutes' => (int)$inEstimated,
            'tags' => $allTags,
            'description' => $inDescription,
            'end_date' => $inEndDate
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

            <label for="taskEndDate">Data końcowa (RRRR-MM-DD)</label>
            <input type="text" id="taskEndDate" name="end_date" placeholder="np. 2026-05-15" value="<?= htmlspecialchars($inEndDate) ?>">

            <label for="taskDescription">Opis zadania</label>
            <textarea id="taskDescription" name="description" rows="4" style="resize:vertical;"><?= htmlspecialchars($inDescription) ?></textarea>

            <label for="taskTags">Tagi (oddzielone spacją)</label>
            <input type="text" id="taskTags" name="tags" placeholder="np. pilne praca trudne" value="<?= htmlspecialchars($inTags) ?>">

            <button type="submit">Dodaj zadanie</button>
        </form>



    </aside>

    <!--main-->
    <main class="main-shii">

        <?php
            // Filtrowanie z $_GET
            $searchRegex = $_GET['search_regex'] ?? '';
            $searchTag = $_GET['search_tag'] ?? '';
            $searchPriority = $_GET['search_priority'] ?? '';
            $searchStatus = $_GET['search_status'] ?? '';

            $filteredTasks = $tasks;

            if (!empty($searchRegex)) {
                $filteredTasks = searchTasks($filteredTasks, $searchRegex);
            }
            if (!empty($searchTag)) {
                $filteredTasks = filterTasksByTag($filteredTasks, $searchTag);
            }
            if (!empty($searchPriority)) {
                $filteredTasks = array_filter($filteredTasks, function($t) use ($searchPriority) {
                    return $t['priority'] === $searchPriority;
                });
            }
            if (!empty($searchStatus)) {
                $filteredTasks = array_filter($filteredTasks, function($t) use ($searchStatus) {
                    return $t['status'] === $searchStatus;
                });
            }

            $totalTasks = count($filteredTasks);

            $todoCount = 0;
            $doneCount = 0;
            foreach ($filteredTasks as $task) {
                if ($task['status'] === 'do zrobienia') {
                    $todoCount++;
                } elseif ($task['status'] === 'zakończone') {
                    $doneCount++;
                }
            }

            $totalMinutes = array_sum(array_column($filteredTasks, 'estimated_minutes'));
        ?>
        
        <div class="search-bar" style="background: #dedede; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #626262;">
            <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <div>
                    <label style="display:block; font-weight:bold; font-size:14px; margin-bottom:5px;">Wyszukaj (Regex)</label>
                    <input type="text" name="search_regex" value="<?= htmlspecialchars($searchRegex) ?>" placeholder="np. ^tytul" style="padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label style="display:block; font-weight:bold; font-size:14px; margin-bottom:5px;">Tag</label>
                    <input type="text" name="search_tag" value="<?= htmlspecialchars($searchTag) ?>" placeholder="np. pilne" style="padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label style="display:block; font-weight:bold; font-size:14px; margin-bottom:5px;">Priorytet</label>
                    <select name="search_priority" style="padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">Wszystkie</option>
                        <option value="niski" <?= $searchPriority === 'niski' ? 'selected' : '' ?>>Niski</option>
                        <option value="średni" <?= $searchPriority === 'średni' ? 'selected' : '' ?>>Średni</option>
                        <option value="wysoki" <?= $searchPriority === 'wysoki' ? 'selected' : '' ?>>Wysoki</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-weight:bold; font-size:14px; margin-bottom:5px;">Status</label>
                    <select name="search_status" style="padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">Wszystkie</option>
                        <option value="do zrobienia" <?= $searchStatus === 'do zrobienia' ? 'selected' : '' ?>>Do zrobienia</option>
                        <option value="w trakcie" <?= $searchStatus === 'w trakcie' ? 'selected' : '' ?>>W trakcie</option>
                        <option value="zakończone" <?= $searchStatus === 'zakończone' ? 'selected' : '' ?>>Zakończone</option>
                    </select>
                </div>
                <div>
                    <button type="submit" style="padding: 7px 15px; background: #3f2929; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">Szukaj / Filtruj</button>
                    <a href="index.php" style="display: inline-block; padding: 7px 15px; background: #626262; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 5px;">Wyczyść</a>
                </div>
            </form>
        </div>
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
            <?php foreach ($filteredTasks as $task): ?>
                <?php
                    $title = $task['title'];
                    // Wykorzystanie substr() do ewentualnego skracania długich tytułów (wymóg techniczny)
                    if (strlen($title) > 50) {
                        $title = substr($title, 0, 47) . '...';
                    }
                    $title = htmlspecialchars($title);
                    
                    if (!empty($searchRegex)) {
                        $title = highlightSearchTerm($title, $searchRegex);
                    }
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
                        <?php if (!empty($task['end_date'])): ?>
                            <p><strong>Data końcowa:</strong> <?= htmlspecialchars($task['end_date']) ?></p>
                        <?php endif; ?>
                        <p><strong>Tagi:</strong> <?= $tagsString ?></p>
                        
                        <?php if (!empty($task['description'])): ?>
                            <hr style="border: 0; border-top: 1px dashed #ccc; margin: 10px 0;">
                            <div class="task-description">
                                <?= formatTaskDescription($task['description']) ?>
                            </div>
                        <?php endif; ?>
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
