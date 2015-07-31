<?php

require_once(__DIR__.'/DatabaseEngine.class.php');
define("APPROVED", 1);
define("DECLINED", 2);
define("ERROR", 3);

class gwapi {

// Initial Setting Functions

  function setLogin($username, $password) {
    $this->login['username'] = $username;
    $this->login['password'] = $password;
  }

  function execute($params) {
  	$params['username'] = $this->login['username'];
  	$params['password'] = $this->login['password'];
  	foreach ($params as $field => $value) {
  		$query[] = "$field=" . urlencode($value);
  	}
  	$query = implode('&', $query);
  	return $this->_doPost($query);
  }

  function _doPost($query) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://secure.paylinedatagateway.com/api/transact.php");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_POST, 1);

    if (!($data = curl_exec($ch))) {
        return ERROR;
    }
    curl_close($ch);
    unset($ch);
    // return $data;
    // print "\n$data\n";
    $data = explode("&",$data);
    for($i=0;$i<count($data);$i++) {
        $rdata = explode("=",$data[$i]);
        $response[$rdata[0]] = $rdata[1];
    }
    return $response;
  }
}


class PaymentMethodsStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
		$this->client = new MongoClient();
		$this->db = $this->client->{$config['db']};
		$this->gw = new gwapi;
		$this->gw->setLogin('Divvy34', 'divvyDOSE1');
	}

	private function document($collection, $id) {
		try {
			return $this->db->{$collection}->findOne(array('_id' => $id));			
		}
		catch (Exception $e) {
			return null;
		}
	}

	private function storageId(array $storageConfig, $modelId) {
		if ($storageConfig['id']['auto'] || !isset($storageConfig['id']['auto'])) {
			try {
				return new MongoId($modelId);
			}
			catch (Exception $e) {
				return null;
			}
		}
		else {
			return $modelId;
		}
	}

	private function modelId(array $storageConfig, $storageId) {
		if ($storageConfig['id']['auto'] || !isset($storageConfig['id']['auto'])) {
			return $storageId->{'$id'};
		}
		else {
			return $storageId;
		}
	}

	public function ids($model, array $storageConfig) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$ids = array();
		$cursor = $this->db->{$collection}->find();
		foreach ($cursor as $document) {
			$ids[] = $this->modelId($storageConfig, $document['_id']);
		}

		return $ids;
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}
		$document = $this->document($collection, $this->storageId($storageConfig, $id));
		return $document[$attrName];
	}

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
		switch ($relSchema['type']) {
			case 'One':
				if ($storageConfig['collection']) {
					$collection = $storageConfig['collection'];
				}
				else {
					$collection = $model;
				}
				$document = $this->document($collection, $this->storageId($storageConfig, $id));
				$value = $document[$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $relName];
				return !!$value;

			case 'Many':
				if (!$this->embeddedStorage($schema, $model, $relName)) {
					$foreignKey = $relSchema['storage']['foreignKey'] ? $relSchema['storage']['foreignKey'] : $relSchema['inverseRelationship'];

					$relModelStorageConfig = schemaModelStorageConfig($schema, $relSchema['model']);

					if ($relModelStorageConfig['collection']) {
						$collection = $relModelStorageConfig['collection'];
					}
					else {
						$collection = $relSchema['model'];
					}

					$cursor = $this->db->{$collection}->find(array(($foreignKey) => $id));
					foreach ($cursor as $document) {
						$value[] = $this->modelId($relModelStorageConfig, $document['_id']);
					}

					return !!$value;
				}
				else {
					if ($storageConfig['collection']) {
						$collection = $storageConfig['collection'];
					}
					else {
						$collection = $model;
					}
					$document = $this->document($collection, $this->storageId($storageConfig, $id));
					$value = $document[$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $relName];
					return !!$value;
				}
		}
	}

	private function embeddedStorage(array $schema, $model, $rel) {
		$relSchema = $schema['models'][$model]['relationships'][$rel];

		if ($relSchema['type'] == 'One' || $schema['models'][$relSchema['model']]['relationships'][$relSchema['inverseRelationship']]['type'] == 'Many' || $relSchema['type'] == 'Many' && !$relSchema['inverseRelationship']) {
			return true;
		}
		else {
			return false;
		}
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
		$response = $this->gw->execute(array(
			'customer_vault' => 'add_customer',
			'ccnumber' => $changes['attributes']['number'],
			'ccexp' => $changes['attributes']['expMonth'] . $changes['attributes']['expYear'],
			'payment' => 'creditcard',
			'first_name' => $changes['attributes']['firstName'],
			'last_name' => $changes['attributes']['lastName'],

			'address1' => $changes['attributes']['street1'],
			'address2' => $changes['attributes']['street2'],
			'city' => $changes['attributes']['city'],
			'state' => $changes['attributes']['state'],
			'zip' => $changes['attributes']['zip'],
			'country' => 'US',
			// 'phone' => $changes['attributes']['firstName'],
		));

		$changes['attributes']['customerVaultId'] = $response['customer_vault_id'];


		for ($i = 0; $i < strlen($changes['attributes']['number']); ++$i) {
			$number .= '*';
		}
		$number .= substr($changes['attributes']['number'], strlen($changes['attributes']['number']) - 4);
		$changes['attributes']['number'] = $number;


		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$data = array();
		if ($id !== null) {
			$data['_id'] = $this->storageId($storageConfig, $id);
		}

		if ($changes['attributes']) {
			$data += $changes['attributes'];
		}

		if ($relationships = $changes['relationships']) {
			foreach ($relationships as $name => $value) {
				$relSchema = $schema['models'][$model]['relationships'][$name];
				if ($this->embeddedStorage($schema, $model, $name)) {
					$data[$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $name] = $value;
				}
			}
		}

		$this->db->{$collection}->insert($data);
		return $this->modelId($storageConfig, $data['_id']);
	}

	public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		if ($changes['attributes']['number']) {
			$orgNumber = $changes['attributes']['number'];

			for ($i = 0; $i < strlen($changes['attributes']['number']); ++$i) {
				$number .= '*';
			}
			$number .= substr($changes['attributes']['number'], strlen($changes['attributes']['number']) - 4);
			$changes['attributes']['number'] = $number;
		}

		if ($changes['attributes']) {
			$update = array('$set' => $changes['attributes']);		
		}

		if ($relationships = $changes['relationships']) {
			foreach ($relationships as $name => $value) {
				$relSchema = $schema['models'][$model]['relationships'][$name];
				if ($this->embeddedStorage($schema, $model, $name)) {
					$update['$set'][$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $name] = $value;
				}
			}
		}

		if ($update) {
			$this->db->{$collection}->update(array('_id' => $this->storageId($storageConfig, $id)), $update, array('upsert' => true));

			$document = $this->document($collection, $this->storageId($storageConfig, $id));

			$gwUpdate = array(
				'customer_vault' => 'update_customer',
				'customer_vault_id' => $document['customerVaultId'],
				'ccexp' => $document['expMonth'] . $document['expYear'],
				'first_name' => $document['firstName'],
				'last_name' => $document['lastName'],
				'address1' => $document['street1'],
				'address2' => $document['street2'],
				'city' => $document['city'],
				'state' => $document['state'],
				'zip' => $document['zip'],
			);

			if ($orgNumber) {
				$gwUpdate['ccnumber'] = $orgNumber;
			}

			$response = $this->gw->execute($gwUpdate);
		}

		if ($changes['operations']) {
			foreach ($changes['operations'] as $operation) {
				$update = null;
				switch ($operation['operation']) {
					case 'assign':
						$update['$set'][implode('.', $operation['path'])] = $operation['parameters'][0];
						break;

					case 'add':
					case 'push':
						$update['$push'][implode('.', $operation['path'])] = $operation['parameters'][0];
						break;

					case 'pop':
						$update['$pop'][implode('.', $operation['path'])] = 1;
						break;

					case 'shift':
						$update['$pop'][implode('.', $operation['path'])] = -1;
						break;

					case 'remove':
						$update['$pull'][implode('.', $operation['path'])] = $operation['parameters'][0];
						break;
				}

				if ($update) {
					$this->db->{$collection}->update(array('_id' => $this->storageId($storageConfig, $id)), $update, array('upsert' => true));
				}
			}
		}
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$document = $this->document($collection, $this->storageId($storageConfig, $id));

		if ($document['customerVaultId']) {
			$this->gw->execute(array(
				'customer_vault' => 'delete_customer',
				'customer_vault_id' => $document['customerVaultId'],
			));			
		}

		$this->db->{$collection}->remove(array('_id' => $this->storageId($storageConfig, $id)));
	}

	public function truncate(array $schema, array $storageConfig, $model) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$this->db->{$collection}->drop();
	}
}
