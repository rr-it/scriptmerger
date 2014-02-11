﻿.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

Constants
=========

Properties
^^^^^^^^^^

===================================================== ===================================================================== ======================= ==================
Property                                              Data type                                                             :ref:`t3tsref:stdwrap`  Default
===================================================== ===================================================================== ======================= ==================
externalFileCacheLifetime_                            :ref:`t3tsref:data-type-integer`                                      no                      3600
css.enable_                                           :ref:`t3tsref:data-type-boolean`                                      no                      1
css.addContentInDocument_                             :ref:`t3tsref:data-type-boolean`                                      no                      0
css.minify.enable_                                    :ref:`t3tsref:data-type-boolean`                                      no                      1
css.minify.ignore_                                    :ref:`t3tsref:data-type-string`                                       no                      \.min\.
css.compress.enable_                                  :ref:`t3tsref:data-type-boolean`                                      no                      1
css.compress.ignore_                                  :ref:`t3tsref:data-type-string`                                       no                      \.gz\.
css.merge.enable_                                     :ref:`t3tsref:data-type-boolean`                                      no                      1
css.merge.ignore_                                     :ref:`t3tsref:data-type-string`                                       no                      *empty*
css.uniqueCharset.enable_                             :ref:`t3tsref:data-type-boolean`                                      no                      1
css.uniqueCharset.value_                              :ref:`t3tsref:data-type-string`                                       no                      @charset "UTF-8";
css.postUrlProcessing.pattern_                        :ref:`t3tsref:data-type-string`                                       no                      *empty*
css.postUrlProcessing.replacement_                    :ref:`t3tsref:data-type-string`                                       no                      *empty*
javascript.enable_                                    :ref:`t3tsref:data-type-boolean`                                      no                      1
javascript.addContentInDocument_                      :ref:`t3tsref:data-type-boolean`                                      no                      0
javascript.parseBody_                                 :ref:`t3tsref:data-type-boolean`                                      no                      0
javascript.addBeforeBody_                             :ref:`t3tsref:data-type-boolean`                                      no                      0
javascript.doNotRemoveInDocInBody_                    :ref:`t3tsref:data-type-boolean`                                      no                      1
javascript.minify.enable_                             :ref:`t3tsref:data-type-boolean`                                      no                      1
javascript.minify.ignore_                             :ref:`t3tsref:data-type-string`                                       no                      \?,\.min\.
javascript.minify.useJSMinPlus_                       :ref:`t3tsref:data-type-boolean`                                      no                      0
javascript.minify.useJShrink_                         :ref:`t3tsref:data-type-boolean`                                      no                      1
javascript.compress.enable_                           :ref:`t3tsref:data-type-boolean`                                      no                      1
javascript.compress.ignore_                           :ref:`t3tsref:data-type-string`                                       no                      \?,\.gz\.
javascript.merge.enable_                              :ref:`t3tsref:data-type-boolean`                                      no                      1
javascript.merge.ignore_                              :ref:`t3tsref:data-type-string`                                       no                      \?
===================================================== ===================================================================== ======================= ==================

Property details
^^^^^^^^^^^^^^^^

externalFileCacheLifetime
"""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.externalFileCacheLifetime =` :ref:`t3tsref:data-type-integer`

“Time to live” in seconds for the cache files of external CSS and JS

css.enable
""""""""""

:typoscript:`plugin.tx_scriptmerger.css.enable =` :ref:`t3tsref:data-type-boolean`

Enable all css processes

css.addContentInDocument
""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.addContentInDocument =` :ref:`t3tsref:data-type-boolean`

Embed the resulting css directly into the document in favor of a
linked resource (this automatically disables the compression step).

css.minify.enable
"""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.minify.enable =` :ref:`t3tsref:data-type-boolean`

Enable the minification process

css.minify.ignore
"""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.minify.ignore =` :ref:`t3tsref:data-type-string`

A comma-separated list of files which should be ignored from the minification process.
Be careful, because you need to quote the characters yourself as the entries are considered as regular expressions.

css.compress.enable
"""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.compress.enable =` :ref:`t3tsref:data-type-boolean`

Enable the compression process

css.compress.ignore
"""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.compress.ignore =` :ref:`t3tsref:data-type-string`

