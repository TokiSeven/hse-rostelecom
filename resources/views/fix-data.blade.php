<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
    </head>
    <body>
        @php
            $notValidData = session('notValidData', []);
            $validData = session('validData', []);
        @endphp
        <form action="/fix-xls" method='post'>
            {{ csrf_field() }}
            <div style="display: none;">
                @foreach ($validData as $row => $data)
                    @if (count($data) == session('maxColumns'))
                        @foreach ($data as $sym => $value)
                            <input name="data[{{ $row }}][{{ $sym }}]" type="text" value="{{ $value }}" readonly />
                        @endforeach
                    @endif
                @endforeach
            </div>
            <p>Valid data count: {{ count($validData) }}</p>
            <p>Non - valid data count: {{ count($notValidData) }}</p>
            <br />
            <p>Please fix non - valid data:</p>
            @foreach ($notValidData as $row => $data)
                <hr />
                <ul>
                    Valid values:
                    @foreach ($validData[$row] as $sym => $value)
                        <div>
                            <input name="data[{{ $row }}][{{ $sym }}]" type="text" value="{{ $value }}" readonly />
                        </div>
                    @endforeach
                </ul>
                <ul>
                    Not valid values:
                    @foreach ($data as $sym => $value)
                        <div>
                            <input name="data[{{ $row }}][{{ $sym }}]" type="text" value="{{ $value }}" />
                        </div>
                    @endforeach
                </ul>
            @endforeach
            <input type="submit" value="Fix!" />
        </form>
    </body>
</html>
