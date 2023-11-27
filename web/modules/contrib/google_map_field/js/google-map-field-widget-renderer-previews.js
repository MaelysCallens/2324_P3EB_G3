/**
 * @file
 * JavaScript Google Map Field Preview Renderer.
 *
 * Renders a preview of a Google Maps Field in entity edit forms.
 */

(function ($, Drupal) {

  googleMapFieldPreviews = function (delta) {

    delta = typeof delta === 'undefined' ? -1 : delta;

    $('.google-map-field-preview').each(function () {
      var data_delta = $(this).attr('data-delta');

      if (data_delta == delta || delta == -1) {

        var data_lat = $('input[data-lat-delta="' + data_delta + '"]').val();
        var data_lon = $('input[data-lon-delta="' + data_delta + '"]').val();
        var data_zoom = $('input[data-zoom-delta="' + data_delta + '"]').attr('value');
        var data_type = $('input[data-type-delta="' + data_delta + '"]').attr('value');
        var data_traffic = $('input[data-traffic-delta="' + data_delta + '"]').val() === "1";
        var data_marker = $('input[data-marker-delta="' + data_delta + '"]').val() === "1";
        var data_marker_icon = $('input[data-marker-icon-delta="' + data_delta + '"]').attr('value');
        var show_controls = $('input[data-controls-delta="' + data_delta + '"]').val() === "1";

        data_lat = googleMapFieldValidateLat(data_lat);
        data_lon = googleMapFieldValidateLon(data_lon);
        data_zoom = googleMapFieldValidateZoom(data_zoom);

        var latlng = new google.maps.LatLng(data_lat, data_lon);

        // Create the map preview.
        var mapOptions = {
          zoom: parseInt(data_zoom),
          center: latlng,
          mapTypeId: data_type,
          draggable: false,
          zoomControl: false,
          scrollwheel: false,
          disableDoubleClickZoom: true,
          disableDefaultUI: show_controls ? false : true,
        };
        google_map_field_map = new google.maps.Map(this, mapOptions);

        // See if we need to add a traffic layer.
        if (data_traffic) {
          var trafficLayer = new google.maps.TrafficLayer();
          trafficLayer.setMap(google_map_field_map);
        }

        // Drop a marker at the specified lat/lng coords.
        marker = new google.maps.Marker({
          position: latlng,
          optimized: false,
          visible: data_marker,
          icon: data_marker_icon,
          map: google_map_field_map
        });

        $('#map_setter_' + data_delta).unbind();
        $('#map_setter_' + data_delta).bind('click', function (event) {
          event.preventDefault();
          googleMapFieldSetter($(this).attr('data-delta'));
        });

      }

    });
    // End .each.
  }

  googleMapFieldValidateLat = function (lat) {
    lat = parseFloat(lat);
    if (lat >= -90 && lat <= 90) {
      return lat;
    }
    else {
      return '51.524295';
    }
  }

  googleMapFieldValidateLon = function (lon) {
    lon = parseFloat(lon);
    if (lon >= -180 && lon <= 180) {
      return lon;
    }
    else {
      return '-0.127990';
    }
  }

  googleMapFieldValidateZoom = function (zoom) {
    zoom = parseInt(zoom);
    if (zoom === null || zoom === '' || isNaN(zoom)) {
      return '9';
    }
    else {
      return zoom;
    }
  }

})(jQuery);
