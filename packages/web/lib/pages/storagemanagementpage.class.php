<?php
class StorageManagementPage extends FOGPage {
    // Base variables
    public $node = 'storage';
    public function __construct($name = '') {
        $this->name = 'Storage Management';
        parent::__construct($this->name);
        $this->menu = array(
            '' => self::$foglang['AllSN'],
            'add-storage-node' => self::$foglang['AddSN'],
            'storage-group' => self::$foglang['AllSG'],
            'add-storage-group' => self::$foglang['AddSG'],
        );
        if (in_array($_REQUEST['sub'],array('edit','delete','delete-storage-node','delete_storage_node'))) {
            if (isset($_REQUEST['id'])) {
                $this->obj = self::getClass('StorageNode',$_REQUEST['id']);
                if ((int) $_REQUEST['id'] === 0 || !is_numeric($_REQUEST['id']) || !$this->obj->isValid()) {
                    unset($this->obj);
                        $this->setMessage(sprintf(_('%s ID %s is not valid'),$this->childClass,$_REQUEST['id']));
                    $this->redirect(sprintf('?node=%s',$this->node));
                }
                $this->subMenu = array(
                    "?node={$this->node}&sub={$_REQUEST['sub']}&id={$_REQUEST['id']}" => self::$foglang['General'],
                    "?node={$this->node}&sub=delete-storage-node&id={$_REQUEST['id']}" => self::$foglang['Delete'],
                );
                $this->notes = array(
                    sprintf('%s %s',self::$foglang['Storage'],self::$foglang['Node']) => $this->obj->get('name'),
                    self::$foglang['ImagePath'] => $this->obj->get('path'),
                    self::$foglang['FTPPath'] => $this->obj->get('ftppath'),
                );
            }
        } else if (in_array($_REQUEST['sub'],array('edit-storage-group','delete-storage-group','edit_storage_group','delete_storage_group'))) {
            if (isset($_REQUEST['id'])) {
                $this->obj = self::getClass('StorageGroup',$_REQUEST['id']);
                if ((int) $_REQUEST['id'] === 0 || !is_numeric($_REQUEST['id']) || !$this->obj->isValid()) {
                    unset($this->obj);
                        $this->setMessage(sprintf(_('%s ID %s is not valid'),$this->childClass,$_REQUEST['id']));
                    $this->redirect(sprintf('?node=%s',$this->node));
                }
                $this->subMenu = array(
                    "?node={$this->node}&sub={$_REQUEST['sub']}&id={$_REQUEST['id']}" => self::$foglang['General'],
                    "?node={$this->node}&sub=delete-storage-group&id={$_REQUEST['id']}" => self::$foglang['Delete'],
                );
                $this->notes = array(
                    sprintf('%s %s',self::$foglang['Storage'],self::$foglang['Group']) => $this->obj->get('name'),
                );
            }
        }
    }
    public function search() {
        $this->index();
    }
    public function edit() {
        $this->edit_storage_node();
    }
    public function edit_post() {
        $this->edit_storage_node_post();
    }
    public function delete() {
        $this->delete_storage_node();
    }
    public function delete_post() {
        $this->delete_storage_node_post();
    }
    public function index() {
        $this->title = self::$foglang['AllSN'];
        foreach ((array)self::getClass('StorageNodeManager')->find() AS $i => &$StorageNode) {
            $StorageGroup = self::getClass('StorageGroup',$StorageNode->get('storageGroupID'));
            $this->data[] = array_merge((array)$StorageNode->get(),array(
                'isMasterText'=>($StorageNode->get('isMaster')?'Yes':'No'),
                'isEnabledText'=>($StorageNode->get('isEnabled')?'Yes':'No'),
                'isGraphEnabledText'=>($StorageNode->get('isGraphEnabled') ? 'Yes' : 'No'),
                'storage_group'=>$StorageGroup->get('name'),
            ));
        }
        unset($StorageNode);
        $this->headerData = array(
            self::$foglang['SN'],
            self::$foglang['SG'],
            self::$foglang['Enabled'],
            self::$foglang['GraphEnabled'],
            self::$foglang['MasterNode'],
            ''
        );
        // Row templates
        $this->templates = array(
            sprintf('<a href="?node=%s&sub=edit&%s=${id}" title="%s">${name}</a>', $this->node, $this->id, self::$foglang['Edit']),
            sprintf('${storage_group}',$this->node,$this->id),
            sprintf('${isEnabledText}',$this->node,$this->id),
            sprintf('${isGraphEnabledText}',$this->node,$this->id),
            sprintf('${isMasterText}',$this->node,$this->id),
            sprintf('<a href="?node=%s&sub=edit&%s=${id}" title="%s"><i class="icon fa fa-pencil"></i></a> <a href="?node=%s&sub=delete&%s=${id}" title="%s"><i class="icon fa fa-minus-circle"></i></a>',$this->node,$this->id,self::$foglang['Edit'],$this->node,$this->id,self::$foglang['Delete'])
        );
        $this->attributes = array(
            array(),
            array(),
            array('class'=>'c','width'=>90),
            array('class'=>'c','width'=>90),
            array('class'=>'c','width'=>90),
            array('class'=>'c filter-false','width'=>50),
        );
        self::$HookManager->processEvent('STORAGE_NODE_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));

        $this->render();
    }
    public function add_storage_node() {
        $this->title = self::$foglang['AddSN'];
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            '<input style="display:none" type="text" name="fakeusernameremembered"/>'=>'<input style="display:none" type="text" name="fakepasswordremembered"/>',
            self::$foglang['SNName'] => '<input type="text" name="name" value="${node_name}" autocomplete="off" />*',
            self::$foglang['SNDesc'] => '<textarea name="description" rows="8" cols="40" autocomplete="off">${node_desc}</textarea>',
            self::$foglang['IPAdr'] => '<input type="text" name="ip" value="${node_ip}" autocomplete="off" />*',
            _('Web root')  => '<input type="text" name="webroot" value="${node_webroot}" autocomplete="off" />*',
            self::$foglang['MaxClients'] => '<input type="text" name="maxClients" value="${node_maxclient}" autocomplete="off" />*',
            self::$foglang['IsMasterNode'] => '<input type="checkbox" name="isMaster" value="1" />&nbsp;&nbsp;${span}',
            self::$foglang['BandwidthReplication'].' (Kbps)' => '<input type="text" name="bandwidth" value="${node_bandwidth}" autocomplete="off" />&nbsp;&nbsp;${span2}',
            self::$foglang['SG'] => '${node_group}',
            self::$foglang['ImagePath'] => '<input type="text" name="path" value="${node_path}" autocomplete="off" />',
            self::$foglang['FTPPath'] => '<input type="text" name="ftppath" value="${node_ftppath}" autocomplete="off" />',
            self::$foglang['SnapinPath'] => '<input type="text" name="snapinpath" value="${node_snapinpath}" autocomplete="off" />',
            self::$foglang['SSLPath'] => '<input type="text" name="sslpath" value="${node_sslpath}" autocomplete="off" />',
            _('Bitrate') => '<input type="text" name="bitrate" value="${node_bitrate}" autocomplete="off" />',
            self::$foglang['Interface'] => '<input type="text" name="interface" value="${node_interface}" autocomplete="off" />',
            self::$foglang['IsEnabled'] => '<input type="checkbox" name="isEnabled" checked value="1" />',
            self::$foglang['IsGraphEnabled'].'<br /><small>('.self::$foglang['OnDash'].')'  => '<input type="checkbox" name="isGraphEnabled" checked value="1" />',
            self::$foglang['ManUser'] => '<input type="text" name="user" value="${node_user}" autocomplete="off" />*',
            self::$foglang['ManPass'] => '<input type="password" name="pass" value="${node_pass}" autocomplete="off" />*',
            '<input type="hidden" name="add" value="1" />' => '<input type="submit" value="'.self::$foglang['Add'].'" autocomplete="off" />',
        );
        echo '<form method="post" action="'.$this->formAction.'">';
        foreach ((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
                'node_name'=>$_REQUEST['name'],
                'node_desc'=>$_REQUEST['description'],
                'node_ip'=>$_REQUEST['ip'],
                'node_webroot'=>$_REQUEST['webroot'],
                'node_maxclient'=>$_REQUEST['maxClients']?$_REQUEST['maxClients']:10,
                'span'=>'<i class="icon fa fa-question hand" title="'.self::$foglang['CautionPhrase'].'"></i>',
                'span2'=>'<i class="icon fa fa-question hand" title="'.self::$foglang['BandwidthRepHelp'].'"></i>',
                'node_group'=>self::getClass('StorageGroupManager')->buildSelectBox(1, 'storageGroupID'),
                'node_path'=>$_REQUEST['path']?$_REQUEST['path']:'/images/',
                'node_ftppath'=>$_REQUEST['ftppath']?$_REQUEST['ftppath']:'/images/',
                'node_snapinpath'=>$_REQUEST['snapinpath']?$_REQUEST['snapinpath']:'/opt/fog/snapins/',
                'node_sslpath'=>$_REQUEST['sslpath']?$_REQUEST['sslpath']:'/opt/fog/snapins/ssl/',
                'node_bitrate'=>$_REQUEST['bitrate'],
                'node_interface'=>$_REQUEST['interface'] ? $_REQUEST['interface'] : 'eth0',
                'node_user'=>$_REQUEST['user'],
                'node_pass'=>$_REQUEST['pass'],
                'node_bandwidth'=>$_REQUEST['bandwidth'],
            );
        }
        unset($input);
        self::$HookManager->processEvent('STORAGE_NODE_ADD',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        echo '</form>';
    }
    public function add_storage_node_post() {
        self::$HookManager->processEvent('STORAGE_NODE_ADD_POST');
        try {
            if (empty($_REQUEST['name'])) throw new Exception(self::$foglang['StorageNameRequired']);
            if (self::getClass('StorageNodeManager')->exists($_REQUEST['name'])) throw new Exception(self::$foglang['StorageNameExists']);
            if (empty($_REQUEST['ip'])) throw new Exception(self::$foglang['StorageIPRequired']);
            if (empty($_REQUEST['maxClients'])) throw new Exception(self::$foglang['StorageClientsRequired']);
            if (empty($_REQUEST['interface'])) throw new Exception(self::$foglang['StorageIntRequired']);
            if (empty($_REQUEST['user'])) throw new Exception(self::$foglang['StorageUserRequired']);
            if (empty($_REQUEST['pass'])) throw new Exception(self::$foglang['StoragePassRequired']);
            if (((is_numeric($_REQUEST['bandwidth']) && $_REQUEST['bandwidth'] <= 0) || !is_numeric($_REQUEST['bandwidth'])) && $_REQUEST['bandwidth']) throw new Exception(_('Bandwidth should be numeric and greater than 0'));
            $StorageNode = self::getClass('StorageNode')
                ->set('name',$_REQUEST['name'])
                ->set('description',$_REQUEST['description'])
                ->set('ip',$_REQUEST['ip'])
                ->set('webroot',$_REQUEST['webroot'])
                ->set('maxClients',$_REQUEST['maxClients'])
                ->set('isMaster',(int)isset($_REQUEST['isMaster']))
                ->set('storageGroupID',$_REQUEST['storageGroupID'])
                ->set('path',$_REQUEST['path'])
                ->set('ftppath',$_REQUEST['ftppath'])
                ->set('snapinpath',$_REQUEST['snapinpath'])
                ->set('sslpath',$_REQUEST['sslpath'])
                ->set('bitrate', $_REQUEST['bitrate'])
                ->set('interface',$_REQUEST['interface'])
                ->set('isGraphEnabled',(string)isset($_REQUEST['isGraphEnabled']))
                ->set('isEnabled',(int)isset($_REQUEST['isEnabled']))
                ->set('user',$_REQUEST['user'])
                ->set('pass',$_REQUEST['pass'])
                ->set('bandwidth',$_REQUEST['bandwidth']);
            if (!$StorageNode->save()) throw new Exception(self::$foglang['DBupfailed']);
            if ($StorageNode->get('isMaster')) self::getClass('StorageNodeManager')->update(array('id'=>array_diff((array)$StorageNode->get('id'),self::getSubObjectIDs('StorageNode',array('isMaster'=>1,'storageGroupID'=>$StorageNode->get('storageGroupID'))))),'',array('isMaster'=>0));
            self::$HookManager->processEvent('STORAGE_NODE_ADD_SUCCESS',array('StorageNode'=>&$StorageNode));
            $this->setMessage(self::$foglang['SNCreated']);
            $this->redirect(sprintf('?node=%s&id=%s',$_REQUEST['node'],$this->id, $StorageNode->get('id')));
        } catch (Exception $e) {
            self::$HookManager->processEvent('STORAGE_NODE_ADD_FAIL',array('StorageNode'=>&$StorageNode));
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
    public function edit_storage_node() {
        $this->title = sprintf('%s: %s',self::$foglang['Edit'],$this->obj->get('name'));
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            '<input style="display:none" type="text" name="fakeusernameremembered"/>'=>'<input style="display:none" type="text" name="fakepasswordremembered"/>',
            self::$foglang['SNName'] => '<input type="text" name="name" value="${node_name}" autocomplete="off" />*',
            self::$foglang['SNDesc'] => '<textarea name="description" rows="8" cols="40" autocomplete="off">${node_desc}</textarea>',
            self::$foglang['IPAdr'] => '<input type="text" name="ip" value="${node_ip}" autocomplete="off" />*',
            _('Web root')  => '<input type="text" name="webroot" value="${node_webroot}" autocomplete="off" />*',
            self::$foglang['MaxClients'] => '<input type="text" name="maxClients" value="${node_maxclient}" autocomplete="off" />*',
            self::$foglang['IsMasterNode'] => '<input type="checkbox" name="isMaster" value="1" ${ismaster} autocomplete="off" />&nbsp;&nbsp;${span}',
            self::$foglang['BandwidthReplication'].'  (Kbps)' => '<input type="text" name="bandwidth" value="${node_bandwidth}" autocomplete="off" />&nbsp;&nbsp;${span2}',
            self::$foglang['SG'] => '${node_group}',
            self::$foglang['ImagePath'] => '<input type="text" name="path" value="${node_path}" autocomplete="off"/>',
            self::$foglang['FTPPath'] => '<input type="text" name="ftppath" value="${node_ftppath}" autocomplete="off"/>',
            self::$foglang['SnapinPath'] => '<input type="text" name="snapinpath" value="${node_snapinpath}" autocomplete="off"/>',
            self::$foglang['SSLPath'] => '<input type="text" name="sslpath" value="${node_sslpath}" autocomplete="off"/>',
            _('Bitrate') => '<input type="text" name="bitrate" value="${node_bitrate}" autocomplete="off" />',
            self::$foglang['Interface'] => '<input type="text" name="interface" value="${node_interface}" autocomplete="off"/>',
            self::$foglang['IsEnabled'] => '<input type="checkbox" name="isEnabled" value="1" ${isenabled}/>',
            self::$foglang['IsGraphEnabled'].'<br /><small>('.self::$foglang['OnDash'].')'  => '<input type="checkbox" name="isGraphEnabled" value="1" ${graphenabled} />',
            self::$foglang['ManUser'] => '<input type="text" name="user" value="${node_user}" autocomplete="off" />*',
            self::$foglang['ManPass'] => '<input type="password" name="pass" value="${node_pass}" autocomplete="off" />*',
            '&nbsp;' => '<input type="submit" name="update" value="'.self::$foglang['Update'].'" />',
        );
        echo '<form method="post" action="'.$this->formAction.'">';
        foreach ((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
                'node_name'=>$this->obj->get('name'),
                'node_desc'=>$this->obj->get('description'),
                'node_ip'=>$this->obj->get('ip'),
                'node_webroot'=>$this->obj->get('webroot'),
                'node_maxclient'=>$this->obj->get('maxClients'),
                'ismaster'=>$this->obj->get('isMaster') ? 'checked' : '',
                'isenabled'=>$this->obj->get('isEnabled') ? 'checked' : '',
                'graphenabled'=>$this->obj->get('isGraphEnabled') ? 'checked' : '',
                'span'=>'<i class="icon fa fa-question hand" title="'.self::$foglang['CautionPhrase'].'"></i>',
                'span2'=>'<i class="icon fa fa-question hand" title="'.self::$foglang['BandwidthRepHelp'].'"></i>',
                'node_group'=>self::getClass('StorageGroupManager')->buildSelectBox($this->obj->get('storageGroupID'),'storageGroupID'),
                'node_bandwidth'=>$this->obj->get('bandwidth'),
                'node_path'=>$this->obj->get('path'),
                'node_ftppath'=>$this->obj->get('ftppath'),
                'node_snapinpath'=>$this->obj->get('snapinpath'),
                'node_sslpath'=>$this->obj->get('sslpath'),
                'node_bitrate'=>$this->obj->get('bitrate'),
                'node_interface'=>$this->obj->get('interface'),
                'node_user'=>$this->obj->get('user'),
                'node_pass'=>$this->obj->get('pass'),
            );
        }
        unset($input);
        self::$HookManager->processEvent('STORAGE_NODE_EDIT',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        echo "</form>";
    }
    public function edit_storage_node_post() {
        self::$HookManager->processEvent('STORAGE_NODE_EDIT_POST',array('StorageNode'=>&$this->obj));
        try {
            if (empty($_REQUEST['name'])) throw new Exception(self::$foglang['StorageNameRequired']);
            if ($this->obj->get('name') != $_REQUEST['name'] && self::getClass('StorageNodeManager')->exists($_REQUEST['name'], $this->obj->get('id'))) throw new Exception(self::$foglang['StorageNameExists']);
            if (empty($_REQUEST['ip'])) throw new Exception(self::$foglang['StorageIPRequired']);
            if (!is_numeric($_REQUEST['maxClients']) || $_REQUEST['maxClients'] < 0) throw new Exception(self::$foglang['StorageClientRequired']);
            if (empty($_REQUEST['interface'])) throw new Exception(self::$foglang['StorageIntRequired']);
            if (empty($_REQUEST['user'])) throw new Exception(self::$foglang['StorageUserRequired']);
            if (empty($_REQUEST['pass'])) throw new Exception(self::$foglang['StoragePassRequired']);
            if (((is_numeric($_REQUEST['bandwidth']) && $_REQUEST['bandwidth'] <= 0) || !is_numeric($_REQUEST['bandwidth'])) && $_REQUEST['bandwidth']) throw new Exception(_('Bandwidth should be numeric and greater than 0'));
            $this->obj
                ->set('name',$_REQUEST['name'])
                ->set('description',$_REQUEST['description'])
                ->set('ip',$_REQUEST['ip'])
                ->set('webroot',$_REQUEST['webroot'])
                ->set('maxClients',$_REQUEST['maxClients'])
                ->set('isMaster',(int)isset($_REQUEST['isMaster']))
                ->set('storageGroupID',$_REQUEST['storageGroupID'])
                ->set('path',$_REQUEST['path'])
                ->set('ftppath',$_REQUEST['ftppath'])
                ->set('snapinpath',$_REQUEST['snapinpath'])
                ->set('sslpath',$_REQUEST['sslpath'])
                ->set('bitrate',$_REQUEST['bitrate'])
                ->set('interface',$_REQUEST['interface'])
                ->set('isGraphEnabled',(string)isset($_REQUEST['isGraphEnabled']))
                ->set('isEnabled',(int)isset($_REQUEST['isEnabled']))
                ->set('user',$_REQUEST['user'])
                ->set('pass',$_REQUEST['pass'])
                ->set('bandwidth',$_REQUEST['bandwidth']);
            // Save
            if (!$this->obj->save()) throw new Exception(self::$foglang['DBupfailed']);
            if ($this->obj->get('isMaster')) self::getClass('StorageNodeManager')->update(array('id'=>array_diff((array)$this->obj->get('id'),self::getSubObjectIDs('StorageNode',array('isMaster'=>1,'storageGroupID'=>$this->obj->get('storageGroupID'))))),'',array('isMaster'=>0));
            self::$HookManager->processEvent('STORAGE_NODE_EDIT_SUCCESS',array('StorageNode'=>&$this->obj));
            $this->setMessage(self::$foglang['SNUpdated']);
            $this->redirect($this->formAction);
        } catch (Exception $e) {
            self::$HookManager->processEvent('STORAGE_NODE_EDIT_FAIL',array('StorageNode'=>&$this->obj));
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
    public function delete_storage_node() {
        $this->title = sprintf('%s: %s',self::$foglang['Remove'],$this->obj->get('name'));
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            self::$foglang['ConfirmDel'].' <b>'.$this->obj->get('name').'</b>' => '<input type="submit" value="${title}" />',
        );
        foreach ((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
                'title'=>$this->title,
            );
        }
        unset($input);
        echo '<form method="post" action="'.$this->formAction.'" class="c">';
        self::$HookManager->processEvent('STORAGE_NODE_DELETE',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        echo '</form>';
    }
    public function delete_storage_node_post() {
        self::$HookManager->processEvent('STORAGE_NODE_DELETE_POST', array('StorageNode'=>&$this->obj));
        try {
            if (!$this->obj->destroy()) throw new Exception(self::$foglang['FailDelSN']);
            self::$HookManager->processEvent('STORAGE_NODE_DELETE_SUCCESS',array('StorageNode'=>&$this->obj));
            $this->setMessage(sprintf('%s: %s',self::$foglang['SNDelSuccess'],$this->obj->get('name')));
            $this->redirect(sprintf('?node=%s',$_REQUEST['node']));
        } catch (Exception $e) {
            self::$HookManager->processEvent('STORAGE_NODE_DELETE_FAIL',array('StorageNode'=>&$this->obj));
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
    public function storage_group() {
        $this->title = self::$foglang['AllSG'];
        foreach ((array)self::getClass('StorageGroupManager')->find() AS $i => &$StorageGroup) {
            $this->data[] = $StorageGroup->get();
            unset($StorageGroup);
        }
        $this->headerData = array(
            self::$foglang['SG'],
            '',
        );
        $this->templates = array(
            sprintf('<a href="?node=%s&sub=edit-storage-group&%s=${id}" title="%s">${name}</a>',$this->node,$this->id,self::$foglang[Edit]),
            sprintf('<a href="?node=%s&sub=edit-storage-group&%s=${id}" title="%s"><i class="icon fa fa-pencil"></i></a> <a href="?node=%s&sub=delete-storage-group&%s=${id}" title="%s"><i class="icon fa fa-minus-circle"></i></a>',$this->node,$this->id,self::$foglang[Edit],$this->node,$this->id,self::$foglang[Delete])
        );
        // Row attributes
        $this->attributes = array(
            array(),
            array('class'=>'c filter-false','width'=>50),
        );
        unset($this->data);
        $StorageGroups = self::getClass(StorageGroup)->getManager()->find();
        foreach ($StorageGroups AS $i => &$StorageGroup) {
            $this->data[] = $StorageGroup->get();
        }
        unset($StorageGroup);
        // Hook
        self::$HookManager->processEvent(STORAGE_GROUP_DATA,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
    }
    public function add_storage_group() {
        // Set title
        $this->title = self::$foglang[AddSG];
        // Header Data
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        // Fields
        $fields = array(
            self::$foglang[SGName] => '<input type="text" name="name" value="${storgrp_name}" />',
            self::$foglang[SGDesc] => '<textarea name="description" rows="8" cols="40">${storgrp_desc}</textarea>',
            '&nbsp;' => '<input type="submit" value="'.self::$foglang[Add].'" />',
        );
        echo '<form method="post" action="'.$this->formAction.'">';
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                field=>$field,
                input=>$input,
                storgrp_name=>$_REQUEST[name],
                storgrp_desc=>$_REQUEST[description],
            );
        }
        unset($input);
        // Hook
        self::$HookManager->processEvent(STORAGE_GROUP_ADD,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        echo '</form>';
    }
    public function add_storage_group_post() {
        // Hook
        self::$HookManager->processEvent('STORAGE_GROUP_ADD_POST');
        // POST
        try {
            // Error checking
            if (empty($_REQUEST['name'])) throw new Exception(self::$foglang['SGNameReq']);
            if (self::getClass('StorageGroupManager')->exists($_REQUEST['name'])) throw new Exception(self::$foglang['SGExist']);
            // Create new Object
            $StorageGroup = self::getClass('StorageGroup')
                ->set('name',$_REQUEST['name'])
                ->set('description',$_REQUEST['description']);
            // Save
            if (!$StorageGroup->save()) throw new Exception(self::$foglang['DBupfailed']);
            // Hook
            self::$HookManager->processEvent('STORAGE_GROUP_ADD_POST_SUCCESS',array(StorageGroup=>&$StorageGroup));
            // Set session message
            $this->setMessage(self::$foglang['SGCreated']);
            // Redirect to new entry
            $this->redirect(sprintf('?node=%s&sub=edit-storage-group&%s=%s',$_REQUEST['node'],$this->id,$StorageGroup->get('id')));
        } catch (Exception $e) {
            // Hook
            self::$HookManager->processEvent('STORAGE_GROUP_ADD_POST_FAIL', array('StorageGroup'=>&$StorageGroup));
            // Set session message
            $this->setMessage($e->getMessage());
            // Redirect to new entry
            $this->redirect($this->formAction);
        }
    }
    public function edit_storage_group() {
        // Title
        $this->title = sprintf('%s: %s', self::$foglang['Edit'], $this->obj->get('name'));
        // Header Data
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        // Fields
        $fields = array(
            self::$foglang['SGName']=>'<input type="text" name="name" value="'.$this->obj->get('name').'" />',
            self::$foglang['SGDesc']=>'<textarea name="description" rows="8" cols="40">'.$this->obj->get('description').'</textarea>',
            '&nbsp;'=>'<input type="submit" value="'.self::$foglang['Update'].'" />',
        );
        echo '<form method="post" action="'.$this->formAction.'">';
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
            );
        }
        unset($input);
        // Hook
        self::$HookManager->processEvent('STORAGE_GROUP_EDIT',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        // Output
        $this->render();
        echo '</form>';
    }
    public function edit_storage_group_post() {
        // Hook
        self::$HookManager->processEvent('STORAGE_GROUP_EDIT_POST',array('StorageGroup'=>&$this->obj));
        // POST
        try {
            // Error checking
            if (empty($_REQUEST['name'])) throw new Exception(self::$foglang['SGName']);
            if ($this->obj->get('name') != $_REQUEST['name'] && self::getClass('StorageGroupManager')->exists($_REQUEST['name'], $this->obj->get('id'))) throw new Exception(self::$foglang['SGExist']);
            // Update Object
            $this->obj
                ->set('name',$_REQUEST['name'])
                ->set('description',$_REQUEST['description']);
            // Save
            if (!$this->obj->save()) throw new Exception(self::$foglang['DBupfailed']);
            // Hook
            self::$HookManager->processEvent('STORAGE_GROUP_EDIT_POST_SUCCESS',array('StorageGroup'=>&$this->obj));
            // Set session message
            $this->setMessage(self::$foglang['SGUpdated']);
            // Redirect to new entry
            $this->redirect(sprintf('?node=%s&sub=storage-group', $_REQUEST['node'],$this->id,$this->obj->get('id')));
        } catch (Exception $e) {
            // Hook
            self::$HookManager->processEvent('STORAGE_GROUP_EDIT_FAIL',array('StorageGroup'=>&$this->obj));
            // Set session message
            $this->setMessage($e->getMessage());
            // Redirect to new entry
            $this->redirect($this->formAction);
        }
    }
    public function delete_storage_group() {
        // Title
        $this->title = sprintf('%s: %s',self::$foglang['Remove'],$this->obj->get('name'));
        // Headerdata
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            self::$foglang['ConfirmDel'].' <b>'.$this->obj->get('name').'</b>' => '<input type="submit" value="'.$this->title.'" />',
        );
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
            );
        }
        unset($input);
        echo '<form method="post" action="'.$this->formAction.'" class="c">';
        // Hook
        self::$HookManager->processEvent('STORAGE_GROUP_DELETE',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        // Output
        $this->render();
        echo '</form>';
    }
    public function delete_storage_group_post() {
        // Hook
        self::$HookManager->processEvent('STORAGE_GROUP_DELETE_POST',array('StorageGroup'=>&$this->obj));
        // POST
        try {
            // Error checking
            if (self::getClass('StorageGroupManager')->count() == 1) throw new Exception(self::$foglang['OneSG']);
            // Destroy
            if (!$this->obj->destroy()) throw new Exception(self::$foglang['FailDelSG']);
            // Hook
            self::$HookManager->processEvent('STORAGE_GROUP_DELETE_POST_SUCCESS', array('StorageGroup'=>&$this->obj));
            // Set session message
            $this->setMessage(sprintf('%s: %s',self::$foglang['SGDelSuccess'],$this->obj->get('name')));
            // Redirect
            $this->redirect(sprintf('?node=%s&sub=storage-group', $_REQUEST['node']));
        } catch (Exception $e) {
            // Hook
            self::$HookManager->processEvent('STORAGE_GROUP_DELETE_POST_FAIL',array('StorageGroup'=>&$this->obj));
            // Set session message
            $this->setMessage($e->getMessage());
            // Redirect
            $this->redirect($this->formAction);
        }
    }
}
