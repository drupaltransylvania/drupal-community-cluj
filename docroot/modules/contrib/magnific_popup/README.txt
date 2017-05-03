Drupal Magnific Popup 8.x Module:
-----------------------------
Maintainers:
    Eric Goodwin (https://www.drupal.org/user/2877061)
Requires - Drupal 8
License - GPL (see LICENSE)

Magnific Popup jQuery Plugin:
-----------------------------
Author:
    Dmitry Semenov (http://dimsemenov.com)
License - MIT
Github - https://github.com/dimsemenov/Magnific-Popup

Overview:
---------
This module provides field formatters for the Magnific Popup jquery plugin by
Dmitry Semenov (https://github.com/dimsemenov/Magnific-Popup).
This plugin is ideal for creating pop-up galleries of pictures or videos.

The 8.x module is still under heavy development with new features being added
as they are thought of or suggested. If you have an idea or want to report a bug
please don't hesitate to file an issue! :)
https://www.drupal.org/project/issues/magnific_popup

Features:
---------
- Has different gallery types including:
 * 'First Item Only' - Display only one thumbnail from multiple images on the
 field, but all images can be viewed by navigating in the pop-up gallery.
 * 'Gallery - All Items' - Show all thumbnails and allow all items to be seen
 in the pop-up gallery.
 * 'Separate Items' - Show all thumbnails, but don't allow navigation to other
 items in the pop-up gallery. This only displays the clicked image in the
 pop-up.

- Integration with video_embed_field to provide pop-up videos for different
  providers with various embed and auto-play options. Also has the gallery options
  listed above available for the video thumbnails and galleries.

Installation:
-------------
1. Download a 1.x magnific popup release from https://github.com/dimsemenov/Magnific-Popup/releases.
2. Copy the contents of the "dist" folder into DRUPAL_ROOT/libraries/magnific-popup.
   To be correctly detected and used, the JS and CSS must be located at these paths:
    * libraries/magnific-popup/jquery.magnific-popup.min.js
    * libraries/magnific-popup/magnific-popup.css
3. You can check that the library is install correctly by checking the status report
   for your drupal installation at /admin/reports/status.
4. Enable the magnific_popup module and select it as the display formatter
   for a supported field (e.g. image, video_embed_field).
   You can then configure the gallery settings from the display formatter options.

Configuration:
--------------
Currently all settings for this module are configured on the field formatter.
