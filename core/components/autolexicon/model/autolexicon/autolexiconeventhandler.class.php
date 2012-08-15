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
abstract class AutoLexiconEventHandler {
    /** @var modX A reference to the modX object. */
    public $modx = null;
    /** @var AutoLexicon A reference to the AutoLexicon object. */
    public $al = null;
    /** @var array A collection of properties to adjust AutoLexicon behaviour. */
    public $config = array();
    /**
     * The AutoLexiconEventHandler Constructor.
     *
     * This method is used to create a new AutoLexiconEventHandler object.
     *
     * @param AutoLexicon $al AutoLexicon service reference
     * @param array $config A collection of properties that modify AutoLexicon
     * behaviour.
     * @return \AutoLexiconEventHandler A unique AutoLexiconEventHandler instance.
     */
    function __construct(AutoLexicon &$al, array $config = array()) {
        $this->modx =& $al->modx;
        $this->al =& $al;
        $this->config = $config;
    }
    /**
     * Handles a MODX event and returns an error code if a problem occurred.
     *
     * @abstract
     * @param string $name The event name (from modx->event->name)
     * @param array $params The event params (from modx->event->params
     * @return mixed
     */
    abstract public function handleEvent($name, array $params);
}

class AutoLexiconEventHandlerWeb extends AutoLexiconEventHandler {
    public function handleEvent($name, array $params) {
        $output = null;
            switch($name) {
                case 'OnInitCulture':
                    $this->OnInitCulture();
                    break;
                case 'OnHandleRequest':
                    $this->OnHandleRequest();
                    break;
                case 'OnLoadWebDocument':
                    $resource =& $this->modx->resource;
                    if (!$resource) {break;}
                    $this->OnLoadWebDocument($resource);
                    break;
                case 'OnWebPageInit':
                    break;
            }
        return $output;
    }

    /*******************************************/
    /*               Web Only                  */
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
        $this->modx->setOption('cultureKey', $lang);
        // separates resource caching for each language
        $this->modx->setOption('cache_resource_key', ($this->config['resource_cache_key_prefix'] . $lang));
        // reloads the lexicon for the new language
        $this->modx->getService('lexicon', 'modLexicon');
        if ($this->modx->lexicon) {
            // todo: extend to other topics
            $this->modx->lexicon->load($lang . ':autolexicon:resource');
        }
        // todo: extend to other settings
        foreach($this->config['translate_settings'] as $setting) {
            $this->modx->setOption($setting, $this->al->translateSetting($setting, $lang));
        }
    }
    public function _generateAliases($lang) {
        $aliases = array();
        foreach ($this->modx->aliasMap as $default_uri => $id) {
            $translated_uri = $this->al->translateAlias($id, $lang);
            if ($translated_uri) {
               $aliases[$translated_uri] = $id;
           } else {
               $aliases[$default_uri] = $id;
           }
        }
        $this->modx->aliasMap = $aliases;
    }

    public function OnInitCulture() {
        if (MODX_API_MODE) {
            return;
        }
        if ($this->modx->context->get('key') == 'mgr') {
            return;
        }
        // todo: specify that this is set via site-specific code or htaccess
        $lang = $this->modx->cultureKey;
        $this->_switchLanguage($lang);
    }

    public function OnHandleRequest() {
        $lang = $this->modx->cultureKey;
        $this->_generateAliases($lang);
    }

    public function OnLoadWebDocument(modResource $resource) {
        // insert the lexicon key into each resource field to avoid extra tag parsing
        // todo: make sure right lang
        $current_lang = $this->modx->cultureKey;
        $this->al->translateObjectFields($resource, $current_lang);
//        $this->al->debug($resource, $current_lang);
    }
}

