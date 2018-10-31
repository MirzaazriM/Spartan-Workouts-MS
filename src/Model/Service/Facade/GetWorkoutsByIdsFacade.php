<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 7/31/18
 * Time: 10:30 AM
 */

namespace Model\Service\Facade;

use Model\Entity\Workout;

class GetWorkoutsByIdsFacade
{

    private $ids;
    private $lang;
    private $state;
    private $connection;
    private $configuration;

    public function __construct(array $ids, string $lang, string $state, $connection, $configuration)
    {
        $this->ids = $ids;
        $this->lang = $lang;
        $this->state = $state;
        $this->connection = $connection;
        $this->configuration = $configuration;
    }


    /**
     * Get workouts data
     *
     * @return array
     */
    public function handleWorkouts(){
        // create entity and set its values
        $entity = new Workout();
        $entity->setIds($this->ids);
        $entity->setLang($this->lang);
        $entity->setState($this->state);

        // get response
        $res = $this->connection->getWorkoutsById($entity);

        // convert collection to array
        $data = $this->convertor($res);

        // return data
        return $data;
    }


    /**
     * Convert collection data to array
     *
     * @param $res
     * @return array
     */
    public function convertor($res){
        // convert collection
        $data = [];
        for($i = 0; $i < count($res); $i++){
            $data[$i]['id'] = $res[$i]->getId();
            $data[$i]['name'] = $res[$i]->getName();
            $data[$i]['description'] = $res[$i]->getDescription();
            $data[$i]['duration'] = $res[$i]->getDuration();
            $data[$i]['version'] = $res[$i]->getVersion();

            $tagTypes = explode(',', $res[$i]->getTypes());

            // get tags ids
            $tagIds = $res[$i]->getTags();

            // create guzzle client and call MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$this->lang. '&state=' .$this->state. '&ids=' .$tagIds, []);
            $tags = $result->getBody()->getContents();

            $tags = json_decode($tags);

            $fullTags = [];
            // loop through tags to add their corresponding types
            for($t = 0; $t < count($tags); $t++){

                $fullTags[$t]['id'] = $tags[$t]->id;
                $fullTags[$t]['name'] = $tags[$t]->name;
                $fullTags[$t]['language'] = $tags[$t]->language;
                $fullTags[$t]['state'] = $tags[$t]->state;
                $fullTags[$t]['behavior'] = $tags[$t]->behavior;
                $fullTags[$t]['version'] = $tags[$t]->version;
                $fullTags[$t]['workout_type'] = $tagTypes[$t];
            }

            $data[$i]['tags'] = $fullTags;

            // convert rounds to array
            $roundsCollection = $res[$i]->getRounds();

            $rounds = [];
            for($j = 0; $j < count($roundsCollection); $j++){
                $rounds[$j]['id'] = $roundsCollection[$j]->getId();
                $rounds[$j]['round'] = $roundsCollection[$j]->getRound();

                // $rounds[$j]['exercise_id'] = $roundsCollection[$j]->getExerciseId();
                $rounds[$j]['round_exercises'] = $roundsCollection[$j]->getExerciseId();

                // create guzzle client and call MS for data
                $ids = $rounds[$j]['round_exercises'];

                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['exercises_url'] . '/exercises/ids?lang=' .$this->lang. '&state=' . $this->state . '&ids=' .$ids, []);

                // set data to variable
                $exercisesData = $result->getBody()->getContents();

                //$rounds[$j]['workout_id'] = $roundsCollection[$j]->getWorkoutId();
                $rounds[$j]['duration'] = $roundsCollection[$j]->getDuration();
                $rounds[$j]['rest_duration'] = $roundsCollection[$j]->getRestDuration();
                $rounds[$j]['type'] = $roundsCollection[$j]->getType();
                $rounds[$j]['behavior'] = $roundsCollection[$j]->getBehavior();
                $rounds[$j]['round_exercises'] = json_decode($exercisesData);
            }

            $data[$i]['rounds'] = $rounds;
        }

        // return data
        return $data;
    }
}