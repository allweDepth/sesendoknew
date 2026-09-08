-- Multi-item contracts allocate value through kontrak_item_neo.
-- Legacy header-based guards incorrectly compare the full contract value
-- with the first/representative DPA row.
DROP TRIGGER IF EXISTS trg_dpa_protect_contract_update;
DROP TRIGGER IF EXISTS trg_dpa_protect_contract_delete;
DROP TRIGGER IF EXISTS trg_dppa_protect_contract_update;
DROP TRIGGER IF EXISTS trg_dppa_protect_contract_delete;
