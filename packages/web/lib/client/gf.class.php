<?php
class GF extends FOGClient implements FOGClientSend {
    public function send() {
        $SendEnc = '';
        $SendMe = array();
        $this->send = '#!na';
        foreach (self::getClass('GreenFogManager')->find() AS &$gf) {
            if (!$gf->isValid()) continue;
            $val = sprintf('%s@%s@%s',$gf->get('hour'),$gf->get('min'),$gf->get('action'));
            $SendMe[$i] = base64_encode($val);
            if ($this->newService) {
                if ($this->json) {
                    $vals['tasks'][] = array(
                        'hour' => $gf->get('hour'),
                        'min' => $gf->get('min'),
                        'action' => ($gf->get('action') === 'r' ? 'reboot' : ($gf->get('action') === 's' ? 'shutdown' : false)),
                    );
                    continue;
                }
                if (!$i) $SendMe[$i] = "#!ok\n";
                $SendMe[$i] .= sprintf("#task%s=%s\n",$i,$val);
            }
            unset($gf);
        }
        if ($this->json) {
            if (count($vals)) return $vals;
            return array('error'=>'na');
        }
        if (count($SendMe)) $this->send = implode($SendMe);
    }
}
