@extends('layouts.main')

@section('content')
    <div class="row">
        <div class="d-flex align-items-center justify-content-center" style="height: 100vh;">
            <div class="alert alert-light" role="alert">
                Страница сайта была успешно скачана! Найти ее можно в папке public/pages/<strong>{{$page_name}}</strong>
                <br><br>
                <a href="{{route('index')}}" class="btn btn-outline-warning float-end">Назад</a>
            </div>
        </div>
    </div>
@endsection

