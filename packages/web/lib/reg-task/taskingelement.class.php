<?php
abstract class TaskingElement extends FOGBase {
    protected $Host;
    protected $Task;
    protected $Image;
    protected $StorageGroup;
    protected $StorageNode;
    protected $StorageNodes;
    protected $imagingTask;
    public function __construct() {
        parent::__construct();
        try {
            $this->Host = $this->getHostItem(false);
            $this->Task = $this->Host->get('task');
            self::checkTasking($this->Task,$this->Host->get('name'),$this->Host->get('mac'));
            $this->imagingTask = in_array($this->Task->get('typeID'),array(1,2,8,15,16,17,24));
            $this->StorageGroup = $this->StorageNode = null;
            self::$HookManager->processEvent('HOST_NEW_SETTINGS',array('Host'=>&$this->Host,'StorageNode'=>&$this->StorageNode,'StorageGroup'=>&$this->StorageGroup));
            if (!$this->StorageGroup || !$this->StorageGroup->isValid()) $this->StorageGroup = $this->Task->getStorageGroup();
            if ($this->imagingTask) {
                if (!$this->StorageNode || !$this->StorageNode->isValid()) $this->StorageNode = $this->Task->isCapture() || $this->Task->isMulticast() ? $this->StorageGroup->getMasterStorageNode() : $this->StorageGroup->getOptimalStorageNode($this->Host->get('imageID'));
                self::checkStorageGroup($this->StorageGroup);
                self::checkStorageNodes($this->StorageGroup);
                $this->Image = $this->Task->getImage();
                $this->StorageNodes = self::getClass('StorageNodeManager')->find(array('id'=>$this->StorageGroup->get('enablednodes')));
                $this->Host->set('sec_tok',null)->set('pub_key',null)->save();
                if ($this->Task->isCapture() || $this->Task->isMulticast()) $this->StorageNode = $this->StorageGroup->getMasterStorageNode();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }
    protected static function checkTasking(&$Task,$name,$mac) {
        if (!$Task->isValid()) throw new Exception(sprintf('%s: %s (%s)', _('No Active Task found for Host'),$name,$mac));
    }
    protected static function checkStorageGroup(&$StorageGroup) {
        if (!$StorageGroup->isValid()) throw new Exception(_('Invalid Storage Group'));
    }
    protected static function checkStorageNodes(&$StorageGroup) {
        if (!$StorageGroup->get('enablednodes')) throw new Exception(_('Could not find a Storage Node, is there one enabled within this group?'));
    }
    protected static function nodeFail($StorageNode,$Host) {
        if ($StorageNode->getNodeFailure($Host)) {
            $StorageNode = self::getClass('StorageNode',0);
            printf('%s %s (%s) %s',_('Storage Node'),$StorageNode->get('name'),$StorageNode->get('ip'),_('is open, but has recently failed for this Host'));
        }
        return $StorageNode;
    }
    protected function TaskLog() {
        return self::getClass('TaskLog',$this->Task)
            ->set('taskID',$this->Task->get('id'))
            ->set('taskStateID',$this->Task->get('stateID'))
            ->set('createdTime',$this->Task->get('createdTime'))
            ->set('createdBy',$this->Task->get('createdBy'))
            ->save();
    }
    protected function ImageLog($checkin = false) {
        if ($checkin === true) {
            self::getClass('ImagingLogManager')->destroy(array('hostID'=>$this->Host->get('id'),'finish'=>'0000-00-00 00:00:00'));
            return self::getClass('ImagingLog')
                ->set('hostID',$this->Host->get('id'))
                ->set('start',$this->formatTime('','Y-m-d H:i:s'))
                ->set('image',$this->Image->get('name'))
                ->set('type',$_REQUEST['type'])
                ->set('createdBy',$this->Task->get('createdBy'))
                ->save();
        }
        return self::getClass('ImagingLog',@max(self::getSubObjectIDs('ImagingLog',array('hostID'=>$this->Host->get('id')))))
            ->set('finish',$this->formatTime('','Y-m-d H:i:s'))
            ->save();
    }
}
