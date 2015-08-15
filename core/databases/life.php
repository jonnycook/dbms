<?php

return [
	'databases' => [
		'default' => 'mongodb',
		// 'mysql' => array(
		// 	'type' => 'mysql',
		// 	'db' => 'journal',
		// 	'server' => '127.0.0.1',
		// 	'user' => 'root',
		// 	'password' => '9wo7bCrA'
		// ),
		'mongodb' => [
			'type' => 'mongodb',
			'db' => 'life',
		],
	],

	// 'queryProfiles' => array(
	// 	'default' => array(
	// 		'Model' => array(
	// 			'attributes' => array(
	// 				'hash' => array(
	// 					'retrieval' => 'lazy'
	// 				)
	// 			),
	// 			'relationships' => array(
	// 				'children' => array(
	// 					'paged' => 10
	// 				)
	// 			)
	// 		)
	// 	)
	// ),

	'models' => [
		'Activity' => [
			'attributes' => [
				'name' => [
					'type' => 'string',
				],
			],
			'relationships' => [
				'instances' => [
					'type' => 'Many',
					'model' => 'Instance',
					'structure' => 'OrderedSet', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'activity',
					'storage' => [
						'foreignKey' => 'activity_id',
					]
				]
			]
		],

		'ActivityPlan' => [
			'attributes' => [
				'date' => ['type' => 'string'],
				'time' => ['type' => 'duration'],
				'completed' => ['type' => 'bool']
			],
			'relationships' => [
				'activity' => [
					'model' => 'Activity',
					'type' => 'One'
				],
				'day' => [
					'model' => 'Day',
					'type' => 'One',
					'inverseRelationship' => 'activityPlans'
				],
			]
		],

		'Instance' => [
			'attributes' => [
				'begin' => ['type' => 'datetime'],
				'end' => ['type' => 'datetime'],
				'comment' => ['type' => 'string'],
			],
			'relationships' => [
				'activity' => [
					'type' => 'One',
					'model' => 'Activity',
					'structure' => 'OrderedSet', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'instances',
					'storage' => [
						'key' => 'activity_id',
					]
				]
			]
		],
		'Goal' => [
			'attributes' => [
				'name' => [
					'type' => 'string'
				]
			]
		],
		'Day' => [
			'attributes' => [
				'begin' => [
					'type' => 'datetime',
				],
				'end' => [
					'type' => 'datetime',
				],
				'intendedEnd' => [
					'type' => 'datetime',
				]
			],
			'relationships' => [
				'activityPlans' => [
					'type' => 'Many',
					'model' => 'ActivityPlan',
					'inverseRelationship' => 'day'
				]
			]
		],

		'JournalEntry' => [],

		'ScheduleItemType' => [
			'attributes' => [
				'label' => ['type' => 'string'],
				'sedentary' => ['type' => 'bool'],
				'active' => ['type' => 'bool'],
				'stimulating' => ['type' => 'bool'],
			],
			'relationships' => [
				'activities' => [
					'model' => 'Activity',
					'type' => 'Many',
				],
				'rules' => [
					'model' => 'ScheduleItemTypeRule',
					'type' => 'Many',
					'inverseRelationship' => 'type'
				]
			]
		],

		'ScheduleItemTypeRule' => [
			'attributes' => [
				// fixed
				'start' => ['type' => 'string'],
				'length' => ['type' => 'duration'],
				
				// distributed
				'weight' => ['type' => 'int'],
				'priority' => ['type' => 'int'],
				'minTime' => ['type' => 'duration'],
				'maxTime' => ['type' => 'duration'],
				'minBlockSize' => ['type' => 'duration'],
				'maxBlockSize' => ['type' => 'duration'],
			],
			'relationships' => [
				'day' => [
					'model' => 'Day',
					'type' => 'One',
				],
				'type' => [
					'model' => 'ScheduleItemType',
					'type' => 'One',
					'inverseRelationship' => 'rules'
				]
			]
		],

		'Schedule' => [
			'attributes' => [
				'begin' => ['type' => 'datetime'],
				'end' => ['type' => 'datetime'],
				'firstBlock' => ['type' => 'datetime'],
			],
			'relationships' => [
				'items' => [
					'model' => 'ScheduleItem',
					'type' => 'Many',
					'inverseRelationship' => 'schedule',
				]
			]
		],

		'ScheduleItem' => [
			'attributes' => [
				'begin' => ['type' => 'datetime'],
				'end' => ['type' => 'datetime'],

				'started' => ['type' => 'datetime'],
				'finished' => ['type' => 'datetime'],
			],
			'relationships' => [
				'schedule' => [
					'model' => 'Schedule',
					'type' => 'One',
					'inverseRelationship' => 'items'
				],
				'type' => [
					'model' => 'ScheduleItemType',
					'type' => 'One',
					'inverseRelationship' => 'items',
				],
			]
		],

		'ShuffleState' => [
			'attributes' => [
				'nextBlockTime' => ['type' => 'duration']
			],
			'relationships' => [
				'lastItem' => [
					'type' => 'One',
					'model' => 'ShuffleItem',
				],
				'nextBlockItem' => [
					'type' => 'One',
					'model' => 'ShuffleItem',
				],
			]
		],

		'ShuffleItem' => [
			'attributes' => [
				'label' => ['type' => 'string'],
				'unit' => ['type' => 'duration'],
			],
			'relationships' => [
				'activities' => [
					'model' => 'Activity',
					'type' => 'Many',
				],
				'rules' => [
					'model' => 'ShuffleItemRule',
					'type' => 'Many',
					'inverseRelationship' => 'item',
				],
				'bodyStateValues' => [
					'model' => 'BodyStateValue',
					'type' => 'Many',
					'inverseRelationship' => 'item'
				],
				'blocks' => [
					'model' => 'ShuffleItemBlock',
					'type' => 'Many',
					'inverseRelationship' => 'shuffleItem',
				]
			],
		],

		'ShuffleItemBlock' => [
			'attributes' => [
				'length' => ['type' => 'duration'],
				'finished' => ['type' => 'bool'],

				'begin' => ['type' => 'datetime'],
				'end' => ['type' => 'datetime'],
			],
			'relationships' => [
				'shuffleItem' => [
					'model' => 'ShuffleItem',
					'type' => 'One',
					'inverseRelationship' => 'blocks'
				]
			]
		],

		// 'ShuffleItemInstance' => array(
		// 	'attributes' => array(
		// 		'begin' => array('type' => 'datetime'),
		// 		'end' => array('type' => 'datetime'),
		// 	),
		// 	'relationships' => array(
		// 		'shuffleItem' => array(
		// 			'model' => 'ShuffleItem',
		// 			'type' => 'One',
		// 			'inverseRelationship' => 'items',
		// 		)
		// 	)
		// ),

		'BodyState' => [
			'attributes' => [
				'label' => ['type' => 'string'],
				'based' => ['type' => 'string', 'values' => ['time', 'unit']]
			]
		],

		'CurrentBodyState' => [
			'attributes' => [
				'timestamp' => ['type' => 'datetime']
			],
			'relationships' => [
				'values' => [
					'model' => 'CurrentBodyStateValue',
					'type' => 'Many',
					'inverseRelationship' => 'currentBodyState'
				],
			]
		],

		'CurrentBodyStateValue' => [
			'attributes' => [
				'value' => ['type' => 'float']
			],
			'relationships' => [
				'state' => [
					'model' => 'BodyState',
					'type' => 'One',
				],
				'currentBodyState' => [
					'model' => 'CurrentBodyState',
					'type' => 'One',
					'inverseRelationship' => 'values',
				]
			]
		],

		'BodyStateValue' => [
			'attributes' => [
				'value' => ['type' => 'float']
			],
			'relationships' => [
				'state' => [
					'model' => 'BodyState',
					'type' => 'One',
				],
				'item' => [
					'model' => 'ShuffleItem',
					'type' => 'One',
					'inverseRelationship' => 'bodyStateValues'
				]
			]
		],

		'ShuffleItemRule' => [
			'attributes' => [
				'date' => ['type' => 'string'],
				'minTime' => ['type' => 'duration'],
				'maxTime' => ['type' => 'duration'],
				'priority' => ['type' => 'int'],
				'minBlockTime' => ['type' => 'duration'],
				'maxBlockTime' => ['type' => 'duration'],
			],
			'relationships' => [
				'item' => [
					'model' => 'ShuffleItem',
					'type' => 'One',
					'inverseRelationship' => 'rules'
				]
			]
		],

		'Thought' => [
			'attributes' => [
				'content' => [
					'type' => 'string',
				],
				'timestamp' => [
					'type' => 'datetime',
				],
			]
		],

		// '@Money' => array(
			'FinancialWindow' => [
				'attributes' => [
					'name' => ['type' => 'string'],
					'begin' => ['type' => 'string'],
					'end' => ['type' => 'string'],
				],

				'relationships' => [
					'budgetGroups' => [
						'type' => 'Many',
						'model' => 'WindowBudgetGroup',
						'inverseRelationship' => 'window',
						'relationship' => 'Children'
					]
				]
			],

			'WindowBudgetGroup' => [
				'attributes' => [
					'name' => ['type' => 'string']
				],
				'relationships' => [
					'window' => [
						'type' => 'One',
						'model' => 'FinancialWindow',
						'inverseRelationship' => 'budgetGroups'
					],
					'budgets' => [
						'type' => 'Many',
						'model' => 'WindowBudget',
						'inverseRelationship' => 'group',
					]
				]
			],

			'WindowBudget' => [
				'attributes' => [
					'period' => ['type' => 'string', 'values' => ['Day', 'Week', 'Month', 'Year', 'Window', 'Portions']],
					'amount' => ['type' => 'float'],
				],
				'relationships' => [
					'group' => [
						'type' => 'One',
						'model' => 'WindowBudgetGroup',
						'inverseRelationship' => 'budgets',
						'relationship' => 'Parent'
					],
					'expense' => [
						'type' => 'One',
						'model' => 'Expense'
					],
					'portions' => [
						'type' => 'Many',
						'model' => 'WindowBudgetPortion',
						'inverseRelationship' => 'budget'
					]
				]
			],

			'WindowBudgetPortion' => [
				'attributes' => [
					'portion' => ['type' => 'int'],
					'amount' => ['type' => 'float'],
				],
				'relationships' => [
					'budget' => [
						'type' => 'One',
						'model' => 'WindowBudget',
						'inverseRelationship' => 'portions',
					]
				]
			],

			'IncomePlan' => [
				'attributes' => [
					'label' => ['type' => 'string'],
					'amount' => ['type' => 'float'],
					'period' => ['type' => 'string'],
					'begin' => ['type' => 'string'],
					'end' => ['type' => 'string'],
				]
			],


			'Expense' => [
				'attributes' => [
					'name' => ['type' => 'string'],
				]
			],

			'RecurringBill' => [
				'attributes' => [
					'date' => ['type' => 'int'],
					'label' => ['type' => 'string'],
					'amount' => ['type' => 'float']
				],
				'relationships' => [
					'payments' => [
						'type' => 'Many',
						'model' => 'RecurringBillPayment',
						'inverseRelationship' => 'bill'
					]
				]
			],

			'RecurringBillPayment' => [
				'attributes' => [
					'timestamp' => ['type' => 'datetime'],
					'amount' => ['type' => 'float']
				],
				'relationships' => [
					'bill' => [
						'model' => 'RecurringBill',
						'type' => 'One',
						'inverseRelationship' => 'payments'
					],
					'reserve' => [
						'type' => 'One',
						'model' => 'MoneyReserve',
					],
				],
			],

			'Income' => [
				'attributes' => [
					'amount' => ['type' => 'float'],
					'label' => ['type' => 'string']
				],
				'relationships' => [
					'payments' => [
						'model' => 'IncomePayment',
						'type' => 'Many',
						'inverseRelationship' => 'income',
					]
				]
			],

			'IncomePayment' => [
				'attributes' => [
					'amount' => ['type' => 'float'],
					'timestamp' => ['type' => 'datetime'],
				],
				'relationships' => [
					'reserve' => [
						'type' => 'One',
						'model' => 'MoneyReserve',
					],
					'income' => [
						'type' => 'One',
						'model' => 'Income',
						'inverseRelationship' => 'payments'
					]
				]
			],

			'MoneyReserve' => [
				'attributes' => [
					'name' => ['type' => 'string'],
					'currency' => ['type' => 'string'],
					'type' => ['type' => 'string'],
					'amount' => ['type' => 'float'],
				],
				'relationships' => [
					'counts' => [
						'type' => 'Many',
						'model' => 'MoneyReserveCount',
						'inverseRelationship' => 'reserve'
					]
				]
			],

			'MoneyReserveTransfer' => [
				'attributes' => [
					'timestamp' => ['type' => 'datetime'],
					'fromAmount' => ['type' => 'float'],
					'toAmount' => ['type' => 'float']
				],
				'relationships' => [
					'fromReserve' => [
						'model' => 'MoneyReserve',
						'type' => 'One',
					],
					'toReserve' => [
						'model' => 'MoneyReserve',
						'type' => 'One'
					]
				]
			],

			'MoneyReserveCount' => [
				'attributes' => [
					'timestamp' => ['type' => 'datetime'],
					'amount' => ['type' => 'float'],
				],
				'relationships' => [
					'reserve' => [
						'model' => 'MoneyReserve',
						'type' => 'One',
						'inverseRelationship' => 'counts'
					]
				]
			],

			'MoneyEvent' => [
				'attributes' => [
					'timestamp' => ['type' => 'datetime'],
					'type' => ['type' => 'string'],
					'amount' => ['type' => 'float'],
				],
				'relationships' => [
					'store' => [
						'type' => 'One',
						'model' => 'MoneyStore',
						'inverseRelationship' => 'events',
					],
					'recurringBill' => [
						'type' => 'One',
						'model' => 'RecurringBill',
						'inverseRelationship' => 'payments'
					]
				]
			],

			'DuePayment' => [
				'attributes' => [
					'label' => ['type' => 'string'],
					'amount' => ['type' => 'float'],
					'timestamp' => ['type' => 'datetime'],
					'due' => ['type' => 'datetime']
				]
			],

			'Loan' => [
				'attributes' => [
					'label' => ['type' => 'string'],
					'amount' => ['type' => 'float'],
					'timestamp' => ['type' => 'datetime'],
				],
				'relationships' => [
					'payments' => [
						'model' => 'LoanPayment',
						'type' => 'Many',
						'inverseRelationship' => 'loan'
					]
				]
			],

			'LoanPayment' => [
				'attributes' => [
					'timestamp' => ['type' => 'datetime'],
					'amount' => ['type' => 'float'],
				],
				'relationships' => [
					'loan' => [
						'model' => 'Loan',
						'type' => 'One',
						'inverseRelationship' => 'payments',
					],
					'reserve' => [
						'type' => 'One',
						'model' => 'MoneyReserve',
					],

				]
			],

			'Purchase' => [
				'attributes' => [
					'timestamp' => ['type' => 'datetime'],
					'amount' => ['type' => 'float'],
					'comment' => ['type' => 'string']
				],
				'relationships' => [
					'reserve' => [
						'type' => 'One',
						'model' => 'MoneyReserve',
					],
					'expense' => [
						'type' => 'One',
						'model' => 'Expense'
					],
					'transfer' => [
						'model' => 'MoneyReserveTransfer',
						'type' => 'One',
					]
				]
			],
		// ),



		'BodyCondition' => [
			'attributes' => [
				'name' => ['type' => 'string'],
			],
			'relationships' => [
				'checkIns' => [
					'model' => 'BodyConditionCheckIn',
					'type' => 'Many',
					'inverseRelationship' => 'condition'
				]
			]
		],

		'BodyCheckIn' => [
			'attributes' => [
				'timestamp' => ['type' => 'datetime'],
				'comment' => ['type' => 'string'],
			],
			'relationships' => [
				'conditions' => [
					'model' => 'BodyConditionCheckIn',
					'type' => 'Many',
					'inverseRelationship' => 'checkIn'
				]
			]
		],

		'BodyConditionCheckIn' => [
			'attributes' => [
				'degree' => ['type' => 'float']
			],
			'relationships' => [
				'checkIn' => [
					'model' => 'BodyCheckIn',
					'type' => 'One',
					'inverseRelationship' => 'conditions'
				],
				'condition' => [
					'model' => 'BodyCondition',
					'type' => 'One',
					'inverseRelationship' => 'checkIns'
				]
			]
		],

		'Meal' => [
			'attributes' => [
				'timestamp' => ['type' => 'datetime'],
				'type' => ['type' => 'string']
			],
			'relationships' => [
				'elements' => [
					'type' => 'Many',
					'model' => 'FoodElement',
				]
			]
		],

		'FoodElement' => [
			'attributes' => [
				'name' => ['type' => 'string'],
			],

			'relationships' => [
				'elements' => [
					'model' => 'FoodElement',
					'type' => 'Many',
				]
			]
		],

		'FoodLog' => [
			'attributes' => [
				'timestamp' => ['type' => 'datetime'],
			],

			'relationships' => [
				'element' => [
					'model' => 'FoodElement',
					'type' => 'One'
				]
			]
		],

		'WaterContainer' => [
			'attributes' => [
				'volume' => ['type' => 'string'],
				'started' => ['type' => 'datetime'],
				'finished' => ['type' => 'datetime'],
			]
		]

		// 'SleepBe'
	],

	'routes' => [
		'/' => [
			'type' => 'db'
		],

		'/:model/:id' => [
			'type' => 'model'
		],
		// '/Model' => array('model' => 'Model'),

		// '/settings/:id' => array(
		// 	'type' => 'hash',
		// 	'storage' => array(
		// 		'db' => 'mongodb',
		// 		'collection' => 'settings',
		// 	)
		// )
	]
];