<?php
declare(strict_types=1);

// Helpers for view elements
function escape(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function getSortIndicator(string $column, string $currentSortBy, string $currentSortOrder): string {
    if ($currentSortBy !== $column) {
        return '↕';
    }
    return $currentSortOrder === 'ASC' ? '▲' : '▼';
}

function getSortUrl(string $column, string $currentSortBy, string $currentSortOrder, string $search, ?string $tag, ?int $priority): string {
    $nextOrder = ($currentSortBy === $column && $currentSortOrder === 'ASC') ? 'desc' : 'asc';
    $params = ['sort' => $column, 'order' => $nextOrder];
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($tag !== null && $tag !== '') {
        $params['tag'] = $tag;
    }
    if ($priority !== null) {
        $params['priority'] = (string)$priority;
    }
    return '?' . http_build_query($params);
}
?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?= escape($success) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors) && isset($errors['general'])): ?>
    <div class="alert alert-danger">
        <?= escape($errors['general']) ?>
    </div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- LEFT PANEL: LIST OF NOTES -->
    <div class="glass-panel">
        <h2 style="margin-bottom: 1.5rem;">Lista notatek</h2>

        <!-- Search and Filter Form -->
        <form method="GET" action="" class="filters-bar">
            <!-- Persist sort settings -->
            <input type="hidden" name="sort" value="<?= escape($sortBy) ?>">
            <input type="hidden" name="order" value="<?= escape($sortOrder) ?>">

            <div class="filter-item">
                <label for="search">Szukaj (tytuł/treść)</label>
                <input type="text" id="search" name="search" value="<?= escape($search) ?>" placeholder="Wpisz frazę...">
            </div>

            <div class="filter-item">
                <label for="tag">Filtruj wg tagu</label>
                <select id="tag" name="tag">
                    <option value="">-- Wszystkie --</option>
                    <?php foreach ($tags as $t): ?>
                        <option value="<?= escape($t) ?>" <?= $filterTag === $t ? 'selected' : '' ?>>
                            <?= escape($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-item">
                <label for="priority">Filtruj wg priorytetu</label>
                <select id="priority" name="priority">
                    <option value="">-- Wszystkie --</option>
                    <option value="1" <?= $filterPriority === 1 ? 'selected' : '' ?>>Niski</option>
                    <option value="2" <?= $filterPriority === 2 ? 'selected' : '' ?>>Średni</option>
                    <option value="3" <?= $filterPriority === 3 ? 'selected' : '' ?>>Wysoki</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filtruj</button>
                <a href="index.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Notes Table -->
        <div class="table-container">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <p>Brak notatek spełniających wybrane kryteria.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>
                                <a href="<?= getSortUrl('title', $sortBy, $sortOrder, $search, $filterTag, $filterPriority) ?>">
                                    Tytuł <?= getSortIndicator('title', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th>Treść</th>
                            <th>Tag</th>
                            <th>
                                <a href="<?= getSortUrl('priority', $sortBy, $sortOrder, $search, $filterTag, $filterPriority) ?>">
                                    Priorytet <?= getSortIndicator('priority', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= getSortUrl('created_at', $sortBy, $sortOrder, $search, $filterTag, $filterPriority) ?>">
                                    Data utworzenia <?= getSortIndicator('created_at', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th style="text-align: right;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notes as $note): ?>
                            <tr>
                                <td style="font-weight: 600;"><?= escape($note->getTitle()) ?></td>
                                <td>
                                    <div class="note-content-preview" title="<?= escape($note->getContent()) ?>">
                                        <?= escape($note->getContent()) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($note->getTag()): ?>
                                        <span class="tag-label"><?= escape($note->getTag()) ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($note->getPriority() === 1): ?>
                                        <span class="badge badge-low">Niski</span>
                                    <?php elseif ($note->getPriority() === 2): ?>
                                        <span class="badge badge-medium">Średni</span>
                                    <?php else: ?>
                                        <span class="badge badge-high">Wysoki</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= escape(date('d.m.Y H:i', strtotime($note->getCreatedAt()))) ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="actions-cell" style="justify-content: flex-end;">
                                        <a href="?action=edit&id=<?= $note->getId() ?>&sort=<?= escape($sortBy) ?>&order=<?= escape($sortOrder) ?>&search=<?= escape($search) ?>&tag=<?= escape($filterTag) ?>&priority=<?= escape((string)$filterPriority) ?>" class="btn btn-secondary btn-sm">Edytuj</a>
                                        <form method="POST" action="?action=delete" onsubmit="return confirm('Czy na pewno chcesz usunąć tę notatkę?');" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $note->getId() ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Usuń</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT PANEL: CREATE/EDIT FORM -->
    <div class="glass-panel <?= $editNote !== null ? 'edit-highlight' : '' ?>">
        <?php if ($editNote !== null): ?>
            <div class="edit-title">
                <h2>Edycja notatki</h2>
                <a href="index.php?sort=<?= escape($sortBy) ?>&order=<?= escape($sortOrder) ?>&search=<?= escape($search) ?>&tag=<?= escape($filterTag) ?>&priority=<?= escape((string)$filterPriority) ?>" class="btn-close" title="Anuluj edycję">&times;</a>
            </div>
            <form method="POST" action="?action=update">
                <input type="hidden" name="id" value="<?= $editNote->getId() ?>">
        <?php else: ?>
            <h2 style="margin-bottom: 1.5rem;">Dodaj nową notatkę</h2>
            <form method="POST" action="?action=create">
        <?php endif; ?>

            <!-- Persist current view settings across form submissions -->
            <input type="hidden" name="sort" value="<?= escape($sortBy) ?>">
            <input type="hidden" name="order" value="<?= escape($sortOrder) ?>">
            <input type="hidden" name="search" value="<?= escape($search) ?>">
            <input type="hidden" name="tag_filter" value="<?= escape($filterTag) ?>">
            <input type="hidden" name="priority_filter" value="<?= escape((string)$filterPriority) ?>">

            <div class="form-group">
                <label for="title">Tytuł notatki <span style="color: var(--priority-high)">*</span></label>
                <input type="text" id="title" name="title" value="<?= escape($oldInput['title'] ?? ($editNote ? $editNote->getTitle() : '')) ?>">
                <?php if (isset($errors['title'])): ?>
                    <span class="error-message"><?= escape($errors['title']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="content">Treść notatki <span style="color: var(--priority-high)">*</span></label>
                <textarea id="content" name="content"><?= escape($oldInput['content'] ?? ($editNote ? $editNote->getContent() : '')) ?></textarea>
                <?php if (isset($errors['content'])): ?>
                    <span class="error-message"><?= escape($errors['content']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="note_tag">Tag (opcjonalnie)</label>
                <input type="text" id="note_tag" name="tag" value="<?= escape($oldInput['tag'] ?? ($editNote ? $editNote->getTag() : '')) ?>" placeholder="np. praca, zakupy, osobiste">
                <?php if (isset($errors['tag'])): ?>
                    <span class="error-message"><?= escape($errors['tag']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="note_priority">Priorytet <span style="color: var(--priority-high)">*</span></label>
                <?php 
                $selectedPriority = (int)($oldInput['priority'] ?? ($editNote ? $editNote->getPriority() : 1));
                ?>
                <select id="note_priority" name="priority">
                    <option value="1" <?= $selectedPriority === 1 ? 'selected' : '' ?>>Niski</option>
                    <option value="2" <?= $selectedPriority === 2 ? 'selected' : '' ?>>Średni</option>
                    <option value="3" <?= $selectedPriority === 3 ? 'selected' : '' ?>>Wysoki</option>
                </select>
                <?php if (isset($errors['priority'])): ?>
                    <span class="error-message"><?= escape($errors['priority']) ?></span>
                <?php endif; ?>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <?= $editNote !== null ? 'Zapisz zmiany' : 'Dodaj notatkę' ?>
                </button>
                <?php if ($editNote !== null): ?>
                    <a href="index.php?sort=<?= escape($sortBy) ?>&order=<?= escape($sortOrder) ?>&search=<?= escape($search) ?>&tag=<?= escape($filterTag) ?>&priority=<?= escape((string)$filterPriority) ?>" class="btn btn-secondary">Anuluj</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
