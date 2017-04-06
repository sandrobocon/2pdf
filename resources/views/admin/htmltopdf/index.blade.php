@extends('layouts.app');

@section('content')
    <div class="container">
        <div class="row">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Fila de requisições</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                            <a href="{{ route('admin.htmltopdf.create') }}" class="btn btn-default">
                                <span class="glyphicon glyphicon-plus"></span>
                            </a>
                        </div>
                    </div>
                    <br/>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th style="width: 10px;">#</th>
                                    <th>User</th>
                                    <th>File</th>
                                    <th>Hash</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($queue as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->user_id }}</td>
                                        <td>{{ $item->file_name }}</td>
                                        <td>{{ $item->hash }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ ($item->status >= 0) ? $item->status.'%' : 'Espera' }}</td>
                                        <td>
                                            <?php /*
                                            <a href="{{route('admin.htmltopdf.edit', ['id'=> $item->id])}}">
                                                <span class="glyphicon glyphicon-pencil"></span>
                                            </a> | */?>
                                            @if(intval($item->status) == 100)
                                            <a href="{{route('admin.htmltopdf.download',$item->id) }}">
                                                <span class="glyphicon glyphicon-floppy-disk"></span>
                                            </a> |
                                            @endif

                                            <a href="{{route('admin.htmltopdf.destroy', ['id'=>$item->id])}}"
                                               onclick="event.preventDefault(); document.getElementById('item-delete-form-{{$item->id}}').submit();"  >
                                                <span class="glyphicon glyphicon-remove"></span>
                                            </a>
                                            {!!
                                             form(\FormBuilder::plain([
                                                'id' => "item-delete-form-{$item->id}",
                                                'method' => 'DELETE',
                                                'url' => route('admin.htmltopdf.destroy', ['id'=>$item->id])
                                             ]))
                                             !!}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            {{ $queue->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection