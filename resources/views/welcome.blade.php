@extends('layouts.admin')

@section('content')
<div class="col-md-6 col-md-offset-3">
    <h3>Site map generator</h3>
    <hr>
    {!! Form::open(['url' => route('generate'), 'class' => 'form-horizontal', 'method' => 'post']) !!}
        <div class="form-group">
            {!! Form::label('url', 'Site URL', ['class' => 'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
                {!! Form::text('url', null, ['class' => 'form-control', 'placeholder' => 'http://google.com']) !!}
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default btn-primary">Generate</button>
            </div>
        </div>
    {!! Form::close() !!}
</div>
@endsection