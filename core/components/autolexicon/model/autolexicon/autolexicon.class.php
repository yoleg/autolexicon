<?php
/**
 * AutoLexicon
 *
 * Copyright 2012 Oleg Pryadko <oleg@websitezen.com>
 * Based on Babel 2.2.5 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of AutoLexicon for MODX Revolution.
 *
 * AutoLexicon is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * AutoLexicon is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * AutoLexicon; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package autolexicon
 */
class AutoLexiconException extends Exception {
    protected $params = array();
    function __construct($message = "", array $params=array(), $code = 0, Exception $previous = null){
        parent::__construct($message, $code, $previous);
        $this->params = $params;
    }
    public function getParams() {return $this->params;}
}
class AutoLexicon {
    /**
     * @access protected
     * @var array A collection of preprocessed chunk values.
     */
    protected $chunks = array();
    /**
     * @access public
     * @var modX A reference to the modX object.
     */
    public $modx = null;
    /**
     * @access public
     * @var array A collection of properties to adjust AutoLexicon behaviour.
     */
    public $config = array();

    /**
     * The AutoLexicon Constructor.
     *
     * This method is used to create a new AutoLexicon object.
     *
     * @param modX &$modx A reference to the modX object.
     * @param array $config A collection of properties that modify AutoLexicon
     * behaviour.
     * @return AutoLexicon A unique AutoLexicon instance.
     */
    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('autolexicon.core_path',null,$modx->getOption('core_path').'components/autolexicon/');
        $assetsUrl = $this->modx->getOption('autolexicon.assets_url',null,$modx->getOption('assets_url').'components/autolexicon/');

        $this->config = array_merge(array(
            'corePath' => $corePath,
            'chunksPath' => $corePath.'elements/chunks/',
            'chunkSuffix' => '.chunk.tpl',
               'cssUrl' => $assetsUrl.'css/',
            'jsUrl' => $assetsUrl.'js/',
            'langs' => $this->commasToArray($modx->getOption('autolexicon.languages',null,'en, es')),
            'sync_fields' => $this->commasToArray($modx->getOption('autolexicon.sync_fields',null,'pagetitle,content,longtitle,description,introtext,menutitle')),
            'sync_tvs' => $this->commasToArray($modx->getOption('autolexicon.sync_tvs',null,'content_below')),
            'skip_value_replacement' => $this->commasToArray($modx->getOption('autolexicon.skip_value_replacement',null,'pagetitle')),
            'set_pagetitle_as_default' => $this->commasToArray($modx->getOption('autolexicon.set_pagetitle_as_default',null,'menutitle')),
            'session_edit_lang_key' => $modx->getOption('autolexicon.session_edit_lang_key',null,'autolexicon.edit_lang'),
            'lexicon_key_prefix' => $modx->getOption('autolexicon.lexicon_key_prefix',null,''),
            'null_value' => $modx->getOption('autolexicon.null_value',null,'NULL'),
            'resource_cache_key_prefix' => $modx->getOption('autolexicon.resource_cache_key_prefix',null,'resource-'),
            'default_lang' => $this->modx->getOption('autolexicon.default_language', null, $this->modx->getOption('cultureKey', null, 'en')),
        ),$config);
        $this->config['fields'] = array_merge($this->config['sync_tvs'],$this->config['sync_fields']);
        // clean
        /* load autolexicon lexicon */
        $this->modx->getService('lexicon','modLexicon');
        if ($this->modx->lexicon) {
            $this->modx->lexicon->load('autolexicon:default');
        }
    }

    // todo-important: fix TV storage (fails to save lexicon key)
    // todo-important: set pagetitle to save to default lang
