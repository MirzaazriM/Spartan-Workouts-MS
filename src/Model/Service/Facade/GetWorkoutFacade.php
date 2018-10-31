<?php

namespace Model\Service\Facade;

use Model\Entity\Workout;
use Model\Entity\WorkoutCollection;
use Model\Mapper\WorkoutsMapper;

class GetWorkoutFacade
{

    private $lang;
    private $app;
    private $like;
    private $state;
    private $workoutMapper;
    private $configuration;


    public function __construct(string $lang, string $app = null, string $like = null, string $state, WorkoutsMapper $workoutMapper) {
        $this->lang = $lang;
        $this->app = $app;
        $this->like = $like;
        $this->state = $state;
        $this->workoutMapper = $workoutMapper;
        $this->configuration = $workoutMapper->getConfiguration();
    }


    /**
     * Handle workouts
     *
     * @return mixed|WorkoutCollection|null
     */
    public function handleWorkouts() {
        $data = null;

        // Calling By App
        if(!empty($this->app)){
            $data = $this->getWorkoutsByApp();
        }
        // Calling by Search
        else if(!empty($this->like)){
            $data = $this->searchWorkouts();
        }
        // Calling by State
        else{
            $data = $this->getWorkouts();
        }

        // return data
        return $data;
    }


    /**
     * Get workouts
     *
     * @return WorkoutCollection
     */
    public function getWorkouts():WorkoutCollection {
        // create entity and set its values
        $entity = new Workout();
        $entity->setLang($this->lang);
        $entity->setState($this->state);

        // call mapper for data
        $collection = $this->workoutMapper->getWorkouts($entity);

        // return data
        return $collection;
    }


    /**
     * Get workouts by app
     *
     * @return mixed
     */
    public function getWorkoutsByApp() {
        // call apps MS for data
        $client = new \GuzzleHttp\Client();
        $result = $client->request('GET', $this->configuration['apps_url'] . '/apps/data?app=' . $this->app . '&lang=' . $this->lang . '&state=' . $this->state . '&type=workouts', []);
        $data = json_decode($result->getBody()->getContents(), true);

        // return data
        return $data;
    }


    /**
     * Search workouts
     *
     * @return WorkoutCollection
     */
    public function searchWorkouts():WorkoutCollection {
        // create entity and set its values
        $entity = new Workout();
        $entity->setLang($this->lang);
        $entity->setState($this->state);
        $entity->setName($this->like);

        // call mapper for data
        $data = $this->workoutMapper->searchWorkouts($entity);

        // return data
        return $data;
    }

}