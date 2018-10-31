<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:18 AM
 */

namespace Model\Service;

use Component\LinksConfiguration;
use Model\Core\Helper\Monolog\MonologSender;
use Model\Entity\NamesCollection;
use Model\Entity\ResponseBootstrap;
use Model\Entity\RoundCollection;
use Model\Entity\Workout;
use Model\Mapper\WorkoutsMapper;
use Model\Service\Facade\GetWorkoutFacade;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;

class WorkoutsService extends LinksConfiguration
{

    private $workoutsMapper;
    private $configuration;
    private $monologHelper;

    public function __construct(WorkoutsMapper $workoutsMapper)
    {
        $this->workoutsMapper = $workoutsMapper;
        $this->configuration = $workoutsMapper->getConfiguration();
        $this->monologHelper = new MonologSender();
    }


    /**
     * Get workout service
     *
     * @param int $id
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getWorkout(int $id, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Workout();
            $entity->setId($id);
            $entity->setLang($lang);
            $entity->setState($state);

            // get mapper response
            $res = $this->workoutsMapper->getWorkout($entity);
            $id = $res->getId();

            $types = $res->getTypes();
            $types = explode(',', $types);
          //  die(print_r($res->getTypes()));

            $roundsCollection = $res->getRounds();

            // create exerciseIds and round variables
            $exerciseIds = [];
            $rounds = [];

            // set counter start position
            $i = 0;

            // TODO check these loops

            // loop through rounds
            foreach($roundsCollection->getPool() as $round){

                if($round != null){
                    $rounds[$i]['id'] = $round->getId();
                    $rounds[$i]['round'] = $round->getRound();

                    // get exercise ids
                    $exerciseIds = $round->getExerciseId();

                    // call exercise MS for exercise data
                    $client = new \GuzzleHttp\Client();
                    $result = $client->request('GET', $this->configuration['exercises_url'] . '/exercises/ids?lang=' .$lang. '&state=R' . '&ids=' .$exerciseIds, []);
                    $data = $result->getBody()->getContents();

                    //$rounds[$i]['workout_id'] = $roundsCollection[$i]->getWorkoutId();
                    $rounds[$i]['duration'] = $round->getDuration();
                    $rounds[$i]['rest_duration'] = $round->getRestDuration();
                    $rounds[$i]['type'] = $round->getType();
                    $rounds[$i]['behavior'] = $round->getBehavior();
                    $rounds[$i]['exercises'] = json_decode($data);
                    $i++;
                }
            }

            // convert rounds collection to array
            $exerciseIds = [];
            $rounds = [];

            for($i = 0; $i < count($roundsCollection); $i++){
                $rounds[$i]['id'] = $roundsCollection[$i]->getId();
                $rounds[$i]['round'] = $roundsCollection[$i]->getRound();

                $exerciseIds = $roundsCollection[$i]->getExerciseId();

                // create guzzle client and call MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['exercises_url'] . '/exercises/ids?lang=' .$lang. '&state=R' . '&ids=' .$exerciseIds, []);

                //set data to variable
                $data = $result->getBody()->getContents();

                //$rounds[$i]['workout_id'] = $roundsCollection[$i]->getWorkoutId();
                $rounds[$i]['duration'] = $roundsCollection[$i]->getDuration();
                $rounds[$i]['rest_duration'] = $roundsCollection[$i]->getRestDuration();
                $rounds[$i]['type'] = $roundsCollection[$i]->getType();
                $rounds[$i]['behavior'] = $roundsCollection[$i]->getBehavior();
                $rounds[$i]['exercises'] = json_decode($data);
            }

            // get tag ids
            $tagIds = $res->getTags();

            // call tags MS for getting tags data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
            $tags = $result->getBody()->getContents();

            $tags = json_decode($tags);

            //die(print_r($tags));
//
//            $fullTags = [];
//            // loop through tags to add their corresponding types
            for ($t = 0; $t < count($tags); $t++) {
                $tags[$t]->workout_type = $this->checkType($tags[$t]->id, $types, $tagIds); // $tagTypes[$t];
            }


            // check data and set response
            if(isset($id)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    'id' => $res->getId(),
                    'name' => $res->getName(),
                    'description' => $res->getDescription(),
                    'duration' => $res->getDuration(),
                    'version' => $res->getVersion(),
                    'tags' => $tags,
                    'rounds' => $rounds
                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get workout service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }

    }


    /**
     * Get list of workouts
     *
     * @param int $from
     * @param int $limit
     * @return ResponseBootstrap
     */
    public function getListOfWorkouts(int $from, int $limit, string $state = null, string $lang = null):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Workout();
            $entity->setFrom($from);
            $entity->setLimit($limit);
            $entity->setState($state);
            $entity->setLang($lang);

            // call mapper for data
            $data = $this->workoutsMapper->getList($entity);

