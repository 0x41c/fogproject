<?php
abstract class FOGManagerController extends FOGBase {
    protected $childClass;
    protected $loadQueryTemplate = 'SELECT * FROM `%s` %s %s %s %s %s';
    protected $loadQueryGroupTemplate = 'SELECT * FROM (%s) `%s` %s %s %s %s %s';
    protected $countQueryTemplate = 'SELECT COUNT(`%s`.`%s`) AS `total` FROM `%s`%s LIMIT 1';
    protected $updateQueryTemplate = 'UPDATE `%s` SET %s %s';
    protected $destroyQueryTemplate = "DELETE FROM `%s` WHERE `%s`.`%s` IN ('%s')";
    protected $existsQueryTemplate = "SELECT COUNT(`%s`.`%s`) AS `total` FROM `%s` WHERE `%s`.`%s`='%s' AND `%s`.`%s` <> '%s'";
    protected $insertBatchTemplate = "INSERT INTO `%s` (`%s`) VALUES %s";
    public function __construct() {
        parent::__construct();
        $this->childClass = preg_replace('#_?Manager$#', '', get_class($this));
        $classVars = self::getClass($this->childClass,'',true);
        $this->databaseTable = $classVars['databaseTable'];
        $this->databaseFields = $classVars['databaseFields'];
        $this->databaseFieldsRequired = $classVars['databaseFieldsRequired'];
        $this->databaseFieldClassRelationships = $classVars['databaseFieldClassRelationships'];
        $this->additionalFields = $classVars['additionalFields'];
        unset($classVars);
    }
    public function find($findWhere = array(), $whereOperator = 'AND', $orderBy = 'name', $sort = 'ASC', $compare = '=', $groupBy = false, $not = false, $idField = false,$onecompare = true,$filter = 'array_unique') {
        // Fail safe defaults
        if (empty($findWhere)) $findWhere = array();
        if (empty($whereOperator)) $whereOperator = 'AND';
        if (empty($sort)) $sort = 'ASC';
        $this->orderBy($orderBy);
        if (empty($compare)) $compare = '=';
        $not = ($not ? ' NOT ' : ' ');
        if (count($findWhere)) {
            $count = 0;
            $whereArray = array();
            array_walk($findWhere,function(&$value,&$field) use (&$count,&$onecompare,&$compare,&$whereArray,&$not) {
                $field = trim($field);
                if (is_array($value)) $whereArray[] = sprintf("`%s`.`%s`%sIN ('%s')",$this->databaseTable,$this->databaseFields[$field],$not,implode("','",$value));
                else $whereArray[] = sprintf("`%s`.`%s`%s%s",$this->databaseTable,$this->databaseFields[$field],(preg_match('#%#',(string)$value) ? $not.'LIKE ' : (trim($not) ? '!' : '').($onecompare ? (!$count ? $compare : '=') : $compare)), ($value === 0 || $value ? "'".(string)$value."'" : null));
                $count++;
                unset($value);
                return ($whereArray);
            });
        }
        if (!is_array($orderBy)) {
            $orderBy = sprintf('ORDER BY %s`%s`.`%s`%s',($orderBy == 'name' ? 'LOWER(' : ''),$this->databaseTable,$this->databaseFields[$orderBy],($orderBy == 'name' ? ')' : ''));
            if ($groupBy) $groupBy = sprintf('GROUP BY `%s`.`%s`',$this->databaseTable,$this->databaseFields[$groupBy]);
            else $groupBy = '';
        } else $orderBy = '';
        list($join, $whereArrayAnd) = self::getClass($this->childClass)->buildQuery($not, $compare);
        $isEnabled = false;
        if (!in_array($this->childClass,array('Image','Snapin','StorageNode')) && array_key_exists('isEnabled',$this->databaseFields)) $isEnabled = sprintf('`%s`=1',$this->databaseFields['isEnabled']);
        $query = sprintf(
            $this->loadQueryTemplate,
            $this->databaseTable,
            $join,
            (count($whereArray) ? sprintf('WHERE %s%s',implode(sprintf(' %s ',$whereOperator),$whereArray),($isEnabled ? sprintf(' AND %s',$isEnabled) : '')) : ($isEnabled ? sprintf('WHERE %s',$isEnabled) : '')),
            (count($whereArrayAnd) ? (count($whereArray) ? sprintf('AND %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd)) : sprintf('WHERE %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd))) : ''),
            $orderBy,
            $sort
        );
        if ($groupBy) {
            $query = sprintf(
                $this->loadQueryGroupTemplate,
                sprintf(
                    $this->loadQueryTemplate,
                    $this->databaseTable,
                    $join,
                    (count($whereArray) ? sprintf('WHERE %s%s',implode(sprintf(' %s ',$whereOperator),$whereArray),($isEnabled ? sprintf(' AND %s',$isEnabled) : '')) : ($isEnabled ? sprintf('WHERE %s',$isEnabled) : '')),
                    (count($whereArrayAnd) ? (count($whereArray) ? sprintf('AND %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd)) : sprintf('WHERE %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd))) : ''),
                    $orderBy,
                    $sort
                ),
                $this->databaseTable,
                $join,
                (count($whereArray) ? sprintf('WHERE %s%s',implode(sprintf(' %s ',$whereOperator),$whereArray),($isEnabled ? sprintf(' AND %s',$isEnabled) : '')) : ($isEnabled ? sprintf('WHERE %s',$isEnabled) : '')),
                (count($whereArrayAnd) ? (count($whereArray) ? sprintf('AND %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd)) : sprintf('WHERE %s',implode(sprintf(' %s ',$whereOperator),(array)$whereArrayAnd))) : ''),
                $groupBy,
                $orderBy,
                $sort
            );
        }
        $data = array();
        if ($idField) {
            $idField = array_map(function(&$item) {
                return trim($item);
            },(array)$idField);
            $htmlEntDecode = function(&$item) {
                return html_entity_decode($item,ENT_QUOTES,'utf-8');
            };
            array_map(function(&$item) use ($query,$htmlEntDecode,&$data) {
                $tmp = array_map($htmlEntDecode,(array)self::$DB->query($query)->fetch('',$item ? 'fetch_assoc' : 'fetch_all')->get($item ? $this->databaseFields[$item] : ''));
                $data[$item] = count($tmp) === 1 ? array_shift($tmp) : $tmp;
            },(array)$idField);
            if (count($data) === 1) {
                if ($filter) return @$filter((array)array_shift($data));
                return array_shift($data);
            }
            if (empty($filter)) return $data;
        } else {
            $data = array_map(function(&$item) {
                return self::getClass($this->childClass)->setQuery($item);
            },(array)self::$DB->query($query)->fetch('','fetch_all')->get()); 
        }
        if ($filter) return @$filter(array_values(array_filter((array)$data)));
        return array_values(array_filter((array)$data));
    }
    public function count($findWhere = array(), $whereOperator = 'AND', $compare = '=') {
        if (empty($findWhere)) $findWhere = array();
        if (empty($whereOperator)) $whereOperator = 'AND';
        $whereArray = array();
        if (count($findWhere)) {
            array_walk($findWhere,function(&$value,&$field) use (&$whereArray,$compare){
                $field = trim($field);
                if (is_array($value)) $whereArray[] = sprintf("`%s`.`%s` IN ('%s')",$this->databaseTable,$this->databaseFields[$field],implode("','",$value));
                else $whereArray[] = sprintf("`%s`.`%s`%s'%s'",$this->databaseTable,$this->databaseFields[$field],(preg_match('#%#',(string)$value) ? 'LIKE' : $compare), (string)$value);
                unset($value,$field);
            });
        }
        $isEnabled = false;
        if (!in_array($this->childClass,array('Image','Snapin')) && array_key_exists('isEnabled',$this->databaseFields)) $isEnabled = sprintf('`%s`=1',$this->databaseFields['isEnabled']);
        $query = sprintf(
            $this->countQueryTemplate,
            $this->databaseTable,
            $this->databaseFields['id'],
            $this->databaseTable,
            (count($whereArray) ? sprintf('WHERE %s%s',implode(sprintf(' %s ',$whereOperator),$whereArray),($isEnabled ? sprintf(' AND %s',$isEnabled) : '')) : ($isEnabled ? sprintf('WHERE %s',$isEnabled) : ''))
        );
        return (int)self::$DB->query($query)->fetch()->get('total');
    }
    public function insert_batch($fields,$values) {
        $fieldlength = count($fields);
        $valuelength = count($values);
        if (!$fieldlength) die(_('No fields passed'));
        if (!$valuelength) die(_('No values passed'));
        array_map(function($value) use ($fieldlength) {
            $valuelength = count((array)$value);
            if ($fieldlength !== $valuelength) die(_('Field and values do not have equal parameters.'));
        },(array)$values);
        $keys = array_map(function(&$key) {
            return $this->databaseFields[$key];
        },(array)$fields);
        $vals = array_map(function(&$value) {
            $value = array_map(function($value) {
                return self::$DB->sanitize($value);
            },(array)$value);
            return sprintf("('%s')",implode("','",(array)$value));
        },(array)$values);
        $query = sprintf($this->insertBatchTemplate,$this->databaseTable,implode('`,`',$keys),implode(',',$vals));
        self::$DB->query($query);
        return array(self::$DB->insert_id(),self::$DB->affected_rows());
    }
    public function update($findWhere = array(), $whereOperator = 'AND', $insertData) {
        if (empty($findWhere)) $findWhere = array();
        if (empty($whereOperator)) $whereOperator = 'AND';
        $insertArray = array();
        array_walk($insertData,function(&$value,&$field) use (&$insertArray) {
            $field = trim($field);
            $insertKey = sprintf('`%s`.`%s`',$this->databaseTable,$this->databaseFields[$field]);
            $insertVal = self::$DB->sanitize($value);
            $insertArray[] = sprintf("%s='%s'",$insertKey,$insertVal);
            unset($value);
        });
        if (count($findWhere)) {
            $whereArray = array();
            array_walk($findWhere,function(&$value,&$field) use (&$whereArray) {
                $field = trim($field);
                if (is_array($value)) $whereArray[] = sprintf("`%s`.`%s` IN ('%s')",$this->databaseTable,$this->databaseFields[$field],implode("','",$value));
                else $whereArray[] = sprintf("`%s`.`%s`%s'%s'",$this->databaseTable,$this->databaseFields[$field],(preg_match('#%#',(string)$value) ? 'LIKE' : '='), (string)$value);
                unset($value,$field);
            });
        }
        $query = sprintf(
            $this->updateQueryTemplate,
            $this->databaseTable,
            implode(',',(array)$insertArray),
            (count($whereArray) ? ' WHERE '.implode(' '.$whereOperator.' ',(array)$whereArray) : '')
        );
        return (bool)self::$DB->query($query)->fetch()->get();
    }
    public function destroy($findWhere = array(), $whereOperator = 'AND', $orderBy = 'name', $sort = 'ASC', $compare = '=', $groupBy = false, $not = false) {
        if (empty($findWhere)) $findWhere = array();
        if (empty($whereOperator)) $whereOperator = 'AND';
        $this->orderBy($orderBy);
        if (empty($sort)) $sort = 'ASC';
        if (empty($compare)) $compare = '=';
        if (array_key_exists('id',$findWhere)) $ids = $findWhere['id'];
        else $ids = $this->find($findWhere, $whereOperator, $orderBy, $sort, $compare, $groupBy, $not, 'id');
        $query = sprintf(
            $this->destroyQueryTemplate,
            $this->databaseTable,
            $this->databaseTable,
            $this->databaseFields['id'],
            implode("','",(array)$ids)
        );
        return self::$DB->query($query)->fetch()->get();
    }
    public function buildSelectBox($matchID = '', $elementName = '', $orderBy = 'name', $filter = '', $template = false) {
        $matchID = ($_REQUEST['node'] == 'image' ? ($matchID === 0 ? 1 : $matchID) : $matchID);
        if (empty($elementName)) $elementName = strtolower($this->childClass);
        $this->orderBy($orderBy);
        $listArray = array_map(function(&$Object) use (&$matchID,&$elementName,&$orderBy,&$filter,&$template) {
            if (!$Object->isValid()) return;
            if (array_key_exists('isEnabled',$this->databaseFields) && !$Object->get('isEnabled')) return;
            $listArray = sprintf('<option value="%s"%s>%s</option>',$Object->get('id'),($matchID == $Object->get('id') ? ' selected' : ($template ? " \${selected_item{$Object->get(id)}" : '')),"{$Object->get(name)} - ({$Object->get(id)})");
            unset($Object);
            return $listArray;
        },(array)$this->find($filter ? array('id'=>$filter):'','',$orderBy,'','','',($filter ? true : false)));
        return (isset($listArray) ? sprintf('<select name="%s" autocomplete="off"><option value="">%s</option>%s</select>',($template ? '${selector_name}' : $elementName),"- ".self::$foglang['PleaseSelect']." -",implode($listArray)) : false);
    }
    public function exists($name, $id = 0, $idField = 'name') {
        if (empty($id)) $id = 0;
        if (empty($idField)) $idField = 'name';
        $query = sprintf(
            $this->existsQueryTemplate,
            $this->databaseTable,
            $this->databaseFields[$idField],
            $this->databaseTable,
            $this->databaseTable,
            $this->databaseFields[$idField],
            $name,
            $this->databaseTable,
            $this->databaseFields[$idField],
            $id
        );
        return (bool)self::$DB->query($query)->fetch()->get('total');
    }
    public function search($keyword = '',$returnObjects = false) {
        if (empty($keyword)) $keyword = trim(self::$isMobile ? $_REQUEST['host-search'] : $_REQUEST['crit']);
        $mac_keyword = join(':',str_split(str_replace(array('-',':'),'',$keyword),2));
        $mac_keyword = preg_replace('#[%\+\s\+]#','%',sprintf('%%%s%%',$mac_keyword));
        if (empty($keyword)) $keyword = '%';
        if ($keyword === '%') return self::getClass($this->childClass)->getManager()->find();
        $keyword = preg_replace('#[%\+\s\+]#','%',sprintf('%%%s%%',$keyword));
        $_SESSION['caller'] = __FUNCTION__;
        $this->array_remove($this->aliasedFields,$this->databaseFields);
        $findWhere = array_fill_keys(array_keys($this->databaseFields),$keyword);
        $itemIDs = self::getSubObjectIDs($this->childClass,$findWhere,'id','','OR');
        $HostIDs = self::getSubObjectIDs('Host',array('name'=>$keyword,'description'=>$keyword,'ip'=>$keyword),'','','OR');
        switch (strtolower($this->childClass)) {
        case 'user':
            break;
        case 'host':
            $HostIDs = self::getSubObjectIDs('MACAddressAssociation',array('mac'=>$mac_keyword,'description'=>$keyword),'hostID','','OR');
            $HostIDs = array_merge($HostIDs,self::getSubObjectIDs('Inventory',array('sysserial'=>$keyword,'caseserial'=>$keyword,'mbserial'=>$keyword,'primaryUser'=>$keyword,'other1'=>$keyword,'other2'=>$keyword,'sysman'=>$keyword,'sysproduct'=>$keyword),'hostID','','OR'));
            $ImageIDs = self::getSubObjectIDs('Image',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $GroupIDs = self::getSubObjectIDs('Group',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $SnapinIDs = self::getSubObjectIDs('Snapin',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $PrinterIDs = self::getSubObjectIDs('Printer',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            if (count($ImageIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('Host',array('imageID'=>$ImageIDs)));
            if (count($GroupIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('GroupAssociation',array('groupID'=>$GroupIDs),'hostID'));
            if (count($SnapinIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('SnapinAssociation',array('snapinID'=>$SnapinIDs),'hostID'));
            if (count($PrinterIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('PrinterAssociation',array('printerID'=>$PrinterIDs),'hostID'));
            $itemIDs = array_merge($itemIDs,$HostIDs);
            break;
        case 'image':
            if (count($HostIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('Host',array('id'=>$HostIDs),'imageID'));
            break;
        case 'task':
            $TaskStateIDs = self::getSubObjectIDs('TaskState',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $TaskTypeIDs = self::getSubObjectIDs('TaskType',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $ImageIDs = self::getSubObjectIDs('Image',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $GroupIDs = self::getSubObjectIDs('Group',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $SnapinIDs = self::getSubObjectIDs('Snapin',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            $PrinterIDs = self::getSubObjectIDs('Printer',array('name'=>$keyword,'description'=>$keyword),'','','OR');
            if (count($ImageIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('Host',array('imageID'=>$ImageIDs)));
            if (count($GroupIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('GroupAssociation',array('groupID'=>$GroupIDs),'hostID'));
            if (count($SnapinIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('SnapinAssociation',array('snapinID'=>$SnapinIDs),'hostID'));
            if (count($PrinterIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('PrinterAssociation',array('printerID'=>$PrinterIDs),'hostID'));
            if (count($TaskStateIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('Task',array('stateID'=>$TaskStateIDs)));
            if (count($TaskTypeIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('Task',array('typeID'=>$TaskTypeIDs)));
            if (count($HostIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs('Task',array('hostID'=>$HostIDs)));
            break;
        default:
            $assoc = sprintf('%sAssociation',$this->childClass);
            $objID = sprintf('%sID',strtolower($this->childClass));
            if (!class_exists($assoc)) break;
            if (count($itemIDs) && !count($HostIDs)) break;
            $HostIDs = array_merge($HostIDs,self::getSubObjectIDs($assoc,array($objID=>$itemIDs),'hostID'));
            if (count($HostIDs)) $itemIDs = array_merge($itemIDs,self::getSubObjectIDs($assoc,array('hostID'=>$HostIDs),$objID));
            break;
        }
        $itemIDs = array_values(array_filter(array_unique($itemIDs)));
        if ($returnObjects) return self::getClass($this->childClass)->getManager()->find(array('id'=>$itemIDs));
        return $itemIDs;
    }
}
