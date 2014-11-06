<!DOCTYPE html>
<html>
<head>
    <title>Distance Matrix service</title>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>
    {{ HTML::script('js/jquery-1.11.1.min.js') }}
    <style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    #outputDiv {
        font-size: 12px;
    }
    </style>

    <script>

    var origins = [
        @foreach($sources as $source)
        new google.maps.LatLng({{ $source->latitude }}, {{ $source->longitude }}),
        @endforeach
    ];

    var destinations = [
        @foreach($destinations as $destination)
        new google.maps.LatLng({{ $destination->latitude }}, {{ $destination->longitude }}),
        @endforeach
    ];

    var service = new google.maps.DistanceMatrixService();

    service.getDistanceMatrix(
    {
        origins: origins,
        destinations: destinations,
        travelMode: google.maps.TravelMode.{{ $mode  }},
        avoidHighways: false,
        avoidTolls: false
    }, callback);

    function callback(response, status) {
        if (status != google.maps.DistanceMatrixStatus.OK) {
            alert('Error was: ' + status);
        } else {
            var origins = [
                @foreach( $sources as $source)
                {{ $source->area_id }},
                @endforeach
            ];
            var destinations = [
                @foreach( $destinations as $destination)
                {{ $destination->area_id }},
                @endforeach
            ];
            var outputDiv = document.getElementById('outputDiv');
            outputDiv.innerHTML = '';

            for (var i = 0; i < origins.length; i++) {
                var results = response.rows[i].elements;
                for (var j = 0; j < results.length; j++) {
                    outputDiv.innerHTML += origins[i] + ' to ' + destinations[j]
                    + ': ' + results[j].distance.value + ' in '
                    + results[j].duration.value + '<br>';
                }
            }

            $.ajax({
                type: "POST",
                url: "{{ action('MatrixMapController@recordDistance') }}",
                dataType: "json",
                data: {
                    sources: JSON.stringify(origins),
                    destinations: JSON.stringify(destinations),
                    mode: "{{ $mode }}",
                    results: JSON.stringify(response.rows)
                }
            })
            .done(function( msg ) {
                outputDiv.innerHTML += 'Saved' + '<br>';

            });
        }

    }
    </script>
</head>

<body>
    <div id="content-pane">
        Content:

        <div id="outputDiv"></div>
    </div>
</body>
</html>