A comma-separated list of files which should be ignored from the compression process.
Be careful, because you need to quote the characters yourself as the entries are considered as regular expressions.

css.merge.enable
""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.merge.enable =` :ref:`t3tsref:data-type-boolean`

Enable the merging process

css.merge.ignore
""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.merge.ignore =` :ref:`t3tsref:data-type-string`

A comma-separated list of files which should be ignored from the merging process.
Be careful, because you need to quote the characters yourself as the entries are considered as regular expressions.

css.uniqueCharset.enable
""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.uniqueCharset.enable =` :ref:`t3tsref:data-type-boolean`

Enables the replacement of multiple @charset definitions by the given value option

css.uniqueCharset.value
"""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.uniqueCharset.value =` :ref:`t3tsref:data-type-string`

@charset definition that is added on the top of the merged css files

css.postUrlProcessing.pattern
"""""""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.postUrlProcessing.pattern =` :ref:`t3tsref:data-type-string`

Regular expression pattern (e.g. /(fileadmin)/i)

The pattern and replacement values can be used to fix broken urls inside the combined css file.

css.postUrlProcessing.replacement
"""""""""""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.css.postUrlProcessing.replacement =` :ref:`t3tsref:data-type-string`

Regular expression replacement (e.g. prefix/$i)

javascript.minify.enable
""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.minify.enable =` :ref:`t3tsref:data-type-boolean`

Enable the minification process

javascript.minify.ignore
""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.minify.ignore =` :ref:`t3tsref:data-type-string`

A comma-separated list of files which should be ignored from the minification process.
Be careful, because you need to quote the characters yourself as the entries are considered as regular expressions.

javascript.minify.useJSMinPlus
""""""""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.minify.useJSMinPlus =` :ref:`t3tsref:data-type-boolean`

Use JSMin+ instead of JSMin or JShrink.

javascript.minify.useJSMinPlus
""""""""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.minify.useJShrink =` :ref:`t3tsref:data-type-boolean`

Use JShrink instead of JSMin or JSMin+.

javascript.compress.enable
""""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.compress.enable =` :ref:`t3tsref:data-type-boolean`

Enable the compression process

javascript.compress.ignore
""""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.compress.ignore =` :ref:`t3tsref:data-type-string`

A comma-separated list of files which should be ignored from the compression process.
Be careful, because you need to quote the characters yourself as the entries are considered as regular expressions.

javascript.merge.enable
"""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.merge.enable =` :ref:`t3tsref:data-type-boolean`

Enable the merging process

javascript.merge.ignore
"""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.merge.ignore =` :ref:`t3tsref:data-type-string`

A comma-separated list of files which should be ignored from the merging process.
Be careful, because you need to quote the characters yourself as the entries are considered as regular expressions.

javascript.enable
"""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.enable=` :ref:`t3tsref:data-type-boolean`

Enable all javascript processes (by default only for the head section)

javascript.addContentInDocument
"""""""""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.addContentInDocument=` :ref:`t3tsref:data-type-boolean`

Embed the resulting javascript code directly into the document in favor of a
linked resource (this automatically disables the compression step).

javascript.parseBody
""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.parseBody=` :ref:`t3tsref:data-type-boolean`

Enable this option to enable the minification, processing and merging processes for the body section.
The resulting files are always included directly before the closing body tag.

javascript.addBeforeBody
""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.addBeforeBody=` :ref:`t3tsref:data-type-boolean`

Add the javascript files of the head section directly before the closing body tag. Be careful with this option as
it can cause harm to your site!

javascript.doNotRemoveInDocInBody
"""""""""""""""""""""""""""""""""

:typoscript:`plugin.tx_scriptmerger.javascript.doNotRemoveInDocInBody=` :ref:`t3tsref:data-type-boolean`

This option can be used to prevent embedded scripts inside the document of the body section to be merged as this
is in many cases a possible error source in the final result. Therefore the option is enabled by default.