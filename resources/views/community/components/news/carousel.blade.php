<?php

use App\Community\Models\News;

$newsData = News::orderByDesc('ID')->take(10)->get();
if ($newsData->isEmpty()) {
    return;
}
?>
<link type="text/css" rel="stylesheet" href="{{ asset('vendor/rcarousel/rcarousel.css') }}"/>
<script src="{{ asset('vendor/rcarousel/jquery.ui.widget.min.js') }}"></script>
<script src="{{ asset('vendor/rcarousel/jquery.ui.rcarousel.min.js') }}"></script>
<script>
    $(function () {
        function generatePages() {
            var _total,
                i,
                _link;

            _total = $('#carousel').rcarousel('getTotalPages');

            for (i = 0; i < _total; i++) {
                _link = $('<a href=\'#\'></a>');

                $(_link).bind('click', { page: i },
                    function (event) {
                        $('#carousel').rcarousel('goToPage', event.data.page);
                        event.preventDefault();
                    },
                ).addClass('bullet').appendTo('#carouselpages');
            }

            // mark first page as active
            $('a:eq(0)', '#carouselpages').addClass('on');

            $('.newstitle').css('opacity', 0.0).delay(500).fadeTo('slow', 1.0);
            $('.newstext').css('opacity', 0.0).delay(900).fadeTo('slow', 1.0);
            $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo('slow', 1.0);
        }

        function pageLoaded(event, data) {
            $('a.on', '#carouselpages').removeClass('on');

            $('a', '#carouselpages').eq(data.page).addClass('on');
        }

        function onNext() {
            $('.newstitle').css('opacity', 0.0).delay(500).fadeTo('slow', 1.0);
            $('.newstext').css('opacity', 0.0).delay(900).fadeTo('slow', 1.0);
            $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo('slow', 1.0);
        }

        function onPrev() {
        }

        $('#carousel').rcarousel(
            {
                visible: 1,
                step: 1,
                speed: 500,
                auto: {
                    enabled: true,
                    interval: 7000,
                },
                width: 480,
                height: 220,
                start: generatePages,
                pageLoaded: pageLoaded,
                onNext: onNext,
                onPrev: onPrev,
            },
        );

        $('#ui-carousel-next').add('#ui-carousel-prev').add('.bullet').hover(
            function () {
                $(this).css('opacity', 0.7);
            },
            function () {
                $(this).css('opacity', 1.0);
            },
        ).click(
            function () {
                $('.newstitle').css('opacity', 0.0).delay(500).fadeTo('slow', 1.0);
                $('.newstext').css('opacity', 0.0).delay(900).fadeTo('slow', 1.0);
                $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo('slow', 1.0);
            },
        );
    });
</script>

<div class="mb-4">
    <h2>News</h2>
    <div id="carouselcontainer">
        <div id="carousel">
            @foreach ($newsData as $news)
                <div class="newsbluroverlay">
                    <div>
                        <div class="newscontainer" style="background: url('{{ $news->Image }}') repeat scroll; opacity:0.5; width: 470px; height:222px; background-size: 100% auto;">
                        </div>
                        <div class="news">
                            <h4 class="whitespace-nowrap absolute" style="width: 460px; top:2px; left:10px">
                                <a class="newstitle shadowoutline" href="{{ $news->Link }}">
                                    {{ $news->Title }}
                                </a>
                            </h4>
                            <div class="newstext shadowoutline absolute" style="width: 90%; top: 40px; left:10px;">
                                {!! $news->Payload !!}
                            </div>
                            <div class="newsauthor shadowoutline absolute" style="width: 470px; top: 196px; left:0; text-align: right">
                                {!! userAvatar($news->Author, icon: false) !!}, {{ $news->Timestamp->format('F j, Y, H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <a href="#" id="ui-carousel-next"><span>next</span></a>
        <a href="#" id="ui-carousel-prev"><span>prev</span></a>
        <div id="carouselpages"></div>
    </div>
</div>