            // set response according to data content
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get workouts list service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get workouts by paramaetars
     *
     * @param string $lang
     * @param string|null $app
     * @param string|null $like
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getWorkouts(string $lang, string $state, string $app = null, string $like = null):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create facade and call its functions for data
            $facade = new GetWorkoutFacade($lang, $app, $like, $state, $this->workoutsMapper);
            $res = $facade->handleWorkouts();

            // check by which parametar data to fetch
            if(gettype($res) === 'object'){
                // convert collection to array
                $data = [];
                for($i = 0; $i < count($res); $i++){
                    $data[$i]['id'] = $res[$i]->getId();
                    $data[$i]['name'] = $res[$i]->getName();
                    $data[$i]['description'] = $res[$i]->getDescription();
                    $data[$i]['duration'] = $res[$i]->getDuration();
                    $data[$i]['version'] = $res[$i]->getVersion();
                    $data[$i]['state'] = $res[$i]->getState();
                    $data[$i]['language'] = $res[$i]->getLang();

                    // get tag ids
                    $tagIds = $res[$i]->getTags();

                    // call tags MS for tags data
                    $client = new \GuzzleHttp\Client();
                    $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
                    $tags = $result->getBody()->getContents();
                    $data[$i]['tags'] = json_decode($tags);

                    // convert rounds to array
                    $roundsCollection = $res[$i]->getRounds();
                    $rounds = [];
                    for($j = 0; $j < count($roundsCollection); $j++){
                        $rounds[$j]['id'] = $roundsCollection[$j]->getId();
                        $rounds[$j]['round'] = $roundsCollection[$j]->getRound();
                        $rounds[$j]['round_exercises'] = $roundsCollection[$j]->getExerciseId();

                        // get round_exercises ids
                        $ids = $rounds[$j]['round_exercises'];

                        // call exercises MS for exercises data
                        $client = new \GuzzleHttp\Client();
                        $result = $client->request('GET', $this->configuration['exercises_url'] . '/exercises/ids?lang=' .$lang. '&state=R' . '&ids=' .$ids, []);
                        $exercisesData = $result->getBody()->getContents();

                        $rounds[$j]['duration'] = $roundsCollection[$j]->getDuration();
                        $rounds[$j]['rest_duration'] = $roundsCollection[$j]->getRestDuration();
                        $rounds[$j]['type'] = $roundsCollection[$j]->getType();
                        $rounds[$j]['behavior'] = $roundsCollection[$j]->getBehavior();
                        $rounds[$j]['round_exercises'] = json_decode($exercisesData);
                    }

                    $data[$i]['rounds'] = $rounds;
                }

            }else if(gettype($res) === 'array'){
                $data = $res;
            }


