<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:18 AM
 */

namespace Model\Mapper;

use Model\Entity\Round;
use Model\Entity\RoundCollection;
use Model\Entity\Shared;
use Model\Entity\Workout;
use Model\Entity\WorkoutCollection;
use PDO;
use PDOException;
use Component\DataMapper;
use Symfony\Component\Cache\Simple\FilesystemCache;

class WorkoutsMapper extends DataMapper
{

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }


    /**
     * Get workout
     *
     * @param Workout $workout
     * @return Workout
     */
    public function getWorkout(Workout $workout):Workout {

        // create response object
        $response = new Workout();

        try {
            // set database instructions
            $sql = "SELECT
                      w.id,
                      w.duration,
                      w.version,
                      w.state,
                      wd.description,
                      wn.name,
                      wn.language,
                      GROUP_CONCAT(DISTINCT wt.tag_id) AS tags,
                      GROUP_CONCAT(wt.type) AS types
                    FROM workout AS w
                    LEFT JOIN workout_description AS wd ON w.id = wd.workout_parent
                    LEFT JOIN workout_name AS wn ON w.id = wn.workout_parent 
                    LEFT JOIN workout_tags AS wt ON w.id = wt.workout_parent
                    WHERE w.id = ?
                    AND w.state = ?
                    AND wn.language = ?
                    AND wd.language = ?
                    GROUP BY w.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $workout->getId(),
                $workout->getState(),
                $workout->getLang(),
                $workout->getLang()
            ]);

            // fetch workout data
            $data = $statement->fetch(PDO::FETCH_ASSOC);

            // fetch rounds and set them to round collection
            $sql = "SELECT 
                      wr.id,
                      wr.round,
                      wr.duration,
                      wr.rest_duration,
                      wr.type,
                      wr.behaviour,
                      GROUP_CONCAT(DISTINCT wr.exercises_id) AS ex_ids 
                    FROM workout as w 
                    LEFT JOIN workout_rounds as wr ON w.id = wr.workout_id 
                    WHERE w.id = ?
                    GROUP BY wr.round";
            $statementRounds = $this->connection->prepare($sql);
            $statementRounds->execute([
                $workout->getId()
            ]);

            // fetch rounds
            $rounds = $statementRounds->fetchAll(PDO::FETCH_ASSOC);

            // set rounds into round entities and add them to round collection
            $roundCollection = new RoundCollection();
            foreach($rounds as $rou){
                // create round entity
                $round =  new Round();

                // set its values
                $round->setId($rou['id']);
                $round->setRound($rou['round']);
                $round->setExerciseId($rou['ex_ids']);
                $round->setDuration($rou['duration']);
                $round->setRestDuration($rou['rest_duration']);
                $round->setType($rou['type']);
                $round->setBehavior($rou['behaviour']);

                // add round to round collection
                $roundCollection->addEntity($round);
            }

            // set entity values
            if($statement->rowCount() > 0){
                $response->setId($data['id']);
                $response->setDuration($data['duration']);
                $response->setState($data['state']);
                $response->setVersion($data['version']);
                $response->setName($data['name']);
                $response->setDescription($data['description']);
                $response->setLang($data['language']);
                $response->setTags($data['tags']);
                $response->setTypes($data['types']);
                $response->setRounds($roundCollection);
            }

        }catch(PDOException $e){
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get workout mapper: " . $e->getMessage());
        }

        // return data
        return $response;
    }


    public function getList(Workout $workout){

        try {

            // get state
            $state = $workout->getState();

            // check if state is set and set query
            if($state === null or $state === ''){
                // set database instructions
                $sql = "SELECT
                        w.id,
                        w.duration,
                        w.state,
                        w.version,
                        wn.name,
                        wn.language
                    FROM workout AS w 
                    LEFT JOIN workout_name AS wn ON w.id = wn.workout_parent
                   /* WHERE wn.language = 'en' */
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set from and limit as core variables
                $from = $workout->getFrom();
                $limit = $workout->getLimit();
                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                // execute query
                $statement->execute();
            }else {
                // set database instructions
                $sql = "SELECT
                        w.id,
                        w.duration,
                        w.state,
                        w.version,
                        wn.name,
                        wn.language
                    FROM workout AS w 
                    LEFT JOIN workout_name AS wn ON w.id = wn.workout_parent
                    WHERE wn.language = :lang AND w.state = :state 
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set from and limit as core variables
                $from = $workout->getFrom();
                $limit = $workout->getLimit();
                $language = $workout->getLang();

                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                $statement->bindParam(':state', $state);
                $statement->bindParam(':lang', $language);

                // execute query
                $statement->execute();
            }

            // set data
            $data = $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch (PDOException $e){
            $data = [];
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get exercises list mapper: " . $e->getMessage());
        }

        // return data
        return $data;
    }


    /**
     * Get workouts
     *
     * @param Workout $workout
     * @return WorkoutCollection
     */
    public function getWorkouts(Workout $workout):WorkoutCollection {

        // create response object
        $workoutCollection = new WorkoutCollection();

        try {
            // set database instructions
            $sql = "SELECT
                      w.id,
                      w.state,
                      w.duration,
                      w.version,
                      wd.description,
                      wn.name,
                      wn.workout_parent,
                      wn.language,
                      GROUP_CONCAT(DISTINCT wt.tag_id) AS tags
                    FROM workout AS w
                    LEFT JOIN workout_description AS wd ON w.id = wd.workout_parent
                    LEFT JOIN workout_name AS wn ON w.id = wn.workout_parent 
                    LEFT JOIN workout_tags AS wt ON w.id = wt.workout_parent
                    WHERE w.state = ? 
                    AND wn.language = ?
                    AND wd.language = ?
                    GROUP BY w.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $workout->getState(),
                $workout->getLang(),
                $workout->getLang()
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create workout entity
                $workout = new Workout();

                // GET ROUNDS
                $sql = "SELECT 
                          w.id AS workout_id,
                          wr.id,
                          wr.round,
                          wr.duration,
                          wr.rest_duration,
                          wr.type,
                          wr.behaviour,
                          GROUP_CONCAT(DISTINCT wr.exercises_id) AS ex_ids 
                        FROM workout as w 
                        LEFT JOIN workout_rounds as wr ON w.id = wr.workout_id 
                        WHERE w.id = ?
                        GROUP BY wr.round";
                $statementRounds = $this->connection->prepare($sql);
                $statementRounds->execute([
                    $row['workout_parent']
                ]);

                // fetch rounds
                $rounds = $statementRounds->fetchAll(PDO::FETCH_ASSOC);
                //die(print_r($rounds));
                // set rounds into round entities and add them to round collection
                $roundCollection = new RoundCollection();
                foreach($rounds as $rou){
                    // create round entity
                    $round =  new Round();

                    // set its values
                    $round->setId($rou['id']);
                    $round->setRound($rou['round']);
                    $round->setExerciseId($rou['ex_ids']);
                    $round->setDuration($rou['duration']);
                    $round->setRestDuration($rou['rest_duration']);
                    $round->setType($rou['type']);
                    $round->setBehavior($rou['behaviour']);

                    // add round to round collection
                    $roundCollection->addEntity($round);
                }


                // SET WORKOUT DATA
                $workout->setId($row['workout_parent']);
                $workout->setDuration($row['duration']);
                $workout->setState($row['state']);
                $workout->setVersion($row['version']);
                $workout->setName($row['name']);
                $workout->setDescription($row['description']);
                $workout->setLang($row['language']);
                $workout->setTags($row['tags']);
                $workout->setRounds($roundCollection);

                // add workout to workout collection
                $workoutCollection->addEntity($workout);
            }

            // set response according to result of previous actions
            if($statement->rowCount() == 0){
                $workoutCollection->setStatusCode(204);
            }else {
                $workoutCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $workoutCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get workouts mapper: " . $e->getMessage());
        }

        // return data
        return $workoutCollection;
    }


    /**
     * Search workouts by app
     *
     * @param Workout $workout
     * @return WorkoutCollection
     */
    public function searchWorkouts(Workout $workout):WorkoutCollection {

        // create response object
        $workoutCollection = new WorkoutCollection();

        try {
            // set database instructions
            $sql = "SELECT
                      w.id,
                      w.state,
                      w.duration,
                      w.version,
                      wd.description,
                      wn.name,
                      wn.workout_parent,
                      wn.language,
                      GROUP_CONCAT(DISTINCT wt.tag_id) AS tags
                    FROM workout AS w
                    LEFT JOIN workout_description AS wd ON w.id = wd.workout_parent
                    LEFT JOIN workout_name AS wn ON w.id = wn.workout_parent 
                    LEFT JOIN workout_tags AS wt ON w.id = wt.workout_parent
                    WHERE w.state = ? 
                    AND wn.language = ?
                    AND wd.language = ?
                    AND wn.name LIKE ? /** ? OR wd.description LIKE ?) **/
                    GROUP BY w.id";
            $term = '%' . $workout->getName() . '%';
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $workout->getState(),
                $workout->getLang(),
                $workout->getLang(),
                $term
              /**  $term **/
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create workout entity
                $workout = new Workout();

                // GET ROUNDS
                $sql = "SELECT 
                          w.id AS workout_id,
                          wr.id,
                          wr.round,
                          wr.duration,
                          wr.rest_duration,
                          wr.type,
                          wr.behaviour,
                          GROUP_CONCAT(DISTINCT wr.exercises_id) AS ex_ids   
                        FROM workout as w 
                        LEFT JOIN workout_rounds as wr ON w.id = wr.workout_id 
                        WHERE w.id = ?
                        GROUP BY wr.round";
                $statementRounds = $this->connection->prepare($sql);
                $statementRounds->execute([
                    $row['workout_parent']
                ]);

                // fetch rounds
                $rounds = $statementRounds->fetchAll(PDO::FETCH_ASSOC);

                // set rounds into round entities and add them to round collection
                $roundCollection = new RoundCollection();
                foreach($rounds as $rou){
                    // create round entity
                    $round =  new Round();

                    // set its values
                    $round->setId($rou['id']);
                    $round->setRound($rou['round']);
                    $round->setExerciseId($rou['ex_ids']);
                    //$round->setWorkoutId($rou['workout_id']);
                    $round->setDuration($rou['duration']);
                    $round->setRestDuration($rou['rest_duration']);
                    $round->setType($rou['type']);
                    $round->setBehavior($rou['behaviour']);

                    // add round to round collection
                    $roundCollection->addEntity($round);
                }

                // SET WORKOUT DATA
                $workout->setId($row['workout_parent']);
                $workout->setDuration($row['duration']);
                $workout->setState($row['state']);
                $workout->setVersion($row['version']);
                $workout->setName($row['name']);
                $workout->setDescription($row['description']);
                $workout->setLang($row['language']);
                $workout->setTags($row['tags']);
                $workout->setRounds($roundCollection);

                // add workout to workout collection
                $workoutCollection->addEntity($workout);
            }


            // set response according to result of previous actions
            if($statement->rowCount() == 0){
                $workoutCollection->setStatusCode(204);
            }else {
                $workoutCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $workoutCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Search workouts mapper: " . $e->getMessage());
        }

        // return data
        return $workoutCollection;
    }


    /**
     * Get workouts by ids
     *
     * @param Workout $workout
     * @return WorkoutCollection
     */
    public function getWorkoutsById(Workout $workout):WorkoutCollection {

        // Create response object
        $workoutCollection = new WorkoutCollection();

        // helper function for converting array to comma separated string
        $whereIn = $this->sqlHelper->whereIn($workout->getIds());

        try {
            // set database instructions
            $sql = "SELECT
                      w.id,
                      w.state,
                      w.duration,
                      w.version,
                      wd.description,
                      wn.name,
                      wn.workout_parent,
                      wn.language,
                      GROUP_CONCAT(DISTINCT wt.tag_id) AS tags,
                      GROUP_CONCAT(wt.type) AS types
                    FROM workout AS w
                    LEFT JOIN workout_description AS wd ON w.id = wd.workout_parent
                    LEFT JOIN workout_name AS wn ON w.id = wn.workout_parent 
                    LEFT JOIN workout_tags AS wt ON w.id = wt.workout_parent
                    WHERE w.id IN (" . $whereIn . ")
                    AND w.state = ? 
                    AND wn.language = ?
                    AND wd.language = ?
                    GROUP BY w.id
                    ORDER BY FIELD(w.id, " . $whereIn . ")";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $workout->getState(),
                $workout->getLang(),
                $workout->getLang()
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create workout entity
                $workout = new Workout();

                // GET ROUNDS
                $sql = "SELECT 
                          w.id AS workout_id,
                          wr.id,
                          wr.round,
                          wr.duration,
                          wr.rest_duration,
                          wr.type,
                          wr.behaviour,
                          GROUP_CONCAT(DISTINCT wr.exercises_id) AS ex_ids  
                        FROM workout as w 
                        LEFT JOIN workout_rounds as wr ON w.id = wr.workout_id 
                        WHERE w.id = ?
                        GROUP BY wr.round";
                $statementRounds = $this->connection->prepare($sql);
                $statementRounds->execute([
                    $row['workout_parent']
                ]);

                // fetch rounds
                $rounds = $statementRounds->fetchAll(PDO::FETCH_ASSOC);

                // set rounds into round entities and add them to round collection
                $roundCollection = new RoundCollection();
                foreach($rounds as $rou){
                    // create round entity
                    $round =  new Round();

                    // set its values
                    $round->setId($rou['id']);
                    $round->setRound($rou['round']);
                    $round->setExerciseId($rou['ex_ids']);
                    $round->setDuration($rou['duration']);
                    $round->setRestDuration($rou['rest_duration']);
                    $round->setType($rou['type']);
                    $round->setBehavior($rou['behaviour']);

                    // add round to round collection
                    $roundCollection->addEntity($round);
                }

                // SET WORKOUT DATA
                $workout->setId($row['workout_parent']);
                $workout->setDuration($row['duration']);
                $workout->setState($row['state']);
                $workout->setVersion($row['version']);
                $workout->setName($row['name']);
                $workout->setDescription($row['description']);
                $workout->setLang($row['language']);
                $workout->setTags($row['tags']);
                $workout->setTypes($row['types']);
                $workout->setRounds($roundCollection);

                // add workout to workout collection
                $workoutCollection->addEntity($workout);
            }

            // set response according to result of previous actions
            if($statement->rowCount() == 0){
                $workoutCollection->setStatusCode(204);
            }else {
                $workoutCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $workoutCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get workouts by ids mapper: " . $e->getMessage());
        }

        // return data
        return $workoutCollection;
    }


    /**
     * Delete workout
     *
     * @param Workout $workout
     * @return Shared
     */
    public function deleteWorkout(Workout $workout):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "DELETE 
                      w.*,
                      wa.*,
                      wd.*,
                      wda.*,
                      wn.*,
                      wna.*,
                      wr.*,
                      wra.*, 
                      wt.*
                    FROM workout AS w
                    LEFT JOIN workout_audit AS wa ON w.id = wa.workout_parent
                    LEFT JOIN workout_description AS wd ON w.id = wd.workout_parent
                    LEFT JOIN workout_description_audit AS wda ON wd.id = wda.workout_desc_parent
                    LEFT JOIN workout_name AS wn ON w.id = wn.workout_parent
                    LEFT JOIN workout_name_audit AS wna ON wn.id = wna.workout_name_parent
                    LEFT JOIN workout_rounds AS wr ON w.id = wr.workout_id
                    LEFT JOIN workout_rounds_audit AS wra ON wr.id = wra.workout_id 
                    LEFT JOIN workout_tags AS wt ON w.id = wt.workout_parent                 
                    WHERE w.id = ?
                    AND w.state != 'R'";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $workout->getId()
            ]);

            // set response according to result of previous action
            if($statement->rowCount() > 0){
                $shared->setResponse([200]);
            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Delete workout mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Release workout
     *
     * @param Workout $workout
     * @return Shared
     */
    public function releaseWorkout(Workout $workout):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "UPDATE 
                      workout  
                    SET state = 'R'
                    WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $workout->getId()
            ]);

            // set response values
            if($statement->rowCount() > 0){
                // set response status
                $shared->setResponse([200]);

                // get latest version value
                $version = $this->lastVersion();

                // set new version of the workout
                $sql = "UPDATE workout SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute(
                    [
                        $version,
                        $workout->getId()
                    ]
                );

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Release workout mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Create workout
     *
     * @param Workout $workout
     * @return Shared
     */
    public function createWorkout(Workout $workout):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // get newest id for the verision column
            $version = $this->lastVersion();

            // set database instructions for workout table
            $sql = "INSERT INTO workout
                      (duration, state, version)
                     VALUES (?,?,?)";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $workout->getDuration(),
                'P',
                $version
            ]);

            // if first transaction passed continue with rest of inserting
            if($statement->rowCount() > 0){
                // get workout parent id
                $workoutParent = $this->connection->lastInsertId();

                // insert workout name
                $sqlName = "INSERT INTO workout_name
                              (name, language, workout_parent)
                            VALUES (?,?,?)";
                $statementName = $this->connection->prepare($sqlName);

                // insert workout description
                $sqlDescription = "INSERT INTO workout_description
                                     (description, language, workout_parent)
                                   VALUES (?,?,?)";
                $statementDescription = $this->connection->prepare($sqlDescription);

                // loop through names collection
                $names = $workout->getNames();
                foreach($names as $name){
                    // execute querys
                    $statementName->execute([
                        $name->getName(),
                        $name->getLang(),
                        $workoutParent
                    ]);

                    $statementDescription->execute([
                        $name->getDescription(),
                        $name->getLang(),
                        $workoutParent
                    ]);
                }

                // insert workout rounds
                $sqlRounds = "INSERT INTO workout_rounds
                                (round, workout_id, exercises_id, duration, rest_duration, type, behaviour)
                              VALUES (?,?,?,?,?,?,?)";
                $statementRound = $this->connection->prepare($sqlRounds);

                // loop through rounds collection
                $rounds = $workout->getRounds();
                foreach($rounds as $round){

                    $data = $round->getExerciseId();

                   // die(print_r($data));

                    foreach($data as $d){
                        // execute query
                        $statementRound->execute([
                            $round->getRound(),
                            $workoutParent,
                            $d,
                            $round->getDuration(),
                            $round->getRestDuration(),
                            $round->getType(),
                            $round->getBehavior(),
                        ]);
                    }


                }

                // insert workout tags
                $sqlTags = "INSERT INTO workout_tags
                                (workout_parent, tag_id, type)
                              VALUES (?,?,?)";
                $statementTags = $this->connection->prepare($sqlTags);

                // loop through rounds collection
                $regularTags = $workout->getTags();
                $equipmentTags = $workout->getEquipmentTags();
                $allTags = array_merge($regularTags, $equipmentTags);

                foreach($allTags as $tag){

                    // check type
                    if(in_array($tag, $regularTags)){
                        $type = 'R';
                    }else {
                        $type = 'E';
                    }

                    // execute query
                    $statementTags->execute([
                        $workoutParent,
                        $tag,
                        $type
                    ]);
                }

                $shared->setResponse([200]);

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);
// die($e->getMessage());
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Create workout mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Edit workout
     *
     * @param Workout $workout
     * @return Shared
     */
    public function editWorkout(Workout $workout):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // update main workout table
            $sql = "UPDATE workout SET duration = ? WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $workout->getDuration(),
                $workout->getId()
            ]);

            // if duration is changed, update version
            if($statement->rowCount() > 0){
                // get last version
                $lastVersion = $this->lastVersion();

                // set database instructions
                $sql = "UPDATE workout SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute([
                    $lastVersion,
                    $workout->getId()
                ]);

                // delete all cached responses
                $dir = glob("../src/Model/Service/cached_files/*");
                // $files = glob('cached_responses/*');
                foreach($dir as $file){
                    if(is_file($file))
                        unlink($file);
                }
            }

            // update names query
            $sqlNames = "INSERT INTO
                            workout_name (name, language, workout_parent)
                            VALUES (?,?,?)
                        ON DUPLICATE KEY
                        UPDATE
                            name = VALUES(name),
                            language = VALUES(language),
                            workout_parent = VALUES(workout_parent)";
            $statementNames = $this->connection->prepare($sqlNames);

            // update description query
            $sqlDescription = "INSERT INTO
                        workout_description (description, language, workout_parent)
                        VALUES (?,?,?)
                    ON DUPLICATE KEY
                    UPDATE
                        description = VALUES(description),
                        language = VALUES(language),
                        workout_parent = VALUES(workout_parent)";
            $statementDescription = $this->connection->prepare($sqlDescription);

            // loop through data and make updates if neccesary
            $names = $workout->getNames();
            foreach($names as $name){
                // execute name query
                $statementNames->execute([
                    $name->getName(),
                    $name->getLang(),
                    $workout->getId()
                ]);

                // execute description query
                $statementDescription->execute([
                    $name->getDescription(),
                    $name->getLang(),
                    $workout->getId()
                ]);
            }

            // loop through data and make updates if neccesary
            $rounds = $workout->getRounds();


            // UPDATE WORKOUT ROUNDS
            // first delete all rounds data for this workout
            $sqlDeleteRounds = "DELETE FROM workout_rounds WHERE workout_id = ?";
            $sqlDeleteRoundsStatement = $this->connection->prepare($sqlDeleteRounds);
            $sqlDeleteRoundsStatement->execute([
                $workout->getId()
            ]);

            // then insert new edited rounds
            // insert workout rounds
            $sqlRounds = "INSERT INTO workout_rounds
                                (round, workout_id, exercises_id, duration, rest_duration, type, behaviour)
                              VALUES (?,?,?,?,?,?,?)";
            $statementRound = $this->connection->prepare($sqlRounds);

            // loop through rounds collection
            $rounds = $workout->getRounds();
            foreach($rounds as $round){

                $data = $round->getExerciseId();

                // die(print_r($data));

                foreach($data as $d){
                    // execute query
                    $statementRound->execute([
                        $round->getRound(),
                        $workout->getId(),
                        $d,
                        $round->getDuration(),
                        $round->getRestDuration(),
                        $round->getType(),
                        $round->getBehavior(),
                    ]);
                }

            }

