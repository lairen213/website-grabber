@extends('layouts.main')

@section('content')
    <div class="row">
        <div class="d-flex align-items-center justify-content-center" style="height: 100vh;">

                <div class="alert alert-light" role="alert">
                    @if($type === 'success')
                        Страница сайта была успешно скачана! Найти ее можно в папке public/pages/<strong>{{$page_name}}</strong>
                    @else
                        Страница слишком большая для копирования!
                    @endif
                    <br><br>
                    <a href="{{route('index')}}" class="btn btn-outline-warning float-end">Назад</a>
                </div>
        </div>
    </div>
@endsection

