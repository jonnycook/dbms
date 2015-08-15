<?php

class DatabaseEngine {
	public function dependencies($model, $prop) { return []; }
	public function resolveDep($dep, $config, $id) { return []; }
	public function singleInsert() { return false; }

}
