/**
 * @file
 * JavaScript renderer for Google Map Field popup.
 *
 * Renders the settings popup for a Google Maps Field.
 */
(function ($) {

  var dialog;
  var google_map_field_map;

  getMarker = function (position, map) {
    var markerIcon = $('#edit-marker-icon').val();
    var isMarkerVisible = $('#edit-marker').prop('checked');

    var settings = {
      position: position,
      optimized: false,
      draggable: true,
      visible: isMarkerVisible,
      map: map
    };

    if (!!markerIcon) {
      settings.icon = markerIcon;
    }

    return new google.maps.Marker(settings);
  }

  googleMapFieldSetter = function (delta) {

    btns = {};

    btns[Drupal.t('Insert map')] = function () {
      var latlng = marker.position;
      var zoom = $('#edit-zoom').val();;
      var type = $('#edit-type').val();
      var width = $('#edit-width').val();
      var height = $('#edit-height').val();
      var traffic = $('#edit-traffic').prop('checked') ? "1" : "0";
      var show_marker = $('#edit-marker').prop('checked') ? "1" : "0";
      var marker_icon = $('#edit-marker-icon').val();
      var show_controls = $('#edit-controls').prop('checked') ? "1" : "0";
      var infowindow_text = $('#edit-infowindow').val();

      $('input[data-lat-delta="' + delta + '"]').prop('value', latlng.lat()).attr('value', latlng.lat());
      $('input[data-lon-delta="' + delta + '"]').prop('value', latlng.lng()).attr('value', latlng.lng());
      $('input[data-zoom-delta="' + delta + '"]').prop('value', zoom).attr('value', zoom);
      $('input[data-type-delta="' + delta + '"]').prop('value', type).attr('value', type);
      $('input[data-width-delta="' + delta + '"]').prop('value', width).attr('value', width);
      $('input[data-height-delta="' + delta + '"]').prop('value', height).attr('value', height);
      $('input[data-traffic-delta="' + delta + '"]').prop('value', traffic).attr('value', traffic);
      $('input[data-marker-delta="' + delta + '"]').prop('value', show_marker).attr('value', show_marker);
      $('input[data-marker-icon-delta="' + delta + '"]').prop('value', marker_icon).attr('value', marker_icon);
      $('input[data-controls-delta="' + delta + '"]').prop('value', show_controls).attr('value', show_controls);
      $('input[data-infowindow-delta="' + delta + '"]').prop('value', infowindow_text).attr('value', infowindow_text);

      googleMapFieldPreviews(delta);

      $(this).dialog("close");
    };

    btns[Drupal.t('Cancel')] = function () {
      $(this).dialog("close");
    };

    var dialogHTML = '';
    dialogHTML += '<div id="google_map_field_dialog">';
    dialogHTML += '  <div>' + Drupal.t('Use the map below to drop a marker at the required location.') + '</div>';
    dialogHTML += '  <div id="google_map_field_container">';
    dialogHTML += '    <div id="google_map_map_container">';
    dialogHTML += '      <div id="gmf_container"></div>';
    dialogHTML += '      <div id="centre_on">';
    dialogHTML += '        <label>' + Drupal.t('Enter an address/town/postcode, etc., to center the map on:') + '</label><input size="50" type="text" name="centre_map_on" id="centre_map_on" value=""/>';
    dialogHTML += '        <button onclick="return doCentre();" type="button" role="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only button">' + Drupal.t('Find') + '</button>';
    dialogHTML += '        <div id="map_error"></div>';
    dialogHTML += '        <div id="centre_map_results"></div>';
    dialogHTML += '      </div>';
    dialogHTML += '      <div id="infowindow_container">';
    dialogHTML += '        <label for="edit-infowindow">' + Drupal.t('InfoWindow Popup text: (optional)') + '</label>';
    dialogHTML += '        <textarea class="form-textarea" id="edit-infowindow" name="infowindow" rows="3"></textarea>';
    dialogHTML += '      </div>';
    dialogHTML += '    </div>';
    dialogHTML += '    <div id="google_map_field_options">';
    dialogHTML += '      <label for="edit-zoom">' + Drupal.t('Map Zoom') + '</label>';
    dialogHTML += '      <select class="form-select" id="edit-zoom" name="field_zoom"><option value="1">' + Drupal.t('1 (Min)') + '</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">' + Drupal.t('9 (Default)') + '</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option>option value="21">' + Drupal.t('21 (Max)') + '</option></select>';
    dialogHTML += '      <label for="edit-type">' + Drupal.t('Map Type') + '</label>';
    dialogHTML += '      <select class="form-select" id="edit-type" name="field_type"><option value="roadmap">' + Drupal.t('Map') + '</option><option value="satellite">' + Drupal.t('Satellite') + '</option><option value="hybrid">' + Drupal.t('Hybrid') + '</option><option value="terrain">' + Drupal.t('Terrain') + '</option></select>';
    dialogHTML += '      <label for="edit-width">' + Drupal.t('Map Width') + '</label>';
    dialogHTML += '      <input type="text" id="edit-width" size="5" maxlength="6" name="field-width" value="" />';
    dialogHTML += '      <label for="edit-height">' + Drupal.t('Map Height') + '</label>';
    dialogHTML += '      <input type="text" id="edit-height" size="5" maxlength="6" name="field-height" value="" />';
    dialogHTML += '      <div class="form-checkbox">';
    dialogHTML += '        <input type="checkbox" class="form-checkbox" id="edit-controls" name="field_controls" />';
    dialogHTML += '        <label for="edit-controls">' + Drupal.t('Enable controls') + '</label>';
    dialogHTML += '      </div>';
    dialogHTML += '      <div class="form-checkbox">';
    dialogHTML += '        <input type="checkbox" class="form-checkbox" id="edit-traffic" name="field_traffic" />';
    dialogHTML += '        <label for="edit-traffic">' + Drupal.t('Traffic layer') + '</label>';
    dialogHTML += '      </div>';
    dialogHTML += '      <div class="form-checkbox">';
    dialogHTML += '        <input type="checkbox" class="form-checkbox" id="edit-marker" name="field_marker" />';
    dialogHTML += '        <label for="edit-marker">' + Drupal.t('Enable marker') + '</label>';
    dialogHTML += '      </div>';
    dialogHTML += '      <label for="edit-marker-icon">' + Drupal.t('Custom marker') + '</label>';
    dialogHTML += '      <input type="text" size="10" id="edit-marker-icon" name="field_marker_icon" />';
    dialogHTML += '      <p class="marker-text">Markers must be uploaded to your site first. Set an absolute or root-relative path here, eg /sites/default/files/marker.png.</p>';
    dialogHTML += '    </div>';
    dialogHTML += '  </div>';
    dialogHTML += '</div>';

    $('body').append(dialogHTML);

    dialog = $('#google_map_field_dialog').dialog({
      modal: true,
      autoOpen: false,
      width: 750,
      height: 640,
      closeOnEscape: true,
      resizable: false,
      draggable: false,
      title: Drupal.t('Set Map Marker'),
      dialogClass: 'jquery_ui_dialog-dialog',
      buttons: btns,
      close: function (event, ui) {
        $(this).dialog('destroy').remove();
      }
    });

    dialog.dialog('open');

    var trafficLayer = new google.maps.TrafficLayer();

    // Handle map options inside dialog.
    $('#edit-zoom').change(function () {
      google_map_field_map.setZoom(googleMapFieldValidateZoom($(this).val()));
    })
    $('#edit-type').change(function () {
      google_map_field_map.setMapTypeId($(this).val());
    })
    $('#edit-controls').change(function () {
      google_map_field_map.setOptions({disableDefaultUI : !$(this).prop('checked')});
    })
    $('#edit-traffic').change(function () {
      if ($(this).prop('checked')) {
        trafficLayer.setMap(google_map_field_map);
      }
      else {
        trafficLayer.setMap(null);
      }
    })
    $('#edit-marker').change(function () {
      marker.setVisible($(this).prop('checked'));
    })
    $('#edit-marker-icon').change(function () {
      marker.setIcon($(this).val());
    })

    // Create the map setter map.
    // get the lat/lon from form elements.
    var lat = $('input[data-lat-delta="' + delta + '"]').attr('value');
    var lon = $('input[data-lon-delta="' + delta + '"]').attr('value');
    var zoom = $('input[data-zoom-delta="' + delta + '"]').attr('value');
    var type = $('input[data-type-delta="' + delta + '"]').attr('value');
    var width = $('input[data-width-delta="' + delta + '"]').attr('value');
    var height = $('input[data-height-delta="' + delta + '"]').attr('value');
    var traffic = $('input[data-traffic-delta="' + delta + '"]').val() === "1";
    var show_marker = $('input[data-marker-delta="' + delta + '"]').val() === "1";
    var marker_icon = $('input[data-marker-icon-delta="' + delta + '"]').attr('value');
    var show_controls = $('input[data-controls-delta="' + delta + '"]').val() === "1";
    var infowindow_text = $('input[data-infowindow-delta="' + delta + '"]').attr('value');

    lat = googleMapFieldValidateLat(lat);
    lon = googleMapFieldValidateLon(lon);
    zoom = googleMapFieldValidateZoom(zoom);

    $('#edit-zoom').val(zoom);
    $('#edit-type').val(type);
    $('#edit-width').prop('value', width).attr('value', width);
    $('#edit-height').prop('value', height).attr('value', height);
    $('#edit-traffic').prop('checked', traffic);
    $('#edit-marker').prop('checked', show_marker);
    $('#edit-marker-icon').val(marker_icon);
    $('#edit-controls').prop('checked', show_controls);
    $('#edit-infowindow').val(infowindow_text);

    var latlng = new google.maps.LatLng(lat, lon);
    var mapOptions = {
      zoom: parseInt(zoom),
      center: latlng,
      streetViewControl: false,
      mapTypeId: type,
      disableDefaultUI: show_controls ? false : true,
    };
    google_map_field_map = new google.maps.Map(document.getElementById("gmf_container"), mapOptions);

    if (traffic) {
      trafficLayer.setMap(google_map_field_map);
    }

    // Add map listener.
    google.maps.event.addListener(google_map_field_map, 'zoom_changed', function () {
      $('#edit-zoom').val(google_map_field_map.getZoom());
    });

    // Drop a marker at the specified lat/lng coords.
    marker = getMarker(latlng, google_map_field_map);

    // Add a click listener for marker placement.
    google.maps.event.addListener(google_map_field_map, "click", function (event) {
      latlng = event.latLng;
      google_map_field_map.panTo(latlng);
      marker.setMap(null);
      marker = getMarker(latlng, google_map_field_map);
    });

    google.maps.event.addListener(marker, 'dragend', function (event) {
      google_map_field_map.panTo(event.latLng);
    });
    return false;
  }

  doCentreLatLng = function (lat, lng) {
    var latlng = new google.maps.LatLng(lat, lng);
    google_map_field_map.panTo(latlng);
    marker.setMap(null);
    marker = getMarker(latlng, google_map_field_map);
    google.maps.event.addListener(marker, 'dragend', function (event) {
      google_map_field_map.panTo(event.latLng);
    });
  }

  doCentre = function () {
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

      }
      else {
        $('#map_error').html(Drupal.t('Could not find location.'));
      }
    });

    return false;

  }

})(jQuery);
