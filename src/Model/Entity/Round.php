<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 5:55 PM
 */

namespace Model\Entity;


use Model\Contract\HasId;

class Round implements HasId, \Countable
{

    private $id;
    private $round;
    private $workoutId;
    private $exerciseId;
    private $duration;
    private $type;
    private $behavior;
    private $restDuration;
    private $state;


    public function count()
    {
        // TODO: Implement count() method.
    }

    /**
     * @return mixed
     */
    public function getWorkoutId()
    {
        return $this->workoutId;
    }

    /**
     * @param mixed $workoutId
     */
    public function setWorkoutId($workoutId): void
    {
        $this->workoutId = $workoutId;
    }

    /**
     * @return mixed
     */
    public function getBehavior()
    {
        return $this->behavior;
    }

    /**
     * @param mixed $behavior
     */
    public function setBehavior($behavior): void
    {
        $this->behavior = $behavior;
    }

    /**
     * @return mixed
     */
    public function getRestDuration()
    {
        return $this->restDuration;
    }

    /**
     * @param mixed $restDuration
     */
    public function setRestDuration($restDuration): void
    {
        $this->restDuration = $restDuration;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state): void
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getRound()
    {
        return $this->round;
    }

    /**
     * @param mixed $round
     */
    public function setRound($round): void
    {
        $this->round = $round;
    }

    /**
     * @return mixed
     */
    public function getExerciseId()
    {
        return $this->exerciseId;
    }

    /**
     * @param mixed $exerciseId
     */
    public function setExerciseId($exerciseId): void
    {
        $this->exerciseId = $exerciseId;
    }

    /**
     * @return mixed
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param mixed $duration
     */
    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }


}