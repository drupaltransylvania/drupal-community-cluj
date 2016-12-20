
(function ($) {

  var dialog;
  var google_map_field_map;

  googleMapFieldSetter = function(delta) {

    btns = {};

    btns[Drupal.t('Insert map')] = function () {
      var latlng = marker.position;
      var zoom = google_map_field_map.getZoom();

      $('input[data-lat-delta="'+delta+'"]').prop('value', latlng.lat()).attr('value', latlng.lat());
      $('input[data-lon-delta="'+delta+'"]').prop('value', latlng.lng()).attr('value', latlng.lng());
      $('input[data-zoom-delta="'+delta+'"]').prop('value', zoom).attr('value', zoom);

      googleMapFieldPreviews(delta);

      $(this).dialog("close");
    };

    btns[Drupal.t('Cancel')] = function () {
      $(this).dialog("close");
    };

    dialogHTML = '';
    dialogHTML += '<div id="google_map_field_dialog">';
    dialogHTML += '  <p>' + Drupal.t('Use the map below to drop a marker at the required location.') + '</p>';
    dialogHTML += '  <div id="gmf_container"></div>';
    dialogHTML += '  <div id="centre_on">';
    dialogHTML += '    <label>' + Drupal.t('Enter an address/town/postcode, etc., to center the map on:') + '<input type="text" name="centre_map_on" id="centre_map_on" value=""/></label>';
    dialogHTML += '    <button onclick="return doCentre();" type="button" role="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only button">' + Drupal.t('Find') + '</button>';
    dialogHTML += '    <div id="map_error"></div>';
    dialogHTML += '    <div id="centre_map_results"></div>';
    dialogHTML += '  </div>';
    dialogHTML += '</div>';

    $('body').append(dialogHTML);

    dialog = $('#google_map_field_dialog').dialog({
      modal: true,
      autoOpen: false,
      width: 750,
      height: 550,
      closeOnEscape: true,
      resizable: false,
      draggable: false,
      title: Drupal.t('Set Map Marker'),
      dialogClass: 'jquery_ui_dialog-dialog',
      buttons: btns,
      close: function(event, ui) {
        $(this).dialog('destroy').remove();
      }
    });

    dialog.dialog('open');

    // Create the map setter map.
    // get the lat/lon from form elements
    var lat = $('input[data-lat-delta="'+delta+'"]').attr('value');
    var lon = $('input[data-lon-delta="'+delta+'"]').attr('value');
    var zoom = $('input[data-zoom-delta="'+delta+'"]').attr('value');

    lat = googleMapFieldValidateLat(lat);
    lon = googleMapFieldValidateLon(lon);

    if (zoom == null || zoom == '') {
      var zoom = '9';
    }

    var latlng = new google.maps.LatLng(lat, lon);
    var mapOptions = {
      zoom: parseInt(zoom),
      center: latlng,
      streetViewControl: false,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    google_map_field_map = new google.maps.Map(document.getElementById("gmf_container"), mapOptions);

    // drop a marker at the specified lat/lng coords
    marker = new google.maps.Marker({
      position: latlng,
      optimized: false,
      draggable: true,
      map: google_map_field_map
    });

    // add a click listener for marker placement
    google.maps.event.addListener(google_map_field_map, "click", function(event) {
      latlng = event.latLng;
      marker.setMap(null);
      google_map_field_map.panTo(latlng);
      marker = new google.maps.Marker({
        position: latlng,
        optimized: false,
        draggable: true,
        map: google_map_field_map
      });
    });
    google.maps.event.addListener(marker, 'dragend', function(event) {
      google_map_field_map.panTo(event.latLng);
    });
    return false;
  }

  doCentreLatLng = function(lat, lng) {
    var latlng = new google.maps.LatLng(lat, lng);
    google_map_field_map.panTo(latlng);
    marker.setMap(null);
    marker = new google.maps.Marker({
      position: latlng,
      draggable: true,
      map: google_map_field_map
    });
    google.maps.event.addListener(marker, 'dragend', function(event) {
      google_map_field_map.panTo(event.latLng);
    });
  }

  doCentre = function() {
    var centreOnVal = $('#centre_map_on').val();

    if (centreOnVal == '' || centreOnVal == null) {
      $('#centre_map_on').css("border", "1px solid red");
      $('#map_error').html(Drupal.t('Enter a value in the field provided.'));
      return false;
    }
    else {
      $('#centre_map_on').css("border", "1px solid lightgrey");
      $('#map_error').html('');
    }

    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ 'address': centreOnVal}, function (result, status) {
      $('#centre_map_results').html('');
      if (status == 'OK') {
        doCentreLatLng(result[0].geometry.location.lat(), result[0].geometry.location.lng());
        $('#centre_map_results').html(Drupal.formatPlural(result.length, 'One result found.', '@count results found: '));

        if (result.length > 1) {
          for (var i = 0; i < result.length; i++) {
            var lat = result[i].geometry.location.lat();
            var lng = result[i].geometry.location.lng();
            var link = $('<a onclick="return doCentreLatLng(' + lat + ',' + lng + ');">' + (i + 1) + '</a>');
            $('#centre_map_results').append(link);
          }
        }

      } else {
        $('#map_error').html(Drupal.t('Could not find location.'));
      }
    });

    return false;

  }

})(jQuery);
