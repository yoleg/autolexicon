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

        // do not sync pagetitle so it is intelligible in the manager
        // todo: replace menutitle with lexicon key for title if menutitle empty? OR parse lexicon tags in manager display
        $sync_fields = trim($modx->getOption('autolexicon.sync_fields',null,'content,longtitle,description,introtext,menutitle'),' ,');
        $sync_tvs = trim($modx->getOption('autolexicon.sync_tvs',null,''),' ,');
        $langs = trim($modx->getOption('autolexicon.languages',null,'en, es'),' ,');

        $this->config = array_merge(array(
            'corePath' => $corePath,
            'chunksPath' => $corePath.'elements/chunks/',
            'chunkSuffix' => '.chunk.tpl',
               'cssUrl' => $assetsUrl.'css/',
            'jsUrl' => $assetsUrl.'js/',
            'langs' => $langs ? explode(',',$langs) : array(),
            'sync_fields' => $sync_fields ? explode(',',$sync_fields) : array(),
            'sync_tvs' => $sync_tvs ? explode(',',$sync_fields) : array(),
            'session_edit_lang_key' => 'autolexicon.edit_lang',
            'default_lang' => $this->modx->getOption('autolexicon.default_language', null, $this->modx->getOption('cultureKey', null, 'en')),
        ),$config);
        // clean
        foreach($this->config as $k=> $v) {
            if(is_array($v)) {
                foreach($v as $i => $j) {
                    $this->config[$k][$i] = trim($j);
                }
            }
        }
        /* load autolexicon lexicon */
        if ($this->modx->lexicon) {
            $this->modx->lexicon->load('autolexicon:default');
        }
    }

/*******************************************/
/*               Web Only                  */
/*******************************************/
    public function _switchWebLang($lang) {
        // sets the cultureKey used for lexicon translation
        $this->modx->cultureKey = $lang;
        // tris to set the setting cultureKey for use in MODX tags. Doesn't work in all versions of MODX.
        $this->modx->setOption('cultureKey',$lang);
        // separates resource caching for each language
        $this->modx->setOption('cache_resource_key',('resource-'.$lang));
    }
    public function OnHandleRequest() {
        if (!($this->modx->context->get('key') == 'mgr')) {
            $lang = $this->modx->cultureKey;
            $this->_switchWebLang($lang);
        }
        $this->modx->getService('lexicon','modLexicon');
        $this->modx->lexicon->load('autolexicon:resource');
    }
    public function OnLoadWebDocument(modResource &$resource) {
        // insert the lexicon key into each resource field to avoid extra tag parsing
        foreach ($this->config['sync_fields'] as $field) {
            $old_content = $resource->get($field);
            // skip fields that already contain at least one lexicon key
            if(strpos($old_content,'[[%') !== false || strpos($old_content,'[[!%') !== false) {
                continue;
            }
            $lexicon_key = $this->_getLexiconKey($resource->get('id'), $field);
            $field_content = $this->modx->lexicon($lexicon_key);
            if($lexicon_key != $field_content) {
                $resource->set($field,$field_content);
            }
        }
    }

/*******************************************/
/*               Manager Only              */
/*******************************************/
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
    public function _generateLexiconTag($resource_id, $field) {
        $lexicon_key = $this->_getLexiconKey($resource_id, $field);
        return "[[!%{$lexicon_key}? &topic=`resource` &namespace=`autolexicon`]]";
    }
    private function _getLexiconKey($resource_id, $field) {
        return $resource_id.'_'.$field;
    }
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

    public function _refreshCache() {
        $providers = array();
        foreach ($this->config['langs'] as $lang) {
            $providers['resource-'.$lang] = array();
        };
        $this->modx->cacheManager->refresh($providers);
        $this->modx->cacheManager->refresh();
    }

    public function _updateResourceFieldLexicon(modResource $resource, $field, $lang) {
        // todo: finalize support for backwards compatibility
        $entry = $this->_getLexiconEntry($resource->get('id'), $field, $lang);
        $new_value = $resource->get($field);
        // update the current language
        if ($new_value) {
            $tag = $this->_generateLexiconTag($resource->get('id'), $field);
            $resource->set($field, $tag);
            $entry->set('value', $new_value);
        } elseif (!$resource->get($field)) {
            $resource->set($field, '');
            $entry->set('value', '');
        }
        $entry->save();
        return $entry;
    }

    public function _initResourceFieldLexicon(modResource $resource, $field, $lang) {
        $entry = $this->_getLexiconEntry($resource->get('id'), $field, $lang);
        // only save the entry if it has just been created
        if (!$entry->get('id')) {
            $entry->save();
        }
    }



/*******************************************/
/*               Manager Events            */
/*******************************************/
    public function OnDocFormPrerender($resource) {
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

    public function OnDocFormRender(modResource &$resource) {
        // set resource fields without saving resource
        // todo: add support for TVs
        foreach ($this->config['sync_fields'] as $field) {
            $lang = $this->_getCurrentManagerLang();
            $resource_id = $resource->get('id');
            $entry = $this->_getLexiconEntry($resource_id, $field, $lang);
            $old_value = $resource->get($field);
            if($entry->get('id')) {
                $new_value = $entry->get('value');
                $resource->set($field,$new_value);
            } elseif(strpos($old_value,'[[!%')===0 || strpos($old_value,'[[%')===0) {
                $resource->set($field,'');
            }
        }
    }

    public function OnDocFormSave(modResource &$resource) {
        // todo: add support for TVs
        // todo: choose default for missing entries: leave blank, use default lang value, or static default
        $current_lang = $this->_getCurrentManagerLang();
        foreach ($this->config['sync_fields'] as $field) {
            $this->_updateResourceFieldLexicon($resource, $field, $current_lang);
            // create empty slots for the other languages if they don't already exist
            foreach ($this->config['langs'] as $lang) {
                if ($lang == $current_lang) {
                    continue;
                } else {
                    $this->_initResourceFieldLexicon($resource, $field, $lang);
                }
            }
        }
        $this->_refreshCache();
    }




/*******************************************/
/*               Utility Stuff             */
/*******************************************/
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
