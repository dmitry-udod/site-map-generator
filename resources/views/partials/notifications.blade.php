@if ($errors->count())
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> {{  $errors->first('url') }}
</div>
@endif