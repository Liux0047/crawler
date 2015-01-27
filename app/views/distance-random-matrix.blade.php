<!DOCTYPE html>
<html>
<head>
    <title>Distance Matrix service</title>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>
    {{ HTML::script('js/jquery-1.11.1.min.js') }}
    <script>


        var origins = [
            @foreach($sources as $source)
            @for ($i = 0; $i < 5; $i++)
            new google.maps.LatLng({{ $source->latitude }} -Math.random() / 400, {{ $source->longitude }} +Math.random() / 400),
            @endfor
            @endforeach
        ];

        var destinations = [
            @foreach($destinations as $destination)
            new google.maps.LatLng({{ $destination['latitude'] }} -Math.random() / 400, {{ $destination['longitude'] }} +Math.random() / 400),
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
                //handle Google error
                window.location.replace(window.location.href);
            } else {

                //start a new window
                setTimeout(function () {
                    window.open("{{ $nextUrl }}", "_blank");
                }, 0);

                var origins = [
                    @foreach($sources as $source)
                    @for ($i = 0; $i < 5; $i++)
                    {{ $source->grid_index }},
                    @endfor
                    @endforeach
                ];
                var destinations = [
                    @foreach( $destinations as $destination)
                    {{ $destination['grid_index'] }},
                    @endforeach
                ];
                var outputDiv = document.getElementById('outputDiv');
                outputDiv.innerHTML = '';


                $.ajax({
                    type: "POST",
                    url: "{{ action('WalkingRandom@recordDistance') }}",
                    dataType: "json",
                    data: {
                        sources: JSON.stringify(origins),
                        destinations: JSON.stringify(destinations),
                        mode: "{{ $mode }}",
                        results: JSON.stringify(response.rows)
                    }
                }).done(function (msg) {
                    outputDiv.innerHTML += 'Saved' + '<br>';
                }).always(function () {
                    //close this window
                    window.close();

                });
            }

        }
    </script>
</head>

<body>
<div id="content-pane">

    <div id="outputDiv"></div>
</div>
</body>
</html>