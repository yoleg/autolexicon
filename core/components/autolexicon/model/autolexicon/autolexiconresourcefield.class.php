<?php
/**
 * @package:
 * @author: Oleg Pryadko (oleg@websitezen.com)
 * @createdon: 8/11/12
 * @license: GPL v.3 or later
 */
require_once dirname(__FILE__).'/autolexiconfield.class.php';
class AutoLexiconResourceField extends AutoLexiconField {
    /** @var modResource A reference to the modResource object */
    public $object = null;
    public $topic = 'resource';
    function __construct(AutoLexicon &$al, xPDOObject $object, $field, $lang, array $config = array()) {
        parent::__construct($al,$object,$field,$lang,$config);
        unset($this->config['skip_value_replacement']);
        unset($this->config['use_another_as_default']);
    }
    public function getConfig($key) {
        if (!isset($this->config[$key])) {
            switch($key) {
                case 'required_field':
                    $value = in_array($this->field, $this->al->config['required_fields']); break;
                case 'skip_value_replacement':
                    $value = in_array($this->field, $this->al->config['skip_value_replacement']); break;
                case 'use_another_as_default':
                    $value = in_array($this->field, $this->al->config['set_pagetitle_as_default']) ? 'pagetitle' : null;
                    break;
                case 'is_tv':
                    $value = !in_array($this->field, $this->al->config['sync_fields']); break;
                default:
                    $value = parent::getConfig($key); break;
            }
            $this->config[$key] = $value;
        }
        return $this->config[$key];
    }


    public function _setObjectField($value, $allow_save=false) {
        // todo-important: fix TV storage (fails to save lexicon key)
        // todo-important: do not set TV if matches default
        if ($this->getConfig('is_tv')) {
            /** @var modTemplateVar $tv */
            $tv = $this->modx->getObject('modTemplateVar',array('name' => $this->field));
            if ($tv && ($value != $tv->get('default_text'))) {
                $tv->setValue($this->object->get('id'),$value);
                if ($allow_save && !$tv->save()) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, "TV failed to save in AutoLexicon");
                }
            }
        } else {
            parent::_setObjectField($value);
        }
    }

    public function _getObjectFieldValue() {
        if ($this->getConfig('is_tv')) {
            $output = $this->object->getTVValue($this->field);
        } else {
            $output = parent::_getObjectFieldValue();
        }
        return $output;
    }

}

