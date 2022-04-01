<?php

namespace App\Http\Controllers;

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class DynamoDbController extends Controller
{
    public $dynamodb;

    public mixed $faker;

    public function __construct()
    {
        $this->dynamodb = App::make('aws')->createClient('dynamodb');

        $this->faker = Container::getInstance()->make(Generator::class);
    }

    /**
     * list tables
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $result = $this->dynamodb->listTables();

        // TableNames contains an array of table names
        foreach ($result['TableNames'] as $tableName) {
            echo $tableName . "\n";
        }
    }

    /**
     * create table
     *
     * @param Request $request
     */
    public function createTable(Request $request)
    {
        $params = [
            'TableName' => $request->get('table_name'),
            'KeySchema' => [
                [
                    'AttributeName' => 'id_key',
                    'KeyType' => 'HASH'  //Partition key
                ],
                /*[
                    'AttributeName' => 'full_name',
                    'KeyType' => 'RANGE'  //Sort key
                ]*/
            ],
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'id_key',
                    'AttributeType' => 'S'
                ],
                /*[
                    'AttributeName' => 'full_name',
                    'AttributeType' => 'S'
                ],*/
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits' => 5,
                'WriteCapacityUnits' => 10,
            ],
        ];
        try {
            $result = $this->dynamodb->createTable($params);
            echo 'Created table.  Status: ' .
                $result['TableDescription']['TableStatus'] . "\n";

        } catch (DynamoDbException $e) {
            echo "Unable to create table:\n";
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * create record
     *
     * @param Request $request
     */
    public function create(Request $request)
    {
        $marshaler = new Marshaler();

        $item = [
            'id_key' => strval($this->faker->numberBetween(1, 100)),
            'full_name' => $this->faker->name,
            'info' => json_encode([
                'title' => $this->faker->title,
                'email' => $this->faker->email,
                'phone' => $this->faker->phoneNumber,
                'year' => $this->faker->year,
            ])
        ];

        $params = [
            'TableName' => $request->get('table_name'),
            'Item' => $marshaler->marshalJson(json_encode($item)),
        ];

        try {
            $result = $this->dynamodb->putItem($params);
            echo "Added item:\n";
            print_r($item);

        } catch (DynamoDbException $e) {
            echo "Unable to add item:\n";
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * scan records
     *
     * @param Request $request
     */
    public function read(Request $request)
    {
        $marshaler = new Marshaler();

        $params = [
            'TableName' => $request->get('table_name'),
            'Key' => $marshaler->marshalItem([
                'id_key' => $request->get('id_key'),
            ]),
        ];

        try {
            $result = $this->dynamodb->getItem($params);
            if(!$result['Item']) {
                echo "Data not found";
            }
            print_r($result["Item"]);

        } catch (DynamoDbException $e) {
            echo "Unable to get item:\n";
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * update record (optional: required update enabled in table)
     *
     * @param Request $request
     */
    public function update(Request $request)
    {
        $marshaler = new Marshaler();

        $columns = [
            'full_name' => $this->faker->name,
            'info' => json_encode([
                'title' => $this->faker->title,
                'email' => $this->faker->email,
                'phone' => $this->faker->phoneNumber,
                'year' => $this->faker->year,
            ])
        ];

        // Update expression for partial update
        $updateExpression = 'set ';
        foreach($columns as $column => $value) {
            $updateExpression .= $column . ' = :' . $column . ', ';
        }
        $updateExpression = substr($updateExpression, 0, -2);

        // Expression attribute values for partial update
        $expressionAttributeValues = [];
        foreach($columns as $column => $value) {
            $expressionAttributeValues[':' . $column] = $value;
        }
        $expressionAttributeValues = $marshaler->marshalJson(json_encode($expressionAttributeValues));

        $params = [
            'TableName' => $request->get('table_name'),
            'Key' => $marshaler->marshalJson(json_encode([
                'id_key' => $request->get('id_key'),
            ])),
            'UpdateExpression' => $updateExpression,
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ReturnValues' => 'UPDATED_NEW'
        ];

        try {
            $result = $this->dynamodb->updateItem($params);
            echo "Updated item.\n";
            print_r($result['Attributes']);

        } catch (DynamoDbException $e) {
            echo "Unable to update item:\n";
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * delete record
     *
     * @param Request $request
     */
    public function delete(Request $request)
    {
        $marshaler = new Marshaler();

        $params = [
            'TableName' => $request->get('table_name'),
            'Key' => $marshaler->marshalItem([
                'id_key' => $request->get('id_key'),
            ]),
            'ConditionExpression' => 'id_key = :id_key',
            'ExpressionAttributeValues' => $marshaler->marshalItem([
                ':id_key' => $request->get('id_key'),
            ]),
        ];

        try {
            $result = $this->dynamodb->deleteItem($params);
            echo "Deleted item.\n";

        } catch (DynamoDbException $e) {
            echo "Unable to delete item:\n";
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * delete table
     *
     * @param Request $request
     */
    public function deleteTable(Request $request)
    {
        $params = [
            'TableName' => $request->get('table_name'),
        ];

        try {
            $result = $this->dynamodb->deleteTable($params);
            echo "Deleted table.\n";

        } catch (DynamoDbException $e) {
            echo "Unable to delete table:\n";
            echo $e->getMessage() . "\n";
        }
    }

    //
}
