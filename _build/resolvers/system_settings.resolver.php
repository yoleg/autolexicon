<?php
/**
 * Sets user-specified settings and any other dynamic site-specific settings.
 *
 * @package AutoLexicon
 * @subpackage build
 */
/**
 * @var MODx $modx
 * @var xPDOObject $object
 * @var array $options
 */
if ($object->xpdo) {
    $modx = $object->xpdo;
    $namespace = $modx->getOption('namespace', $options, 'autolexicon');
    $prefix = $namespace.'.';
    $settings = array(
        'default_language' => array(
            'label' => 'Default Language',
            'default' => $modx->getOption('cultureKey'),
        ),
        'languages' => array(
            'label' => 'Languages to Translate',
            'default' => $modx->getOption('cultureKey').',es',
        ),
        'translate_settings' => array(
            'label' => 'Site Settings to Translate',
            'default' => 'base_url,site_url,site_name',
        ),
        'enabled' => array(
            'label' => 'Enable AutoLexicon',
            'default' => true,
        ),
    );
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx = $object->xpdo;
            // store options in system settings
            foreach($settings as $key => $conf){
                if (!isset($options[$key])) {
                    $modx->log(modX::LOG_LEVEL_ERROR, "{$key} option not found ");
                    return false;
                }
                // todo: add setting lexicon
                /** @var $setting modSystemSetting */
                $setting_key = $prefix . $key;
                $setting = $modx->getObject('modSystemSetting',array('key' => $setting_key));
                if (!($setting instanceof modSystemSetting)) {
                    $setting = $modx->newObject('modSystemSetting');
                }
                if ($setting instanceof modSystemSetting) {
                    $default = $modx->getOption('default',$conf,$key);
                    $value = $options[$key];
                    $conf = array(
                        'key' => $setting_key,
                        'namespace' => $namespace,
                        'xtype' => is_bool($default) ? 'combo-boolean' : 'textfield',
                        'value' => is_bool($default) ? (!empty($value)) : $value,
                    );
                    $setting->fromArray($conf);
                    $setting->save();
                }
            }
            break;
    }
}
