# Forjix View

Blade-like templating engine for the Forjix framework.

## Installation

```bash
composer require forjix/view
```

## Configuration

```php
use Forjix\View\Engine;

$engine = new Engine(
    viewsPath: '/path/to/views',
    cachePath: '/path/to/cache'
);
```

## Basic Usage

```php
echo $engine->render('welcome', ['name' => 'John']);
```

## Template Syntax

### Displaying Data

```blade
Hello, {{ $name }}!

{{-- Escaped output (default) --}}
{{ $userInput }}

{{-- Unescaped output --}}
{!! $html !!}
```

### Control Structures

```blade
@if($user)
    Welcome, {{ $user->name }}!
@elseif($guest)
    Welcome, guest!
@else
    Please log in.
@endif

@foreach($users as $user)
    {{ $user->name }}
@endforeach

@for($i = 0; $i < 10; $i++)
    {{ $i }}
@endfor

@while($condition)
    ...
@endwhile

@isset($variable)
    ...
@endisset

@empty($variable)
    ...
@endempty
```

### Layouts

**layouts/app.blade.php**
```blade
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
</head>
<body>
    @yield('content')
</body>
</html>
```

**welcome.blade.php**
```blade
@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
    <h1>Welcome to Forjix!</h1>
@endsection
```

### Including Partials

```blade
@include('partials.nav')

@include('partials.card', ['title' => 'Hello'])
```

### Comments

```blade
{{-- This is a comment --}}
```

## License

GPL-3.0
