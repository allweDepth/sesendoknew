<?php
require_once __DIR__ . '/../Services/DynamicTableService.php';

class DynamicController
{
	public function index($params = null)
	{
		$service = new DynamicTableService($_POST);
		echo $service->handle($_POST);
	}
}


