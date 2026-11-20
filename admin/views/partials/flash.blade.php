@if (session('desec_success'))
  <div class="alert alert-success" role="status">{{ session('desec_success') }}</div>
@endif
@if (session('desec_error'))
  <div class="alert alert-danger" role="alert">{{ session('desec_error') }}</div>
@endif
@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <ul class="list-unstyled" style="margin: 0">
      @foreach ($errors->all() as $message)
        <li>{{ $message }}</li>
      @endforeach
    </ul>
  </div>
@endif
