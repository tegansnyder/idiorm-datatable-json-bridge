# idiorm-datatable-json-bridge

A bridge between [Idiorm](https://github.com/j4mie/idiorm) ORM and the popular [DataTables](https://datatables.net) javascript library that aims to abstract some of the pain points in mapping your database data to the JSON data DataTables expects.

> Working code example here:
https://github.com/tegansnyder/idiorm-datatable-json-bridge-example

---------------

### Installation
This package is best loaded using composer and supports PHP versions > 5.2. To install into your project, add these lines to your composer.json:
```json
"require": {
    "tegansnyder/idiorm-datatable-json-bridge": "*"
}
```

---------------

### Usage
Using this library requires you to replace any instance of the `ORM` class with `OrmDatatableBridge` in cases where you wish to retrieve DataTables formated JSON. It also requires you use `get_datatable` in place of `find_many`, `find_one`, or `find_array` when grabbing result sets.

##### Example:
Here is a very simple example showing you can use the same Idorim features you have before.

```php
$datatable_json = ORMDatatableBridge::for_table('products')
->where(
    [
        'name'	=> 'Test Product 1',
        'price'	=> 23
    ]
)
->get_datatable();

echo $datatable_json;
```
Returns:
```json
{
    "recordsTotal": 1,
    "recordsFiltered": 1,
    "data": [
        {
            "id": "1",
            "name": "Test Product 1",
            "price": "23",
            "weight": "12lbs"
        }
    ]
}
```

##### Hidden DT Columns Example:
Hidden data and ID columns can be set to access the data in JQuery later. Reference: http://datatables.net/manual/server-side#Returned-data

```php
$datatable_json = ORMDatatableBridge::for_table('products')
->get_datatable(
	[
		'DT_RowId' => [
			'type' => 'dynamic',
			'key'  => 'products_'
		],
		'DT_RowData' => [
			'weight' => 'total_weight_{{weight}}',
			'price'  => '{{price}}'
		]
	]
);
```
Returns:
```json
{
    "recordsTotal": 3,
    "recordsFiltered": 3,
    "data": [
        {
            "id": "1",
            "name": "Test Product 1",
            "price": "23",
            "weight": "12lbs",
            "DT_RowData": {
                "weight": "total_weight_12lbs",
                "price": "23"
            }
        },
        {
            "id": "2",
            "name": "Test Product 2",
            "price": "44",
            "weight": "66lbs",
            "DT_RowData": {
                "weight": "total_weight_66lbs",
                "price": "44"
            }
        },
        {
            "id": "3",
            "name": "Test Product 3",
            "price": "100",
            "weight": "205lbs",
            "DT_RowData": {
                "weight": "total_weight_205lbs",
                "price": "100"
            }
        }
    ]
}
```

##### Advanced Example:
If you are like me you will find yourself needing to wrap columns of data in custom html. You might also want to add some dynamic columns and add buttons to your datatables for editing a row or removing a row. Below is an example of some advanced options you can pass the `get_datatable` method.

```php
$datatable_json = ORMDatatableBridge::for_table('products')
->where('id', 1)
->get_datatable(
	[
		'DT_RowId' => [
			'type' => 'dynamic',
			'key'  => 'id',
			'prepend' => 'id_'
		],
		'DT_RowData' => [
			'weight' => 'ship_weight_{{weight}}'
		],
		'dynamic_columns' => [
			[
				'key' => 'edit',
				'column_template' => '<a href="edit.php?id={id}}">Edit</a>'
			],
			[
				'key' => 'delete',
				'column_template' => '<a href="/delete.php?id={{id}}">Delete</a>'
			]
		],
		'wrap_columns' => [
			[
				'key' => 'id',
				'column_template' => '<a href="/view.php?id={{id}}">{{id}}</a>'
			],
			[
				'key' => 'name',
				'column_template' => '<a href="/view.php?id={{id}}">{{name}}</a>'
			]
		],
		'wrap_all' => [
            'columns' => '<section class="datatable-column {{col_name}}">{{column_data}}</section>'
		]
	]
);
```

Returns:
```json
{
    "recordsTotal": 1,
    "recordsFiltered": 1,
    "data": [
        {
            "id": "<section class=\"datatable-column\"><a href=\"/view.php?id=1\">1</a></section>",
            "name": "<section class=\"datatable-column\"><a href=\"/view.php?id=<a href=\"/view.php?id=1\">1</a>\">Test Product 1</a></section>",
            "price": "<section class=\"datatable-column\">23</section>",
            "weight": "<section class=\"datatable-column\">12lbs</section>",
            "DT_RowId": "id_1",
            "DT_RowData": {
                "weight": "ship_weight_12lbs"
            },
            "edit": "<section class=\"datatable-column\"><a href=\"edit.php?id={id}}\">Edit</a></section>",
            "delete": "<section class=\"datatable-column\"><a href=\"/delete.php?id=1\">Delete</a></section>"
        }
    ]
}
```

##### Additional Options:
Many times the columns from your result set you may not want to actually use on your frontend. You can elect to hide these columns from the JSON output. In the backend database query they can still be selected, and used in templating, but just not included as a column in the JSON output. Here is an example:

```php
$datatable_json = ORMDatatableBridge::for_table('products')
->select(
    [
        'name', 
        'id',
        'price'
    ]
)
->get_datatable(
    [
        'hide_columns' => [
            'id'
        ]
    ]
);
```

Note in the above example I included a parameter called `hide_column` and set a value to `id`. I can still template off this value (example below):

```php
$datatable_json = ORMDatatableBridge::for_table('products')
->select(
    [
        'name', 
        'id',
        'price'
    ]
)
->get_datatable(
    [
        'hide_columns' => [
            'id'
        ],
        'wrap_columns' => [
            [
                'key' => 'id',
                'column_template' => '<a href="/product/view/{{id}}" target="_self">{{name}}</a>'
            ]
        ],
    ]
);
```

##### Getting Column Names

If you want to grab a list of the column names to be used in marking up the HTML head of your table you can do another query to obtaining them by issuing the `just_columns` parameter. Note you can still supply the `hide_columns` parameter.

```php
$datatable_json = ORMDatatableBridge::for_table('products')
->select(
    [
        'name', 
        'id',
        'price'
    ]
)
->get_datatable(
    [
        'hide_columns' => [
            'id'
        ],
        'just_columns' => true
    ]
);
```

##### Renaming Column Names

If you find yourself auto populating the HTML table head by grabbing a list of the column names from the above query, you may also want to rename some of the column names for display purposes. For instance you may have a column in the database called `store_price` that you want to look like `Store Price` on the table head. To do this see the example below:

```php
$datatable_json = ORMDatatableBridge::for_table('products')
->select(
    [
        'name', 
        'id',
        'store_price'
    ]
)
->get_datatable(
    [
        'column_display_names' => [
            'name' => 'Name',
            'store_price' => 'Store Price'
        ]
    ]
);
```

##### Reordering Columns

If you need to reorder the output of a select statement you can by using the `column_order` option. Please note if you have previously used the `column_display_names` option to rename columns you must use the new names when reordering. Example of reordering below:

```php
$datatable_json = ORMDatatableBridge::for_table('products')
->select(
    [
        'name', 
        'id',
        'store_price'
    ]
)
->get_datatable(
    [
        'column_order' => [
            'store_price',
            'id',
            'name'
        ]
    ]
);
```


##### Record Count:

In order to supply Datatables with the appropriate record count it needs to calculate the pagination this library includes some options for controling the record used for obtaining a count. To obtain this count it overrides the Idiorm `_build_select` method and does an intial COUNT query by wrapping your provided query in a `SELECT COUNT(*)` statement and removing any imposed LIMITS and OFFSETS from your query to get the full record count. It then uses this result to populate the `recordsTotal` portion of the JSON. I couldn't think a better way to do this, but I'm open to suggestions. For idorim raw queries this library expects you to name the query paramater bindings you pass to the `raw_query` method as 'LIMIT :limit' and 'OFFSET :offset' respectively.


---------------

### Contributing
I'm happy to accept PR's that add additional functionality and fix bugs. I will do my best evaluate them prior to merging. The license is released under a [BSD license](http://en.wikipedia.org/wiki/BSD_licenses) similar to [Idiorm](https://github.com/j4mie/idiorm).


---------------

### Links

[DataTables](https://datatables.net)

[Idorim](https://github.com/j4mie/idiorm)


Thanks to all the contributors of both projects!
