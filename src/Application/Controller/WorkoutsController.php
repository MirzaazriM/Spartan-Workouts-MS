<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:18 AM
 */

namespace Application\Controller;


use Model\Entity\Names;
use Model\Entity\NamesCollection;
use Model\Entity\ResponseBootstrap;
use Model\Entity\Round;
use Model\Entity\RoundCollection;
use Model\Service\WorkoutsService;
use Symfony\Component\HttpFoundation\Request;

class WorkoutsController
{

    private $workoutsService;

    public function __construct(WorkoutsService $workoutsService)
    {
        $this->workoutsService = $workoutsService;
    }


    /**
     * Get single workout
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($id) && isset($lang) && isset($state)){
            return $this->workoutsService->getWorkout($id, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * get list of exercises
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getList(Request $request):ResponseBootstrap {
        // get data
        $from = $request->get('from');
        $limit = $request->get('limit');
        $state = $request->get('state');
        $lang = $request->get('lang');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($from) && isset($limit)){
            return $this->workoutsService->getListOfWorkouts($from, $limit, $state, $lang);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get workouts by parameters
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getWorkouts(Request $request):ResponseBootstrap {
        // get data
        $lang = $request->get('lang');
        $app = $request->get('app');
        $like = $request->get('like');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is present
        if(!empty($lang) && !empty($state)){
            return $this->workoutsService->getWorkouts($lang, $state, $app, $like);
        }else{
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get workouts by ids
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getIds(Request $request):ResponseBootstrap {
        // get data
        $ids = $request->get('ids');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // convert ids string to array
        $ids = explode(',', $ids);

        // create response object
        $response = new ResponseBootstrap();

        // check if data is present
        if(!empty($ids) && !empty($lang) && !empty($state)){
            return $this->workoutsService->getWorkoutsById($ids, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Delete workout
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function delete(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is present
        if(isset($id)){
            return $this->workoutsService->deleteWorkout($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Delete workouts cache
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function deleteCache(Request $request):ResponseBootstrap {
        // call service function
        return $this->workoutsService->deleteCache();
    }


    /**
     * Release workout
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function postRelease(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];

        // create response object in case of failure
        $response = new ResponseBootstrap();

        // check if data is present
        if(isset($id)){
            return $this->workoutsService->releaseWorkout($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Add workout
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function post(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $names = $data['names'];
        $regularTags = $data['regular_tags'];
        $equipmentTags = $data['equipment_tags'];
        $rounds = $data['rounds'];
        $duration = $data['duration'];

        // create name collection
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name['name']);
            $temp->setDescription($name['description']);
            $temp->setLang($name['lang']);

            $namesCollection->addEntity($temp);
        }

        // create round collection
        $roundCollection = new RoundCollection();
        // set rounds into rounds collection
        foreach($rounds as $round){
            $temp = new Round();
            $temp->setRound($round['round']);
            $temp->setExerciseId($round['exercise_id']);
            $temp->setDuration($round['duration']);
            $temp->setRestDuration($round['rest_duration']);
            $temp->setType($round['type']);
            $temp->setBehavior($round['behavior']);

            $roundCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check data
        if(isset($namesCollection) && isset($roundCollection) && isset($regularTags) && isset($equipmentTags) && isset($duration)){
            return $this->workoutsService->createWorkout($namesCollection, $roundCollection, $regularTags, $equipmentTags, $duration);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Edit workout
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function put(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $names = $data['names'];
        $regularTags = $data['regular_tags'];
        $equipmentTags = $data['equipment_tags'];
        $rounds = $data['rounds'];
        $duration = $data['duration'];

        // create name collection
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name['name']);
            $temp->setDescription($name['description']);
            $temp->setLang($name['lang']);

            $namesCollection->addEntity($temp);
        }

        // create round collection
        $roundCollection = new RoundCollection();
        // set rounds into rounds collection
        foreach($rounds as $round){
            $temp = new Round();
            $temp->setRound($round['round']);
            $temp->setExerciseId($round['exercise_id']);
            $temp->setDuration($round['duration']);
            $temp->setRestDuration($round['rest_duration']);
            $temp->setType($round['type']);
            $temp->setBehavior($round['behavior']);

            $roundCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check data
        if(isset($id) && isset($namesCollection) && isset($roundCollection) && isset($regularTags) && isset($equipmentTags) && isset($duration)){
            return $this->workoutsService->editWorkout($id, $namesCollection, $roundCollection, $regularTags, $equipmentTags, $duration);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Return response
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getTotal(Request $request):ResponseBootstrap {
        // call service for response
        return $this->workoutsService->getTotal();
    }

}