/*******************************************/
/*               Global                    */
/*******************************************/
    /**
     * Switches the language system setting, lexicon parsing setting, and resource cache key.
     *
     * @param string $lang One of the two-character language keys that is handled by AutoLexicon
     */
    public function _switchLanguage($lang) {
        // sets the cultureKey used for lexicon translation
        $this->modx->cultureKey = $lang;
        // tris to set the setting cultureKey for use in MODX tags. Doesn't work in all versions of MODX.
        $this->modx->setOption('cultureKey',$lang);
        // separates resource caching for each language
        $this->modx->setOption('cache_resource_key',($this->config['resource_cache_key_prefix'].$lang));
        // reloads the lexicon for the new language
        $this->modx->getService('lexicon','modLexicon');
        if ($this->modx->lexicon) {
            $this->modx->lexicon->load($lang.':autolexicon:resource');
        }
    }

    /**
     * Gets the lexicon key for use with a particular resource's field.
     *
     * @param int $resource_id
     * @param string $field
     * @return string The lexicon key
     */
    private function _getLexiconKey($resource_id, $field) {
        return $this->config['lexicon_key_prefix'].$resource_id.'_'.$field;
    }

    public function _fieldIsTv($field) {
        return !in_array($field,$this->config['sync_fields']);
    }
    public function _getResourceField(modResource $resource, $field) {
        if ($this->_fieldIsTv($field)) {
            $output = $resource->getTVValue($field);
        } else {
            $output = $resource->get($field);
        }
        return $output;
    }
    public function _setResourceField(modResource $resource, $field, $value) {
        if ($this->_fieldIsTv($field)) {
            $resource->setTVValue($field, $value);
        } else {
            $resource->set($field, $value);
        }
    }
    public function _translateResourceFields(modResource $resource){
        foreach ($this->config['fields'] as $field) {
            $lexicon_key = $this->_getLexiconKey($resource->get('id'), $field);
            $field_content = $this->modx->lexicon($lexicon_key);
            // if the lexicon tag is NULL, but not the resource content, skip this field
            if($field_content == $this->config['null_value']) {
                $resource_content = $this->_getResourceField($resource, $field);
                if ($resource_content) {
                    return;
                }
            }
            if($lexicon_key != $field_content) {
                $this->_setResourceField($resource, $field, $field_content);
            }
        }
    }

/*******************************************/
/*               Web Only                  */
/*******************************************/
    public function OnHandleRequest() {
        if (!($this->modx->context->get('key') == 'mgr')) {
            $lang = $this->modx->cultureKey;
            $this->_switchLanguage($lang);
        }
    }
    public function OnLoadWebDocument(modResource $resource) {
        // insert the lexicon key into each resource field to avoid extra tag parsing
        $this->_translateResourceFields($resource);
    }

    /*******************************************/
