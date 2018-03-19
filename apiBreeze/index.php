<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once 'vendor/com.rapiddigitalllc/breezechms/api.php';

/**
 * @author Daniel Boorn <daniel.boorn@gmail.com>
 * @copyright Rapid Digital LLC <www.rapiddigitalllc.com>
 * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 **/


$api = BreezeChms\API::forge(array(
    'key'     => '6a9570397f8aae62b56c960695fba077', // set api key here
    'baseUrl' => 'https://inchurchphoenix.breezechms.com',
));

# Example - list people
$people = $api->people()->list()->get();
echo($people)
var_dump($people);

# Example - show person
$person = $api->people($people[0]['id'])->show()->get();
var_dump($person);

# Example - list profile fields
$profileFields = $api->profile()->fields()->list()->get();
var_dump($profileFields);

# Example - list events
$events = $api->events()->list(array(
    'start' => '10-01-2015',
    'end'   => '12-28-2015',
))->get();
var_dump(count($events), $events);

# Example - add attendance record
$record = $api->attendance()->record()->create(array(
    'person_id'   => 'person id here',
    'instance_id' => 'instance id here',
))->get();

# Example - remove attenance record
$api->attendance()->record()->delete(array(
    'person_id'   => 'person id here',
    'instance_id' => 'instance id here',
))->get();

# Example - list contributions
$contributions = $api->contributions()->list(array(
    'start' => date('c', strtotime('-1 year')),
    'end'   => date('c', strtotime('-15 days')), // end filter appears broken on API
))->get();
var_dump($contributions);

# Example - record contribution
$r = $api->contributions()->create(array(
    'date'         => date('j-n-Y'), // d-m-Y
    'person_id'    => $people[0]['id'],
    'name'         => "{$people[0]['first_name']} {$people[0]['last_name']}",
    'note'         => 'testing',
    'uid'          => null,
    'processor'    => null,
    'method'       => 'Credit/Debit Online',
    'funds_json'   => json_encode(array(array(
        'id'     => '33311',
        'amount' => '103.25',
    ))),
    'amount'       => 103.25,
    'group'        => null,
    'batch_number' => null,
    'batch_name'   => 'Test',
))->get();
var_dump($r);

# Example - list funds
$funds = $api->funds()->list()->get();
var_dump($funds);
