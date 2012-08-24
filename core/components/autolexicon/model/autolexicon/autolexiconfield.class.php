<?php
/**
 * @package:
 * @author: Oleg Pryadko (oleg@websitezen.com)
 * @createdon: 8/11/12
 * @license: GPL v.3 or later
 */
class AutoLexiconResourceField extends AutoLexiconField {
    /** @var modResource A reference to the modResource object */
    public $object = null;
    public $class = 'modResource';

    public function getConfig($key) {
        if (!isset($this->config[$key])) {
            switch ($key) {
                case 'is_tv':
                    $this->config[$key] = !in_array($this->field, $this->handler->config['sync_fields']);
                    break;
            }
        }
        return parent::getConfig($key);
    }

    public function _setObjectField($value, $allow_save = false) {
        // todo-important: fix TV storage (fails to save lexicon key)
        // todo-important: do not set TV if matches default
        if ($this->getConfig('is_tv')) {
            /** @var modTemplateVar $tv */
            $tv = $this->modx->getObject('modTemplateVar', array('name' => $this->field));
            if ($tv && ($value != $tv->get('default_text'))) {
                $tv->setValue($this->object->get('id'), $value);
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


abstract class AutoLexiconField {
    /** @var modX A reference to the modX object. */
    public $modx = null;
    /** @var AutoLexicon A reference to the AutoLexicon object. */
    public $al = null;
    /** @var array A collection of properties to adjust AutoLexicon behaviour. */
    public $config = array();
    /** @var xPDOObject The xpdo object */
    public $object;
    /** @var string The xpdoobject field name */
    public $field;
    /** @var string The language code */
    public $lang;
    /** @var string The lexicon key name */
    public $_lexicon_key;
    public $class = null;

    function __construct(AutoLexiconHandler &$handler, xPDOObject $object, $field, $lang, $config = array()) {
        if (empty($object) || empty($field) || empty($lang)) {
            throw new AutoLexiconException("Incorrect parameters for getALFieldObject");
        }
        if (empty($this->class)) {
            throw new AutoLexiconException("Class cannot be empty in a AutoLexiconField");
        }
        $this->al =& $handler->al;
        $this->handler =& $handler;
        $this->modx =& $handler->modx;
        $this->object =& $object;
        $this->field = $field;
        $this->lang = $lang;
        $this->object_id = $this->modx->getOption('object_id', $config, $object->get('id'));
        $this->_lexicon_key = $this->handler->getLexiconKey($object->get('id'), $field);
        $this->config = $config;
    }

    public function getConfig($key) {
        if (!isset($this->config[$key])) {
            $value = null;
            switch ($key) {
                case 'required_field':
                    $value = in_array($this->field, $this->handler->config['required_fields']);
                    break;
                case 'replace_field':
                    $value = in_array($this->field, $this->handler->config['replace_fields']);
                    break;
                case 'set_as_default':
                    $value = in_array($this->field, $this->handler->config['set_as_default']) ? $this->handler->config['default_field'] : false;
                    break;
            }
            $this->config[$key] = $value;
        }
        if (!isset($this->config[$key])) {
            throw new AutoLexiconException("AutoLexiconField::getConfig({$key}) - key not in config.");
        }
        return $this->config[$key];
    }

    /**
     * Creates a lexicon tag for use when the object field is directly parsed.
     *
     * @return string The lexicon tag
     */
    public function generateLexiconTag() {
        return $this->handler->getLexiconTag($this->_lexicon_key);
    }

    /**
     * Gets the lexicon value for this object and field.
     *
     * @return null|string The result or null if not found.
     */
    public function _getLexiconValue() {
        return $this->handler->getLexiconValue($this->_lexicon_key, $this->lang);
    }

    /**
     * Finds or creates a lexicon entry object for the object id, field, and language.
     *
     * @return modLexiconEntry|null|object
     */
    public function _getLexiconEntry() {
        return $this->al->getLexiconEntry($this->_lexicon_key, $this->handler->topic, $this->lang);
    }

    public function _hasLexiconTag($string) {
        foreach (array('[[%', '[[!%') as $marker) {
            if (strpos($string, $marker) !== false) {
                return true;
            }
        }
        return false;
    }

    public function _setLexiconEntryValue(modLexiconEntry $entry, $new_value) {
        $new_value = (string)$new_value ? $new_value : '';
        // do not store empty values if avoidable
        $new = $entry->get('id');
        if (empty($new_value) && $this->handler->config['cleanup_empty'] && $new_value !== $this->handler->config['null_value']) {
            if ($new) {
                $entry->remove();
            }
            return;
        }
        if ($new_value == $entry->get('value') && !$new) {
            return;
        }
        $entry->set('value', $new_value);
        $entry->save();
    }

    public function _getObjectFieldValue() {
        return $this->object->get($this->field);
    }

    /**
     * @param mixed $value Set the object field
     * @param bool $allow_save True if saving related objects is allowed.
     * Used for situations where related objects cannot be temporarily value-replaced
     * and must be saved to the database.
     */
    public function _setObjectField($value, $allow_save = false) {
        $this->object->set($this->field, $value);
    }

    /** updaters */
    /**
     * Loads the translation from the lexicon and temporarily replaces the object field with the new value.
     *
     * @param bool $process_tags Whether or not to process MODX tags in the field before setting.
     */
    public function translate($process_tags = false) {
        $lexicon_content = $this->_getLexiconValue();
        if (is_null($lexicon_content)) {
            return;
        }
        // if the lexicon tag is NULL, but not the object content, skip this field
        if ($lexicon_content == $this->handler->config['null_value']) {
            $object_content = $this->_getObjectFieldValue();
            if ($object_content != $this->handler->config['null_value']) {
                return;
            }
        }
        // unused
        if ($process_tags) {
            $this->modx->parser->processElementTags('', $lexicon_content, true, false);
        }
        $this->_setObjectField($lexicon_content);
    }

    /**
     * Syncs the lexicon with the current set object value, and replaces the object value with lexicon tags if applicable.
     *
     * @param null $object_value Use this as the new object value instead of the currently loaded value.
     * @return mixed|null The new value synced to the lexicon, or null if nothing was saved.
     */
    public function sync($object_value = null) {
        $object_value = is_null($object_value) ? $this->_getObjectFieldValue() : $object_value;
        if (empty($object_value) && $this->getConfig('required_field')) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, "AutoLexicon: {$this->field} cannot be empty. Lang: {$this->lang}. Object: {$this->handler->topic} {$this->object_id}");
            return null;
        }
        $value_has_lexicon_tag = $this->_hasLexiconTag($object_value);
        $lexicon_value = $this->_getNewLexiconValue($object_value, $value_has_lexicon_tag);
        if (!is_null($lexicon_value)) {
            $this->_updateLexiconValue($lexicon_value);
        }
        // save lexicon tag to field in object DB if not already has one
        $substitute_value = $this->_getSubstituteObjectValue($object_value, $value_has_lexicon_tag);
        if (!is_null($substitute_value) && $substitute_value !== $object_value) {
            // allow save
            $this->_setObjectField($substitute_value, true);
        }
        return $lexicon_value;
    }

    public function _updateLexiconValue($lexicon_value) {
        $entry = $this->_getLexiconEntry();
        // save only new or changed values
        $this->_setLexiconEntryValue($entry, $lexicon_value);
        // create empty slots for the other languages if they don't already exist
        $old_value = $this->_getLexiconValue();
        // sync other languages
        // todo: only sync with default lang
        foreach ($this->al->config['langs'] as $other_lang) {
            if ($other_lang == $this->lang) {
                continue;
            }
            $alf = $this->handler->getALFieldObject($this->object, $this->field, $other_lang);
            // if other entry is new or previously synced, update with the new value
            $other_entry = $alf->_getLexiconEntry();
            if ($this->_allowSync($old_value, $other_entry)) {
                $this->_setLexiconEntryValue($other_entry, $lexicon_value);
            }
        }
        return $lexicon_value;
    }

    public function _allowSync($old_value, modLexiconEntry $other_entry){
        $new = !$other_entry->get('id');
        if ($new) {
            return true;
        }
        if ($this->lang != $this->al->config['default_lang']) {
            return false;
        }
        $previously_synced = (!is_null($old_value) && $other_entry->get('value') == $old_value);
        if ($previously_synced) {
            return true;
        }
        return false;
    }

    public function _getNewLexiconValue($object_value, $value_has_lexicon_tag) {
        $lexicon_value = $object_value;
        // special treatment for fields that already contain at least one lexicon key
        if ($value_has_lexicon_tag) {
            $lexicon_tag = $this->generateLexiconTag();
            // prevent temporary lexicon tag from overwriting content
            if ($object_value == $lexicon_tag) {
                $lexicon_tag = null;
            }
            // prevent lexicon tag from being stored in lexicon
            $lexicon_value = $this->handler->config['null_value'];
        }
        return $lexicon_value;
    }

    public function _getSubstituteObjectValue($object_value, $value_has_lexicon_tag) {
        if ($value_has_lexicon_tag) {
            return null;
        }
        // for non-tag-replaced fields, only store value of default language
        if (!$this->getConfig('replace_field')) {
            if ($this->lang == $this->al->config['default_lang']) {
                $substitute_value = $object_value;
            } else {
                $default_lang_field = $this->handler->getALFieldObject($this->object, $this->field, $this->al->config['default_lang']);
                $substitute_value = $default_lang_field->_getLexiconValue();
            }
        } elseif ($object_value) {
            // create a lexicon tag for the value
            $substitute_value = $this->generateLexiconTag();
        } elseif ($this->getConfig('set_as_default')) {
            // use another field's value as the default
            $other_field = $this->getConfig('set_as_default');
            $other_alf = $this->handler->getALFieldObject($this->object, $other_field, $this->lang);
            $substitute_value = $other_alf->generateLexiconTag();
        } else {
            // or use a blank value as the default
            $substitute_value = '';
        }
        return $substitute_value;
    }

}

