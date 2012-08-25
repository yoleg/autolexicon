<?php
/*
 * BaseSite
 *
 * Copyright 2011 by Oleg Pryadko (websitezen.com) 
 
 * This file is part of BaseSite, a quick-start site package for MODx Revolution
 *
 * BaseSite is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * BaseSite is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * BaseSite; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 
 * @package BaseSite
 */
/**
 * Build the setup options form.
 *
 * @package BaseSite
 * @subpackage build
 */
/**
 * @var MODx $modx
 * @var array $options
 */
/* The return value from this script should be an HTML form (minus the
 * <form> tags and submit button) in a single string.
 *
 * The form will be shown to the user during install
 * after the readme.txt display.
 *
 * The user's entries in the form's input field(s) will be available
 * in any php resolvers with $modx->getOption('field_name', $options, 'default_value').
 */
/* set some default values */
$values = array(
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
    'translate_settings' => array(
        'label' => 'Site Settings to Translate',
        'default' => 'base_url,site_url,site_name',
    ),
    'enabled' => array(
        'label' => 'Enable AutoLexicon',
        'default' => true,
    ),
);
$output = '';
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        /** @var $setting modSystemSetting */
        foreach ($values as $key => $config) {
            $default = isset($config['default']) ? $config['default'] : '';
            $label = isset($config['label']) ? $config['label'] : $key;
            $value = $modx->getOption($key,null,$default);
            $unique_key = str_replace('.','_',$key);
            $output .= '<p style="margin-bottom: 1em;"><label for="'.$unique_key.'">'.$label.':</label>';
            if (!is_bool($default)) {
                $output .='<input type="text" name="'.$key.'" id="'.$unique_key.'" width="300" value="'.$value.'" />';
            } else {
                $output .='<input type="hidden" name="'.$key.'" id="'.$unique_key.'" value="" />';
                $checked = $value ? 'checked' : '';
                $output .='<input type="checkbox" name="'.$key.'" id="'.$unique_key.'" value="'.$value.'" checked="'.$checked.'" />';
            }
            $output .='</p>';
        }
		break;
    case xPDOTransport::ACTION_UNINSTALL: break;
}
return $output;