//            foreach($rounds as $round){
//                // get id of the round
//                $id = $round->getId();
//
//                // check if id is set
//                if(isset($id)){
//                    // update round
//                    $sql = "UPDATE workout_rounds SET
//                              round = ?,
//                              workout_id = ?,
//                              exercises_id = ?,
//                              duration = ?,
//                              rest_duration = ?,
//                              type = ?,
//                              behaviour = ?
//                            WHERE id = ?";
//                    $statement = $this->connection->prepare($sql);
//                    // execute query
//                    $statement->execute([
//                        $round->getRound(),
//                        $workout->getId(),
//                        $round->getExerciseId(),
//                        $round->getDuration(),
//                        $round->getRestDuration(),
//                        $round->getType(),
//                        $round->getBehavior(),
//                        $id
//                    ]);
//
//                }else {
//                    // insert round
//                    $sql = "INSERT INTO workout_rounds
//                              (round, workout_id, exercises_id, duration, rest_duration, type, behaviour)
//                              VALUES (?,?,?,?,?,?,?)";
//                    $statement = $this->connection->prepare($sql);
//                    // execute query
//                    $statement->execute([
//                        $round->getRound(),
//                        $workout->getId(),
//                        $round->getExerciseId(),
//                        $round->getDuration(),
//                        $round->getRestDuration(),
//                        $round->getType(),
//                        $round->getBehavior()
//                    ]);
//
//                }
//            }

            // update tags
