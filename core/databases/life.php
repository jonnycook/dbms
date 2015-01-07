<?php

return array(
	'databases' => array(
		'default' => 'mongodb',
		// 'mysql' => array(
		// 	'type' => 'mysql',
		// 	'db' => 'journal',
		// 	'server' => '127.0.0.1',
		// 	'user' => 'root',
		// 	'password' => '9wo7bCrA'
		// ),
		'mongodb' => array(
			'type' => 'mongodb',
			'db' => 'life',
		),
	),

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

	'models' => array(
		'Activity' => array(
			'attributes' => array(
				'name' => array(
					'type' => 'string',
				),
			),
			'relationships' => array(
				'instances' => array(
					'type' => 'Many',
					'model' => 'Instance',
					'structure' => 'OrderedSet', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'activity',
					'storage' => array(
						'foreignKey' => 'activity_id',
					)
				)
			)
		),

		'ActivityPlan' => array(
			'attributes' => array(
				'date' => array('type' => 'string'),
				'time' => array('type' => 'duration'),
				'completed' => array('type' => 'bool')
			),
			'relationships' => array(
				'activity' => array(
					'model' => 'Activity',
					'type' => 'One'
				),
				'day' => array(
					'model' => 'Day',
					'type' => 'One',
					'inverseRelationship' => 'activityPlans'
				),
			)
		),

		'Instance' => array(
			'attributes' => array(
				'begin' => array(
					'type' => 'datetime',
				),
				'end' => array(
					'type' => 'datetime',
				),
			),
			'relationships' => array(
				'activity' => array(
					'type' => 'One',
					'model' => 'Activity',
					'structure' => 'OrderedSet', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'instances',
					'storage' => array(
						'key' => 'activity_id',
					)
				)
			)
		),
		'Goal' => array(
			'attributes' => array(
				'name' => array(
					'type' => 'string'
				)
			)
		),
		'Day' => array(
			'attributes' => array(
				'begin' => array(
					'type' => 'datetime',
				),
				'end' => array(
					'type' => 'datetime',
				),
				'intendedEnd' => array(
					'type' => 'datetime',
				)
			),
			'relationships' => array(
				'activityPlans' => array(
					'type' => 'Many',
					'model' => 'ActivityPlan',
					'inverseRelationship' => 'day'
				)
			)
		),
		'Thought' => array(
			'attributes' => array(
				'content' => array(
					'type' => 'string',
				),
				'timestamp' => array(
					'type' => 'datetime',
				),
			)
		),

		// '@Money' => array(
			'FinancialWindow' => array(
				'attributes' => array(
					'name' => array('type' => 'string'),
					'begin' => array('type' => 'string'),
					'end' => array('type' => 'string'),
				),

				'relationships' => array(
					'budgetGroups' => array(
						'type' => 'Many',
						'model' => 'WindowBudgetGroup',
						'inverseRelationship' => 'window',
						'relationship' => 'Children'
					)
				)
			),

			'WindowBudgetGroup' => array(
				'attributes' => array(
					'name' => array('type' => 'string')
				),
				'relationships' => array(
					'window' => array(
						'type' => 'One',
						'model' => 'FinancialWindow',
						'inverseRelationship' => 'budgetGroups'
					),
					'budgets' => array(
						'type' => 'Many',
						'model' => 'WindowBudget',
						'inverseRelationship' => 'group',
					)
				)
			),

			'WindowBudget' => array(
				'attributes' => array(
					'period' => array('type' => 'string', 'values' => array('Day', 'Week', 'Month', 'Year', 'Window', 'Portions')),
					'amount' => array('type' => 'float'),
				),
				'relationships' => array(
					'group' => array(
						'type' => 'One',
						'model' => 'WindowBudgetGroup',
						'inverseRelationship' => 'budgets',
						'relationship' => 'Parent'
					),
					'expense' => array(
						'type' => 'One',
						'model' => 'Expense'
					),
					'portions' => array(
						'type' => 'Many',
						'model' => 'WindowBudgetPortion',
						'inverseRelationship' => 'budget'
					)
				)
			),

			'WindowBudgetPortion' => array(
				'attributes' => array(
					'portion' => array('type' => 'int'),
					'amount' => array('type' => 'float'),
				),
				'relationships' => array(
					'budget' => array(
						'type' => 'One',
						'model' => 'WindowBudget',
						'inverseRelationship' => 'portions',
					)
				)
			),

			'IncomePlan' => array(
				'attributes' => array(
					'label' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
					'period' => array('type' => 'string'),
					'begin' => array('type' => 'string'),
					'end' => array('type' => 'string'),
				)
			),


			'Expense' => array(
				'attributes' => array(
					'name' => array('type' => 'string'),
				)
			),

			'RecurringBill' => array(
				'attributes' => array(
					'date' => array('type' => 'int'),
					'label' => array('type' => 'string'),
					'amount' => array('type' => 'float')
				),
				'relationships' => array(
					'payments' => array(
						'type' => 'Many',
						'model' => 'RecurringBillPayment',
						'inverseRelationship' => 'bill'
					)
				)
			),

			'RecurringBillPayment' => array(
				'attributes' => array(
					'timestamp' => array('type' => 'datetime'),
					'amount' => array('type' => 'float')
				),
				'relationships' => array(
					'bill' => array(
						'model' => 'RecurringBill',
						'type' => 'One',
						'inverseRelationship' => 'payments'
					),
					'reserve' => array(
						'type' => 'One',
						'model' => 'MoneyReserve',
					),

				),

			),

			'Income' => array(
				'attributes' => array(
					'amount' => array('type' => 'float'),
					'label' => array('type' => 'string')
				),
				'relationships' => array(
					'payments' => array(
						'model' => 'IncomePayment',
						'type' => 'Many',
						'inverseRelationship' => 'income',
					)
				)
			),

			'IncomePayment' => array(
				'attributes' => array(
					'amount' => array('type' => 'float'),
					'timestamp' => array('type' => 'datetime'),
				),
				'relationships' => array(
					'reserve' => array(
						'type' => 'One',
						'model' => 'MoneyReserve',
					),
					'income' => array(
						'type' => 'One',
						'model' => 'Income',
						'inverseRelationship' => 'payments'
					)
				)
			),

			'MoneyReserve' => array(
				'attributes' => array(
					'name' => array('type' => 'string'),
					'currency' => array('type' => 'string'),
					'type' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
				),
				'relationships' => array(
					'counts' => array(
						'type' => 'Many',
						'model' => 'MoneyReserveCount',
						'inverseRelationship' => 'reserve'
					)
				)
			),

			'MoneyReserveTransfer' => array(
				'attributes' => array(
					'timestamp' => array('type' => 'datetime'),
					'fromAmount' => array('type' => 'float'),
					'toAmount' => array('type' => 'float')
				),
				'relationships' => array(
					'fromReserve' => array(
						'model' => 'MoneyReserve',
						'type' => 'One',
					),
					'toReserve' => array(
						'model' => 'MoneyReserve',
						'type' => 'One'
					)
				)
			),

			'MoneyReserveCount' => array(
				'attributes' => array(
					'timestamp' => array('type' => 'datetime'),
					'amount' => array('type' => 'float'),
				),
				'relationships' => array(
					'reserve' => array(
						'model' => 'MoneyReserve',
						'type' => 'One',
						'inverseRelationship' => 'counts'
					)
				)
			),

			'MoneyEvent' => array(
				'attributes' => array(
					'timestamp' => array('type' => 'datetime'),
					'type' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
				),
				'relationships' => array(
					'store' => array(
						'type' => 'One',
						'model' => 'MoneyStore',
						'inverseRelationship' => 'events',
					),
					'recurringBill' => array(
						'type' => 'One',
						'model' => 'RecurringBill',
						'inverseRelationship' => 'payments'
					)
				)
			),

			'DuePayment' => array(
				'attributes' => array(
					'label' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
					'timestamp' => array('type' => 'datetime'),
					'due' => array('type' => 'datetime')
				)
			),

			'Loan' => array(
				'attributes' => array(
					'label' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
					'timestamp' => array('type' => 'datetime'),
				),
				'relationships' => array(
					'payments' => array(
						'model' => 'LoanPayment',
						'type' => 'Many',
						'inverseRelationship' => 'loan'
					)
				)
			),

			'LoanPayment' => array(
				'attributes' => array(
					'timestamp' => array('type' => 'datetime'),
					'amount' => array('type' => 'float'),
				),
				'relationships' => array(
					'loan' => array(
						'model' => 'Loan',
						'type' => 'One',
						'inverseRelationship' => 'payments',
					),
					'reserve' => array(
						'type' => 'One',
						'model' => 'MoneyReserve',
					),

				)
			),

			'Purchase' => array(
				'attributes' => array(
					'timestamp' => array('type' => 'datetime'),
					'amount' => array('type' => 'float'),
					'comment' => array('type' => 'string')
				),
				'relationships' => array(
					'reserve' => array(
						'type' => 'One',
						'model' => 'MoneyReserve',
					),
					'expense' => array(
						'type' => 'One',
						'model' => 'Expense'
					),
					'transfer' => array(
						'model' => 'MoneyReserveTransfer',
						'type' => 'One',
					)
				)
			),
		// ),



		'BodyCondition' => array(
			'attributes' => array(
				'name' => array('type' => 'string'),
			),
			'relationships' => array(
				'checkIns' => array(
					'model' => 'BodyConditionCheckIn',
					'type' => 'Many',
					'inverseRelationship' => 'condition'
				)
			)
		),

		'BodyCheckIn' => array(
			'attributes' => array(
				'timestamp' => array('type' => 'datetime'),
			),
			'relationships' => array(
				'conditions' => array(
					'model' => 'BodyConditionCheckIn',
					'type' => 'Many',
					'inverseRelationship' => 'checkIn'
				)
			)
		),

		'BodyConditionCheckIn' => array(
			'attributes' => array(
				'degree' => array('type' => 'float')
			),
			'relationships' => array(
				'checkIn' => array(
					'model' => 'BodyCheckIn',
					'type' => 'One',
					'inverseRelationship' => 'conditions'
				),
				'condition' => array(
					'model' => 'BodyCondition',
					'type' => 'One',
					'inverseRelationship' => 'checkIns'
				)
			)
		),

		'Meal' => array(
			'attributes' => array(
				'timestamp' => array('type' => 'datetime'),
				'type' => array('type' => 'string')
			),
			'relationships' => array(
				'elements' => array(
					'type' => 'Many',
					'model' => 'FoodElement',
				)
			)
		),

		'FoodElement' => array(
			'attributes' => array(
				'name' => array('type' => 'string'),
			),

			'relationships' => array(
				'elements' => array(
					'model' => 'FoodElement',
					'type' => 'Many',
				)
			)
		),

		'FoodLog' => array(
			'attributes' => array(
				'timestamp' => array('type' => 'datetime'),
			),

			'relationships' => array(
				'element' => array(
					'model' => 'FoodElement',
					'type' => 'One'
				)
			)
		),

		'WaterContainer' => array(
			'attributes' => array(
				'volume' => array('type' => 'string'),
				'started' => array('type' => 'datetime'),
				'finished' => array('type' => 'datetime'),
			)
		)

		// 'SleepBe'
	),

	'routes' => array(
		'/' => array(
			'type' => 'db'
		),

		'/:model/:id' => array(
			'type' => 'model'
		),
		// '/Model' => array('model' => 'Model'),

		// '/settings/:id' => array(
		// 	'type' => 'hash',
		// 	'storage' => array(
		// 		'db' => 'mongodb',
		// 		'collection' => 'settings',
		// 	)
		// )
	)
);