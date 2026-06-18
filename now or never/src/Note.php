<?php
declare(strict_types=1);

class Note {
    private ?int $id = null;
    private string $title = '';
    private string $content = '';
    private ?string $tag = null;
    private int $priority = 1;
    private string $createdAt = '';

    public function __construct(array $data = []) {
        if (isset($data['id'])) {
            $this->id = (int)$data['id'];
        }
        if (isset($data['title'])) {
            $this->title = trim($data['title']);
        }
        if (isset($data['content'])) {
            $this->content = trim($data['content']);
        }
        if (isset($data['tag'])) {
            $this->tag = trim($data['tag']) !== '' ? trim($data['tag']) : null;
        }
        if (isset($data['priority'])) {
            $this->priority = (int)$data['priority'];
        }
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getContent(): string {
        return $this->content;
    }

    public function getTag(): ?string {
        return $this->tag;
    }

    public function getPriority(): int {
        return $this->priority;
    }

    public function getCreatedAt(): string {
        return $this->createdAt;
    }

    public function validate(): array {
        $errors = [];

        if (empty($this->title)) {
            $errors['title'] = 'Tytuł jest wymagany.';
        } elseif (mb_strlen($this->title) < 3 || mb_strlen($this->title) > 100) {
            $errors['title'] = 'Tytuł musi mieć od 3 do 100 znaków.';
        }

        if (empty($this->content)) {
            $errors['content'] = 'Treść notatki jest wymagana.';
        } elseif (mb_strlen($this->content) < 5) {
            $errors['content'] = 'Treść notatki musi mieć co najmniej 5 znaków.';
        }

        if ($this->tag !== null && mb_strlen($this->tag) > 30) {
            $errors['tag'] = 'Tag może mieć maksymalnie 30 znaków.';
        }

        if (!in_array($this->priority, [1, 2, 3], true)) {
            $errors['priority'] = 'Nieprawidłowy priorytet.';
        }

        return $errors;
    }
}
