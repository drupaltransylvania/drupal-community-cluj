(function($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.magnific_popup_video_embed_field = {
    attach: function (context) {
      $(".mfp-video-embed-first-item, .mfp-video-embed-all-items", context).once("mfp-processed").each(function() {
        var gallery_items = [];

        $(this).find(".mfp-video-embed-popup").each(function() {
          gallery_items.push({
            src:
            '<div class="mfp-embedded-video-popup">'+
            $(this).data("mfp-video-embed") +
            '</div>'
          });
        });

        $(this).magnificPopup({
          items: gallery_items,
          gallery: {
            enabled: true
          },
          type: 'inline'
        });
      });

      $(".mfp-video-embed-separate-items", context).each(function() {
        $(this).find(".mfp-video-embed-popup").once("mfp-processed").click(function(e) {
          // Stop linking to video URL instead of showing popup.
          // See video-embed-field.colorbox.js in video_embed_field for more.
          e.preventDefault();

          $.magnificPopup.open({
            items: {
              src:
              '<div class="mfp-embedded-video-popup">'+
              $(this).data("mfp-video-embed") +
              '</div>'
            },
            type: 'inline'
          });
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
