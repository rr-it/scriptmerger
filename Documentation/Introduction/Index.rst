.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

Introduction
------------

This extension provides a significant speedup of your pages. The
speedup depends on the amount of css and javascript files that you
are using on your site. The performance advantage is gained by
concatenating, minimizing and compressing of all css and javascript
files. Furthermore the extension supports different media types and
relations (rel attribute) for css scripts and different output formats
of the result files. Currently you can write them back into the
document as links to external files or as embedded content.

The minification process and @import rule replacement logic is based
upon the extension „Minify“ that you can find at `Google Code <http://code.google.com/p/minify/>`_.
Furthermore the `JSMinPlus <http://crisp.tweakblogs.net/blog/1665/a-new-javascript-minifier-jsmin+.html>`_
project is used as a fast and much more failure-proof alternative to JSMin for the javascript minification
process, but it's still possible to switch to jsmin.