            // Check Data and Set Response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get workouts service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }

    }


    /**
     * Get workouts by ids
     *
     * @param array $ids
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getWorkoutsById(array $ids, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // save ids array for mapper
            $idsMapper = $ids;

            // set ids as a string
            $identifier = implode(',', $ids);

            // create cashing adapter
            $cache = new PhpArrayAdapter(
            // single file where values are cached
                __DIR__ . '/cached_files/' . $identifier . '.cache',
                // a backup adapter, if you set values after warmup
                new FilesystemAdapter()
            );

            // get identifier
            $ids_identifier = $cache->getItem($identifier);

            // loop through cached responses and check if there is an identifier match
            $dir = "../src/Model/Service/cached_files/*";
            foreach(glob($dir) as $file)
            {
                $filenamePartOne = substr($file, 34);
                $position = strpos($filenamePartOne, '.');
                $filename = substr($filenamePartOne, 0, $position);

                // check if filename is equal to the given ids
                if($ids_identifier->getKey() == $filename){
                    // if yes get cached data
                    $cacheItem = $cache->getItem('raw.exercises');
                    $data = $cacheItem->get();
                }
            }


            // if there is no cached response fetch data from database and other MSs
            if(empty($data)) {
                // create entity
                $entity = new Workout();
                $entity->setIds($idsMapper);
                $entity->setLang($lang);
                $entity->setState($state);

                // get response from database
                $res = $this->workoutsMapper->getWorkoutsById($entity);

                // convert collection to array
                $data = [];
                for ($i = 0; $i < count($res); $i++) {
                    $data[$i]['id'] = $res[$i]->getId();
                    $data[$i]['name'] = $res[$i]->getName();
                    $data[$i]['description'] = $res[$i]->getDescription();
                    $data[$i]['duration'] = $res[$i]->getDuration();
                    $data[$i]['version'] = $res[$i]->getVersion();

                    $tagTypes = explode(',', $res[$i]->getTypes());

                    // get tag ids
                    $tagIds = $res[$i]->getTags();

                    // call tags MS for tags data
                    $client = new \GuzzleHttp\Client();
                    $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' . $lang . '&state=R' . '&ids=' . $tagIds, []);
                    $tags = $result->getBody()->getContents();
                    $tags = json_decode($tags);

                    $fullTags = [];
                    // loop through tags to add their corresponding types
                    for ($t = 0; $t < count($tags); $t++) {
                        $fullTags[$t]['id'] = $tags[$t]->id;
                        $fullTags[$t]['name'] = $tags[$t]->name;
                        $fullTags[$t]['language'] = $tags[$t]->language;
                        $fullTags[$t]['state'] = $tags[$t]->state;
                        $fullTags[$t]['behavior'] = $tags[$t]->behavior;
                        $fullTags[$t]['version'] = $tags[$t]->version;
                        $fullTags[$t]['workout_type'] = $this->checkType($tags[$t]->id, $tagTypes, $tagIds); // $tagTypes[$t];
                    }

                    $data[$i]['tags'] = $fullTags;

                    // convert rounds to array
                    $roundsCollection = $res[$i]->getRounds();
                    $rounds = [];
                    for ($j = 0; $j < count($roundsCollection); $j++) {
                        $rounds[$j]['id'] = $roundsCollection[$j]->getId();
                        $rounds[$j]['round'] = $roundsCollection[$j]->getRound();
                        $rounds[$j]['round_exercises'] = $roundsCollection[$j]->getExerciseId();

                        // get round exercises ids
                        $ids = $rounds[$j]['round_exercises'];

                        // call exercises MS for exercises data
                        $client = new \GuzzleHttp\Client();
                        $result = $client->request('GET', $this->configuration['exercises_url'] . '/exercises/ids?lang=' . $lang . '&state=R' . '&ids=' . $ids, []);
                        $exercisesData = $result->getBody()->getContents();

                        $rounds[$j]['duration'] = $roundsCollection[$j]->getDuration();
                        $rounds[$j]['rest_duration'] = $roundsCollection[$j]->getRestDuration();
                        $rounds[$j]['type'] = $roundsCollection[$j]->getType();
                        $rounds[$j]['behavior'] = $roundsCollection[$j]->getBehavior();
                        $rounds[$j]['round_exercises'] = json_decode($exercisesData);
                    }

                    $data[$i]['rounds'] = $rounds;
                }

                // cache data
                $values = array(
                    'id' => $identifier,
                    'raw.exercises' => $data,
                );
                $cache->warmUp($values);
            }


            // Check Data and Set Response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get workouts by ids service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Check workout type for each tag
     *
     * @param $id
     * @param $tagTypes
     * @param $tagIds
     * @return mixed
     */
    public function checkType($id, $tagTypes, $tagIds){
        // make string to array
        $tagIds = explode(',', $tagIds);

        // create associative array
        $arr = array_combine($tagIds, $tagTypes);

        // return type
        return $arr[$id];
    }



    /**
     * Delete workout by id
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function deleteWorkout(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Workout();
            $entity->setId($id);

            // get response from database
            $res = $this->workoutsMapper->deleteWorkout($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Delete workout service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Delete workouts cache
     *
     * @return ResponseBootstrap
     */
    public function deleteCache():ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // delete all cached responses
            $dir = glob("../src/Model/Service/cached_files/*");
            // $files = glob('cached_responses/*');
            foreach($dir as $file){
                if(is_file($file))
                    unlink($file);
            }

            // set response
            $response->setStatus(200);
            $response->setMessage('Success');

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Delete cache service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Release workout
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function releaseWorkout(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Workout();
            $entity->setId($id);

            // get response from database
            $res = $this->workoutsMapper->releaseWorkout($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Release workout service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Create new workout
     *
     * @param NamesCollection $names
     * @param RoundCollection $roundCollection
     * @param array $tags
     * @param int $duration
     * @return ResponseBootstrap
     */
    public function createWorkout(NamesCollection $names, RoundCollection $roundCollection, array $regularTags, array $equipmentTags, int $duration):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Workout();
            $entity->setTags($regularTags);
            $entity->setEquipmentTags($equipmentTags);
            $entity->setNames($names);
            $entity->setRounds($roundCollection);
            $entity->setDuration($duration);

            // get response from database
            $res = $this->workoutsMapper->createWorkout($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Create workout service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Edit workout
     *
     * @param int $id
     * @param NamesCollection $names
     * @param RoundCollection $roundCollection
     * @param array $tags
     * @param int $duration
     * @return ResponseBootstrap
     */
    public function editWorkout(int $id, NamesCollection $names, RoundCollection $roundCollection, array $regularTags, array $equipmentTags, int $duration):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Workout();
            $entity->setId($id);
            $entity->setTags($regularTags);
            $entity->setEquipmentTags($equipmentTags);
            $entity->setNames($names);
            $entity->setRounds($roundCollection);
            $entity->setDuration($duration);

            // get response from database
            $res = $this->workoutsMapper->editWorkout($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Edit workout service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get total number of workouts
     *
     * @return ResponseBootstrap
     */
    public function getTotal():ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // get data from database
            $data = $this->workoutsMapper->getTotal();

            // check data and set response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    $data
                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get total workouts service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }

}