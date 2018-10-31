<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 8/6/18
 * Time: 2:56 PM
 */

namespace Model\Core\Helper\Monolog;


class MonologSender
{

    public function sendMonologRecord($configuration, $code, $record) {
        // check code and set type
        if((int)$code >= 1000 && (int)$code <= 1749){
            $type = 'ERROR';
        }else {
            $type = 'WARNING';
        }

        // create guzzle client and send it data
        $client = new \GuzzleHttp\Client();
        $client->post($configuration['monolog_url'] . '/monolog/add',
            [
                \GuzzleHttp\RequestOptions::JSON => [
                    'microservice' => 'WORKOUTS',
                    'type' => $type,
                    'record' => $record
                ]
            ]);
    }
}