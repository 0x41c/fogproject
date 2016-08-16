<?php
class FOGGetSet extends FOGBase
{
    protected $data = array();
    public function __construct($data = array())
    {
        array_walk($data, function (&$value, &$key) {
            $this->set($key, $value);
            unset($value, $key);
        });
    }
    public function set($key, $value)
    {
        try {
            if (!array_key_exists($key, $this->data)) {
                throw new Exception(_('Invalid key being set'));
            }
            $this->data[$key] =& $value;
        } catch (Exception $e) {
            $this->debug('Set Failed: Key: %s, Value: %s, Error: %s', array($key, $value, $e->getMessage()));
        }
        return $this;
    }
    public function get($key = '')
    {
        if (!$key) {
            return $this->data;
        }
        if (!array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
            return false;
        }
        if (is_object($this->data[$key])) {
            $this->info(sprintf('%s: %s, %s: %s', _('Returning value of key'), $key, _('Object'), $this->data[$key]->__toString()));
        } elseif (is_array($this->data[$key])) {
            $this->info(sprintf('%s: %s', _('Returning array within key'), $key));
        } else {
            $this->data[$key] = str_replace('\r\n', "\n", $this->data[$key]);
            $this->info(sprintf('%s: %s, %s: %s', _('Returning value of key'), $key, _('Value'), $this->data[$key]));
        }
        return $this->data[$key];
    }
}
