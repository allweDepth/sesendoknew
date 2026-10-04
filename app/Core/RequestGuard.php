<?php

final class RequestGuard
{
  public static function requiresPost(?array $route): bool
  {
    if (!$route) return false;
    $method = $route[1];
    return preg_match('/^(save|store|update|delete|upload|import|reset|restore|copy)/i', $method) === 1
      || in_array($method, ['login', 'logout', 'register', 'select', 'selectPeriod', 'privateMessage',
        'comment', 'registerMessageKey', 'readPrivate', 'setApproval', 'approval',
        'tapdSave', 'structureSave', 'structureDelete', 'procurementDraft',
        'procurementSave', 'procurementDelete', 'simpan', 'generateNomor'], true);
  }
}
