<?php

class WallchatModel
{
    private $db;
    private MessageCryptoService $crypto;

    public function __construct()
    {
        $this->db = DB::getInstance();
        require_once __DIR__ . '/../Services/MessageCryptoService.php';
        $this->crypto = new MessageCryptoService();
    }

    /* ==========================================
       GET FEEDS (POST UTAMA)
    ========================================== */
    public function getFeeds(int $limit = 30): array
    {
        $limit = max(1, min($limit, 50));
        $feeds = $this->db->query(
            "SELECT w.*, u.username AS nama
             FROM wallchat w
             JOIN user_sesendok_biila u ON u.id = w.user_id
             WHERE w.parent_id IS NULL
               AND w.type = 'status'
               AND w.is_deleted = 0
             ORDER BY w.created_at DESC
             LIMIT {$limit}"
        )->fetchAll();

        if (!$feeds) {
            return [];
        }

        $postIds = array_map(static fn(array $feed): int => (int)$feed['id'], $feeds);
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $comments = $this->db->query(
            "SELECT w.*, u.username AS nama
             FROM wallchat w
             JOIN user_sesendok_biila u ON u.id = w.user_id
             WHERE w.parent_id IN ({$placeholders})
               AND w.type = 'comment'
               AND w.is_deleted = 0
             ORDER BY w.created_at ASC",
            $postIds
        )->fetchAll();

        $commentsByPost = [];
        foreach ($comments as $comment) {
            $comment['content'] = $this->crypto->decrypt(
                $comment['content_ciphertext'] ?? null,
                $comment['content_nonce'] ?? null,
                $comment['content'] ?? ''
            );
            $commentsByPost[(int)$comment['parent_id']][] = $comment;
        }

        foreach ($feeds as &$feed) {
            $feed['content'] = $this->crypto->decrypt(
                $feed['content_ciphertext'] ?? null,
                $feed['content_nonce'] ?? null,
                $feed['content'] ?? ''
            );
            $feed['comments'] = $commentsByPost[(int)$feed['id']] ?? [];
        }
        unset($feed);

        return $feeds;
    }

    /* ==========================================
       GET COMMENTS BY POST
    ========================================== */
    public function getComments($parent_id)
    {
        $rows=$this->db
            ->table('wallchat w')
            ->selectQB('w.*, u.username AS nama')
            ->join('user_sesendok_biila u', 'u.id = w.user_id')
            ->where('w.parent_id = ?', [$parent_id])
            ->where("w.type = 'comment'")
            ->where('w.is_deleted = 0')
            ->orderBy('w.created_at ASC')
            ->qbGet();
        foreach($rows as &$row)$row['content']=$this->crypto->decrypt($row['content_ciphertext']??null,$row['content_nonce']??null,$row['content']??'');
        return $rows;
    }

    /* ==========================================
       INSERT POST / COMMENT
    ========================================== */
public function store($data)
{
    $encrypted = $this->crypto->encrypt((string)$data['content']);
    $theme = (string)($data['theme'] ?? 'default');
    if (!in_array($theme, ['default', 'ocean', 'sunset', 'forest', 'midnight'], true)) {
        $theme = 'default';
    }
    return $this->db->insert('wallchat', [
        'user_id'    => $data['user_id'],
        'parent_id'  => $data['parent_id'] ?? null,
        'receiver_id'=> $data['receiver_id'] ?? null,
        'type'       => $data['type'] ?? 'status',
        // New records never keep readable message text in the database.
        'content'    => '',
        'content_ciphertext' => $encrypted['ciphertext'],
        'content_nonce' => $encrypted['nonce'],
        'is_ephemeral' => !empty($data['is_ephemeral']) ? 1 : 0,
        'attachment_name' => $data['attachment_name'] ?? null,
        'attachment_path' => $data['attachment_path'] ?? null,
        'attachment_mime' => $data['attachment_mime'] ?? null,
        'attachment_size' => (int)($data['attachment_size'] ?? 0),
        'theme' => $theme,
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
        $encrypted = $this->crypto->encrypt((string)$content);
        return $this->db->update(
            'wallchat',
            [
                'content'    => '',
                'content_ciphertext' => $encrypted['ciphertext'],
                'content_nonce' => $encrypted['nonce'],
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'WHERE id = ?',
            [$id]
        );
    }

    public function updateOwned(int $id,int $userId,string $content,?string $theme=null): bool
    {
        $row=$this->db->query("SELECT id FROM wallchat WHERE id=? AND user_id=? AND is_deleted=0 AND type IN ('status','comment')",[$id,$userId])->fetch();if(!$row)return false;$encrypted=$this->crypto->encrypt($content);$data=['content'=>'','content_ciphertext'=>$encrypted['ciphertext'],'content_nonce'=>$encrypted['nonce'],'updated_at'=>date('Y-m-d H:i:s')];if($theme!==null&&in_array($theme,['default','ocean','sunset','forest','midnight'],true))$data['theme']=$theme;$this->db->update('wallchat',$data,'WHERE id=?',[$id]);return true;
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
        $rows=$this->db->query("SELECT w.*,s.username pengirim,r.username penerima FROM wallchat w JOIN user_sesendok_biila s ON s.id=w.user_id JOIN user_sesendok_biila r ON r.id=w.receiver_id WHERE w.type='private' AND w.is_deleted=0 AND ((w.user_id=? AND w.deleted_by_sender=0) OR (w.receiver_id=? AND w.deleted_by_receiver=0)) ORDER BY w.created_at DESC LIMIT 50",[$userId,$userId])->fetchAll();
        foreach($rows as &$row)$row['content']=$this->crypto->decrypt($row['content_ciphertext']??null,$row['content_nonce']??null,$row['content']??'');
        return $rows;
    }

    public function markRead(int $id, int $userId): bool
    {
        $row=$this->db->query("SELECT id,is_ephemeral FROM wallchat WHERE id=? AND receiver_id=? AND type='private' AND is_deleted=0",[$id,$userId])->fetch();
        if(!$row)return false;
        $this->db->update('wallchat',['read_at'=>date('Y-m-d H:i:s')],'WHERE id=?',[$id]);
        // Ephemeral messages disappear for the recipient after the first read;
        // the encrypted audit row remains until both parties delete it.
        if((int)$row['is_ephemeral']===1)$this->db->update('wallchat',['deleted_by_receiver'=>1],'WHERE id=?',[$id]);
        return true;
    }

    public function deletePrivate(int $id,int $userId): bool
    {
        $row=$this->db->query("SELECT user_id,receiver_id FROM wallchat WHERE id=? AND type='private' AND is_deleted=0",[$id])->fetch();
        if(!$row)return false;
        if((int)$row['user_id']===$userId)$this->db->update('wallchat',['deleted_by_sender'=>1],'WHERE id=?',[$id]);
        elseif((int)$row['receiver_id']===$userId)$this->db->update('wallchat',['deleted_by_receiver'=>1],'WHERE id=?',[$id]);
        else return false;
        $this->db->query('UPDATE wallchat SET is_deleted=1,content_ciphertext=NULL,content_nonce=NULL,content=\'\' WHERE id=? AND deleted_by_sender=1 AND deleted_by_receiver=1',[$id]);
        return true;
    }

    public function privateFile(int $id,int $userId): ?array
    {
        $row=$this->db->query("SELECT * FROM wallchat WHERE id=? AND type='private' AND is_deleted=0 AND (user_id=? OR receiver_id=?)",[$id,$userId,$userId])->fetch();
        return $row?:null;
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
