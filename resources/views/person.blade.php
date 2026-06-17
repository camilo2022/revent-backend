<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Personas</title>
</head>

<body>
    <h2>Personas</h2>
    @foreach ($people as $person)
        <p>{{ $person->names }}</p>
    @endforeach
</body>

</html>
