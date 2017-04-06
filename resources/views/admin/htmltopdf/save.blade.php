@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">{{$title}}</h3>
                </div>
            </div>
            <div class="panel-body">
                <?php $form->add('submit','submit', [
                    'label' => '<span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>'
                ])?>
                {!! form($form) !!}
            </div>
            @if(isset($create))
            <div class="alert alert-warning" role="alert">
                <strong>Atenção!</strong>
                <p>Insira um arquivo .zip contendo todos os .html dentro dele.</p>
            </div>
            @endif
        </div>
    </div>
@endsection