Like Babel for MODX Revolution, but stores the translations in lexicon entries instead of separate contexts.

AutoLexicon avoids all of the duplication that comes with having separate contexts for each language, but has less flexibility for non-linguistic differences between the languages.

NOT READY FOR PRODUCTION USE! Make sure to backup before installing and frequently during testing.

Instructions:
- Create an .htaccess file and gateway plugin just like for Babel, except do NOT switch the context.The important bit is that $modx->cultureKey is set to the proper language before AutoLexicon's OnInitCulture event is run. If the $_REQUEST['cultureKey'] parameter is set early enough, MODX usually will do this automatically.

Known bugs:
    - Cache must be cleared manually after editing elements (chunks, snippets, etc...)
    - Deleting resources or contexts does not delete old lexicon entries
    - Uninstalling package does not allow reverting the substituted resources
    - New topics and languages do not appear for editing automatically in the lexicon editor.


Shortcomings:
    - Pagetitle remains the default language in resource listing snippets such as Wayfinder and GetResources.
        -> Reason: The manager tree cannot be translated easily and must have at least one field in the default language.
        -> Workaround: The menutitle is translated and uses the pagetitle if no other menutitle is set. Use menutitle instead of pagetitle in listing snippets.


Possible roadmap for future releases:
    - automatically translate all text-based TVs without having to list them
    - support for custom database tables

