<?php
class MulticastManager extends FOGService {
    public $dev = '';
    public $log = '';
    public $zzz = '';
    public $sleeptime = 'MULTICASTSLEEPTIME';
    public function __construct() {
        parent::__construct();
        $this->log = sprintf('%s%s',$this->logpath,$this->getSetting('MULTICASTLOGFILENAME'));
        $this->dev = $this->getSetting('MULTICASTDEVICEOUTPUT');
        $this->zzz = (int)$this->getSetting($this->sleeptime);
    }
    private function isMCTaskNew($KnownTasks, $id) {
        foreach((array)$KnownTasks AS $i => &$Known) $output[] = $Known->getID();
        unset($Known);
        return !in_array($id,(array)$output);
    }
    private function getMCExistingTask($KnownTasks, $id) {
        foreach((array)$KnownTasks AS $i => &$Known) {
            if ($Known->getID() == $id) return $Known;
        }
        unset($Known);
    }
    private function removeFromKnownList($KnownTasks, $id) {
        $new = array();
        foreach((array)$KnownTasks AS $i => $Known) {
            if ($Known->getID() != $id) $new[] = $Known;
        }
        unset($Known);
        return array_filter((array)$new);
    }
    private function getMCTasksNotInDB($KnownTasks, $AllTasks) {
        $ret = $allIDs = array();
        foreach ((array)$AllTasks AS $i => &$AllTask) {
            if ($AllTask && $AllTask->getID()) $allIDs[] = $AllTask->getID();
            unset($AllTask);
        }
        foreach ((array)$KnownTasks AS $i => &$Known) {
            if (!in_array($Known->getID(),(array)$allIDs)) $ret[] = $Known;
            unset($Known);
        }
        return array_filter((array)$ret);
    }
    private function serviceLoop() {
        while(true) {
            try {
                $StorageNode = $this->checkIfNodeMaster();
                $myroot = $StorageNode->get('path');
                $taskCount = static::getClass('MulticastSessionsManager')->count(array('stateID'=>array_merge($this->getQueuedStates(),(array)$this->getProgressState())));
                if ($taskCount != $oldCount) $allTasks = static::getClass('MulticastTask')->getAllMulticastTasks($myroot,$StorageNode->get('id'));
                $RMTasks = $this->getMCTasksNotInDB($KnownTasks,$allTasks);
                if (!count($RMTasks) && (!$taskCount || $taskCount < 0)) throw new Exception(' * No tasks found!');
                $jobcancelled = false;
                $this->outall(sprintf(" | %d task%s to be cleaned",count($RMTask),count($RMTask) != 1 ? 's' : ''));
                if (count($RMTasks)) {
                    $this->outall(sprintf(" | Cleaning %s task(s) removed from FOG Database.",count($RMTasks)));
                    foreach ((array)$RMTasks AS $i => &$RMTask) {
                        $this->outall(sprintf(" | Cleaning Task (%s) %s",$RMTask->getID(),$RMTask->getName()));
                        $KnownTasks = $this->removeFromKnownList($KnownTasks,$RMTask->getID());
                        $taskIDs = $this->getSubObjectIDs('MulticastSessionsAssociation',array('msID'=>$RMTask->getID()),'taskID');
                        if (static::getClass('TaskManager')->count(array('id'=>$taskIDs,'stateID'=>$this->getCancelledState()) > 0)) $jobcancelled = true;
                        if ($jobcancelled || static::getClass('MulticastSessions',$RMTask->getID())->get('stateID') == $this->getCancelledState()) {
                            $RMTask->killTask();
                            $this->outall(sprintf(" | Task (%s) %s has been cleaned as cancelled.",$RMTask->getID(),$RMTask->getName()));
                            static::getClass('MulticastSessionsAssociationManager')->destroy(array('msID'=>$RMTask->getID()));
                        } else {
                            $this->outall(sprintf(" | Task (%s) %s has been cleaned as complete.",$RMTask->getID(),$RMTask->getName()));
                            static::getClass('MulticastSessionsAssociationManager')->destroy(array('msID'=>$RMTask->getID()));
                        }
                        unset($RMTask);
                    }
                }
                if ($taskCount > 0) $this->outall(sprintf(' | %s task%s found',$taskCount,($taskCount > 1 || !$taskCount ? 's' : '')));
                if (count($allTasks)) {
                    foreach ((array)$allTasks AS $i => &$curTask) {
                        if ($this->isMCTaskNew($KnownTasks, $curTask->getID())) {
                            $this->outall(sprintf(" | Task (%s) %s is new!",$curTask->getID(),$curTask->getName()));
                            if(!file_exists($curTask->getImagePath())) throw new Exception(sprintf(" Task (%s) %s failed to execute, image file:%s not found!",$curTask->getID(),$curTask->getName(),$curTask->getImagePath()));
                            if (!$curTask->getClientCount()) throw new Exception(sprintf(" Task (%s) %s failed to execute, no clients are included!",$curTask->getID(),$curTask->getName()));
                            if (!is_numeric($curTask->getPortBase()) || !($curTask->getPortBase() % 2 == 0)) throw new Exception(sprintf(" Task (%s) %s failed to execute, port must be even and numeric.",$curTask->getID(),$curTask->getName()));
                            if (!$curTask->startTask()) {
                                $this->outall(sprintf(" | Task (%s) %s failed to start!",$curTask->getID(),$curTask->getName()));
                                $this->outall(sprintf(" | * Don't panic, check all your settings!"));
                                $this->outall(sprintf(" |       even if the interface is incorrect the task won't start."));
                                $this->outall(sprintf(" |       If all else fails run the following command and see what it says:"));
                                $this->outall(sprintf(" |  %s",$curTask->getCMD()));
                                $curTask->killTask();
                                throw new Exception(" Task (%s) %s has been cleaned.");
                            }
                            $this->outall(sprintf(" | Task (%s) %s has been cleaned.",$curTask->getID(),$curTask->getName()));
                            $this->outall(sprintf(" | Task (%s) %s image file found.",$curTask->getID(),$curTask->getImagePath()));
                            $this->outall(sprintf(" | Task (%s) %s client(s) found.",$curTask->getID(),$curTask->getClientCount()));
                            $this->outall(sprintf(" | Task (%s) %s sending on base port: %s",$curTask->getID(),$curTask->getName(),$curTask->getPortBase()));
                            $this->outall(sprintf(" | CMD: %s",$curTask->getCMD()));
                            $this->outall(sprintf(" | Task (%s) %s has started.",$curTask->getID(),$curTask->getName()));
                            $KnownTasks[] = $curTask;
                        } else {
                            $runningTask = $this->getMCExistingTask($KnownTasks, $curTask->getID());
                            $taskIDs = $this->getSubObjectIDs('MulticastSessionsAssociation',array('msID'=>$runningTask->getID()),'taskID');
                            if (static::getClass('TaskManager')->count(array('id'=>$taskIDs,'stateID'=>$this->getCancelledState()) > 0)) $jobcancelled = true;
                            if ($runningTask->isRunning($runningTask->procRef)) {
                                $this->outall(sprintf(" | Task (%s) %s is already running PID %s",$runningTask->getID(),$runningTask->getName(),$runningTask->getPID($runningTask->procRef)));
                                $runningTask->updateStats();
                            } else {
                                $this->outall(sprintf(" | Task (%s) %s is no longer running.",$runningTask->getID(),$runningTask->getName()));
                                if ($jobcancelled || static::getClass('MulticastSessions',$runningTask->getID())->get('stateID') == $this->getCancelledState()) {
                                    $KnownTasks = $this->removeFromKnownList($KnownTasks,$runningTask->getID());
                                    if (!$runningTask->killTask()) throw new Exception(sprintf(" Failed to kill task (%s) %s PID:%s!",$runningTask->getID(),$runningTask->getName(),$runningTask->getPID($runningTask->procRef)));
                                    $this->outall(sprintf(" | Task (%s) %s has been cleaned as cancelled.",$runningTask->getID(),$runningTask->getName()));
                                } else {
                                    static::getClass('MulticastSessions',$runningTask->getID())->set('clients',0)->set('completetime',$this->nice_date()->format('Y-m-d H:i:s'))->set('name','')->set('stateID',$this->getCompleteState())->save();
                                    $KnownTasks = $this->removeFromKnownList($KnownTasks,$runningTask->getID());
                                    $this->outall(sprintf(" | Task (%s) %s has been cleaned as complete.",$runningTask->getID(),$runningTask->getName()));
                                }
                            }
                        }
                        unset($curTask);
                    }
                }
            } catch(Exception $e) {
                $this->outall($e->getMessage());
            }
            static::out(' +---------------------------------------------------------',$this->dev);
            $tmpTime = (int)$this->getSetting($this->sleeptime);
            if ($this->zzz != $tmpTime) {
                $this->zzz = $tmpTime;
                $this->outall(sprintf(" | Sleep time has changed to %s seconds",$this->zzz));
            }
            sleep($this->zzz);
            $oldCount = $taskCount;
        }
    }
    public function serviceRun() {
        static::out(' ',$this->dev);
        static::out(' +---------------------------------------------------------',$this->dev);
        static::serviceLoop();
    }
}