/*               Manager Only              */
/*******************************************/
    /**
     * Gets the language currently being edited, or updates the session if switched.
     *
     * @return string The current language code.
     */
    public function _getCurrentManagerLang() {
        $session_lang = $this->modx->getOption($this->config['session_edit_lang_key'], $_SESSION, $this->config['default_lang']);
        $current_lang = in_array($session_lang, $this->config['langs']) ? $session_lang : $this->config['default_lang'];
        if(isset($_GET['autolexicon_lang']) && in_array($_GET['autolexicon_lang'],$this->config['langs'])) {
            $current_lang = $_GET['autolexicon_lang'];
        }
        if ($current_lang != $session_lang) {
            $_SESSION[$this->config['session_edit_lang_key']] = $current_lang;
        }
        return $current_lang;
    }


    /**
     * Generates the HTML for the buttons used to switch the resource editor language.
     *
     * @param modResource $resource
     * @param array $actions
     * @return string The button HTML
     */
    public function _createManagerButtons(modResource $resource, array $actions) {
        $outputLanguageItems ='';
        $current_lang = $this->_getCurrentManagerLang();
        foreach ($this->config['langs'] as $lang) {
            $current = ($current_lang && $current_lang == $lang) ? true : false;
            $resourceUrl = $current ? '#' : (
                '?a='.$actions['resource/update'].'&amp;id='.
                $resource->get('id').'&amp;autolexicon_lang='.$lang
            );
            $className = $current ? 'selected' : '';
            $placeholders = array(
                'cultureKey' => $lang,
                'resourceUrl' => $resourceUrl,
                'className' => $className,
            );
            $outputLanguageItems .= $this->getChunk('mgr/autolexiconBoxItem', $placeholders);
        }
        return $outputLanguageItems;
    }

    public function _updateResource(modResource $resource, $current_lang){
        // todo: choose default for missing entries: leave blank, use default lang value, or static default
        // todo: add alternative to ignoring pagetitle, or add lexicon key to menutitle
        foreach ($this->config['fields'] as $field) {
            $this->_updateResourceFieldLexicon($resource, $field, $current_lang);
            $this->_updateResourceField($resource, $field);
            // create empty slots for the other languages if they don't already exist
            foreach ($this->config['langs'] as $lang) {
                if ($lang != $current_lang) {
                    $this->_initResourceFieldLexicon($resource, $field, $lang);
                }
            }
        }
        $resource->save();
    }

    /**
     * Initializes the lexicon entries for a resource field.
     *
     * @param modResource $resource
     * @param string $field Field Name
     * @param string $lang Language Key
     */
    public function _initResourceFieldLexicon(modResource $resource, $field, $lang) {
        $entry = $this->_getLexiconEntry($resource->get('id'), $field, $lang);
        // only save the entry if it has just been created
        if (!$entry->get('id')) {
            $entry->save();
        }
    }
    /**
     * Updates the lexicon entries for a resource field.
     *
     * @param modResource $resource
     * @param string $field Field Name
     * @param string $lang Language Key
     */
    public function _updateResourceFieldLexicon(modResource $resource, $field, $lang) {
        $entry = $this->_getLexiconEntry($resource->get('id'), $field, $lang);
        $new_value = $this->_getResourceField($resource, $field);
        // set as null fields that already contain at least one lexicon key
        if($this->_hasLexiconTag($new_value)) {
            $new_value = $this->config['null_value'];
        }
        // update the current language
        $new_value = (string) $new_value ? $new_value : '';
        if ($new_value != $entry->get('value')) {
            $entry->set('value', $new_value);
            $entry->save();
        }
    }

    /**
     * Updates the permanent values of the resource field without saving the resource.
     *
     * @param modResource $resource
     * @param string $field Field Name
     */
    public function _updateResourceField(modResource $resource, $field) {
        if (in_array($field,$this->config['skip_value_replacement'])) {
            return;
        }
        $new_value = $this->_getResourceField($resource, $field);
        // skip fields that already contain at least one lexicon key
        if($this->_hasLexiconTag($new_value)) {
            return;
        }
        // update the current language
        if ($new_value) {
            $value_to_set = $this->_generateLexiconTag($resource->get('id'), $field);
        } elseif (in_array($field,$this->config['set_pagetitle_as_default'])) {
            $value_to_set = $this->_generateLexiconTag($resource->get('id'), 'pagetitle');
        } else {
            $value_to_set = '';
        }
        $this->_setResourceField($resource, $field, $value_to_set);
    }

    /**
     * Creates a lexicon tag for use when the resource field is directly parsed.
     *
     * @param int $resource_id
     * @param string $field
     * @return string The lexicon tag
     */
    public function _generateLexiconTag($resource_id, $field) {
        $lexicon_key = $this->_getLexiconKey($resource_id, $field);
        // [[!%139_tvOrFieldName? &topic=`resource` &namespace=`autolexicon`]]
        return "[[!%{$lexicon_key}? &topic=`resource` &namespace=`autolexicon`]]";
    }

    public function _hasLexiconTag($string) {
        foreach(array('[[%','[[!%') as $marker) {
            if (strpos($string, $marker) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Finds or creates a lexicon entry object for the resource id, field, and language.
     *
     * @param int $resource_id Resource ID
     * @param string $field Field Name
     * @param string $lang Language code
     * @return modLexiconEntry|null|object
     */
    public function _getLexiconEntry($resource_id, $field, $lang) {
        $lexicon_key = $this->_getLexiconKey($resource_id, $field);
        $base_array = array(
            'name' => $lexicon_key,
            'topic' => 'resource',
            'namespace' => 'autolexicon',
            'language' => $lang,
        );
        $entry = $this->modx->getObject('modLexiconEntry',$base_array);
        if(!($entry instanceof modLexiconEntry)) {
            /** @var $entry modLexiconEntry */
            $entry = $this->modx->newObject('modLexiconEntry');
            $entry->fromArray($base_array);
        }
        if(!($entry instanceof modLexiconEntry)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,"AutoLexicon could not create a lexicon entry for field {$field}, lang {$lang}, and resource id {$resource_id}");
        }
        return $entry;
    }


    /**
     * Refreshes the cache for all the special resource cache keys used by AutoLexicon.
     */
    public function _refreshCache() {
        $providers = array();
        foreach ($this->config['langs'] as $lang) {
            $providers[$this->config['resource_cache_key_prefix'].$lang] = array();
        };
        $this->modx->cacheManager->refresh($providers);
        $this->modx->cacheManager->refresh();
    }


/*******************************************/
/*               Manager Events            */
/*******************************************/
    public function OnDocFormPrerender(modResource $resource) {
        /* grab manager actions IDs */
        $actions = $this->modx->request->getAllActionIDs();
        /* create autolexicon-box with links to translations */
        $outputLanguageItems = $this->_createManagerButtons($resource, $actions);
        $output = '<div id="autolexicon-box">'.$outputLanguageItems.'</div>';
        $this->modx->event->output($output);
        /* include CSS */
        $this->modx->regClientCSS($this->config['cssUrl'].'autolexicon.css?v=6');
        $this->modx->regClientStartupScript($this->config['jsUrl'].'autolexicon.js?v=3');
    }

    public function OnDocFormRender(modResource $resource) {
        $old_lang = $this->modx->cultureKey;
        $lang = $this->_getCurrentManagerLang();
        $this->_switchLanguage($lang);
        // set resource fields without saving resource
        $this->_translateResourceFields($resource);
        $this->_switchLanguage($old_lang);
    }

    public function OnDocFormSave(modResource $resource) {
        $current_lang = $this->_getCurrentManagerLang();
        $this->_updateResource($resource, $current_lang);
        $this->_refreshCache();
    }















/*******************************************/
/*               UNUSED                    */
/*******************************************/
    public function _getLexiconTagRegEx() {
        return '\[\[[\!]*\%'.$this->config['lexicon_key_prefix'].'([0-9]+)\_([\w-]+)\?\s+('.
            '(\&topic\=\`resource\`\s+&namespace=\`autolexicon\`)'.
            '|'.
            '(&namespace=\`autolexicon\`\s+\&topic\=\`resource\`)'.
            ')\]\]';
    }

    public function _parseLexiconTags($string) {
        // todo: load lexicon topic right before parsing?
        $regex = '/'.$this->_getLexiconTagRegEx().'/';
        preg_match_all($regex, $string, $matches, PREG_SET_ORDER);
        foreach ($matches as $val) {
            $full_lexicon_tag = $val[0];
            $resource_id = $val[1];
            $field = $val[2];
            $lexicon_key = $this->_getLexiconKey($resource_id, $field);
            $lexicon_value = $this->modx->lexicon($lexicon_key);
//            $new_value = $lexicon_value != $lexicon_key ? $lexicon_value : '';
            $new_value = $lexicon_value;
            $string = str_replace($full_lexicon_tag,$new_value,$string);
        }
        return $string;
    }

    public function OnManagerPageAfterRender(modManagerController $controller) {
        return;
        $old_lang = $this->modx->cultureKey;
        $lang = $this->_getCurrentManagerLang();
        $this->_switchLanguage($lang);
        $content = $controller->content;
        $content = $this->_parseLexiconTags($content);
        $this->_switchLanguage($old_lang);
        $controller->content = $content;
    }



/*******************************************/
/*               Utility Stuff             */
/*******************************************/
    /**
     * Cleans a comma-separated string into an array of non-empty values
     *
     * @param string $string Input comma-separated string
     * @param string $separator Explode separator
     * @param string $trim_chars Characters to trim from each value
     * @return array Trimmed array of non-empty values
     */
    public function commasToArray($string, $separator=',', $trim_chars=' ') {
        $raw = explode($separator,$string);
        $output = array();
        foreach($raw as $v) {
            $v = trim($v,$trim_chars);
            if (!empty($v)) {
                $output[] = $v;
            }
        }
        return $output;
    }

    /**
    * Gets a Chunk and caches it; also falls back to file-based templates
    * for easier debugging.
    *
    * @access public
    * @param string $name The name of the Chunk
    * @param array $properties The properties for the Chunk
    * @return string The processed content of the Chunk
    */
    public function getChunk($name,array $properties = array()) {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
            $chunk = $this->modx->getObject('modChunk',array('name' => $name),true);
            if (empty($chunk)) {
                $chunk = $this->_getTplChunk($name,$this->config['chunkSuffix']);
                if ($chunk == false) return false;
            }
            $this->chunks[$name] = $chunk->getContent();
        } else {
            $o = $this->chunks[$name];
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setContent($o);
        }
        $chunk->setCacheable(false);
        return $chunk->process($properties);
    }

    /**
    * Returns a modChunk object from a template file.
    *
    * @access private
    * @param string $name The name of the Chunk. Will parse to name.chunk.tpl by default.
    * @param string $suffix The suffix to add to the chunk filename.
    * @return modChunk/boolean Returns the modChunk object if found, otherwise
    * false.
    */
    private function _getTplChunk($name,$suffix = '.chunk.tpl') {
        $chunk = false;
        $f = $this->config['chunksPath'].strtolower($name).$suffix;
        if (file_exists($f)) {
            $o = file_get_contents($f);
            /** @var $chunk modChunk */
            $chunk = $this->modx->newObject('modChunk');
            $chunk->set('name',$name);
            $chunk->setContent($o);
        }
        return $chunk;
    }

}