//            $sqlTags = "INSERT INTO
//                            workout_tags (workout_parent, tag_id)
//                            VALUES (?,?)
//                        ON DUPLICATE KEY
//                        UPDATE
//                            workout_parent = VALUES(workout_parent),
//                            tag_id = VALUES(tag_id)";
//            $statementTags = $this->connection->prepare($sqlTags);
//
//            // loop through data and make updates if neccesary
//            $tags = $workout->getTags();
//            foreach($tags as $tag){
//                // execute query
//                $statementTags->execute([
//                    $workout->getId(),
//                    $tag
//                ]);
//            }

            // UPDATE WORKOUT TAGS
            // first delete all tags data for this workout
            $sqlDeleteTags = "DELETE FROM workout_tags WHERE workout_parent= ?";
            $sqlDeleteTagsStatement = $this->connection->prepare($sqlDeleteTags);
            $sqlDeleteTagsStatement->execute([
                $workout->getId()
            ]);


            // insert workout tags
            $sqlTags = "INSERT INTO workout_tags
                                (workout_parent, tag_id, type)
                              VALUES (?,?,?)";
            $statementTags = $this->connection->prepare($sqlTags);

            // loop through rounds collection
            $regularTags = $workout->getTags();
            $equipmentTags = $workout->getEquipmentTags();
            $allTags = array_merge($regularTags, $equipmentTags);

            foreach($allTags as $tag){

                // check type
                if(in_array($tag, $regularTags)){
                    $type = 'R';
                }else {
                    $type = 'E';
                }

                // execute query
                $statementTags->execute([
                    $workout->getId(),
                    $tag,
                    $type
                ]);
            }


