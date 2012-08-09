<?php
/**
 * AutoLexicon
 *
 * From Babel copyright 2010 by Jakob Class <jakob.class@class-zec.de>, adapted by Oleg Pryadko <oleg@websitezen.com> for use with AutoLexicon
 *
 * This file is part of AutoLexicon.
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
/**
 * AutoLexicon English language file
 * 
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package autolexicon
 * @subpackage lexicon
 * 
 * @todo complete autolexicon.language_xx entries for every language
 */

$_lang['autolexicon.tv_caption'] = 'AutoLexicon Translation Links';
$_lang['autolexicon.tv_description'] = 'Maintained by AutoLexicon plugin. Please do not change!';
$_lang['autolexicon.create_translation'] = 'Create translation';
$_lang['autolexicon.unlink_translation'] = 'Unlink translation';
$_lang['autolexicon.link_translation_manually'] = 'or <strong>link translation manually</strong>:';
$_lang['autolexicon.id_of_target'] = 'ID of target:';
$_lang['autolexicon.copy_tv_values'] = 'Copy synchronized TVs to target';
$_lang['autolexicon.save'] = 'Save';
$_lang['autolexicon.translation_pending'] = '[translations pending]';

/* language names */
$_lang['autolexicon.language_ar'] = 'Arabic';
$_lang['autolexicon.language_bg'] = 'Bulgarian';
$_lang['autolexicon.language_ca'] = 'Catalan';
$_lang['autolexicon.language_cs'] = 'Czech';
$_lang['autolexicon.language_da'] = 'Danish';
$_lang['autolexicon.language_de'] = 'German';
$_lang['autolexicon.language_en'] = 'English';
$_lang['autolexicon.language_es'] = 'Spanish';
$_lang['autolexicon.language_fa'] = 'Persian';
$_lang['autolexicon.language_fi'] = 'Finnish';
$_lang['autolexicon.language_fr'] = 'French';
$_lang['autolexicon.language_he'] = 'Hebrew';
$_lang['autolexicon.language_hu'] = 'Hungarian';
$_lang['autolexicon.language_id'] = 'Indonesian';
$_lang['autolexicon.language_it'] = 'Italian';
$_lang['autolexicon.language_ja'] = 'Japanese';
$_lang['autolexicon.language_ko'] = 'Korean';
$_lang['autolexicon.language_lt'] = 'Lithuanian';
$_lang['autolexicon.language_ms'] = 'Malay';
$_lang['autolexicon.language_nl'] = 'Dutch';
$_lang['autolexicon.language_no'] = 'Norwegian (Bokmål)';
$_lang['autolexicon.language_pl'] = 'Polish';
$_lang['autolexicon.language_pt'] = 'Portuguese';
$_lang['autolexicon.language_ro'] = 'Romanian';
$_lang['autolexicon.language_ru'] = 'Russian';
$_lang['autolexicon.language_sk'] = 'Slovak';
$_lang['autolexicon.language_sl'] = 'Slovenian';
$_lang['autolexicon.language_sr'] = 'Serbian';
$_lang['autolexicon.language_sv'] = 'Swedish';
$_lang['autolexicon.language_tr'] = 'Turkish';
$_lang['autolexicon.language_uk'] = 'Ukrainian';
$_lang['autolexicon.language_vi'] = 'Vietnamese';
$_lang['autolexicon.language_zh'] = 'Chinese';

/* error messages */
$_lang['error.invalid_context_key'] = '[[+context]] is no valid context key.';
$_lang['error.invalid_resource_id'] = '[[+resource]] is no valid resource id.';
$_lang['error.resource_from_other_context'] = 'Resource [[+resource]] does not exist in context [[+context]].';
$_lang['error.resource_already_linked'] = 'Resource [[+resource]] is already linked with other resources.';
$_lang['error.no_link_to_context'] = 'There does not exist any link to context [[+context]].';
$_lang['error.unlink_of_selflink_not_possible'] = 'A link to a resource itself can not be unlinked.';
$_lang['error.translation_in_same_context'] = 'A translation can not be created within the same context.';
$_lang['error.translation_already_exists'] = 'There is already a translation in context [[+context]].';
$_lang['error.could_not_create_translation'] = 'An error occured while trying to create a translation in context [[+context]].';
