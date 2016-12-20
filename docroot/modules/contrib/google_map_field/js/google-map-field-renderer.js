
var google_map_field_map;

(function ($, Drupal) {

  Drupal.behaviors.google_map_field_renderer = {
    attach: function (context) {

      $('.google-map-field .map-container').each(function(index, item) {

        // Get the settings for the map from the Drupal.settings object.
        var lat = $(this).attr('data-lat');
        var lon = $(this).attr('data-lon');
        var zoom = parseInt($(this).attr('data-zoom'));

        // Create the map coords and map options.
        var latlng = new google.maps.LatLng(lat, lon);
        var mapOptions = {
          zoom: zoom,
          center: latlng,
          streetViewControl: false,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        google_map_field_map = new google.maps.Map(this, mapOptions);

        google.maps.event.trigger(google_map_field_map, 'resize')

        // Drop a marker at the specified position.
        marker = new google.maps.Marker({
          position: latlng,
          optimized: false,
          map: google_map_field_map
        });

      });

    }
  }

})(jQuery, Drupal);
