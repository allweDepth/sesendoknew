<?php

final class RequestGuard
{
  // List write operations explicitly. Page names such as uploadPage or
  // importPage do not establish whether a route changes application data.
  private const POST_ACTIONS = [
    'AuthController' => ['login', 'logout', 'register'],
    'MapsController' => ['saveGeometry', 'saveStyle', 'upload', 'delete'],
    'DynamicController' => ['import'],
    'KontrakController' => ['saveRealization', 'deleteRealizationDocument', 'saveItems',
      'saveRab', 'saveSchedule', 'importRab', 'uploadDocument', 'deleteDocument',
      'procurementSave', 'procurementDelete'],
    'ReferensiController' => ['store', 'update', 'delete'],
    'RenstraController' => ['storeMisi', 'storeTujuan', 'storeSasaran', 'storeIndikator',
      'storeProgram', 'storeAnggaran', 'update', 'delete', 'importExcel'],
    'KepegawaianController' => ['structureSave', 'structureDelete'],
    'StandarHargaController' => ['copyYear', 'importSipd'],
    'PengaturanController' => ['savePageSetup', 'uploadIdentityImage', 'savePaguLimit'],
    'ScopeController' => ['select'],
    'UserOpdController' => ['save', 'delete'],
    'WallchatController' => ['store', 'update', 'comment', 'privateMessage',
      'registerMessageKey', 'readPrivate', 'deletePrivate', 'delete'],
    'ProfilController' => ['save', 'selectPeriod', 'uploadPhoto'],
    'TataNaskahController' => ['generateNomor', 'simpan', 'updateStatus', 'uploadSignature'],
    'KopSuratController' => ['save'],
    'ResetTabelController' => ['reset', 'restore'],
    'AnggaranController' => ['tapdSave', 'approval'],
  ];

  public static function requiresPost(?array $route): bool
  {
    if (!$route) return false;
    return in_array($route[1], self::POST_ACTIONS[$route[0]] ?? [], true);
  }
}
