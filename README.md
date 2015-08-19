# idiorm-datatable-json-bridge

A bridge between [Idiorm](https://github.com/j4mie/idiorm) ORM and the popular [DataTables](https://datatables.net) javascript library that aims to abstract some of the pain points in mapping your database data to the JSON data DataTables expects.

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
->where(array(
    'name'	=> 'Test Product 1',
    'price'	=> 23
))
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
	array(
		'DT_RowId' => array(
			'type' => 'dynamic',
			'key'  => 'products_'
		),
		'DT_RowData' => array(
			'weight' => 'total_weight_{{weight}}',
			'price'  => '{{price}}'
		)
	)
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
	array(
		'DT_RowId' => array(
			'type' => 'dynamic',
			'key'  => 'id',
			'prepend' => 'id_'
		),
		'DT_RowData' => array(
			'weight' => 'ship_weight_{{weight}}'
		),
		'dynamic_columns' => array(
			array(
				'key' => 'edit',
				'column_template' => '<a href="edit.php?id={id}}">Edit</a>'
			),
			array(
				'key' => 'delete',
				'column_template' => '<a href="/delete.php?id={{id}}">Delete</a>'
			)
		),
		'wrap_columns' => array(
			array(
				'key' => 'id',
				'column_template' => '<a href="/view.php?id={{id}}">{{id}}</a>'
			),
			array(
				'key' => 'name',
				'column_template' => '<a href="/view.php?id={{id}}">{{name}}</a>'
			)
		),
		'wrap_all' => array(
			'columns' => '<section class="datatable-column">{{column_data}}</section>'
		)
	)
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

---------------

### Contributing
I'm happy to accept PR's that add additional functionality and fix bugs. I will do my best evaluate them prior to merging. The license is released under a [BSD license](http://en.wikipedia.org/wiki/BSD_licenses) similar to [Idiorm](https://github.com/j4mie/idiorm).


---------------

### Links

[DataTables](https://datatables.net)
[Idorim](https://github.com/j4mie/idiorm)

Thanks to all the contributors of both projects!