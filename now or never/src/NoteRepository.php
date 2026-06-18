<?php
declare(strict_types=1);

require_once __DIR__ . '/Note.php';

class NoteRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getById(int $id): ?Note {
        $stmt = $this->db->prepare("SELECT * FROM notes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ? new Note($data) : null;
    }

    public function getAll(
        string $search = '',
        ?string $filterTag = null,
        ?int $filterPriority = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'DESC'
    ): array {
        $sql = "SELECT * FROM notes WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (title LIKE :search OR content LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($filterTag !== null && $filterTag !== '') {
            $sql .= " AND tag = :tag";
            $params['tag'] = $filterTag;
        }

        if ($filterPriority !== null) {
            $sql .= " AND priority = :priority";
            $params['priority'] = $filterPriority;
        }

        $allowedSortColumns = ['title', 'priority', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY {$sortBy} {$sortOrder}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $notes = [];
        while ($row = $stmt->fetch()) {
            $notes[] = new Note($row);
        }
        return $notes;
    }

    public function getUniqueTags(): array {
        $stmt = $this->db->query("SELECT DISTINCT tag FROM notes WHERE tag IS NOT NULL AND tag != '' ORDER BY tag ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function save(Note $note): bool {
        $stmt = $this->db->prepare("INSERT INTO notes (title, content, tag, priority, created_at) VALUES (:title, :content, :tag, :priority, :created_at)");
        return $stmt->execute([
            'title' => $note->getTitle(),
            'content' => $note->getContent(),
            'tag' => $note->getTag(),
            'priority' => $note->getPriority(),
            'created_at' => $note->getCreatedAt()
        ]);
    }

    public function update(Note $note): bool {
        $stmt = $this->db->prepare("UPDATE notes SET title = :title, content = :content, tag = :tag, priority = :priority WHERE id = :id");
        return $stmt->execute([
            'id' => $note->getId(),
            'title' => $note->getTitle(),
            'content' => $note->getContent(),
            'tag' => $note->getTag(),
            'priority' => $note->getPriority()
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM notes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
