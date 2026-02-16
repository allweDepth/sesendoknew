<?php

class WallchatModel
{
    private $db;

    public function __construct()
    {
        $this->db = DB::getInstance();
    }

    /* ==========================================
       GET FEEDS (POST UTAMA)
    ========================================== */
    public function getFeeds()
{
    return $this->db
        ->table('wallchat w')
        ->selectQB('w.*, u.username AS nama')
        ->join('user_sesendok_biila u', 'u.id = w.user_id')
        ->where('w.parent_id IS NULL')
        ->where('w.is_deleted = 0')
        ->orderBy('w.created_at DESC')
        ->qbGet();
}

    /* ==========================================
       GET COMMENTS BY POST
    ========================================== */
    public function getComments($parent_id)
    {
        return $this->db
            ->table('wallchat w')
            ->selectQB('w.*, u.username AS nama')
            ->join('user_sesendok_biila u', 'u.id = w.user_id')
            ->where('w.parent_id = ?', [$parent_id])
            ->where('w.is_deleted = 0')
            ->orderBy('w.created_at ASC')
            ->qbGet();
    }

    /* ==========================================
       INSERT POST / COMMENT
    ========================================== */
    public function store($data)
{
    return $this->db->insert('wallchat', [
        'user_id'    => $data['user_id'],
        'parent_id'  => $data['parent_id'] ?? null,
        'type'       => $data['type'] ?? 'status',
        'content'    => $data['content'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => null,
        'is_deleted' => 0
    ]);
}

    /* ==========================================
       UPDATE POST / COMMENT
    ========================================== */
    public function update($id, $content)
    {
        return $this->db->update(
            'wallchat',
            [
                'content'    => $content,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'WHERE id = ?',
            [$id]
        );
    }

    /* ==========================================
       SOFT DELETE
    ========================================== */
    public function delete($id)
    {
        return $this->db->update(
            'wallchat',
            ['is_deleted' => 1],
            'WHERE id = ?',
            [$id]
        );
    }

    /* ==========================================
       FIND SINGLE POST
    ========================================== */
    public function find($id)
    {
        return $this->db
            ->table('wallchat')
            ->where('id = ?', [$id])
            ->where('is_deleted = 0')
            ->qbFirst();
    }
}