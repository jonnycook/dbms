<?php

class DatabaseEngine {
	public function dependencies($model, $prop) { return array(); }
	public function resolveDep($dep, $config, $id) { return array(); }
	public function singleInsert() { return false; }

}
