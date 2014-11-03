<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Crawler</title>
	<style>
		@import url(//fonts.googleapis.com/css?family=Lato:700);

		body {
			margin:0;
			font-family:'Lato', sans-serif;
			text-align:center;
			color: #999;
		}

		a, a:visited {
			text-decoration:none;
		}

		h1 {
			font-size: 32px;
			margin: 16px 0 0 0;
		}
	</style>
</head>
<body>
    @foreach($records as $record)
    @foreach($record as $entry)
    @if(!empty($entry))
    {{ $entry }}<br>
    @endif
    @endforeach
    @endforeach
</body>
</html>