class AutoLexiconEventHandlerManager extends AutoLexiconEventHandler {
    /** @var array Resources scheduled for removal before the context is permanently removed. The lexicon entries are to be removed after the context is successfully removed. */
    public $resources_removed = array();
    public function handleEvent($name, array $params) {
        $output = null;
        switch($name) {
            case 'OnDocFormPrerender':
            case 'OnDocFormRender':
            case 'OnBeforeDocFormSave':
            case 'OnDocFormSave':
                $resource =& $this->modx->event->params['resource'];
                if (!$resource) {
                    break;
                }
                $output = $this->{$this->modx->event->name}($resource, $this->modx->event->params);
                break;
            case 'OnPageNotFound':
                break;
            case 'OnSiteRefresh':
                $this->OnSiteRefresh();
                break;
            case 'OnResourceDuplicate':
                /* remove translation links to non-existing resources */
                $newResource =& $this->modx->event->params['newResource'];
                $oldResource =& $this->modx->event->params['oldResource'];
                $this->OnDocFormSave($newResource);
                break;
            case 'OnEmptyTrash':
                /* remove translation links to non-existing resources */
                $deletedResourceIds =& $this->modx->event->params['ids'];
                $this->OnEmptyTrash($deletedResourceIds);
                break;
            case 'OnContextBeforeRemove':
            case 'OnContextRemove':
                /* remove translation links to non-existing contexts */
                $context =& $this->modx->event->params['context'];
                if (!$context) {break;}
                $this->{$this->modx->event->name}($context);
                break;
            case 'OnManagerPageAfterRender':
                $controller =& $this->modx->event->params['controller'];
                if (!$controller) {break;}
                $this->OnManagerPageAfterRender($controller);
                break;

        }
        return $output;
    }    
    /**
     * Gets the language currently being edited, or updates the session if switched.
     *
     * @return string The current language code.
     */
    public function _getCurrentManagerLang() {
        $session_lang = $this->modx->getOption($this->config['session_edit_lang_key'], $_SESSION, $this->config['default_lang']);
        $current_lang = in_array($session_lang, $this->config['langs']) ? $session_lang : $this->config['default_lang'];
        if (isset($_GET['autolexicon_lang']) && in_array($_GET['autolexicon_lang'], $this->config['langs'])) {
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
        $outputLanguageItems = '';
        $current_lang = $this->_getCurrentManagerLang();
        foreach ($this->config['langs'] as $lang) {
            $current = ($current_lang && $current_lang == $lang) ? true : false;
            $resourceUrl = $current ? '#' : (
                '?a=' . $actions['resource/update'] . '&amp;id=' .
                    $resource->get('id') . '&amp;autolexicon_lang=' . $lang
            );
            $className = $current ? 'selected' : '';
            $placeholders = array(
                'cultureKey' => $lang,
                'resourceUrl' => $resourceUrl,
                'className' => $className,
            );
            $outputLanguageItems .= $this->al->getChunk('mgr/autolexiconBoxItem', $placeholders);
        }
        return $outputLanguageItems;
    }

    /**
     * PERMANENTLY syncs resource with lexicon per AutoLexicon rules.
     *
     * @param modResource $resource
     * @param $current_lang
     */
    public function _updateResource(modResource $resource, $current_lang) {
        // todo: choose default for missing entries: leave blank, use default lang value, or static default
        foreach ($this->config['fields'] as $field) {
            /** @var $alfo AutoLexiconResourceField */
            $alfo = $this->al->getALFieldObject($resource, $field, $current_lang);
            $alfo->sync();
        }
        $resource->save();
    }

    /**
     * Refreshes the cache for all the special resource cache keys used by AutoLexicon.
     */
    public function _refreshCache() {
        $providers = array();
        foreach ($this->config['langs'] as $lang) {
            $providers[$this->config['resource_cache_key_prefix'] . $lang] = array();
        }
        ;
        $this->modx->cacheManager->refresh($providers);
        $this->modx->cacheManager->refresh();
    }

    public function _removeLexiconLinks(array $resource_ids) {
        $name_array = array();
        $prefix = '';
        foreach ($resource_ids as $resource_id) {
            $entry = array();
            $entry[$prefix . 'name:LIKE'] = $this->al->getLexiconKey($resource_id, '');
            $name_array[] = $entry;
            $prefix = 'OR:';
        }
        $query = $this->modx->newQuery('modLexiconEntry', array(
            'topic' => 'resource',
            'namespace' => 'autolexicon',
            $name_array,
        ));
        $entries = $this->modx->getCollection('modLexiconEntry', $query);
        foreach ($entries as $entry) {
            /** @var $entry modLexiconEntry */
            $entry->remove();
        }
    }

    /*******************************************/
    /*               Manager Events            */
    /*******************************************/
    // todo: add onloadculture event to manager
    public function OnDocFormPrerender(modResource $resource, array $params=array()) {
        /* grab manager actions IDs */
        $actions = $this->modx->request->getAllActionIDs();
        /* create autolexicon-box with links to translations */
        // todo: manually sync langs
        // todo: manually copy langs
        $managerButtons = $this->_createManagerButtons($resource, $actions);
        $output = '<div id="autolexicon-box">' . $managerButtons . '</div>';
        $this->modx->event->output($output);
        /* include CSS/JS */
        $this->modx->regClientCSS($this->config['cssUrl'] . 'autolexicon.css?v=6');
        $this->modx->regClientStartupScript($this->config['jsUrl'] . 'autolexicon.js?v=3');
        return null;
    }

    public function OnDocFormRender(modResource $resource, array $params=array()) {
        $lang = $this->_getCurrentManagerLang();
        $this->al->translateObjectFields($resource, $lang);
    }

    public function OnManagerPageAfterRender(modManagerController $controller) {
//        $old_lang = $this->modx->cultureKey;
//        $lang = $this->_getCurrentManagerLang();
//        $content = $controller->content;
//        $content = $this->_parseLexiconTags($content, $lang);
//        $controller->content = $content;
        return null;
    }

    public function OnBeforeDocFormSave(modResource $resource, array $params=array()) {
//        $data = $this->modx->getOption('data',$params,array());
        return null;
    }

    public function OnDocFormSave(modResource $resource, array $params=array()) {
        // todo: should this be skipped? reloadOnly is when template is switched without saving
        $current_lang = $this->_getCurrentManagerLang();
        if (!$this->modx->getOption('reloadOnly',$params,false)) {
            $this->_updateResource($resource, $current_lang);
            $this->_refreshCache();
        }
        $this->al->translateObjectFields($resource, $current_lang);
        return null;
    }

    public function OnSiteRefresh() {
        $this->_refreshCache();
    }

    public function OnEmptyTrash(array $deletedResourceIds) {
        $this->_removeLexiconLinks($deletedResourceIds);
    }

    public function OnContextBeforeRemove(modContext $context) {
        $resource_ids = array();
        $resources = $context->getMany('ContextResources');
        foreach ($resources as $resource) {
            /** @var $resource modResource */
            $resource_ids[] = $resource->get('id');
        }
        $this->resources_removed[$context->get('key')] = $resource_ids;
    }

    public function OnContextRemove(modContext $context) {
        if (isset($this->resources_removed[$context->get('key')])) {
            $resources_removed = $this->resources_removed[$context->get('key')];
            $this->_removeLexiconLinks($resources_removed);
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, "Failed to remove resources for deleted context " . $context->get('key'));
        }
    }

    public function _getLexiconTagRegEx($topic = 'resource') {
        return '\[\[[\!]*\%' . $this->config['lexicon_key_prefix'] . '([0-9]+)\_([\w-]+)\?\s+(' .
            '(\&topic\=\`' . $topic . '\`\s+&namespace=\`autolexicon\`)' .
            '|' .
            '(&namespace=\`autolexicon\`\s+\&topic\=\`' . $topic . '\`)' .
            ')\]\]';
    }

    public function _parseLexiconTags($string, $topic = 'resource', $lang) {
        $regex = '/' . $this->_getLexiconTagRegEx() . '/';
        preg_match_all($regex, $string, $matches, PREG_SET_ORDER);
        foreach ($matches as $val) {
            $full_lexicon_tag = $val[0];
            $resource_id = $val[1];
            $field = $val[2];
            $lexicon_key = $this->al->getLexiconKey($resource_id, $field);
            $lexicon_value = $this->al->getLexiconValue($lexicon_key, $topic, $lang);
            $string = str_replace($full_lexicon_tag, $lexicon_value, $string);
        }
        return $string;
    }



}
