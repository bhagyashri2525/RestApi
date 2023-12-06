<?php
namespace App\Services\Sandbox;

use BaoPham\DynamoDb\Facades\DynamoDb as DD;
use Illuminate\Support\Facades\Storage;

class DBService{

    private $client;
    private $config = [];
    

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        if(env('DYNAMODB_LOCAL')) {
            $this->config['endpoint'] = env('DYNAMODB_LOCAL_ENDPOINT');
        }
        //$this->client = App::make('aws')->createClient('dynamodb', $this->config);
        $this->client = DD::client();
        
    }

    public function jsonBackup(Array $tables = [],$all = false){
        $dbTables = $this->getAllTables();
        if(!empty($dbTables) && !empty($tables) && $all == false){
            foreach($tables as $table){
                if(in_array($table,$dbTables)){
                    $this->scan($table);
                    echo "\n";
                }
            }
        }else if($all){
            $tables = $this->getAllTables();
            echo "<pre>";
            print_r($tables);
            
            foreach($tables as $table){
                echo "\n\n";
                $this->scan($table);
            } 
        }
    }

    public function restoreTables(Array $tables = [], $all = false){
        print_r($tables);
        //$dbTables = $this->getAllTables();
        if(!empty($tables) && $all == false){
            foreach($tables as $table){
                //if(in_array($table,$dbTables)){
                    //$this->deleteTable($table);
                    echo "\n";
                    if(Storage::disk('disk_path')->exists('database/dynamodb_backup/'.$table.'.json')){
                        $tableJson = Storage::disk('disk_path')->get('database/dynamodb_backup/'.$table.'.json');
                        $tableItems = json_decode($tableJson,true);
                        //print_r($tableItems);
                        foreach($tableItems as $item){
                            $this->addItem($table,$item);
                        }
                    }else{
                        echo "\n ".$table.".json not found. \n";
                    }
                //}
            }
        }else if($all){
            $tables = $this->getAllTables();
            echo "<pre>";
            print_r($tables);

            foreach($tables as $table){
                echo "\n";
                if(Storage::disk('disk_path')->exists('database/dynamodb_backup/'.$table.'.json')){
                    $tableJson = Storage::disk('disk_path')->get('database/dynamodb_backup/'.$table.'.json');
                    $tableItems = json_decode($tableJson,true);
                    //print_r($tableItems);
                    foreach($tableItems as $item){
                        $this->addItem($table,$item);
                    }
                }else{
                    echo "\n ".$table.".json not found. \n";
                }
            }
            
        }
    }

    public function getAllTables() {
        $output = [];
        $tables = $this->client->listTables();
        foreach($tables['TableNames'] as $name=>$table) {
            $output[] = $table;
        }
        return $output;
    }

    private function createTable($table){
        $schema = [
            "AttributeDefinitions" => [
                [
                    "AttributeName" => "id", 
                    "AttributeType" => "S"
                ]
            ], 
            "TableName" => $table, 
            "KeySchema" => [
                [
                    "AttributeName" => "id", 
                    "KeyType" => "HASH"
                ]
            ],
            "ProvisionedThroughput" => [
                "ReadCapacityUnits" => 1, 
                "WriteCapacityUnits" => 1
            ],
            "StreamSpecification" => [
                "StreamEnabled" => true,
                "StreamViewType" => "NEW_AND_OLD_IMAGES"
            ],
            "Tags" => [
                [ "Key" => "AWSTagKey", "Value" => "SomeCustomValue" ],
            ]
        ];

        $table = $this->client->createTable($schema);
        echo "\n ".$table." created. \n";
    }

    private function addItem($table,$item){
        echo "\n ";
        print_r($item);
        echo "\n";
        
       
        $response = $this->client->putItem(array(
            'TableName' => $table, 
            'Item' => $item
            // 'Item' => array(
            //     'eid'         => array('N'       =>  01      ), 
            //     'name'        => array('S'       => 'emp 01' ),
            //     'department'  => array('S'       => 'dep 1' ),
            //     'age'         => array('N'       =>  44 ),
            //     'gender'      => array('S'       => 'male' ),
            //     'address'     => array('S'       => 'Address 1 ' ),
            //     'salary'      => array('N'       =>  100000  )
            // )
        ));
        echo '<pre>';
        //print_r($response);
        //exit();
        
    }

    public function scan($table){
        echo "Scanning: " . $table . "\n";
        $items = $this->client->getIterator('Scan', [
            'TableName' => $table,
        ]);
        $output = [];
        foreach($items as $item) {
           $output[] = $item;
        }
        //print_r($output);
        $this->backupTable($table,json_encode($output));
        echo $table.'.json created.';
        echo "\n\n\n\n";
    }

    public function backupTable($table,$jsonData){
        Storage::disk('disk_path')->put('database/dynamodb_backup/'.$table.'.json', $jsonData);
    }

    public function deleteTable($table){

        $this->client->deleteTable(array(
            'TableName' => $table
        ));

        echo "\n ".$table." deleted. \n"; 
    }
}