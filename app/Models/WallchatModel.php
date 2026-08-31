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
    $feeds = $this->db
        ->table('wallchat w')
        ->selectQB('w.*, u.username AS nama')
        ->join('user_sesendok_biila u', 'u.id = w.user_id')
        ->where('w.parent_id IS NULL')
        ->where("w.type = 'status'")
        ->where('w.is_deleted = 0')
        ->orderBy('w.created_at DESC')
        ->qbGet();
    foreach($feeds as &$feed)$feed['comments']=$this->getComments((int)$feed['id']);
    return $feeds;
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
            ->where("w.type = 'comment'")
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
        'receiver_id'=> $data['receiver_id'] ?? null,
        'type'       => $data['type'] ?? 'status',
        'content'    => $data['content'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => null,
        'is_deleted' => 0,
        'username_insert' => $data['username']
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
    public function delete($id, int $userId)
    {
        return $this->db->update(
            'wallchat',
            ['is_deleted' => 1],
            'WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function getPrivateMessages(int $userId): array
    {
        return $this->db->query("SELECT w.*,s.username pengirim,r.username penerima FROM wallchat w JOIN user_sesendok_biila s ON s.id=w.user_id JOIN user_sesendok_biila r ON r.id=w.receiver_id WHERE w.type='private' AND w.is_deleted=0 AND (w.user_id=? OR w.receiver_id=?) ORDER BY w.created_at DESC LIMIT 50",[$userId,$userId])->fetchAll();
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
