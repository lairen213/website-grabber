@extends('layouts.main')

@section('content')
    <div class="row">
        <div class="d-flex align-items-center justify-content-center" style="height: 100vh;">
            <form action="{{route('copyWebpage')}}" method="POST" class="bg-light p-5 rounded" id="download_page_form">
                @csrf

                <div class="mb-3">
                    <label for="inputLinkToSite" class="form-label">Ссылка на сайт <strong>*</strong></label>
                    <input required name="site_link" type="text" class="form-control" id="inputLinkToSite"
                           placeholder="https://vusapye.website/lander/friocard---id---1---1015252_1648462141/index.html">
                </div>
                <div class="mb-3">
                    <label for="changeLinkInput" class="form-label">Ссылка, на которую изменятся все ссылки находящийся
                        на странице сайта <strong>*</strong></label>
                    <input required name="change_link" type="text" class="form-control" id="changeLinkInput">
                </div>
                <button class="btn btn-outline-info float-end" id="btnDownloadPage">Скачать</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $('#btnDownloadPage').on('click', function (e) {
            e.preventDefault();
            if (isUrlValid($('#inputLinkToSite').val()) && isUrlValid($('#changeLinkInput').val())) {
                $(this).attr('disabled', true);
                $('#download_page_form').submit();
            } else {
                alert('Укажите верную ссылку в двух полях!');
            }
        })

        function isUrlValid(url) {
            return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
        }
    </script>
@endsection