//            $sqlTags = "INSERT INTO workout_tags
//                                (workout_parent, tag_id, type)
//                                 VALUES (?,?,?)
//                        ON DUPLICATE KEY
//                        UPDATE
//                            workout_parent = VALUES(workout_parent),
//                            tag_id = VALUES(tag_id),
//                            type = VALUES(type)";
//            $statementTags = $this->connection->prepare($sqlTags);
//
//            // loop through rounds collection
//            $regularTags = $workout->getTags();
//            $equipmentTags = $workout->getEquipmentTags();
//            $allTags = array_merge($regularTags, $equipmentTags);
//
//            foreach($allTags as $tag){
//
//                // check type
//                if(in_array($tag, $regularTags)){
//                    $type = 'R';
//                }else {
//                    $type = 'E';
//                }
//
//                // execute query
//                $statementTags->execute([
//                    $workout->getId(),
//                    $tag,
//                    $type
//                ]);
//            }

            // commit transaction
            $this->connection->commit();

            // set response
            $shared->setResponse([200]);

        }catch(PDOException $e){
            // rollback everything n case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);
die($e->getMessage());
            // send monolog record  in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Edit workout mapper: " . $e->getMessage());
        }

        // return data
        return $shared;
    }


    /**
     * Get total number of workouts
     *
     * @return null
     */
    public function getTotal() {

        try {
            // set database instructions
            $sql = "SELECT COUNT(*) as total FROM workout";
            $statement = $this->connection->prepare($sql);
            $statement->execute();

            // set total number
            $total = $statement->fetch(PDO::FETCH_ASSOC)['total'];

        }catch(PDOException $e){
            $total = null;
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get total workouts mapper: " . $e->getMessage());
        }

        // return data
        return $total;
    }


    /**
     * Get last version number
     *
     * @return string
     */
    public function lastVersion(){
        // set database instructions
        $sql = "INSERT INTO version VALUES(null)";
        $statement = $this->connection->prepare($sql);
        $statement->execute([]);

        // fetch id
        $lastId = $this->connection->lastInsertId();

        // return last id
        return $lastId;
    }
}