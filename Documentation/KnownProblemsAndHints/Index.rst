.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

Known Problems And Hints
------------------------

- It's known that the used @import replacement utility from the „Minify“
  project doesn't resolves URL's. It's not a real problem, because such
  @import rules will simply not be replaced.

- The defer, async and rev attributes are completely ignored.

- Any embedded contents except the javascript code inside the body are
  automatically added to temporary files. It's a small hidden feature
  which shouldn't harm.

- The addBeforeBody option doesn't merges the javascripts of head and
  body. You will have at least two referenced files afterwards. It's a
  known bug which can't be resolved easily, but in most cases you can
  simply ignore it.

- If you experience problems with the compression, make sure you have
  mod\_headers installed and activated. You can do this in e.g. ubuntu
  with a simple „sudo a2enmod headers && sudo service apache2 restart“ command.

- You must have mod\_expires installed to use the expire dates feature in the .htaccess for the
  images, css and javascript files. You can activate the module in e.g. ubuntu by this command:
  „sudo a2enmod expires && sudo service apache2 restart“

- If you have some missing images after the merging process, you can use the
  "postUrlProcessing" option to manually fix them.

- Often problems are caused by an incorrect ordering after the concatenation process if
  some files are ignored. Please make sure that your excluded files don't have
  any dependencies to other scripts. Merged files are currently loaded above all excluded scripts and
  the excluded ones are added one by another in their previous order.

- You can disable the scriptmerger for the request by an URL parameter. Just add the following to your query string:
  “?no\_cache=1&disableScriptmerger=1”.

- If you want to post-process the written files, because you want to push them to a CDN or something like that, you
  can use the proviced internal hook. Just register some class in your ext_localconf.php into this array
  $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['scriptmerger']['writeFilePostHook'] and add the method "writeFilePostHook"
  inside this class with the required logic.

Your problem isn't listed here and you still experience problems with your minified, compressed and/or concatenated
contents? Then please report your issue at the project tracker
on `forge <http://forge.typo3.org/projects/extension-scriptmerger/issues>`_.