/**
 * @file
 * Defined custom functionalities for google maps.
 */

(function ($, Drupal) {
  /**
   * Latitude and longitude processing.
   */
  Drupal.behaviors.scheduler_map = {
    attach: function () {
      getCurrentLocation();

      // Success callback for geolocation.getCurrentPosition function.
      function showLocation(position) {
        var latitude = position.coords.latitude;
        var longitude = position.coords.longitude;
        latlng = new google.maps.LatLng(latitude, longitude);
        setDefaultCoords(latlng);
      }

      // Error callback for geolocation.getCurrentPosition function.
      function errorHandler(err) {
        console.log(err.message);
      }

      // Get the current location based on geolocation.
      function getCurrentLocation() {
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(showLocation, errorHandler);
        }
      }

      function setDefaultCoords(latlng) {
        $("#dcc-lat").prop('value', latlng.lat()).attr('value', latlng.lat());
        $("#dcc-lng").prop('value', latlng.lng()).attr('value', latlng.lng());

        marker = new google.maps.Marker({
          position: latlng,
          optimized: false,
          draggable: true,
          map: google_map_field_map
        });
        google_map_field_map.panTo(latlng);
      }

      // Set location based on the clicked coordinates.
      google.maps.event.addListener(google_map_field_map, "click", function (event) {
        latlng = event.latLng;
        marker.setMap(null);
        setDefaultCoords(latlng);
      });
    }
  }
})(jQuery, Drupal);
